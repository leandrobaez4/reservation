<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationStatusRequest extends FormRequest {
    public function rules(): array {
        return ['status'=>['required','in:PENDING,CONFIRMED,CANCELLED,CHECKED_IN']];
    }
}
