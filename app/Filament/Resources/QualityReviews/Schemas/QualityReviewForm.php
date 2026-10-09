<?php

namespace App\Filament\Resources\QualityReviews\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QualityReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Sessione di controllo')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->columnSpanFull(),
                        Select::make('reviewer_user_id')
                            ->label('Responsabile controllo qualità')
                            ->options(fn () => User::query()->where('role', UserRole::QUALITY->value)->pluck('name', 'id'))
                            ->searchable(),
                        TextInput::make('sample_size')
                            ->label('Pratiche nel campione')
                            ->disabled(),
                        DatePicker::make('period_from')->label('Pratiche inserite dal')->displayFormat('d/m/y')->disabled(),
                        DatePicker::make('period_to')->label('Pratiche inserite fino al')->displayFormat('d/m/y')->disabled(),
                        Textarea::make('notes')->label('Note')->rows(3)->columnSpanFull(),
                    ]),
            ]);
    }
}
