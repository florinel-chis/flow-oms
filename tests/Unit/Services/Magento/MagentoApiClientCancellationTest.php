<?php

namespace Tests\Unit\Services\Magento;

use App\Models\MagentoStore;
use App\Services\Magento\MagentoApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MagentoApiClientCancellationTest extends TestCase
{
    private MagentoStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = MagentoStore::factory()->create([
            'api_url' => 'https://magento.test',
            'access_token' => 'test-token-123',
        ]);
    }

    public function test_it_can_cancel_an_order_successfully()
    {
        Http::fake([
            'magento.test/rest/V1/orders/123/cancel' => Http::response(true, 200),
        ]);

        $client = new MagentoApiClient($this->store);
        $result = $client->cancelOrder(123);

        $this->assertTrue($result['success'] ?? $result === true);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://magento.test/rest/V1/orders/123/cancel'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-token-123');
        });
    }

    public function test_it_can_cancel_order_with_json_success_response()
    {
        Http::fake([
            'magento.test/rest/V1/orders/456/cancel' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $client = new MagentoApiClient($this->store);
        $result = $client->cancelOrder(456);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function test_it_throws_exception_when_order_not_found()
    {
        Http::fake([
            'magento.test/rest/V1/orders/999/cancel' => Http::response([
                'message' => 'Requested entity doesn\'t exist',
                'parameters' => [],
            ], 404),
        ]);

        $client = new MagentoApiClient($this->store);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('Order #999 not found in Magento');

        $client->cancelOrder(999);
    }

    public function test_it_throws_exception_when_order_already_canceled()
    {
        Http::fake([
            'magento.test/rest/V1/orders/123/cancel' => Http::response([
                'message' => 'Order already cancelled, complete, closed or on hold.',
                'parameters' => [],
            ], 400),
        ]);

        $client = new MagentoApiClient($this->store);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Cannot cancel order #123');

        $client->cancelOrder(123);
    }

    public function test_it_throws_exception_when_order_already_shipped()
    {
        Http::fake([
            'magento.test/rest/V1/orders/789/cancel' => Http::response([
                'message' => 'Cannot cancel order that has been shipped.',
                'parameters' => [],
            ], 400),
        ]);

        $client = new MagentoApiClient($this->store);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(400);

        $client->cancelOrder(789);
    }

    public function test_it_throws_exception_when_insufficient_permissions()
    {
        Http::fake([
            'magento.test/rest/V1/orders/123/cancel' => Http::response([
                'message' => 'Consumer is not authorized to access %resources',
                'parameters' => ['Magento_Sales::cancel'],
            ], 403),
        ]);

        $client = new MagentoApiClient($this->store);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage('Insufficient permissions to cancel order #123');

        $client->cancelOrder(123);
    }

    public function test_it_retries_on_server_errors()
    {
        // First two attempts fail with 500, third succeeds
        Http::fake([
            'magento.test/rest/V1/orders/123/cancel' => Http::sequence()
                ->push(null, 500)
                ->push(null, 500)
                ->push(true, 200),
        ]);

        $client = new MagentoApiClient($this->store);
        $result = $client->cancelOrder(123);

        $this->assertTrue($result['success'] ?? $result === true);
    }

    public function test_it_handles_generic_errors()
    {
        Http::fake([
            'magento.test/rest/V1/orders/123/cancel' => Http::response([
                'message' => 'Some unexpected error occurred',
            ], 500),
        ]);

        $client = new MagentoApiClient($this->store);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Failed to cancel order #123');

        $client->cancelOrder(123);
    }
}
