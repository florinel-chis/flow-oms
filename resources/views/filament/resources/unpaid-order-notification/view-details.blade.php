<div class="space-y-6">
    {{-- Summary Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Notification Summary</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Order</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">
                    #{{ $record->order->increment_id }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $record->order->customer_name }}<br>
                    <span class="text-gray-600 dark:text-gray-400">{{ $record->order->customer_email }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notification Type</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $record->is_warning ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                        {{ $record->is_warning ? '⚠️ Warning' : '🚫 Cancellation' }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Triggered At</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $record->triggered_at->format('M j, Y g:i A') }}<br>
                    <span class="text-gray-600 dark:text-gray-400">{{ $record->triggered_at->diffForHumans() }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Hours Unpaid</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">
                    {{ number_format($record->hours_unpaid, 1) }} hours
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $record->sent_successfully ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                        {{ $record->sent_successfully ? '✓ Sent Successfully' : '✗ Failed' }}
                    </span>
                </dd>
            </div>
        </dl>
    </div>

    {{-- HTTP Response Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">HTTP Response</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Endpoint URL</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono break-all">
                    {{ $record->endpoint_url }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">HTTP Status Code</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $record->response_status >= 200 && $record->response_status < 300 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                           ($record->response_status >= 400 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}">
                        {{ $record->response_status ?: 'N/A' }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Retry Count</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $record->retry_count }} {{ Str::plural('attempt', $record->retry_count) }}
                </dd>
            </div>
            @if($record->last_retry_at)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Retry At</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $record->last_retry_at->format('M j, Y g:i A') }}
                </dd>
            </div>
            @endif
        </dl>

        @if($record->error_message)
        <div class="mt-4">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Error Message</dt>
            <dd class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                <pre class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap font-mono">{{ $record->error_message }}</pre>
            </dd>
        </div>
        @endif

        @if($record->response_body)
        <div class="mt-4">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Response Body</dt>
            <dd class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-mono">{{ Str::limit($record->response_body, 500) }}</pre>
            </dd>
        </div>
        @endif
    </div>

    {{-- Payload Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Notification Payload</h3>
        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 overflow-x-auto">
            <pre class="text-sm text-gray-800 dark:text-gray-200 font-mono">{{ json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
        <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
            This is the complete JSON payload that was sent to the external endpoint.
        </div>
    </div>

    {{-- Order Details Section --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Order Details</h3>
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Grand Total</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">
                    {{ $record->order->currency_code }} {{ number_format($record->order->grand_total, 2) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Status</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">
                    {{ str_replace('_', ' ', $record->order->payment_status) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Order Status</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">
                    {{ $record->order->status }}
                </dd>
            </div>
        </dl>
    </div>
</div>
