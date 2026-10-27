<?php

namespace App\Filament\Master\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    // This line enables the sidebar navigation ("beside panel")
    protected static string $layout = 'filament-panels::components.layout.index';

    // Ensure the navigation label is translated
    public static function getNavigationLabel(): string
    {
        return __('Profile');
    }

    public static function getLabel(): string
    {
        return __('');
    }

    public function form(Form $form): Form
    {
        // This is your exact original form structure
        return $form
            ->schema([
                // Section 1: Basic Info
                Section::make(__('Account Info'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('Email Address'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ]),

                // Section 2: Password Update
                Section::make(__('Update Password'))
                    ->description(__('Leave blank if you do not want to change your password.'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('New Password'))
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->same('passwordConfirmation'),

                        TextInput::make('passwordConfirmation')
                            ->label(__('Confirm Password'))
                            ->password()
                            ->revealable()
                            ->required(fn ($get) => filled($get('password')))
                            ->dehydrated(false),
                    ]),
            ]);
    }
}