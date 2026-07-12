<?php

namespace App\Filament\Unicofin\Resources\Clients\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->searchable(),
                IconColumn::make('is_person')
                    ->boolean(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('first_name')
                    ->searchable(),
                TextColumn::make('tax_code')
                    ->searchable(),
                TextColumn::make('vat_number')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('website')
                    ->searchable(),
                IconColumn::make('is_pep')
                    ->boolean(),
                TextColumn::make('clientType.id')
                    ->searchable(),
                IconColumn::make('is_sanctioned')
                    ->boolean(),
                IconColumn::make('is_remote_interaction')
                    ->boolean(),
                TextColumn::make('general_consent_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('privacy_policy_read_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('consent_special_categories_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('consent_sic_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('consent_marketing_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('consent_profiling_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                IconColumn::make('is_company')
                    ->boolean(),
                IconColumn::make('is_lead')
                    ->boolean(),
                TextColumn::make('leadsource.name')
                    ->searchable(),
                TextColumn::make('acquired_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('contoCOGE')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('privacy_consent')
                    ->boolean(),
                IconColumn::make('is_client')
                    ->boolean(),
                IconColumn::make('is_requiredApprovation')
                    ->boolean(),
                IconColumn::make('is_approved')
                    ->boolean(),
                IconColumn::make('is_anonymous')
                    ->boolean(),
                TextColumn::make('blacklist_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('blacklisted_by')
                    ->searchable(),
                TextColumn::make('salary')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('salary_quote')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_art108')
                    ->boolean(),
                IconColumn::make('is_consultant_gdpr')
                    ->boolean(),
                TextColumn::make('privacy_contact_email')
                    ->searchable(),
                TextColumn::make('dpo_email')
                    ->searchable(),
                IconColumn::make('is_iso27001_certified')
                    ->boolean(),
                IconColumn::make('is_dummy')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
