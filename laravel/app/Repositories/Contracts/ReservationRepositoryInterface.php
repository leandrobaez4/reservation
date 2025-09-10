<?php
namespace App\Repositories\Contracts;

use App\Domain\Entities\Reservation;

interface ReservationRepositoryInterface {
    public function createWithPassengers(array $reservationData, array $passengers): Reservation;
    public function find(int $id): ?Reservation;
    public function updateStatus(Reservation $reservation, string $status): Reservation;
    public function list(array $filters): iterable;
}
