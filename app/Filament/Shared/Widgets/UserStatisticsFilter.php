<?php

namespace App\Filament\Shared\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Attributes\On;

class UserStatisticsFilter extends Widget implements HasForms
{
    use InteractsWithForms;
    
    protected static string $view = 'filament.widgets.user-statistics-filter';
    
    protected int | string | array $columnSpan = 'full';
    
    public string $dateRange = 'today';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $statusFilter = 'all';
    
    public function mount(): void
    {
        // Get filters from session if exists
        $this->dateRange = session()->get('user_stats_date_range', 'today');
        $this->startDate = session()->get('user_stats_start_date', null);
        $this->endDate = session()->get('user_stats_end_date', null);
        $this->statusFilter = session()->get('user_stats_status_filter', 'all');
    }
    
    protected function getFormSchema(): array
    {
        return [
            Grid::make(5)
                ->schema([
                    Select::make('dateRange')
                        ->label('ရက်စွဲအပိုင်းအခြား')
                        ->options([
                            'today' => 'ယနေ့',
                            'yesterday' => 'မနေ့က',
                            'week' => 'ဤအပတ်',
                            'month' => 'ဤလ',
                            'year' => 'ဤနှစ်',
                            'custom' => 'စိတ်ကြိုက်',
                        ])
                        ->default($this->dateRange)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state !== 'custom') {
                                $set('startDate', null);
                                $set('endDate', null);
                            }
                            $this->applyFilters();
                        })
                        ->live(),
                    
                    DatePicker::make('startDate')
                        ->label('မှစတင်၍')
                        ->placeholder('စတင်ရက်')
                        ->hidden(fn (callable $get) => $get('dateRange') !== 'custom')
                        ->afterStateUpdated(fn () => $this->applyFilters()),
                    
                    DatePicker::make('endDate')
                        ->label('မှ')
                        ->placeholder('ပြီးဆုံးရက်')
                        ->hidden(fn (callable $get) => $get('dateRange') !== 'custom')
                        ->afterStateUpdated(fn () => $this->applyFilters()),
                    
                    Select::make('statusFilter')
                        ->label('အခြေအနေ')
                        ->options([
                            'all' => 'အားလုံး',
                            'active' => 'အသုံးပြုသူများ',
                            'banned' => 'ပိတ်သိမ်းခံထားရသူများ',
                        ])
                        ->default($this->statusFilter)
                        ->afterStateUpdated(fn () => $this->applyFilters()),
                    
                    \Filament\Forms\Components\Actions::make([
                        \Filament\Forms\Components\Actions\Action::make('reset')
                            ->label('ပြန်လည်စတင်မည်')
                            ->color('gray')
                            ->button()
                            ->action(function () {
                                $this->dateRange = 'today';
                                $this->startDate = null;
                                $this->endDate = null;
                                $this->statusFilter = 'all';
                                $this->applyFilters();
                            }),
                    ]),
                ]),
        ];
    }
    
    public function applyFilters(): void
    {
        // Save to session
        session()->put('user_stats_date_range', $this->dateRange);
        session()->put('user_stats_start_date', $this->startDate);
        session()->put('user_stats_end_date', $this->endDate);
        session()->put('user_stats_status_filter', $this->statusFilter);
        
        // Dispatch event to widget
        $this->dispatch('updateWidgetFilters', 
            dateRange: $this->dateRange,
            startDate: $this->startDate,
            endDate: $this->endDate,
            statusFilter: $this->statusFilter
        );
    }
    
    public static function canView(): bool
    {
        return true;
    }
}