<?php

namespace App\Services;

class HhMmTimeSumService
{
    /**
     * @param array<int, string> $times
     */
    public function sum(array $times): string
    {
        $minutes = 0;

        foreach ($times as $time) {
            $minutes += $this->toMinutes($time);
        }

        return $this->fromMinutes($minutes);
    }

    public function add(string $left, string $right): string
    {
        return $this->fromMinutes($this->toMinutes($left) + $this->toMinutes($right));
    }

    private function toMinutes(string $time): int
    {
        if (! preg_match('/^(?<hours>\d{1,3}):(?<minutes>\d{2})$/', $time, $matches)) {
            return 0;
        }

        return ((int) $matches['hours'] * 60) + (int) $matches['minutes'];
    }

    private function fromMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
