<?php
namespace App\Http\Controllers;

use App\Repositories\Contracts\PassengerRepositoryInterface;

class PassengerController extends Controller {
    public function __construct(private PassengerRepositoryInterface $passengers) {}
    public function show(int $id) {
        $p = $this->passengers->find($id);
        abort_unless($p, 404);
        return response()->json($p);
    }
}
