<?php

namespace App\Filament\Shared\Resources;

use App\Filament\Shared\Resources\PromotionADSResource\Pages;
use App\Models\PromotionADS; // <--- Updated Model Import
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PromotionADSResource extends Resource
{
    protected static ?string $model = PromotionADS::class; // <--- Updated Model Class

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    protected static ?string $slug = 'promotions';

    public static function getNavigationLabel(): string
    {
        return __('Promotions'); 
    }

    public static function getModelLabel(): string
    {
        return __('Promotions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Promotion Details')) 
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('Title'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image')
                            ->label(__('Image')) 
                            ->image()
                            ->disk('public')
                            ->directory('promotions')
                            ->visibility('public')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label(__('Description'))
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('sort')
                            ->label(__('Sort Order')) 
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\Toggle::make('status')
                            ->label(__('Active')) 
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),

                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),

                        Forms\Components\Hidden::make('user_role')
                            ->default(fn() => self::getCurrentUserRoleId()),

                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('Image')) 
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),

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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListPromotionADS::route('/'),
            'create' => Pages\CreatePromotionADS::route('/create'),
            'edit' => Pages\EditPromotionADS::route('/{record}/edit'),
        ];
    }

    protected static function getCurrentUserRoleId(): int
    {
        $user = Auth::user();
        
        if ($user->hasRole('super')) return 1;
        if ($user->hasRole('senior')) return 2;
        if ($user->hasRole('master')) return 3;
        if ($user->hasRole('agent')) return 4;
        
        return 5; 
    }
}