<?php

namespace App\Http\Requests\Api;

use App\DTOs\Api\ApiResponse;
use App\Enums\ApiErrorCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateShipmentDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by Sanctum middleware
        // Token ability checks can be added here if needed
        $token = $this->user()?->currentAccessToken();

        if (! $token) {
            return false;
        }

        // Check if token has the required ability
        return $token->can('shipments:update-delivery');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'delivered_at' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
            'carrier_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'signature' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'delivery_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'photo_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:2048',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'delivered_at.required' => 'The delivered_at timestamp is required.',
            'delivered_at.date' => 'The delivered_at must be a valid date/time.',
            'delivered_at.before_or_equal' => 'The delivered_at cannot be in the future.',
            'carrier_code.max' => 'The carrier_code must not exceed 50 characters.',
            'signature.max' => 'The signature must not exceed 255 characters.',
            'delivery_notes.max' => 'The delivery_notes must not exceed 1000 characters.',
            'photo_url.url' => 'The photo_url must be a valid URL.',
            'photo_url.max' => 'The photo_url must not exceed 2048 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'delivered_at' => 'delivery timestamp',
            'carrier_code' => 'carrier code',
            'delivery_notes' => 'delivery notes',
            'photo_url' => 'delivery photo URL',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        $response = ApiResponse::validationError(
            $validator->errors()->toArray()
        )->toResponse();

        throw new HttpResponseException($response);
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        $response = ApiResponse::error(
            errorCode: ApiErrorCode::INSUFFICIENT_PERMISSIONS,
            message: 'Token does not have permission to update shipment deliveries.',
        )->toResponse();

        throw new HttpResponseException($response);
    }
}
