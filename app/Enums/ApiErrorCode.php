<?php

namespace App\Enums;

enum ApiErrorCode: string
{
    // Authentication & Authorization
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    case TOKEN_REVOKED = 'TOKEN_REVOKED';
    case INSUFFICIENT_PERMISSIONS = 'INSUFFICIENT_PERMISSIONS';

    // Resource Errors
    case SHIPMENT_NOT_FOUND = 'SHIPMENT_NOT_FOUND';
    case ORDER_NOT_FOUND = 'ORDER_NOT_FOUND';
    case INVOICE_NOT_FOUND = 'INVOICE_NOT_FOUND';
    case TENANT_NOT_FOUND = 'TENANT_NOT_FOUND';
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';

    // Validation Errors
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case INVALID_TRACKING_NUMBER = 'INVALID_TRACKING_NUMBER';
    case INVALID_TIMESTAMP = 'INVALID_TIMESTAMP';
    case INVALID_CARRIER_CODE = 'INVALID_CARRIER_CODE';

    // Rate Limiting
    case RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';

    // Server Errors
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';

    // Business Logic Errors
    case SHIPMENT_ALREADY_DELIVERED = 'SHIPMENT_ALREADY_DELIVERED';
    case TENANT_MISMATCH = 'TENANT_MISMATCH';

    /**
     * Get default HTTP status code for this error.
     */
    public function httpStatus(): int
    {
        return match ($this) {
            self::UNAUTHORIZED,
            self::TOKEN_EXPIRED,
            self::TOKEN_REVOKED => 401,

            self::FORBIDDEN,
            self::INSUFFICIENT_PERMISSIONS,
            self::TENANT_MISMATCH => 403,

            self::SHIPMENT_NOT_FOUND,
            self::ORDER_NOT_FOUND,
            self::INVOICE_NOT_FOUND,
            self::TENANT_NOT_FOUND,
            self::RESOURCE_NOT_FOUND => 404,

            self::VALIDATION_ERROR,
            self::INVALID_TRACKING_NUMBER,
            self::INVALID_TIMESTAMP,
            self::INVALID_CARRIER_CODE,
            self::SHIPMENT_ALREADY_DELIVERED => 422,

            self::RATE_LIMIT_EXCEEDED => 429,

            self::INTERNAL_ERROR => 500,
            self::SERVICE_UNAVAILABLE => 503,
        };
    }

    /**
     * Get human-readable message for this error.
     */
    public function message(): string
    {
        return match ($this) {
            self::UNAUTHORIZED => 'Authentication required. Please provide a valid API token.',
            self::FORBIDDEN => 'Access denied. You do not have permission to perform this action.',
            self::TOKEN_EXPIRED => 'API token has expired. Please generate a new token.',
            self::TOKEN_REVOKED => 'API token has been revoked.',
            self::INSUFFICIENT_PERMISSIONS => 'Token does not have required permissions.',

            self::SHIPMENT_NOT_FOUND => 'No shipment found with the specified tracking number.',
            self::ORDER_NOT_FOUND => 'No order found with the specified ID.',
            self::INVOICE_NOT_FOUND => 'No invoice found with the specified ID.',
            self::TENANT_NOT_FOUND => 'Tenant not found.',
            self::RESOURCE_NOT_FOUND => 'Requested resource not found.',

            self::VALIDATION_ERROR => 'Request validation failed.',
            self::INVALID_TRACKING_NUMBER => 'Invalid tracking number format.',
            self::INVALID_TIMESTAMP => 'Invalid timestamp format. Use ISO 8601 format.',
            self::INVALID_CARRIER_CODE => 'Invalid or unsupported carrier code.',

            self::RATE_LIMIT_EXCEEDED => 'Too many requests. Please slow down.',

            self::INTERNAL_ERROR => 'An unexpected error occurred. Please try again later.',
            self::SERVICE_UNAVAILABLE => 'Service temporarily unavailable.',

            self::SHIPMENT_ALREADY_DELIVERED => 'Shipment has already been marked as delivered.',
            self::TENANT_MISMATCH => 'Token not authorized for this tenant.',
        };
    }
}
