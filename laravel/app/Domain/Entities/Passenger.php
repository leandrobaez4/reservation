<?php
namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passenger extends Model {
    protected $fillable = ['reservation_id','first_name','last_name','document'];
    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
}
