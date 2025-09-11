<?php
namespace App\Services;

use App\Domain\Entities\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService {


    public function __construct(
        private ReservationRepositoryInterface $reservations
    ) {}

    public function create(array $payload): Reservation {
        try {
            Log::info('Iniciando creación de reserva', ['payload' => $payload]);

            $reservation = $this->reservations->createWithPassengers([
                'flight_number'=>$payload['flight_number'],
                'departure_time'=>$payload['departure_time'],
                'status'=>'PENDING'
            ], $payload['passengers'] ?? []);

            DB::table('notifications')->insert([
                'type' => 'reservation.created',
                'payload' => json_encode($reservation->toArray()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            
            event(new \App\Events\ReservationCreated($reservation->id, $reservation->status));
            
            Log::info('Reserva creada exitosamente', [
                'reservation_id' => $reservation->id,
                'flight_number' => $reservation->flight_number,
                'status' => $reservation->status
            ]);
            
            return $reservation;
        } catch (\Throwable $e) {
            Log::error('Error al crear la reserva', [
                'payload' => $payload,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error al crear la reserva: ' . $e->getMessage(), 500);
        }
    }

    public function updateStatus(int $id, string $status): Reservation {
        try {
            Log::info('Iniciando actualización de estado', [
                'reservation_id' => $id,
                'new_status' => $status
            ]);

            $reservation = $this->reservations->find($id);
            
            if (!$reservation) {
                Log::warning('Reserva no encontrada', ['reservation_id' => $id]);
                abort(404, 'Reservation not found');
            }
            
            $reservation = $this->reservations->updateStatus($reservation, $status);

            DB::table('notifications')->insert([
                'type' => 'reservation.updated',
                'payload' => json_encode($reservation->toArray()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            event(new \App\Events\ReservationStatusChanged($reservation->id, $reservation->status));

            Log::info('Estado de reserva actualizado exitosamente', [
                'reservation_id' => $reservation->id,
                'new_status' => $reservation->status
            ]);
            
            return $reservation;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Re-lanzar excepciones HTTP sin modificar
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar el estado', [
                'reservation_id' => $id,
                'status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error al actualizar el estado: ' . $e->getMessage(), 500);
        }
    }
}
