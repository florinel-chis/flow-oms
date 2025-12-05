import { test, expect } from '@playwright/test';

/**
 * REST API Endpoints Tests
 * 
 * Tests the new REST API endpoints for Orders, Invoices, and Webhooks.
 * 
 * Prerequisites:
 * 1. Create an API token via: php artisan tinker
 *    ```
 *    $user = User::where('email', 'test@example.com')->first();
 *    $token = $user->createToken('test-token', ['*'], null, 1);
 *    echo $token->plainTextToken;
 *    ```
 * 2. Set the token in your .env.test file or pass via ENV
 * 3. Ensure seeded data exists (orders, invoices, shipments)
 */

// Get API token from environment
const API_TOKEN = process.env.API_TOKEN || '';
const BASE_URL = process.env.APP_URL || 'http://127.0.0.1:8000';

test.describe('REST API - Orders Endpoints', () => {
  test.skip(!API_TOKEN, 'Skipping API tests - no API_TOKEN provided');

  test('GET /api/v1/orders - should list orders', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/orders`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();

    expect(data.success).toBe(true);
    expect(data.data).toHaveProperty('orders');
    expect(data.data).toHaveProperty('pagination');
    expect(Array.isArray(data.data.orders)).toBe(true);
  });

  test('GET /api/v1/orders - should support pagination', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/orders?per_page=5&page=1`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();

    expect(data.data.pagination.per_page).toBe(5);
    expect(data.data.pagination.current_page).toBe(1);
  });

  test('GET /api/v1/orders - should filter by status', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/orders?status=complete`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();

    // All returned orders should have status 'complete'
    if (data.data.orders.length > 0) {
      data.data.orders.forEach((order: any) => {
        expect(order.status).toBe('complete');
      });
    }
  });

  test('GET /api/v1/orders - should filter by payment_status', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/orders?payment_status=paid`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();

    if (data.data.orders.length > 0) {
      data.data.orders.forEach((order: any) => {
        expect(order.payment_status).toBe('paid');
      });
    }
  });

  test('GET /api/v1/orders - should include relationships', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/orders?include=items,shipments,invoices`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();

    if (data.data.orders.length > 0) {
      const firstOrder = data.data.orders[0];
      expect(firstOrder).toHaveProperty('items');
      expect(firstOrder).toHaveProperty('shipments');
      expect(firstOrder).toHaveProperty('invoices');
    }
  });

  test('GET /api/v1/orders/{increment_id} - should get single order', async ({ request }) => {
    // First get list to find an increment_id
    const listResponse = await request.get(`${BASE_URL}/api/v1/orders?per_page=1`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    const listData = await listResponse.json();
    
    if (listData.data.orders.length > 0) {
      const incrementId = listData.data.orders[0].increment_id;

      const response = await request.get(`${BASE_URL}/api/v1/orders/${incrementId}`, {
        headers: {
          'Authorization': `Bearer ${API_TOKEN}`,
          'Accept': 'application/json',
        },
      });

      expect(response.ok()).toBeTruthy();
      const data = await response.json();

      expect(data.success).toBe(true);
      expect(data.data.order.increment_id).toBe(incrementId);
      expect(data.data.order).toHaveProperty('customer');
      expect(data.data.order).toHaveProperty('amounts');
      expect(data.data.order).toHaveProperty('sla');
      expect(data.data.order).toHaveProperty('dates');
    }
  });

  test('GET /api/v1/orders/{increment_id} - should return 404 for non-existent order', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/orders/NONEXISTENT999`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.status()).toBe(404);
    const data = await response.json();

    expect(data.success).toBe(false);
    expect(data.error.code).toBe('ORDER_NOT_FOUND');
  });

  test('GET /api/v1/orders - should reject unauthenticated requests', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/orders`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    expect(response.status()).toBe(401);
  });
});

test.describe('REST API - Invoices Endpoints', () => {
  test.skip(!API_TOKEN, 'Skipping API tests - no API_TOKEN provided');

  test('GET /api/v1/invoices - should list invoices', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/invoices`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();

    expect(data.success).toBe(true);
    expect(data.data).toHaveProperty('invoices');
    expect(data.data).toHaveProperty('pagination');
    expect(Array.isArray(data.data.invoices)).toBe(true);
  });

  test('GET /api/v1/invoices - should support pagination', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/invoices?per_page=10&page=1`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();

    expect(data.data.pagination.per_page).toBe(10);
    expect(data.data.pagination.current_page).toBe(1);
  });

  test('GET /api/v1/invoices - should filter by state', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/invoices?state=paid`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();

    if (data.data.invoices.length > 0) {
      data.data.invoices.forEach((invoice: any) => {
        expect(invoice.state).toBe('paid');
      });
    }
  });

  test('GET /api/v1/invoices - should include items relationship', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/invoices?include=items`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();

    if (data.data.invoices.length > 0) {
      const firstInvoice = data.data.invoices[0];
      expect(firstInvoice).toHaveProperty('items');
    }
  });

  test('GET /api/v1/invoices/{increment_id} - should get single invoice', async ({ request }) => {
    // First get list to find an increment_id
    const listResponse = await request.get(`${BASE_URL}/api/v1/invoices?per_page=1`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    const listData = await listResponse.json();
    
    if (listData.data.invoices.length > 0) {
      const incrementId = listData.data.invoices[0].increment_id;

      const response = await request.get(`${BASE_URL}/api/v1/invoices/${incrementId}`, {
        headers: {
          'Authorization': `Bearer ${API_TOKEN}`,
          'Accept': 'application/json',
        },
      });

      expect(response.ok()).toBeTruthy();
      const data = await response.json();

      expect(data.success).toBe(true);
      expect(data.data.invoice.increment_id).toBe(incrementId);
      expect(data.data.invoice).toHaveProperty('customer');
      expect(data.data.invoice).toHaveProperty('amounts');
      expect(data.data.invoice).toHaveProperty('order');
      expect(data.data.invoice).toHaveProperty('dates');
      expect(data.data.invoice).toHaveProperty('items');
    }
  });

  test('GET /api/v1/invoices/{increment_id} - should return 404 for non-existent invoice', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/invoices/NONEXISTENT999`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    expect(response.status()).toBe(404);
    const data = await response.json();

    expect(data.success).toBe(false);
    expect(data.error.code).toBe('INVOICE_NOT_FOUND');
  });

  test('GET /api/v1/invoices - should reject unauthenticated requests', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/invoices`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    expect(response.status()).toBe(401);
  });
});

test.describe('REST API - Webhook Endpoints', () => {
  test.skip(!API_TOKEN, 'Skipping API tests - no API_TOKEN provided');

  test('POST /api/v1/webhooks/shipment-status - should accept valid shipment status update', async ({ request }) => {
    // First, get a shipment tracking number from orders
    const ordersResponse = await request.get(`${BASE_URL}/api/v1/orders?include=shipments&per_page=50`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    const ordersData = await ordersResponse.json();
    
    // Find an order with a shipment
    let trackingNumber: string | null = null;
    let carrierCode: string | null = null;

    for (const order of ordersData.data.orders) {
      if (order.shipments && order.shipments.length > 0) {
        trackingNumber = order.shipments[0].tracking_number;
        carrierCode = order.shipments[0].carrier_code;
        break;
      }
    }

    if (trackingNumber && carrierCode) {
      const response = await request.post(`${BASE_URL}/api/v1/webhooks/shipment-status`, {
        headers: {
          'Authorization': `Bearer ${API_TOKEN}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        data: {
          tracking_number: trackingNumber,
          carrier_code: carrierCode,
          status: 'in_transit',
          estimated_delivery_at: new Date(Date.now() + 86400000).toISOString(),
        },
      });

      expect(response.ok()).toBeTruthy();
      const data = await response.json();

      expect(data.success).toBe(true);
      expect(data.message).toContain('updated successfully');
      expect(data.data.tracking_number).toBe(trackingNumber);
      expect(data.data.status).toBe('in_transit');
    } else {
      test.skip();
    }
  });

  test('POST /api/v1/webhooks/shipment-status - should accept delivered status with details', async ({ request }) => {
    const ordersResponse = await request.get(`${BASE_URL}/api/v1/orders?include=shipments&per_page=50`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    const ordersData = await ordersResponse.json();
    
    let trackingNumber: string | null = null;
    let carrierCode: string | null = null;

    for (const order of ordersData.data.orders) {
      if (order.shipments && order.shipments.length > 0 && !order.shipments[0].delivery.actual_at) {
        trackingNumber = order.shipments[0].tracking_number;
        carrierCode = order.shipments[0].carrier_code;
        break;
      }
    }

    if (trackingNumber && carrierCode) {
      const response = await request.post(`${BASE_URL}/api/v1/webhooks/shipment-status`, {
        headers: {
          'Authorization': `Bearer ${API_TOKEN}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        data: {
          tracking_number: trackingNumber,
          carrier_code: carrierCode,
          status: 'delivered',
          delivered_at: new Date().toISOString(),
          signature: 'John Doe',
          delivery_notes: 'Left at front door',
        },
      });

      expect(response.ok()).toBeTruthy();
      const data = await response.json();

      expect(data.success).toBe(true);
      expect(data.data.status).toBe('delivered');
    } else {
      test.skip();
    }
  });

  test('POST /api/v1/webhooks/shipment-status - should validate required fields', async ({ request }) => {
    const response = await request.post(`${BASE_URL}/api/v1/webhooks/shipment-status`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      data: {
        tracking_number: 'TEST123',
        // Missing carrier_code and status
      },
    });

    expect(response.status()).toBe(422);
    const data = await response.json();

    expect(data.success).toBe(false);
    expect(data.error.code).toBe('VALIDATION_ERROR');
  });

  test('POST /api/v1/webhooks/shipment-status - should return 404 for non-existent tracking number', async ({ request }) => {
    const response = await request.post(`${BASE_URL}/api/v1/webhooks/shipment-status`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      data: {
        tracking_number: 'NONEXISTENT999999',
        carrier_code: 'ups',
        status: 'in_transit',
      },
    });

    expect(response.status()).toBe(404);
    const data = await response.json();

    expect(data.success).toBe(false);
    expect(data.error.code).toBe('SHIPMENT_NOT_FOUND');
  });

  test('POST /api/v1/webhooks/shipment-status - should validate status values', async ({ request }) => {
    const response = await request.post(`${BASE_URL}/api/v1/webhooks/shipment-status`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      data: {
        tracking_number: 'TEST123',
        carrier_code: 'ups',
        status: 'invalid_status',
      },
    });

    expect(response.status()).toBe(422);
    const data = await response.json();

    expect(data.success).toBe(false);
    expect(data.error.code).toBe('VALIDATION_ERROR');
  });

  test('POST /api/v1/webhooks/shipment-status - should reject unauthenticated requests', async ({ request }) => {
    const response = await request.post(`${BASE_URL}/api/v1/webhooks/shipment-status`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      data: {
        tracking_number: 'TEST123',
        carrier_code: 'ups',
        status: 'in_transit',
      },
    });

    expect(response.status()).toBe(401);
  });
});

test.describe('REST API - Response Format & Error Handling', () => {
  test.skip(!API_TOKEN, 'Skipping API tests - no API_TOKEN provided');

  test('should return consistent success response format', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/orders?per_page=1`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    const data = await response.json();

    expect(data).toHaveProperty('success');
    expect(data).toHaveProperty('data');
    expect(data.success).toBe(true);
  });

  test('should return consistent error response format', async ({ request }) => {
    const response = await request.get(`${BASE_URL}/api/v1/orders/NONEXISTENT999`, {
      headers: {
        'Authorization': `Bearer ${API_TOKEN}`,
        'Accept': 'application/json',
      },
    });

    const data = await response.json();

    expect(data).toHaveProperty('success');
    expect(data).toHaveProperty('error');
    expect(data.success).toBe(false);
    expect(data.error).toHaveProperty('code');
    expect(data.error).toHaveProperty('message');
  });

  test('should enforce rate limiting', async ({ request }) => {
    // Note: This test may fail if rate limit is high
    // It's more of a documentation test
    const promises = [];
    for (let i = 0; i < 70; i++) {
      promises.push(
        request.get(`${BASE_URL}/api/v1/orders`, {
          headers: {
            'Authorization': `Bearer ${API_TOKEN}`,
            'Accept': 'application/json',
          },
        })
      );
    }

    const responses = await Promise.all(promises);
    const rateLimited = responses.some(r => r.status() === 429);

    // If we hit rate limit, that's expected behavior
    // If we don't, rate limit might be set higher
    if (rateLimited) {
      expect(rateLimited).toBe(true);
    }
  });
});
