<?php

namespace Database\Factories;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Pass1234'),
            'remember_token' => Str::random(10),
            'role' => 'sales_staff',
        ];
    }

    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'root',
        ]);
    }

    public function storeOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'store_owner',
        ]);
    }

    public function storeManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'store_manager',
        ]);
    }

    public function inventoryStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'inventory_staff',
        ]);
    }

    public function salesStaff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'sales_staff',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
