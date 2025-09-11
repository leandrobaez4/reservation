<?php
namespace App\Http\Controllers;

use App\Repositories\Contracts\PassengerRepositoryInterface;
use Illuminate\Support\Facades\Log;

class PassengerController extends Controller {
    public function __construct(private PassengerRepositoryInterface $passengers) {}
    public function show(int $id) {
        try {
            Log::info('Iniciando búsqueda de pasajero', ['passenger_id' => $id]);
            
            $p = $this->passengers->find($id);
            abort_unless($p, 404);
            
            Log::info('Pasajero encontrado exitosamente', [
                'passenger_id' => $id,
                'passenger_name' => $p->first_name . ' ' . $p->last_name,
                'document' => $p->document
            ]);
            
            return response()->json($p);
        } catch (\Throwable $e) {
            Log::error('Error al obtener pasajero', [
                'passenger_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al obtener pasajero',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
