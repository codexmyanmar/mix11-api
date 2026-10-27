<?php

namespace App\Filament\Shared\Resources;

use App\Filament\Shared\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    protected static ?string $slug = 'announcements';

    public static function getNavigationLabel(): string
    {
        return __('Announcements');
    }
    
    public static function getModelLabel(): string
    {
        return __('Announcements');
    }


     public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    // 1. QUERY: Show ONLY current user's created data
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Announcement Details'))
                    ->schema([
                        // 1. Message (Normal Text, First)
                        Forms\Components\Textarea::make('text')
                            ->label(__('Message'))
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),

                        // 2. Sort Order (Second)
                        Forms\Components\TextInput::make('sort')
                            ->label(__('Sort Order'))
                            ->numeric()
                            ->default(0)
                            ->required(),

                        // 3. Status (Third)
                        Forms\Components\Toggle::make('status')
                            ->label(__('Active'))
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),

                        // --- HIDDEN AUTO-FILLED FIELDS ---
                        
                        // Auto-fill User ID
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                        // Auto-fill User Role with Creator Role ID
                        Forms\Components\Hidden::make('user_role')
                            ->default(fn() => self::getCurrentUserRoleId()),

                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('text')
                    ->label(__('Message'))
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('sort')
                    ->label(__('Sort'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('status')
                    ->label(__('Active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('sort', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }

    // Helper to get Role ID based on String Role
    protected static function getCurrentUserRoleId(): int
    {
        $user = Auth::user();
        
        if ($user->hasRole('super')) return 1;
        if ($user->hasRole('senior')) return 2;
        if ($user->hasRole('master')) return 3;
        if ($user->hasRole('agent')) return 4;
        
        return 5; // Default User
    }
}