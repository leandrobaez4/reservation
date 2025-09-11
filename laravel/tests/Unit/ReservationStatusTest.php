<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Domain\ValueObjects\ReservationStatus;

class ReservationStatusTest extends TestCase
{
    public function test_reservation_status_enum_values()
    {
        $this->assertEquals('PENDING', ReservationStatus::PENDING->value);
        $this->assertEquals('CONFIRMED', ReservationStatus::CONFIRMED->value);
        $this->assertEquals('CANCELLED', ReservationStatus::CANCELLED->value);
        $this->assertEquals('CHECKED_IN', ReservationStatus::CHECKED_IN->value);
    }

    public function test_reservation_status_cases()
    {
        $cases = ReservationStatus::cases();
        $this->assertCount(4, $cases);
        
        $values = array_map(fn($case) => $case->value, $cases);
        $this->assertContains('PENDING', $values);
        $this->assertContains('CONFIRMED', $values);
        $this->assertContains('CANCELLED', $values);
        $this->assertContains('CHECKED_IN', $values);
    }
}
