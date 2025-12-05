<?php

use App\Services\ExternalNotificationClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->client = new ExternalNotificationClient;
    Cache::flush();
});

it('sends warning notification successfully', function () {
    Http::fake([
        'https://example.com/api/notifications' => Http::response([
            'success' => true,
            'message' => 'Notification received',
        ], 200),
    ]);

    $payload = [
        'event_type' => 'order_cancellation_warning',
        'order' => ['id' => 123, 'increment_id' => '000000123'],
    ];

    $result = $this->client->sendWarningNotification(
        'https://example.com/api/notifications',
        $payload
    );

    expect($result)->toMatchArray([
        'success' => true,
        'status_code' => 200,
        'retry_count' => 0,
    ])
        ->and($result['error'])->toBeNull();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://example.com/api/notifications'
            && $request->hasHeader('X-Notification-Type', 'warning')
            && $request['event_type'] === 'order_cancellation_warning';
    });
});

it('sends cancellation notification successfully', function () {
    Http::fake([
        'https://example.com/api/notifications' => Http::response([
            'success' => true,
        ], 201),
    ]);

    $payload = [
        'event_type' => 'order_cancelled',
        'order' => ['id' => 123],
    ];

    $result = $this->client->sendCancellationNotification(
        'https://example.com/api/notifications',
        $payload
    );

    expect($result['success'])->toBeTrue()
        ->and($result['status_code'])->toBe(201)
        ->and($result['retry_count'])->toBe(0);

    Http::assertSent(function ($request) {
        return $request->hasHeader('X-Notification-Type', 'cancellation');
    });
});

it('retries on server error with exponential backoff', function () {
    Http::fake([
        'https://example.com/api/notifications' => Http::sequence()
            ->push(['error' => 'Server error'], 500)
            ->push(['error' => 'Server error'], 503)
            ->push(['success' => true], 200),
    ]);

    $payload = ['event_type' => 'order_cancellation_warning'];

    $result = $this->client->sendWarningNotification(
        'https://example.com/api/notifications',
        $payload
    );

    expect($result['success'])->toBeTrue()
        ->and($result['status_code'])->toBe(200)
        ->and($result['retry_count'])->toBe(2); // 2 retries before success

    // Should have made 3 requests total
    Http::assertSentCount(3);
});

it('fails after max retries on persistent server error', function () {
    Http::fake([
        'https://example.com/api/notifications' => Http::response([
            'error' => 'Server error',
        ], 500),
    ]);

    $payload = ['event_type' => 'order_cancellation_warning'];

    $result = $this->client->sendWarningNotification(
        'https://example.com/api/notifications',
        $payload
    );

    expect($result['success'])->toBeFalse()
        ->and($result['status_code'])->toBeNull()
        ->and($result['retry_count'])->toBe(2) // Max retries reached
        ->and($result['error'])->toContain('Failed after all retries');

    // Should have attempted max retries (3 attempts)
    Http::assertSentCount(3);
});

it('does not retry on client error (4xx)', function () {
    Http::fake([
        'https://example.com/api/notifications' => Http::response([
            'error' => 'Bad request',
        ], 400),
    ]);

    $payload = ['event_type' => 'order_cancellation_warning'];

    $result = $this->client->sendWarningNotification(
        'https://example.com/api/notifications',
        $payload
    );

    expect($result['success'])->toBeFalse()
        ->and($result['status_code'])->toBe(400)
        ->and($result['retry_count'])->toBe(0) // No retries on client errors
        ->and($result['error'])->toContain('Client error');

    // Should only make 1 request (no retries)
    Http::assertSentCount(1);
});

it('handles connection timeout', function () {
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
    });

    $payload = ['event_type' => 'order_cancellation_warning'];

    $result = $this->client->sendWarningNotification(
        'https://example.com/api/notifications',
        $payload
    );

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Connection timeout')
        ->and($result['retry_count'])->toBe(2); // Should have retried

    Http::assertSentCount(3);
});

