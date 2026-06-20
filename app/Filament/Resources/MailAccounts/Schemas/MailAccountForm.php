<?php

namespace App\Filament\Resources\MailAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MailAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('email_address')
                    ->email()
                    ->required(),
                Toggle::make('is_pec')
                    ->required(),
                Select::make('incoming_protocol')
                    ->options(['pop3' => 'Pop3', 'imap' => 'Imap'])
                    ->default('pop3')
                    ->required(),
                TextInput::make('incoming_host')
                    ->required(),
                TextInput::make('incoming_port')
                    ->required()
                    ->numeric(),
                TextInput::make('incoming_username')
                    ->required(),
                Textarea::make('incoming_password')
                    ->required()
                    ->columnSpanFull(),
                Select::make('incoming_encryption')
                    ->options(['none' => 'None', 'ssl' => 'Ssl', 'tls' => 'Tls'])
                    ->default('ssl')
                    ->required(),
                TextInput::make('smtp_host')
                    ->required(),
                TextInput::make('smtp_port')
                    ->required()
                    ->numeric(),
                TextInput::make('smtp_username')
                    ->required(),
                Textarea::make('smtp_password')
                    ->required()
                    ->columnSpanFull(),
                Select::make('smtp_encryption')
                    ->options(['none' => 'None', 'ssl' => 'Ssl', 'tls' => 'Tls'])
                    ->default('ssl')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('mailable_type'),
                TextInput::make('mailable_id'),
            ]);
    }
}
