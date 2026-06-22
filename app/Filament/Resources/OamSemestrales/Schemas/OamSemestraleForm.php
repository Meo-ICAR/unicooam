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
                    ->label('Azienda')
                    ->relationship('company', 'name'),
                TextInput::make('period')
                    ->label('Periodo'),
                TextInput::make('numero_iscrizione_m510')
                    ->label('N. iscrizione M510'),
                TextInput::make('prodotto_creditizio')
                    ->label('Prodotto creditizio'),
                TextInput::make('intermediari_convenzionati')
                    ->label('Intermediari convenzionati')
                    ->numeric()
                    ->default(0),
                TextInput::make('intermediari_non_convenzionati')
                    ->label('Intermediari non convenzionati')
                    ->numeric()
                    ->default(0),
                TextInput::make('pratiche_intermediate')
                    ->label('Pratiche intermedie')
                    ->numeric()
                    ->default(0),
                TextInput::make('pratiche_lavorazione')
                    ->label('Pratiche in lavorazione')
                    ->numeric()
                    ->default(0),
                TextInput::make('erogato_lordo')
                    ->label('Erogato lordo')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('erogato_lavorazione')
                    ->label('Erogato in lavorazione')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('provv_clientela')
                    ->label('Provvigioni clientela')
                    ->tel()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('provv_istituto_comp')
                    ->label('Provvigioni istituto')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('premi_istituto_comp')
                    ->label('Premi istituto')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payin_ass_banche')
                    ->label('Pay-in ass. banche')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payin_ass_broker')
                    ->label('Pay-in ass. broker')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payin_ass_broker_cap')
                    ->label('Pay-in ass. broker captive')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payout_rete_credito')
                    ->label('Pay-out rete credito')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payout_rete_ass_banche')
                    ->label('Pay-out rete ass. banche')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payout_rete_ass_broker')
                    ->label('Pay-out rete ass. broker')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payout_rete_ass_broker_cap')
                    ->label('Pay-out rete ass. broker captive')
                    ->numeric()
                    ->default(0.0),
                TextInput::make('num_rivalse')
                    ->label('N. rivalse')
                    ->numeric()
                    ->default(0),
                TextInput::make('importo_retrocesse')
                    ->label('Importo retrocesse')
                    ->numeric()
                    ->default(0.0),
            ]);
    }
}
