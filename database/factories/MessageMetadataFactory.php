<?php

namespace Database\Factories;

use App\Models\MessageMetadata;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageMetadataFactory extends Factory
{
    protected $model = MessageMetadata::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'folder_path' => 'INBOX',
            'uid' => fake()->unique()->randomNumber(8),
            'message_id' => '<' . fake()->uuid . '@example.com>',
            'subject' => fake()->sentence(),
            'from_address' => fake()->safeEmail(),
            'from_name' => fake()->name(),
            'to_address' => fake()->safeEmail(),
            'date' => fake()->dateTimeBetween('-30 days', 'now'),
            'snippet' => fake()->text(100),
            'body_text' => fake()->text(200),
            'has_attachments' => false,
            'is_seen' => false,
            'is_flagged' => false,
            'size' => fake()->numberBetween(1000, 1000000),
        ];
    }
}
