<?php

namespace App\Filament\Master\Resources\MemberResource\Pages;

use App\Filament\Master\Resources\MemberResource;
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
            Actions\CreateAction::make()->label(__('Create New Agent')),
        ];
    }

    public function getTabs(): array
    {
        return [


            



            'agent' => Tab::make(__('Agent'))
                ->modifyQueryUsing(fn (Builder $query) => $query->role('agent'))
                ->badge($this->getModel()::role('agent')->count()),

            'user' => Tab::make(__('User'))
                ->modifyQueryUsing(fn (Builder $query) => $query->role('user'))
                ->badge($this->getModel()::role('user')->count()),
        ];
    }
}