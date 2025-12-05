<?php

use App\Enums\ApiErrorCode;
use App\Enums\ShipmentStatus;
use App\Events\ShipmentDelivered;
use App\Models\ApiLog;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a tenant and user for testing
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create();
    $this->user->tenants()->attach($this->tenant, ['role' => 'admin']);

    // Create an order and shipment
    $this->order = Order::factory()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->shipment = Shipment::factory()->create([
        'tenant_id' => $this->tenant->id,
        'order_id' => $this->order->id,
        'tracking_number' => '1Z999AA10123456784',
        'carrier_code' => 'ups',
        'carrier_title' => 'UPS',
        'status' => 'in_transit',
    ]);
});

describe('Authentication', function () {
    it('returns 401 when no token is provided', function () {
        $response = $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => now()->toIso8601String()]
        );

        $response->assertStatus(401);
    });

    it('returns 401 when token is invalid', function () {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => now()->toIso8601String()]
        );

        $response->assertStatus(401);
    });

    it('returns 403 when token lacks required ability', function () {
        // Create token without the required ability
        $token = $this->user->createToken('test-token', ['read-only']);
        Sanctum::actingAs($this->user, ['read-only']);

        $response = $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => now()->toIso8601String()]
        );

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiErrorCode::INSUFFICIENT_PERMISSIONS->value);
    });
});

describe('Validation', function () {
    beforeEach(function () {
        Sanctum::actingAs($this->user, ['shipments:update-delivery']);
    });

    it('returns 422 when delivered_at is missing', function () {
        $response = $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            []
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiErrorCode::VALIDATION_ERROR->value)
            ->assertJsonStructure([
                'success',
                'error' => ['code', 'message', 'details' => ['delivered_at']],
            ]);
    });

    it('returns 422 when delivered_at is invalid format', function () {
        $response = $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => 'not-a-date']
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'error' => ['details' => ['delivered_at']],
            ]);
    });

    it('returns 422 when delivered_at is in the future', function () {
        $response = $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => now()->addDay()->toIso8601String()]
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure([
                'error' => ['details' => ['delivered_at']],
            ]);
    });

    it('accepts valid delivery data', function () {
        $response = $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            [
                'delivered_at' => now()->subHour()->toIso8601String(),
                'carrier_code' => 'ups',
                'signature' => 'John Doe',
                'delivery_notes' => 'Left at front door',
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });
});

describe('Successful Delivery Update', function () {
    beforeEach(function () {
        Sanctum::actingAs($this->user, ['shipments:update-delivery']);
    });

    it('updates shipment delivery information', function () {
        $deliveredAt = now()->subHour();

        $response = $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            [
                'delivered_at' => $deliveredAt->toIso8601String(),
                'signature' => 'John Doe',
                'delivery_notes' => 'Left at front door',
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tracking_number', $this->shipment->tracking_number)
            ->assertJsonPath('message', 'Shipment delivery information updated successfully');

        $this->shipment->refresh();

        expect($this->shipment->status)->toBe(ShipmentStatus::DELIVERED->value)
            ->and($this->shipment->delivery_signature)->toBe('John Doe')
            ->and($this->shipment->delivery_notes)->toBe('Left at front door')
            ->and($this->shipment->actual_delivery_at)->not->toBeNull();
    });

    it('returns order number in response', function () {
        $response = $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => now()->subHour()->toIso8601String()]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.order_number', $this->order->increment_id);
    });

    it('dispatches ShipmentDelivered event', function () {
        Event::fake([ShipmentDelivered::class]);

        $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => now()->subHour()->toIso8601String()]
        );

        Event::assertDispatched(ShipmentDelivered::class, function ($event) {
            return $event->shipment->id === $this->shipment->id
                && $event->source === 'api';
        });
    });

    it('allows updating already delivered shipment', function () {
        // First delivery
        $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            [
                'delivered_at' => now()->subHours(2)->toIso8601String(),
                'signature' => 'First Signature',
            ]
        );

        // Second update
        $response = $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            [
                'delivered_at' => now()->subHour()->toIso8601String(),
                'signature' => 'Updated Signature',
            ]
        );

        $response->assertStatus(200);

        $this->shipment->refresh();
        expect($this->shipment->delivery_signature)->toBe('Updated Signature');
    });
});

describe('Not Found', function () {
    beforeEach(function () {
        Sanctum::actingAs($this->user, ['shipments:update-delivery']);
    });

    it('returns 404 for non-existent tracking number', function () {
        $response = $this->patchJson(
            '/api/v1/shipments/NONEXISTENT123/delivery',
            ['delivered_at' => now()->toIso8601String()]
        );

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', ApiErrorCode::SHIPMENT_NOT_FOUND->value);
    });
});

