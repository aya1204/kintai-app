<?php

namespace Database\Factories;

use App\Models\BreakTime;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BreakTimeFactory extends Factory
{
    protected $model = BreakTime::class;

    public function definition()
    {
        return [
            'work_id' => Work::factory(),
            'start_time' => null,
            'end_time' => null,
        ];
    }

    // 休憩中の状態
    public function takeBreak()
    {
        return $this->state(fn() => [
            'start_time' => Carbon::now()->format('H:i'),
            'end_time' => null,
        ]);
    }

    // 休憩終了の状態
    public function breakReturn()
    {
        return $this->state(fn() => [
            'start_time' => Carbon::now()->subHours(8)->format('H:i'),
            'end_time' => Carbon::now()->format('H:i'),
        ]);
    }
}
