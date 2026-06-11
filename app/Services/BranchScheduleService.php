<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use Carbon\Carbon;

class BranchScheduleService
{
    private const TIMEZONE = 'America/El_Salvador';

    private const DAY_NAMES = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    /**
     * Check if a branch is currently open.
     * Priority: 1) Special schedule for today's date, 2) Regular schedule for day of week, 3) Open 24/7 (no schedule = open)
     * Supports SPLIT SHIFTS: multiple time ranges per day (e.g., 8:00-12:00 and 14:00-20:00)
     */
    public function checkAvailability(Branch $branch, ?Carbon $at = null): array
    {
        $now = $at ? $at->copy()->setTimezone(self::TIMEZONE) : Carbon::now(self::TIMEZONE);
        $today = $now->toDateString();
        $dayOfWeek = (int) $now->dayOfWeek; // 0=Sunday
        $currentTime = $now->format('H:i:s');

        // 1. Check special schedules for today
        $specials = $branch->specialSchedules->filter(fn ($s) => $s->date->toDateString() === $today);
        if ($specials->isNotEmpty()) {
            // If ANY special entry is marked closed, the branch is closed
            $closedEntry = $specials->firstWhere('is_closed', true);
            if ($closedEntry) {
                return [
                    'is_open' => false,
                    'reason' => 'La sucursal se encuentra cerrada hoy' . ($closedEntry->label ? " por {$closedEntry->label}" : '') . '.',
                    'schedule' => $this->getFullSchedule($branch),
                ];
            }
            // Check if current time falls within any special time range
            $openEntries = $specials->where('is_closed', false)->filter(fn ($s) => $s->open_time && $s->close_time);
            if ($openEntries->isNotEmpty()) {
                foreach ($openEntries as $entry) {
                    if ($currentTime >= $entry->open_time && $currentTime <= $entry->close_time) {
                        return ['is_open' => true, 'reason' => 'Abierto (horario especial)', 'schedule' => null];
                    }
                }
                // Outside all special time ranges
                $ranges = $openEntries->map(fn ($s) => substr($s->open_time, 0, 5) . ' - ' . substr($s->close_time, 0, 5))->join(', ');
                return [
                    'is_open' => false,
                    'reason' => "La sucursal está cerrada en este momento. Horario especial hoy: {$ranges}.",
                    'schedule' => $this->getFullSchedule($branch),
                ];
            }
        }

        // 2. Check regular schedule for today's day of week
        $regularSlots = $branch->schedules->where('day_of_week', $dayOfWeek);
        if ($regularSlots->isEmpty()) {
            // No schedule defined = open 24/7
            return ['is_open' => true, 'reason' => 'Abierto (sin horario definido)', 'schedule' => null];
        }

        // If ALL slots for this day are marked closed
        $allClosed = $regularSlots->every(fn ($s) => $s->is_closed);
        if ($allClosed) {
            return [
                'is_open' => false,
                'reason' => 'La sucursal no atiende los ' . self::DAY_NAMES[$dayOfWeek] . '.',
                'schedule' => $this->getFullSchedule($branch),
            ];
        }

        // Check if current time falls within any open slot (split shifts)
        $openSlots = $regularSlots->where('is_closed', false);
        foreach ($openSlots as $slot) {
            if ($currentTime >= $slot->open_time && $currentTime <= $slot->close_time) {
                return ['is_open' => true, 'reason' => 'Abierto', 'schedule' => null];
            }
        }

        // Outside all time ranges for today
        $ranges = $openSlots->map(fn ($s) => substr($s->open_time, 0, 5) . ' - ' . substr($s->close_time, 0, 5))->join(', ');
        $dayName = self::DAY_NAMES[$dayOfWeek];
        return [
            'is_open' => false,
            'reason' => "La sucursal está cerrada en este momento. Horario del {$dayName}: {$ranges}.",
            'schedule' => $this->getFullSchedule($branch),
        ];
    }

    /**
     * Returns the full weekly schedule and upcoming special dates.
     */
    public function getFullSchedule(Branch $branch): array
    {
        $regular = [];
        for ($day = 0; $day <= 6; $day++) {
            $slots = $branch->schedules->where('day_of_week', $day);
            if ($slots->isEmpty()) {
                $regular[] = [
                    'day' => $day,
                    'day_name' => self::DAY_NAMES[$day],
                    'is_closed' => false,
                    'shifts' => [['open_time' => '00:00', 'close_time' => '23:59']],
                ];
            } elseif ($slots->every(fn ($s) => $s->is_closed)) {
                $regular[] = [
                    'day' => $day,
                    'day_name' => self::DAY_NAMES[$day],
                    'is_closed' => true,
                    'shifts' => [],
                ];
            } else {
                $shifts = $slots->where('is_closed', false)->map(fn ($s) => [
                    'open_time' => substr($s->open_time, 0, 5),
                    'close_time' => substr($s->close_time, 0, 5),
                ])->values()->toArray();
                $regular[] = [
                    'day' => $day,
                    'day_name' => self::DAY_NAMES[$day],
                    'is_closed' => false,
                    'shifts' => $shifts,
                ];
            }
        }

        $today = Carbon::now(self::TIMEZONE)->toDateString();
        $specials = $branch->specialSchedules
            ->filter(fn ($s) => $s->date->toDateString() >= $today)
            ->sortBy('date')
            ->take(10)
            ->map(fn ($s) => [
                'date' => $s->date->format('Y-m-d'),
                'label' => $s->label,
                'is_closed' => $s->is_closed,
                'shifts' => $s->is_closed ? [] : [[
                    'open_time' => $s->open_time ? substr($s->open_time, 0, 5) : null,
                    'close_time' => $s->close_time ? substr($s->close_time, 0, 5) : null,
                ]],
            ])->values()->toArray();

        return ['regular' => $regular, 'special' => $specials];
    }
}
