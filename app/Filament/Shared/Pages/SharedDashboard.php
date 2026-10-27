<?php

namespace App\Filament\Shared\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;

class SharedDashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->form([
                    Section::make('ရှာဖွေမှုစစ်ထုတ်ရန်')
                        ->schema([
                            Select::make('dateRange')
                                ->label('အချိန်ကာလ')
                                ->options([
                                    'all' => 'အားလုံး',
                                    'today' => 'ယနေ့',
                                    'yesterday' => 'မနေ့က',
                                    'this_week' => 'ယခုအပတ်',
                                    'this_year' => 'ယခုနှစ်',
                                    'custom' => 'စိတ်ကြိုက်ရက်စွဲ',
                                ])
                                ->default('all')
                                ->live(),

                            
                            DatePicker::make('startDate')
                                ->label('စတင်သည့်ရက်စွဲ')
                                ->visible(fn ($get) => $get('dateRange') === 'custom')
                                ->live(),

                            DatePicker::make('endDate')
                                ->label('ကုန်ဆုံးသည့်ရက်စွဲ')
                                ->visible(fn ($get) => $get('dateRange') === 'custom')
                                ->live(),

                            Select::make('userStatus')
                                ->label('အသုံးပြုသူအခြေအနေ')
                                ->options([
                                    'all' => 'အားလုံး',
                                    'active' => 'ပုံမှန် (Active)',
                                    'banned' => 'ပိတ်ထားသူ (Banned)',
                                ])
                                ->default('all')
                                ->live(),
                        ])->columns(2),
                ])
                ->modalWidth('lg'),
        ];
    }
}