import { APIRequestContext } from '@playwright/test';

/**
 * API Test Helpers
 * 
 * These helpers manage API authentication and requests for REST API testing.
 */

export interface ApiConfig {
  baseURL: string;
  token?: string;
  tenantId?: number;
}

/**
 * Create an API token for testing
 * This would typically be done via a seeder or test setup
 * For now, we'll document the expected token format
 */
export async function createTestApiToken(
  request: APIRequestContext,
  baseURL: string,
  email: string = 'test@example.com',
  password: string = 'password'
): Promise<string> {
  // Note: In a real scenario, you'd need to implement token creation
  // For testing, you should:
  // 1. Create a user via seeder
  // 2. Create a Sanctum token for that user
  // 3. Return the token
  
  // This is a placeholder - you'll need to implement actual token creation
  // via your Laravel API or console command
  throw new Error('Token creation not yet implemented - create token via php artisan tinker or seeder');
}

/**
 * Make an authenticated API request
 */
export async function makeApiRequest(
  request: APIRequestContext,
  config: ApiConfig,
  method: 'GET' | 'POST' | 'PATCH' | 'DELETE',
  endpoint: string,
  data?: any
) {
  const headers: Record<string, string> = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  if (config.token) {
    headers['Authorization'] = `Bearer ${config.token}`;
  }

  const url = `${config.baseURL}/api/v1/${endpoint}`;

  switch (method) {
    case 'GET':
      return await request.get(url, { headers });
    case 'POST':
      return await request.post(url, { headers, data });
    case 'PATCH':
      return await request.patch(url, { headers, data });
    case 'DELETE':
      return await request.delete(url, { headers });
  }
}

/**
 * Verify API response structure
 */
export function expectApiSuccess(response: any) {
  if (!response.success) {
    throw new Error(`API request failed: ${JSON.stringify(response)}`);
  }
  return response;
}

/**
 * Verify API error response structure
 */
export function expectApiError(response: any, expectedCode?: string) {
  if (response.success) {
    throw new Error('Expected error response but got success');
  }
  if (expectedCode && response.error?.code !== expectedCode) {
    throw new Error(`Expected error code ${expectedCode} but got ${response.error?.code}`);
  }
  return response;
}
