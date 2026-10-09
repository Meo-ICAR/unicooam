<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\QualityReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QualityReview>
 */
class QualityReviewFactory extends Factory
{
    protected $model = QualityReview::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'Controllo qualità '.fake()->unique()->numberBetween(1, 99999),
            'period_from' => now()->subMonths(3)->toDateString(),
            'period_to' => now()->toDateString(),
            'sample_size' => 10,
            'filters' => [],
        ];
    }
}
