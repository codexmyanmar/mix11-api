<?php

namespace App\Filament\Super\Resources\MemberResource\Pages;

use App\Filament\Super\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

        protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('copy_info')
                ->label(__('Copy All Info'))
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->action(function ($record) {
                    $textToCopy = implode("\n", [
                        "Name: " . $record->name,
                        "Email: " . $record->email,
                        "User ID: " . ($record->user_id ?? 'N/A'),
                        "Balance: " . ($record->balance ?? '0'),
                        "Commission: " . ($record->commission ?? '0'),
                        "Status: " . ($record->status ? 'Active' : 'Inactive'),
                        "Last IP: " . ($record->last_login_ip ?? 'N/A'),
                    ]);

                    // Filament wrapper to execute JS copy to clipboard
                    $this->js("window.navigator.clipboard.writeText(`{$textToCopy}`)");

                    Notification::make()
                        ->title(__('Copied to clipboard!'))
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make()
                ->visible(fn () => auth()->user()->hasRole('super')),
        ];
    }
}