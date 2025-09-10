<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest {
    public function rules(): array {
        return [
            'flight_number'=>['required','string','max:20'],
            'departure_time'=>['required','date'],
            'passengers'=>['array','min:1'],
            'passengers.*.first_name'=>['required','string','max:80'],
            'passengers.*.last_name'=>['required','string','max:80'],
            'passengers.*.document'=>['required','string','max:50'],
        ];
    }
}
