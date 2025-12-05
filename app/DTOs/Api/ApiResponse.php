<?php

namespace App\DTOs\Api;

use App\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;

readonly class ApiResponse
{
    private function __construct(
        public bool $success,
        public ?array $data = null,
        public ?string $message = null,
        public ?array $error = null,
        public int $statusCode = 200,
    ) {}

    /**
     * Create a successful response.
     */
    public static function success(
        array $data,
        ?string $message = null,
        int $statusCode = 200,
    ): self {
        return new self(
            success: true,
            data: $data,
            message: $message,
            statusCode: $statusCode,
        );
    }

    /**
     * Create an error response.
     */
    public static function error(
        ApiErrorCode $errorCode,
        ?string $message = null,
        ?array $details = null,
        ?int $statusCode = null,
    ): self {
        return new self(
            success: false,
            error: [
                'code' => $errorCode->value,
                'message' => $message ?? $errorCode->message(),
                'details' => $details,
            ],
            statusCode: $statusCode ?? $errorCode->httpStatus(),
        );
    }

    /**
     * Create a validation error response.
     */
    public static function validationError(array $errors): self
    {
        return self::error(
            errorCode: ApiErrorCode::VALIDATION_ERROR,
            message: 'Validation failed',
            details: $errors,
            statusCode: 422,
        );
    }

    /**
     * Convert to JSON response.
     */
    public function toResponse(): JsonResponse
    {
        $body = ['success' => $this->success];

        if ($this->data !== null) {
            $body['data'] = $this->data;
        }

        if ($this->message !== null) {
            $body['message'] = $this->message;
        }

        if ($this->error !== null) {
            $body['error'] = array_filter($this->error, fn ($v) => $v !== null);
        }

        return response()->json($body, $this->statusCode);
    }

    /**
     * Get array representation for logging.
     */
    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'error' => $this->error,
        ], fn ($v) => $v !== null);
    }
}
