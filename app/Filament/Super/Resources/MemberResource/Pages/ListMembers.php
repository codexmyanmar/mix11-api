<?php

namespace App\Filament\Super\Resources\MemberResource\Pages;

use App\Filament\Super\Resources\MemberResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('Create New Senior')),
        ];
    }

    public function getTabs(): array
    {
        return [


          

            

            'senior' => Tab::make(__('Senior'))
                ->modifyQueryUsing(fn (Builder $query) => $query->role('senior'))
                ->badge($this->getModel()::role('senior')->count()),

            'master' => Tab::make(__('Master'))
                ->modifyQueryUsing(fn (Builder $query) => $query->role('master'))
                ->badge($this->getModel()::role('master')->count()),

            'agent' => Tab::make(__('Agent'))
                ->modifyQueryUsing(fn (Builder $query) => $query->role('agent'))
                ->badge($this->getModel()::role('agent')->count()),

            'user' => Tab::make(__('User'))
                ->modifyQueryUsing(fn (Builder $query) => $query->role('user'))
                ->badge($this->getModel()::role('user')->count()),
        ];
    }
}