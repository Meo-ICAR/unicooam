<?php

namespace App\Filament\Resources\QualityReviews;

use App\Filament\Resources\QualityReviews\Pages\EditQualityReview;
use App\Filament\Resources\QualityReviews\Pages\ListQualityReviews;
use App\Filament\Resources\QualityReviews\RelationManagers\AuditsRelationManager;
use App\Filament\Resources\QualityReviews\Schemas\QualityReviewForm;
use App\Filament\Resources\QualityReviews\Tables\QualityReviewsTable;
use App\Filament\Traits\HasPlanAccess;
use App\Models\QualityReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class QualityReviewResource extends Resource
{
    use HasPlanAccess;

    protected static ?string $model = QualityReview::class;

    protected static ?int $navigationSort = 21;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Controllo qualità';

    protected static ?string $modelLabel = 'Controllo qualità';

    protected static ?string $pluralModelLabel = 'Controlli qualità';

    public static function form(Schema $schema): Schema
    {
        return QualityReviewForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QualityReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQualityReviews::route('/'),
            'edit' => EditQualityReview::route('/{record}/edit'),
        ];
    }
}
