<?php
namespace App\Domain\Entities;

use App\Domain\ValueObjects\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model {
    protected $fillable = [
        'flight_number','departure_time','status'
    ];
    protected $casts = [
        'departure_time' => 'datetime',
    ];

    public function passengers(): HasMany { return $this->hasMany(Passenger::class); }
}
