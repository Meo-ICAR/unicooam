<?php

namespace App\Filament\Resources\QualityReviews\Pages;

use App\Filament\Resources\QualityReviews\QualityReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQualityReview extends EditRecord
{
    protected static string $resource = QualityReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
