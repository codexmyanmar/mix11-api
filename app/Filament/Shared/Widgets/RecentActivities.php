<?php

namespace App\Filament\Shared\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\User;
use App\Models\TopUp;
use App\Models\Withdraw;
use Illuminate\Database\Eloquent\Builder;

class RecentActivities extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected function getTableHeading(): string
    {
        return 'Recent Activities';
    }
    
    public function table(Table $table): Table
    {
        $role = auth()->user()->getRoleNames()->first();
        
        // Build query based on role
        $query = TopUp::query();
        
        switch($role) {
            case 'admin':
                $query->with('user')->latest()->limit(10);
                break;
            case 'agent':
                $query->where('agent_id', auth()->id())->with('user')->latest()->limit(10);
                break;
            default:
                $query->whereRaw('1 = 0'); // Return empty for other roles
        }
        
        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->getStateUsing(fn ($record) => 'Top Up')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}