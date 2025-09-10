<?php
namespace App\Repositories\Eloquent;

use App\Domain\Entities\Passenger;
use App\Repositories\Contracts\PassengerRepositoryInterface;

class PassengerRepository implements PassengerRepositoryInterface {
    public function find(int $id): ?Passenger { return Passenger::find($id); }
}
