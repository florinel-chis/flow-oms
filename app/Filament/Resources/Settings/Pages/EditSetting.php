<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure value is properly formatted for the form based on type
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'json':
                    // TagsInput expects an array of strings
                    if (is_array($data['value'])) {
                        $data['value'] = array_map('strval', $data['value']);
                    }
                    break;
                case 'boolean':
                    $data['value'] = (bool) $data['value'];
                    break;
                case 'integer':
                    $data['value'] = (int) $data['value'];
                    break;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Convert values back to their proper types before saving
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'json':
                    // Keep as array, Eloquent cast will handle JSON encoding
                    break;
                case 'boolean':
                    $data['value'] = (bool) $data['value'];
                    break;
                case 'integer':
                    $data['value'] = (int) $data['value'];
                    break;
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