it('validates endpoint URL', function () {
    $payload = ['event_type' => 'order_cancellation_warning'];

    $result = $this->client->sendWarningNotification(
        'not-a-valid-url',
        $payload
    );

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Invalid or insecure endpoint');

    Http::assertNothingSent();
});

it('requires HTTPS in production', function () {
    app()->instance('env', 'production');

    $payload = ['event_type' => 'order_cancellation_warning'];

    $result = $this->client->sendWarningNotification(
        'http://example.com/api/notifications',
        $payload
    );

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Invalid or insecure endpoint');

    Http::assertNothingSent();
})->skip(fn () => app()->environment('local'), 'Only runs in production environment');

it('allows HTTP in non-production', function () {
    Http::fake([
        'http://localhost/api/notifications' => Http::response(['success' => true], 200),
    ]);

    $payload = ['event_type' => 'order_cancellation_warning'];

    $result = $this->client->sendWarningNotification(
        'http://localhost/api/notifications',
        $payload
    );

    expect($result['success'])->toBeTrue();
});

it('implements circuit breaker after threshold failures', function () {
    Http::fake([
        'https://example.com/api/notifications' => Http::response(['error' => 'Server error'], 500),
    ]);

    $payload = ['event_type' => 'order_cancellation_warning'];

    // Make 5 failed requests to trigger circuit breaker
    for ($i = 0; $i < 5; $i++) {
        $this->client->sendWarningNotification(
            'https://example.com/api/notifications',
            $payload
        );
    }

    // Next request should be blocked by circuit breaker
    $result = $this->client->sendWarningNotification(
        'https://example.com/api/notifications',
        $payload
    );

    expect($result['success'])->toBeFalse()
        ->and($result['error'])->toContain('Circuit breaker open')
        ->and($result['retry_count'])->toBe(0);

    // Should have made 15 requests (5 failures × 3 attempts each)
    // Circuit breaker blocks the 6th attempt
    Http::assertSentCount(15);
});

it('resets circuit breaker on success', function () {
    Cache::put('circuit_breaker:'.md5('https://example.com/api/notifications'), 3, 300);

    Http::fake([
        'https://example.com/api/notifications' => Http::response(['success' => true], 200),
    ]);

    $payload = ['event_type' => 'order_cancellation_warning'];

    $result = $this->client->sendWarningNotification(
        'https://example.com/api/notifications',
        $payload
    );

    expect($result['success'])->toBeTrue();

    // Circuit breaker should be reset
    expect(Cache::has('circuit_breaker:'.md5('https://example.com/api/notifications')))->toBeFalse();
});

it('sanitizes long response bodies', function () {
    $longResponse = str_repeat('a', 2000);

    Http::fake([
        'https://example.com/api/notifications' => Http::response($longResponse, 200),
    ]);

    $payload = ['event_type' => 'order_cancellation_warning'];

    $result = $this->client->sendWarningNotification(
        'https://example.com/api/notifications',
        $payload
    );

    expect($result['success'])->toBeTrue()
        ->and(strlen($result['body']))->toBeLessThan(1100) // Truncated + message
        ->and($result['body'])->toContain('(truncated)');
});

it('includes correct headers in request', function () {
    Http::fake([
        'https://example.com/api/notifications' => Http::response(['success' => true], 200),
    ]);

    $payload = ['event_type' => 'order_cancellation_warning'];

    $this->client->sendWarningNotification(
        'https://example.com/api/notifications',
        $payload
    );

    Http::assertSent(function ($request) {
        return $request->hasHeader('Content-Type', 'application/json')
            && $request->hasHeader('User-Agent', 'MagentoOMS/1.0')
            && $request->hasHeader('X-Notification-Type', 'warning')
            && $request->hasHeader('X-Event-Type', 'order_cancellation_warning');
    });
});

it('verifies endpoint correctly', function () {
    $client = new ExternalNotificationClient;

    expect($client->verifyEndpoint('https://example.com/api'))->toBeTrue()
        ->and($client->verifyEndpoint('http://localhost/api'))->toBeTrue()
        ->and($client->verifyEndpoint('not-a-url'))->toBeFalse()
        ->and($client->verifyEndpoint(''))->toBeFalse();
});
