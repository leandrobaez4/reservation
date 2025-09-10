<?php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationStatusChanged {
    use Dispatchable, SerializesModels;
    public function __construct(public int $reservationId, public string $status) {}
}
