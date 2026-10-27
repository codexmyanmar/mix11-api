<?php

namespace App\Filament\Admin\Resources\MemberResource\Pages;

use App\Filament\Admin\Resources\MemberResource;
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
            Actions\CreateAction::make()->label(__('Create New Super')),
        ];
    }

    public function getTabs(): array
    {
        return [


            'super' => Tab::make(__('Super'))
                ->modifyQueryUsing(fn (Builder $query) => $query->role('super'))
                ->badge($this->getModel()::role('super')->count()),

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