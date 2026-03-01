<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

class Login extends BaseLogin
{
    /**
     * Override: ganti label "Email" jadi "Username"
     * tetapi tetap pakai email sebagai identifier.
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Username')
            ->placeholder('contoh@email.com')
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }
}
