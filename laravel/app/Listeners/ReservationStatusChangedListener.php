<?php
namespace App\Listeners;

use App\Events\ReservationStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ReservationStatusChangedListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ReservationStatusChanged $event): void
    {
        $payload = [
            'event' => 'reservation.status_changed',
            'data' => [
                'reservation_id' => $event->reservationId,
                'status' => $event->status
            ]
        ];
        // Siempre mostrar en consola/logs
        echo "Payload ReservationStatusChanged: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n";
        Log::info("Payload ReservationStatusChanged", $payload);
    }
}