describe('Tenant Isolation', function () {
    it('prevents access to shipments from other tenants when token is tenant-specific', function () {
        // Create another tenant with a shipment
        $otherTenant = Tenant::factory()->create();
        $otherOrder = Order::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherShipment = Shipment::factory()->create([
            'tenant_id' => $otherTenant->id,
            'order_id' => $otherOrder->id,
            'tracking_number' => 'OTHER123456789',
        ]);

        // Create a tenant-specific token using createToken then update tenant_id
        $newToken = $this->user->createToken(
            'tenant-specific',
            ['shipments:update-delivery'],
        );

        // Update the token's tenant_id directly in the database
        \Laravel\Sanctum\PersonalAccessToken::where('id', $newToken->accessToken->id)
            ->update(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$newToken->plainTextToken,
        ])->patchJson(
            "/api/v1/shipments/{$otherShipment->tracking_number}/delivery",
            ['delivered_at' => now()->subHour()->toIso8601String()]
        );

        $response->assertStatus(404)
            ->assertJsonPath('error.code', ApiErrorCode::SHIPMENT_NOT_FOUND->value);
    });

    it('allows multi-tenant token to access any shipment', function () {
        // Create another tenant with a shipment
        $otherTenant = Tenant::factory()->create();
        $otherOrder = Order::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherShipment = Shipment::factory()->create([
            'tenant_id' => $otherTenant->id,
            'order_id' => $otherOrder->id,
            'tracking_number' => 'MULTI123456789',
        ]);

        // Create a multi-tenant token (no tenant_id)
        $token = $this->user->createToken(
            'multi-tenant',
            ['shipments:update-delivery'],
        );
        // Token has no tenant_id, so it's multi-tenant

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->patchJson(
            "/api/v1/shipments/{$otherShipment->tracking_number}/delivery",
            ['delivered_at' => now()->subHour()->toIso8601String()]
        );

        $response->assertStatus(200);
    });
});

describe('API Logging', function () {
    it('logs successful API requests', function () {
        Sanctum::actingAs($this->user, ['shipments:update-delivery']);

        $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => now()->toIso8601String()]
        );

        expect(ApiLog::count())->toBe(1);

        $log = ApiLog::first();
        expect($log->method)->toBe('PATCH')
            ->and($log->endpoint)->toContain('shipments')
            ->and($log->is_success)->toBeTrue()
            ->and($log->response_status)->toBe(200)
            ->and($log->resource_id)->toBe($this->shipment->tracking_number);
    });

    it('logs failed API requests', function () {
        Sanctum::actingAs($this->user, ['shipments:update-delivery']);

        $this->patchJson(
            '/api/v1/shipments/NONEXISTENT/delivery',
            ['delivered_at' => now()->toIso8601String()]
        );

        expect(ApiLog::count())->toBe(1);

        $log = ApiLog::first();
        expect($log->is_success)->toBeFalse()
            ->and($log->response_status)->toBe(404)
            ->and($log->error_code)->toBe(ApiErrorCode::SHIPMENT_NOT_FOUND->value);
    });

    it('does not log sensitive data', function () {
        Sanctum::actingAs($this->user, ['shipments:update-delivery']);

        $this->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => now()->toIso8601String()]
        );

        $log = ApiLog::first();

        // Authorization header should not be logged
        expect($log->request_headers)->not->toHaveKey('authorization');
    });
});

describe('Rate Limiting', function () {
    it('allows requests within rate limit', function () {
        Sanctum::actingAs($this->user, ['shipments:update-delivery']);

        // Make 5 requests quickly
        for ($i = 0; $i < 5; $i++) {
            $response = $this->patchJson(
                "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
                ['delivered_at' => now()->subMinutes($i)->toIso8601String()]
            );

            $response->assertStatus(200);
        }
    });
});

describe('Token Expiration', function () {
    it('rejects expired tokens', function () {
        // Create an expired token
        $token = $this->user->createToken(
            'expired-token',
            ['shipments:update-delivery'],
            now()->subDay() // Expired yesterday
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->patchJson(
            "/api/v1/shipments/{$this->shipment->tracking_number}/delivery",
            ['delivered_at' => now()->toIso8601String()]
        );

        $response->assertStatus(401);
    });
});

describe('Health Check', function () {
    it('returns healthy status without authentication', function () {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'healthy')
            ->assertJsonStructure(['status', 'timestamp']);
    });
});
