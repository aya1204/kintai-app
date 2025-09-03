<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class WorkFactory extends Factory
{
    protected $model = Work::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'date' => Carbon::today()->toDateString(),
            'start_time' => null,
            'end_time' => null,
        ];
    }

    // 出勤中の状態
    public function working()
    {
        return $this->state(fn () => [
            'start_time' => Carbon::now()->format('H:i'),
            'end_time' => null,
        ]);
    }

    // 退勤済みの状態
    public function finished()
    {
        return $this->state(fn () => [
            'start_time' => Carbon::now()->subHours(8)->format('H:i'),
            'end_time' => Carbon::now()->format('H:i'),
        ]);
    }
}
