<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Manager;

class ManagerFactory extends Factory
{
    protected $model = Manager::class;

    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
