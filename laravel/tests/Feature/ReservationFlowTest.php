<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Domain\Entities\Reservation;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_reservation_and_updates_status()
    {
        $response = $this->postJson('/api/reservations', [
            'flight_number'=>'AR1234',
            'departure_time'=>now()->addDay()->toDateTimeString(),
            'passengers'=>[
                ['first_name'=>'Ana','last_name'=>'Paz','document'=>'DNI1'],
                ['first_name'=>'Luis','last_name'=>'Diaz','document'=>'DNI2']
            ]
        ]);

        $response->assertCreated();
        $create = $response->json();
        $id = $create['id'];

        $update = $this->patchJson("/api/reservations/{$id}/status", ['status'=>'CONFIRMED']);
        $update->assertOk()->assertJsonPath('status','CONFIRMED');

        $this->assertEquals('CONFIRMED', Reservation::find($id)->status);
    }
}
