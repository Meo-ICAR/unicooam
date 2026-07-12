<?php

namespace App\Filament\Unicofin\Resources\Clients\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name'),
                Toggle::make('is_person')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('first_name'),
                TextInput::make('tax_code'),
                TextInput::make('vat_number'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('website')
                    ->url(),
                Toggle::make('is_pep')
                    ->required(),
                Select::make('client_type_id')
                    ->relationship('clientType', 'id'),
                Toggle::make('is_sanctioned')
                    ->required(),
                Toggle::make('is_remote_interaction')
                    ->required(),
                DateTimePicker::make('general_consent_at'),
                DateTimePicker::make('privacy_policy_read_at'),
                DateTimePicker::make('consent_special_categories_at'),
                DateTimePicker::make('consent_sic_at'),
                DateTimePicker::make('consent_marketing_at'),
                DateTimePicker::make('consent_profiling_at'),
                TextInput::make('status')
                    ->required()
                    ->default('raccolta_dati'),
                Toggle::make('is_company')
                    ->required(),
                Toggle::make('is_lead')
                    ->required(),
                Select::make('leadsource_id')
                    ->relationship('leadsource', 'name'),
                DateTimePicker::make('acquired_at'),
                TextInput::make('contoCOGE'),
                Toggle::make('privacy_consent')
                    ->required(),
                Toggle::make('is_client')
                    ->required(),
                Textarea::make('subfornitori')
                    ->columnSpanFull(),
                Toggle::make('is_requiredApprovation')
                    ->required(),
                Toggle::make('is_approved')
                    ->required(),
                Toggle::make('is_anonymous')
                    ->required(),
                DateTimePicker::make('blacklist_at'),
                TextInput::make('blacklisted_by'),
                TextInput::make('salary')
                    ->numeric(),
                TextInput::make('salary_quote')
                    ->numeric(),
                Toggle::make('is_art108')
                    ->required(),
                Toggle::make('is_consultant_gdpr')
                    ->required(),
                TextInput::make('privacy_contact_email')
                    ->email(),
                TextInput::make('dpo_email')
                    ->email(),
                Toggle::make('is_iso27001_certified')
                    ->required(),
                Toggle::make('is_dummy')
                    ->required(),
            ]);
    }
}
