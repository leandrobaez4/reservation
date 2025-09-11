<?php
namespace App\Listeners;

use App\Events\ReservationCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ReservationCreatedListener implements ShouldQueue
{
    public function handle(ReservationCreated $event): void
    {
        $payload = [
            'event' => 'reservation.created',
            'data' => [
                'reservation_id' => $event->reservationId,
                'status' => $event->status
            ]
        ];
        // Siempre mostrar en consola/logs
        echo "Payload ReservationCreated: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
        Log::info("Payload ReservationCreated", $payload);
    }
}
