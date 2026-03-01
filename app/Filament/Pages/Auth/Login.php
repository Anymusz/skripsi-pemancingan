<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Schemas\Schema;

class Login extends BaseLogin
{
    /**
     * Override form: ganti label "Email" jadi "Username"
     * tetapi tetap validasi sebagai email di belakang layar.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Username')
                    ->placeholder('email@contoh.com')
                    ->email()
                    ->required()
                    ->autocomplete('username')
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }
}
