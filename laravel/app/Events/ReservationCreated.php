<?php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationCreated {
    use Dispatchable, SerializesModels;
    public function __construct(public int $reservationId, public string $status) {}
}
