<?php

namespace App\Filament\Resources\OamSemestrales\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OamSemestraleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name'),
                TextInput::make('period'),
                TextInput::make('numero_iscrizione_m510'),
                TextInput::make('prodotto_creditizio'),
                TextInput::make('intermediari_convenzionati')
                    ->numeric()
                    ->default(0),
                TextInput::make('intermediari_non_convenzionati')
                    ->numeric()
                    ->default(0),
                TextInput::make('pratiche_intermediate')
                    ->numeric()
                    ->default(0),
                TextInput::make('pratiche_lavorazione')
                    ->numeric()
                    ->default(0),
                TextInput::make('erogato_lordo')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('erogato_lavorazione')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('provv_clientela')
                    ->tel()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('provv_istituto_comp')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('premi_istituto_comp')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payin_ass_banche')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payin_ass_broker')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payin_ass_broker_cap')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payout_rete_credito')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payout_rete_ass_banche')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payout_rete_ass_broker')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payout_rete_ass_broker_cap')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('num_rivalse')
                    ->numeric()
                    ->default(0),
                TextInput::make('importo_retrocesse')
                    ->numeric()
                    ->default(0.0),
            ]);
    }
}
