<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ReservationService;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use App\Domain\Entities\Reservation;
use Illuminate\Support\Facades\Event;
use App\Events\ReservationCreated;
use App\Events\ReservationStatusChanged;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReservationService::class);
    }

    public function test_creates_reservation_with_passengers()
    {
        Event::fake();

        $payload = [
            'flight_number' => 'AR1234',
            'departure_time' => now()->addDay()->toDateTimeString(),
            'passengers' => [
                ['first_name' => 'Ana', 'last_name' => 'Paz', 'document' => 'DNI1'],
                ['first_name' => 'Luis', 'last_name' => 'Diaz', 'document' => 'DNI2']
            ]
        ];

        $reservation = $this->service->create($payload);

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertEquals('AR1234', $reservation->flight_number);
        $this->assertEquals('PENDING', $reservation->status);
        
        // passengers viene como string JSON desde la DB
        $passengers = json_decode($reservation->passengers, true);
        $this->assertCount(2, $passengers);

        Event::assertDispatched(ReservationCreated::class);
    }

    public function test_updates_reservation_status()
    {
        Event::fake();

        // Crear reserva primero
        $reservation = Reservation::create([
            'flight_number' => 'AR1234',
            'departure_time' => now()->addDay(),
            'status' => 'PENDING',
            'passengers' => json_encode([
                ['first_name' => 'Ana', 'last_name' => 'Paz', 'document' => 'DNI1']
            ])
        ]);

        $updatedReservation = $this->service->updateStatus($reservation->id, 'CONFIRMED');

        $this->assertEquals('CONFIRMED', $updatedReservation->status);
        $this->assertEquals($reservation->id, $updatedReservation->id);

        Event::assertDispatched(ReservationStatusChanged::class);
    }

    public function test_throws_exception_for_nonexistent_reservation()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Reservation not found');
        
        $this->service->updateStatus(99999, 'CONFIRMED');
    }
}
