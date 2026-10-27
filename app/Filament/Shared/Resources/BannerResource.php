<?php

namespace App\Filament\Shared\Resources;

use App\Filament\Shared\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    
    protected static ?string $slug = 'banners';

    public static function getNavigationLabel(): string
    {
        return __('Banners'); 
    }

    public static function getModelLabel(): string
    {
        return __('Banners');
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
                Forms\Components\Section::make(__('Banner Details')) 
                    ->schema([
                        // 1. Image Upload (Added disk & visibility)
                        Forms\Components\FileUpload::make('image')
                            ->label(__('Image')) 
                            ->image()
                            ->disk('public') // <--- REQUIRED: Store in public disk
                            ->directory('banners')
                            ->visibility('public')
                            ->required()
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
                // 2. Image Column (Added disk)
                Tables\Columns\ImageColumn::make('image')
                    ->label(__('Image')) 
                    ->disk('public') // <--- REQUIRED: Read from public disk
                    ->square(),

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
                
                // 3. Delete Action
                // Filament handles file deletion automatically if the Model casts are correct
                // and the FileUpload component was used.
                Tables\Actions\DeleteAction::make(), 
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
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