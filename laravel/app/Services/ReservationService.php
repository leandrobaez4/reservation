<?php
namespace App\Services;

use App\Domain\Entities\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ReservationService {
    public function __construct(
        private ReservationRepositoryInterface $reservations,
        private EventPublisher $publisher
    ) {}

    public function create(array $payload): Reservation {
        $reservation = $this->reservations->createWithPassengers([
            'flight_number'=>$payload['flight_number'],
            'departure_time'=>$payload['departure_time'],
            'status'=>'PENDING'
        ], $payload['passengers'] ?? []);

        // Persistir notificación en la base de datos
    DB::table('notifications')->insert([
            'type' => 'reservation.created',
            'payload' => json_encode($reservation->toArray()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->publisher->publish('reservation.created', $reservation->toArray());
        return $reservation;
    }

    public function updateStatus(int $id, string $status): Reservation {
        $reservation = $this->reservations->find($id);
        abort_unless($reservation, 404, 'Reservation not found');
        $reservation = $this->reservations->updateStatus($reservation, $status);
        $this->publisher->publish('reservation.updated', $reservation->load('passengers')->toArray());
        return $reservation;
    }
}
