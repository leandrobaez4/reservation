<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Domain\Entities\Reservation;
use App\Domain\Entities\Passenger;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear reservas con pasajeros como JSON
        $reservations = [
            [
                'flight_number' => 'AR1001',
                'departure_time' => now()->addDays(1),
                'status' => 'PENDING',
                'passengers' => [
                    [
                        'first_name' => 'Ana',
                        'last_name' => 'García',
                        'document' => 'DNI12345678'
                    ],
                    [
                        'first_name' => 'Carlos',
                        'last_name' => 'López',
                        'document' => 'DNI87654321'
                    ]
                ]
            ],
            [
                'flight_number' => 'LA2002',
                'departure_time' => now()->addDays(2),
                'status' => 'CONFIRMED',
                'passengers' => [
                    [
                        'first_name' => 'María',
                        'last_name' => 'Rodríguez',
                        'document' => 'DNI11223344'
                    ]
                ]
            ],
            [
                'flight_number' => 'AA3003',
                'departure_time' => now()->addDays(3),
                'status' => 'CHECKED_IN',
                'passengers' => [
                    [
                        'first_name' => 'José',
                        'last_name' => 'Martínez',
                        'document' => 'DNI55667788'
                    ],
                    [
                        'first_name' => 'Laura',
                        'last_name' => 'Fernández',
                        'document' => 'DNI99001122'
                    ],
                    [
                        'first_name' => 'Pedro',
                        'last_name' => 'Sánchez',
                        'document' => 'DNI33445566'
                    ]
                ]
            ]
        ];

        foreach ($reservations as $reservationData) {
            // Crear la reserva con passengers como JSON
            $reservationData['passengers'] = json_encode($reservationData['passengers']);
            Reservation::create($reservationData);
        }

        // Crear algunos pasajeros independientes (sin reservation_id)
        $independentPassengers = [
            [
                'first_name' => 'Elena',
                'last_name' => 'Vargas',
                'document' => 'DNI77889900'
            ],
            [
                'first_name' => 'Roberto',
                'last_name' => 'Díaz',
                'document' => 'DNI44556677'
            ],
            [
                'first_name' => 'Sofia',
                'last_name' => 'Morales',
                'document' => 'DNI22334455'
            ]
        ];

        foreach ($independentPassengers as $passengerData) {
            Passenger::create($passengerData);
        }
    }
}
