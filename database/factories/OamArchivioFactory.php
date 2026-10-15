<?php

namespace Database\Factories;

use App\Models\OamArchivio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OamArchivio>
 */
class OamArchivioFactory extends Factory
{
    protected $model = OamArchivio::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period' => '202601',
            'note' => fake()->sentence(),
            'semestrali_count' => 0,
            'pratiche_count' => 0,
        ];
    }
}
