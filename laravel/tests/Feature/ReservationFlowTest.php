<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Domain\Entities\Reservation;
use Illuminate\Support\Facades\Event;
use App\Events\ReservationCreated;
use App\Events\ReservationStatusChanged;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_reservation_with_passengers()
    {
        Event::fake();

        $response = $this->postJson('/api/reservations', [
            'flight_number' => 'AR1234',
            'departure_time' => now()->addDay()->toDateTimeString(),
            'passengers' => [
                ['first_name' => 'Ana', 'last_name' => 'Paz', 'document' => 'DNI1'],
                ['first_name' => 'Luis', 'last_name' => 'Diaz', 'document' => 'DNI2']
            ]
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id', 'flight_number', 'departure_time', 'status', 'passengers'
            ])
            ->assertJsonPath('status', 'PENDING')
            ->assertJsonPath('flight_number', 'AR1234');

        // Verificar que passengers es una string JSON válida
        $data = $response->json();
        $passengers = json_decode($data['passengers'], true);
        $this->assertCount(2, $passengers);
        $this->assertEquals('Ana', $passengers[0]['first_name']);

        // Verificar que se disparó el evento
        Event::assertDispatched(ReservationCreated::class);
    }

    public function test_updates_reservation_status()
    {
        Event::fake();

        // Crear reserva
        $reservation = Reservation::create([
            'flight_number' => 'AR1234',
            'departure_time' => now()->addDay(),
            'status' => 'PENDING',
            'passengers' => json_encode([
                ['first_name' => 'Ana', 'last_name' => 'Paz', 'document' => 'DNI1']
            ])
        ]);

        $response = $this->patchJson("/api/reservations/{$reservation->id}/status", [
            'status' => 'CONFIRMED'
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'CONFIRMED');

        $this->assertEquals('CONFIRMED', $reservation->fresh()->status);

        // Verificar que se disparó el evento
        Event::assertDispatched(ReservationStatusChanged::class);
    }

    public function test_validates_required_fields_on_create()
    {
        $response = $this->postJson('/api/reservations', [
            'flight_number' => '',
            'passengers' => []
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['flight_number', 'departure_time', 'passengers']);
    }

    public function test_validates_status_on_update()
    {
        $reservation = Reservation::create([
            'flight_number' => 'AR1234',
            'departure_time' => now()->addDay(),
            'status' => 'PENDING',
            'passengers' => json_encode([['first_name' => 'Test', 'last_name' => 'User', 'document' => 'DNI1']])
        ]);

        $response = $this->patchJson("/api/reservations/{$reservation->id}/status", [
            'status' => 'INVALID_STATUS'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_filters_reservations_by_status()
    {
        // Crear reservas con diferentes estados
        Reservation::create([
            'flight_number' => 'AR1001',
            'departure_time' => now()->addDay(),
            'status' => 'PENDING',
            'passengers' => json_encode([['first_name' => 'Test1', 'last_name' => 'User1', 'document' => 'DNI1']])
        ]);

        Reservation::create([
            'flight_number' => 'AR1002',
            'departure_time' => now()->addDays(2),
            'status' => 'CONFIRMED',
            'passengers' => json_encode([['first_name' => 'Test2', 'last_name' => 'User2', 'document' => 'DNI2']])
        ]);

        $response = $this->getJson('/api/reservations?status=CONFIRMED');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'CONFIRMED');
    }

    public function test_returns_404_for_nonexistent_reservation()
    {
        $response = $this->patchJson('/api/reservations/99999/status', [
            'status' => 'CONFIRMED'
        ]);

        $response->assertNotFound();
    }
}
