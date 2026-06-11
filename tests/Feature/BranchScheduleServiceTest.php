<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchSchedule;
use App\Models\SpecialSchedule;
use App\Services\BranchScheduleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BranchScheduleService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BranchScheduleService();
        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'address' => '123 Test St',
            'phone' => '12345678',
            'city' => 'San Salvador',
            'latitude' => 13.6929,
            'longitude' => -89.2182,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_returns_open_24_7_if_no_schedules_defined()
    {
        // For Tuesday (2026-06-09) at 12:00:00
        $dateTime = Carbon::create(2026, 6, 9, 12, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);

        $this->assertTrue($availability['is_open']);
        $this->assertStringContainsString('sin horario definido', $availability['reason']);
    }

    /** @test */
    public function it_respects_regular_schedule_opening_hours()
    {
        // Set Tuesday as Lunes-Viernes style open 08:00 - 18:00 (Tuesday = 2)
        BranchSchedule::create([
            'branch_id' => $this->branch->id,
            'day_of_week' => 2,
            'open_time' => '08:00:00',
            'close_time' => '18:00:00',
            'is_closed' => false,
        ]);

        // Test at 12:00:00 (should be open)
        $dateTime = Carbon::create(2026, 6, 9, 12, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);
        $this->assertTrue($availability['is_open']);

        // Test at 07:00:00 (should be closed)
        $dateTime = Carbon::create(2026, 6, 9, 7, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);
        $this->assertFalse($availability['is_open']);
        $this->assertStringContainsString('cerrada', $availability['reason']);

        // Test at 19:00:00 (should be closed)
        $dateTime = Carbon::create(2026, 6, 9, 19, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);
        $this->assertFalse($availability['is_open']);
        $this->assertStringContainsString('cerrada', $availability['reason']);
    }

    /** @test */
    public function it_supports_split_shifts()
    {
        // Set Tuesday (2) split shifts: 08:00-12:00 and 14:00-18:00
        BranchSchedule::create([
            'branch_id' => $this->branch->id,
            'day_of_week' => 2,
            'open_time' => '08:00:00',
            'close_time' => '12:00:00',
            'is_closed' => false,
        ]);
        BranchSchedule::create([
            'branch_id' => $this->branch->id,
            'day_of_week' => 2,
            'open_time' => '14:00:00',
            'close_time' => '18:00:00',
            'is_closed' => false,
        ]);

        // Test at 10:00 (should be open)
        $dateTime = Carbon::create(2026, 6, 9, 10, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);
        $this->assertTrue($availability['is_open']);

        // Test at 13:00 (should be closed - mid-day gap)
        $dateTime = Carbon::create(2026, 6, 9, 13, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);
        $this->assertFalse($availability['is_open']);
        $this->assertStringContainsString('cerrada', $availability['reason']);

        // Test at 15:00 (should be open)
        $dateTime = Carbon::create(2026, 6, 9, 15, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);
        $this->assertTrue($availability['is_open']);
    }

    /** @test */
    public function it_respects_closed_days()
    {
        // Set Tuesday (2) closed
        BranchSchedule::create([
            'branch_id' => $this->branch->id,
            'day_of_week' => 2,
            'is_closed' => true,
        ]);

        $dateTime = Carbon::create(2026, 6, 9, 12, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);
        $this->assertFalse($availability['is_open']);
        $this->assertStringContainsString('no atiende los Martes', $availability['reason']);
    }

    /** @test */
    public function it_respects_special_schedules_override_closed()
    {
        // Set regular schedule open on Tuesday (2) 08:00-18:00
        BranchSchedule::create([
            'branch_id' => $this->branch->id,
            'day_of_week' => 2,
            'open_time' => '08:00:00',
            'close_time' => '18:00:00',
            'is_closed' => false,
        ]);

        // Set special schedule on 2026-06-09 (which is Tuesday) as CLOSED for "Navidad"
        SpecialSchedule::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-06-09',
            'label' => 'Día de Prueba',
            'is_closed' => true,
        ]);

        // Test at 12:00 (should be closed despite regular hours)
        $dateTime = Carbon::create(2026, 6, 9, 12, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);

        $this->assertFalse($availability['is_open']);
        $this->assertStringContainsString('cerrada hoy por Día de Prueba', $availability['reason']);
    }

    /** @test */
    public function it_respects_special_schedules_override_custom_hours()
    {
        // Set regular schedule open on Tuesday (2) 08:00-18:00
        BranchSchedule::create([
            'branch_id' => $this->branch->id,
            'day_of_week' => 2,
            'open_time' => '08:00:00',
            'close_time' => '18:00:00',
            'is_closed' => false,
        ]);

        // Set special schedule on 2026-06-09 as open but short hours: 09:00 - 12:00
        SpecialSchedule::create([
            'branch_id' => $this->branch->id,
            'date' => '2026-06-09',
            'label' => 'Corto',
            'is_closed' => false,
            'open_time' => '09:00:00',
            'close_time' => '12:00:00',
        ]);

        // Test at 10:00 (should be open)
        $dateTime = Carbon::create(2026, 6, 9, 10, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);
        $this->assertTrue($availability['is_open']);

        // Test at 15:00 (should be closed, despite regular schedule being open till 18:00)
        $dateTime = Carbon::create(2026, 6, 9, 15, 0, 0, 'America/El_Salvador');
        $availability = $this->service->checkAvailability($this->branch, $dateTime);
        $this->assertFalse($availability['is_open']);
        $this->assertStringContainsString('Horario especial hoy: 09:00 - 12:00', $availability['reason']);
    }
}
