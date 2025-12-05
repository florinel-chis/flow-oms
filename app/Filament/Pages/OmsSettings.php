<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class OmsSettings extends Page
{
    use InteractsWithForms;
    use \Filament\Pages\Concerns\HasUnsavedDataChangesAlert;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'OMS Settings';

    protected static ?string $title = 'Order Management Settings';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.oms-settings';

    public ?array $data = [];

    public array $statusDistribution = [];
    public int $matchingOrdersCount = 0;

    public function mount(): void
    {
        $this->loadStatusDistribution();
        $this->form->fill($this->getFormData());
        $this->updateMatchingOrdersCount();
    }

    protected function getFormData(): array
    {
        $tenant = Filament::getTenant();

        return [
            'ready_to_ship_order_statuses' => Setting::get('ready_to_ship', 'order_statuses', ['new', 'payment_completed'], $tenant),
            'ready_to_ship_payment_statuses' => Setting::get('ready_to_ship', 'payment_statuses', ['paid'], $tenant),
            'ready_to_ship_check_shipments' => Setting::get('ready_to_ship', 'check_shipments', true, $tenant),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $orderStatusOptions = $this->getOrderStatusOptions();
        $paymentStatusOptions = $this->getPaymentStatusOptions();

        return $schema
            ->components([
                Section::make('Ready to Ship Configuration')
                    ->description('Configure which orders appear in the "Ready to Ship" widget on the dashboard.')
                    ->schema([
                        Placeholder::make('status_distribution')
                            ->label('Current Order Status Distribution')
                            ->content(fn () => $this->renderStatusDistribution()),

                        CheckboxList::make('ready_to_ship_order_statuses')
                            ->label('Order Statuses')
                            ->helperText('Select which order statuses indicate an order is ready to ship.')
                            ->options($orderStatusOptions)
                            ->columns(3)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateMatchingOrdersCount()),

                        CheckboxList::make('ready_to_ship_payment_statuses')
                            ->label('Payment Statuses')
                            ->helperText('Select which payment statuses are required for an order to be ready to ship.')
                            ->options($paymentStatusOptions)
                            ->columns(3)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateMatchingOrdersCount()),

                        Checkbox::make('ready_to_ship_check_shipments')
                            ->label('Exclude orders with shipments')
                            ->helperText('When enabled, orders that already have shipments created will not appear as "ready to ship".')
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateMatchingOrdersCount()),

                        Placeholder::make('preview')
                            ->label('Preview')
                            ->content(fn () => new \Illuminate\Support\HtmlString(
                                "<div class='rounded-lg bg-gray-50 dark:bg-gray-800 p-4'>
                                    <div class='flex items-center gap-2'>
                                        <svg class='w-5 h-5 text-primary-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                                        </svg>
                                        <span class='font-semibold text-gray-900 dark:text-white'>
                                            {$this->matchingOrdersCount} orders
                                        </span>
                                        <span class='text-gray-600 dark:text-gray-400'>
                                            currently match these criteria
                                        </span>
                                    </div>
                                </div>"
                            )),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function getOrderStatusOptions(): array
    {
        $statuses = DB::table('orders')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get();

        $options = [];
        foreach ($statuses as $status) {
            $withShipments = DB::table('orders')
                ->where('status', $status->status)
                ->whereExists(function($q) {
                    $q->select(DB::raw(1))
                      ->from('shipments')
                      ->whereColumn('shipments.order_id', 'orders.id');
                })
                ->count();

            $label = ucwords(str_replace('_', ' ', $status->status));
            $options[$status->status] = "{$label} ({$status->count} orders, {$withShipments} shipped)";
        }

        return $options;
    }

    protected function getPaymentStatusOptions(): array
    {
        $statuses = DB::table('orders')
            ->select('payment_status', DB::raw('count(*) as count'))
            ->groupBy('payment_status')
            ->orderBy('count', 'desc')
            ->get();

        $options = [];
        foreach ($statuses as $status) {
            $label = ucwords(str_replace('_', ' ', $status->payment_status));
            $options[$status->payment_status] = "{$label} ({$status->count} orders)";
        }

        return $options;
    }

    protected function loadStatusDistribution(): void
    {
        $this->statusDistribution = DB::table('orders')
            ->select('status', 'payment_status', DB::raw('count(*) as count'))
            ->groupBy('status', 'payment_status')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    protected function renderStatusDistribution(): \Illuminate\Support\HtmlString
    {
        if (empty($this->statusDistribution)) {
            return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-500">No orders found</p>');
        }

        $html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">';

        foreach ($this->statusDistribution as $item) {
            $status = ucwords(str_replace('_', ' ', $item->status));
            $payment = ucwords(str_replace('_', ' ', $item->payment_status));

            $html .= "
                <div class='flex items-center justify-between p-2 rounded bg-white dark:bg-gray-700'>
                    <span class='text-gray-700 dark:text-gray-300'>
                        <strong>{$status}</strong> + {$payment}
                    </span>
                    <span class='font-semibold text-gray-900 dark:text-white'>{$item->count}</span>
                </div>
            ";
        }

        $html .= '</div>';

        return new \Illuminate\Support\HtmlString($html);
    }

    protected function updateMatchingOrdersCount(): void
    {
        $data = $this->form->getState();

        $orderStatuses = $data['ready_to_ship_order_statuses'] ?? [];
        $paymentStatuses = $data['ready_to_ship_payment_statuses'] ?? [];
        $checkShipments = $data['ready_to_ship_check_shipments'] ?? true;

        if (empty($orderStatuses) || empty($paymentStatuses)) {
            $this->matchingOrdersCount = 0;
            return;
        }

        $query = Order::query()
            ->whereIn('status', $orderStatuses)
            ->whereIn('payment_status', $paymentStatuses);

        if ($checkShipments) {
            $query->whereDoesntHave('shipments');
        }

        $this->matchingOrdersCount = $query->count();
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $tenant = Filament::getTenant();

            // Save ready to ship settings
            Setting::set(
                'ready_to_ship',
                'order_statuses',
                $data['ready_to_ship_order_statuses'],
                'json',
                'Order statuses that indicate ready to ship',
                $tenant
            );

            Setting::set(
                'ready_to_ship',
                'payment_statuses',
                $data['ready_to_ship_payment_statuses'],
                'json',
                'Payment statuses required for ready to ship',
                $tenant
            );

            Setting::set(
                'ready_to_ship',
                'check_shipments',
                $data['ready_to_ship_check_shipments'],
                'boolean',
                'Exclude orders with shipments from ready to ship',
                $tenant
            );

            // Clear cache
            Cache::forget("settings:{$tenant->id}:ready_to_ship:order_statuses");
            Cache::forget("settings:{$tenant->id}:ready_to_ship:payment_statuses");
            Cache::forget("settings:{$tenant->id}:ready_to_ship:check_shipments");

            Notification::make()
                ->title('Settings Saved')
                ->body("Ready to Ship configuration updated successfully. {$this->matchingOrdersCount} orders now match the criteria.")
                ->success()
                ->send();

        } catch (Halt $exception) {
            return;
        }
    }
}
