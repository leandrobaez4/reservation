<?php
namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passenger extends Model {
    protected $fillable = ['first_name','last_name','document'];
    // ...existing code...
}
