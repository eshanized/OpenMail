<?php

namespace Database\Factories;

use App\Models\Label;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LabelFactory extends Factory
{
    protected $model = Label::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word(),
            'color' => fake()->randomElement([
                'bg-blue-500', 'bg-green-600', 'bg-red-600', 'bg-yellow-600',
                'bg-purple-600', 'bg-pink-600', 'bg-orange-600', 'bg-teal-600',
                'bg-indigo-600', 'bg-gray-500',
            ]),
        ];
    }
}
