<?php
namespace App\Repositories\Contracts;

use App\Domain\Entities\Passenger;

interface PassengerRepositoryInterface {
    public function find(int $id): ?Passenger;
}
