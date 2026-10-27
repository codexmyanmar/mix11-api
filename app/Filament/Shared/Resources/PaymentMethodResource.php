<?php

namespace App\Filament\Shared\Resources;

use App\Filament\Shared\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $slug = 'payment-methods';

    public static function getNavigationLabel(): string
    {
        return __('Payment Methods'); 
    }

    public static function getModelLabel(): string
    {
        return __('Payment Methods');
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
                Forms\Components\Section::make(__('Payment Details')) 
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label(__('Bank Name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('icon')
                            ->label(__('Icon')) 
                            ->image()
                            ->disk('public') 
                            ->directory('payment-methods')
                            ->visibility('public')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label(__('Transaction Type'))
                            ->options([
                                '0' => __('Cash In'),
                                '1' => __('Cash Out'),
                                '2' => __('Both'),
                            ])
                            ->default('0')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Components\Select $component) => $component->getContainer()->validate()),

                        Forms\Components\TextInput::make('name')
                            ->label(__('Account Name'))
                            ->maxLength(255)
                            ->required(fn (Forms\Get $get) => in_array($get('type'), ['0', '2']))
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['0', '2'])),

                        Forms\Components\TextInput::make('account_no')
                            ->label(__('Account Number'))
                            ->maxLength(255)
                            ->required(fn (Forms\Get $get) => in_array($get('type'), ['0', '2']))
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['0', '2'])),

                        // New Amount Limit Fields
                        Forms\Components\TextInput::make('min_amount')
                            ->label(__('Minimum Amount'))
                            ->numeric()
                            ->default(0)
                            ->prefix('K') // Optional: Add currency prefix
                            ->required(),

                        Forms\Components\TextInput::make('max_amount')
                            ->label(__('Maximum Amount'))
                            ->numeric()
                            ->prefix('K') // Optional: Add currency prefix
                            ->placeholder('Unlimited'),

                        Forms\Components\Toggle::make('status')
                            ->label(__('Active')) 
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),

                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),

                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label(__('Icon')) 
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('bank_name')
                    ->label(__('Bank Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Account Name'))
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('account_no')
                    ->label(__('Account Number'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),
                
                // Display Min/Max amounts in table
                Tables\Columns\TextColumn::make('min_amount')
                    ->label(__('Min Amount'))
                    ->money('MMK') // Formats as currency
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_amount')
                    ->label(__('Max Amount'))
                    ->money('MMK') // Formats as currency
                    ->placeholder('Unlimited')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => __('Cash In'),
                        '1' => __('Cash Out'),
                        '2' => __('Both'),
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '0' => 'success',
                        '1' => 'warning',
                        '2' => 'info',
                        default => 'gray',
                    }),

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
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), 
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}