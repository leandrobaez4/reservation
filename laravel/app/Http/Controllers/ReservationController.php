<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationStatusRequest;
use App\Services\ReservationService;
use Illuminate\Http\Request;

class ReservationController extends Controller {
    public function __construct(private ReservationService $service) {}

    public function store(StoreReservationRequest $request) {
        return response()->json($this->service->create($request->validated()), 201);
    }

    public function updateStatus(int $id, UpdateReservationStatusRequest $request) {
        return response()->json($this->service->updateStatus($id, $request->validated('status')));
    }

    public function index(Request $request) {
        return response()->json(app(\App\Repositories\Contracts\ReservationRepositoryInterface::class)->list([
            'status'=>$request->query('status'),
            'from'=>$request->query('from'),
            'to'=>$request->query('to'),
        ]));
    }
}
