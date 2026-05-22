<?php

namespace Database\Factories;

use App\Models\CashSession;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashSessionFactory extends Factory
{
    protected $model = CashSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'opened_at' => now()->subHours(8),
            'opening_balance' => 50000,
        ];
    }
}
