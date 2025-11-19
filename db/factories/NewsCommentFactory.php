<?php

declare(strict_types=1);

namespace Database\Factories\Volunteersystem\Models;

use Volunteersystem\Models\News;
use Volunteersystem\Models\NewsComment;
use Volunteersystem\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsCommentFactory extends Factory
{
    /** @var string */
    protected $model = NewsComment::class; // phpcs:ignore

    public function definition(): array
    {
        return [
            'news_id' => News::factory(),
            'user_id' => User::factory(),
            'text'    => $this->faker->text(),
        ];
    }
}
