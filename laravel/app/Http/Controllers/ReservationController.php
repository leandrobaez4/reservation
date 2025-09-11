<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationStatusRequest;
use App\Services\ReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller {
    public function __construct(private ReservationService $service) {}

    public function store(StoreReservationRequest $request) {
        try {
            Log::info('Iniciando creación de reserva desde controlador', [
                'request_data' => $request->validated()
            ]);
            
            $reservation = $this->service->create($request->validated());
            
            Log::info('Reserva creada exitosamente desde controlador', [
                'reservation_id' => $reservation->id
            ]);
            
            return response()->json($reservation, 201);
        } catch (\Throwable $e) {
            Log::error('Error en controlador al crear reserva', [
                'request_data' => $request->validated(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al crear la reserva',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(int $id, UpdateReservationStatusRequest $request) {
        try {
            Log::info('Iniciando actualización de estado desde controlador', [
                'reservation_id' => $id,
                'new_status' => $request->validated('status')
            ]);
            
            $reservation = $this->service->updateStatus($id, $request->validated('status'));
            
            Log::info('Estado actualizado exitosamente desde controlador', [
                'reservation_id' => $id,
                'status' => $request->validated('status')
            ]);
            
            return response()->json($reservation);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            // Re-lanzar excepciones HTTP (como 404) sin modificar
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error en controlador al actualizar estado', [
                'reservation_id' => $id,
                'status' => $request->validated('status'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al actualizar el estado',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request) {
        try {
            Log::info('Iniciando listado de reservas desde controlador', [
                'filters' => [
                    'status' => $request->query('status'),
                    'from' => $request->query('from'),
                    'to' => $request->query('to')
                ]
            ]);
            
            $reservations = app(\App\Repositories\Contracts\ReservationRepositoryInterface::class)->list([
                'status'=>$request->query('status'),
                'from'=>$request->query('from'),
                'to'=>$request->query('to'),
            ]);
            
            Log::info('Reservas listadas exitosamente desde controlador', [
                'total_results' => $reservations->total()
            ]);
            
            return response()->json($reservations);
        } catch (\Throwable $e) {
            Log::error('Error en controlador al listar reservas', [
                'filters' => [
                    'status' => $request->query('status'),
                    'from' => $request->query('from'),
                    'to' => $request->query('to')
                ],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al listar reservas',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
