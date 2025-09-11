<?php
namespace App\Repositories\Eloquent;

use App\Domain\Entities\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;

class ReservationRepository implements ReservationRepositoryInterface {
    public function createWithPassengers(array $data, array $passengers): Reservation {
        $data['passengers'] = json_encode($passengers);
        $reservation = Reservation::create($data);
        // Crear pasajeros en la tabla
        foreach ($passengers as $p) {
            \App\Domain\Entities\Passenger::create([
                'first_name' => $p['first_name'],
                'last_name' => $p['last_name'],
                'document' => $p['document']
            ]);
        }
        return $reservation;
    }
    public function find(int $id): ?Reservation { return Reservation::find($id); }
    public function updateStatus(Reservation $r, string $status): Reservation { $r->update(['status'=>$status]); return $r; }
    public function list(array $filters): iterable {
        return Reservation::when($filters['status'] ?? null, fn($q,$s)=>$q->where('status',$s))
            ->when($filters['from'] ?? null, fn($q,$f)=>$q->where('departure_time','>=',$f))
            ->when($filters['to'] ?? null, fn($q,$t)=>$q->where('departure_time','<=',$t))
            ->orderByDesc('created_at')
            ->paginate(20);
    }
}
