<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Ragione sociale')
                    ->required(),
                Select::make('company_type')
                    ->label('Tipo società')
                    ->options([
                        'mediatore' => 'Mediatore',
                        'call center' => 'Call center',
                        'hotel' => 'Hotel',
                        'sw house' => 'Software house',
                    ]),
                TextInput::make('vat_number')
                    ->label('Partita IVA / Codice fiscale'),

                /*
                 * TextInput::make('vat_name')
                 *
                 *     ->label('Denominazione su fatture'),
                 */
                TextInput::make('sponsor')
                    ->label('Gruppo Societario'),
                TextInput::make('oam')
                    ->label('Numero iscrizione OAM'),
                DatePicker::make('oam_at')
                    ->label('Data iscrizione OAM')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('oam_name')
                    ->label('Denominazione OAM'),
                TextInput::make('numero_iscrizione_rui')
                    ->label('Numero iscrizione RUI'),
                TextInput::make('ivass')
                    ->label('Codice IVASS'),
                DatePicker::make('ivass_at')
                    ->label('Data iscrizione IVASS')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                TextInput::make('ivass_name')
                    ->label('Nome IVASS'),
                Select::make('ivass_section')
                    ->label('Sezione IVASS')
                    ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D', 'E' => 'E']),

                /*
                 * Textarea::make('page_header')
                 *     ->label('Intestazione report')
                 *     ->columnSpanFull(),
                 * Textarea::make('page_footer')
                 *     ->label('Piè di pagina report')
                 *     ->columnSpanFull(),
                 */
            ]);
    }
}
