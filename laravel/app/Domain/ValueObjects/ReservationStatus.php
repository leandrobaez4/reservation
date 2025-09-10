<?php
namespace App\Domain\ValueObjects;

enum ReservationStatus: string {
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case CHECKED_IN = 'CHECKED_IN';
}
