<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Entities\Reservation;
use App\Services\ReservationService;

class SimulateReservationEvents extends Command {
    protected $signature = 'skylink:simulate {--interval=5}';
    protected $description = 'Cada N segundos cambia aleatoriamente el estado de una reserva y publica evento';

    public function handle(ReservationService $service): int {
        $interval = (int)$this->option('interval');
        $statuses = ['CONFIRMED','CANCELLED','CHECKED_IN'];
        $this->info("Simulando cada {$interval}s… Ctrl+C para salir");
        while (true) {
            $reservation = Reservation::inRandomOrder()->first();
            if ($reservation) {
                $status = $statuses[array_rand($statuses)];
                $service->updateStatus($reservation->id, $status);
                $this->info("Reservation {$reservation->id} -> {$status}");
            }
            sleep($interval);
        }
        return self::SUCCESS;
    }
}
