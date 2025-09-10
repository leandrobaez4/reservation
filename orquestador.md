# SkyLink Orchestrator – Laravel + Node (Socket.io) Boilerplate

> **Stack**: Laravel 10 (PHP >=8.1 recommended), MySQL, Redis (Pub/Sub), Node.js 18+ with Socket.io.
> **Goal**: CRUD de reservas/pasajeros + streaming en tiempo real de cambios de estado.

---

## 0) Estructura del repo

```
skylink/
├── README.md
├── docker-compose.yml
├── .env.example
├── openapi.yaml
├── laravel/              # API + dominio
│   ├── composer.json
│   ├── artisan
│   ├── .env.example
│   ├── app/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Reservation.php
│   │   │   │   └── Passenger.php
│   │   │   └── ValueObjects/
│   │   │       └── ReservationStatus.php
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── ReservationController.php
│   │   │   │   └── PassengerController.php
│   │   │   └── Requests/
│   │   │       ├── StoreReservationRequest.php
│   │   │       └── UpdateReservationStatusRequest.php
│   │   ├── Repositories/
│   │   │   ├── Contracts/
│   │   │   │   ├── ReservationRepositoryInterface.php
│   │   │   │   └── PassengerRepositoryInterface.php
│   │   │   ├── Eloquent/
│   │   │   │   ├── ReservationRepository.php
│   │   │   │   └── PassengerRepository.php
│   │   ├── Services/
│   │   │   ├── ReservationService.php
│   │   │   └── EventPublisher.php
│   │   ├── Events/
│   │   │   └── ReservationStatusChanged.php
│   │   ├── Console/
│   │   │   └── Commands/
│   │   │       └── SimulateReservationEvents.php
│   │   └── Providers/
│   │       └── AppServiceProvider.php
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── 2025_09_10_000001_create_reservations_table.php
│   │   │   ├── 2025_09_10_000002_create_passengers_table.php
│   │   │   └── 2025_09_10_000003_create_notifications_table.php
│   │   └── seeders/
│   │       └── DatabaseSeeder.php
│   ├── routes/
│   │   └── api.php
│   └── tests/
│       └── Feature/
│           └── ReservationFlowTest.php
└── node-realtime/
    ├── package.json
    ├── src/
    │   ├── server.js
    │   ├── subscriber.js
    │   └── ws-client.js
    └── test/
        └── server.test.js
```

---

## 1) Docker Compose (MySQL + Redis + Node opcional)

```yaml
# docker-compose.yml
version: "3.9"
services:
  mysql:
    image: mysql:8
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: skylink
      MYSQL_USER: skylink
      MYSQL_PASSWORD: skylink
    ports: ["3306:3306"]
    command: ["--default-authentication-plugin=mysql_native_password"]
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7
    ports: ["6379:6379"]

  node:
    build: ./node-realtime
    environment:
      REDIS_HOST: redis
      REDIS_PORT: 6379
      SOCKET_PORT: 4000
    ports: ["4000:4000"]
    depends_on: [redis]

volumes:
  mysql_data:
```

---

## 2) Variables de entorno base

```env
# .env.example (raíz)
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_DATABASE=skylink
MYSQL_USER=skylink
MYSQL_PASSWORD=skylink

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Node WebSocket (cliente y dashboard simulado)
SOCKET_URL=ws://localhost:4000
```

```env
# laravel/.env.example
APP_NAME=SkyLink
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skylink
DB_USERNAME=skylink
DB_PASSWORD=skylink

BROADCAST_CONNECTION=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Canal pub/sub entre Laravel y Node
EVENT_CHANNEL=skylink:events
```

---

## 3) Laravel – composer.json (mínimo)

```json
{
  "name": "skylink/laravel",
  "type": "project",
  "require": {
    "php": ">=8.1",
    "laravel/framework": "^10.0",
    "laravel/sanctum": "^3.3",
    "predis/predis": "^2.2",
    "laravel/redis": "^1.5"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.5",
    "nunomaduro/collision": "^7.0"
  }
}
```

---

## 4) Dominio y Value Object

```php
// laravel/app/Domain/ValueObjects/ReservationStatus.php
<?php
namespace App\Domain\ValueObjects;

enum ReservationStatus: string {
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case CHECKED_IN = 'CHECKED_IN';
}
```

```php
// laravel/app/Domain/Entities/Reservation.php
<?php
namespace App\Domain\Entities;

use App\Domain\ValueObjects\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model {
    protected $fillable = [
        'flight_number','departure_time','status'
    ];
    protected $casts = [
        'departure_time' => 'datetime',
    ];

    public function passengers(): HasMany { return $this->hasMany(Passenger::class); }
}
```

```php
// laravel/app/Domain/Entities/Passenger.php
<?php
namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passenger extends Model {
    protected $fillable = ['reservation_id','first_name','last_name','document'];
    public function reservation(): BelongsTo { return $this->belongsTo(Reservation::class); }
}
```

---

## 5) Migrations

```php
// laravel/database/migrations/2025_09_10_000001_create_reservations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('flight_number')->index();
            $table->dateTime('departure_time')->index();
            $table->enum('status',['PENDING','CONFIRMED','CANCELLED','CHECKED_IN'])->default('PENDING')->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('reservations'); }
};
```

```php
// laravel/database/migrations/2025_09_10_000002_create_passengers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('document')->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('passengers'); }
};
```

```php
// laravel/database/migrations/2025_09_10_000003_create_notifications_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->json('payload');
            $table->timestamps();
            $table->index(['type','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('notifications'); }
};
```

**Índices & EXPLAIN**

* Índices sugeridos ya incluidos: `status`, `departure_time`, `flight_number`, y `document` en passengers.
* Consultas típicas: `SELECT * FROM reservations WHERE status='CONFIRMED' AND departure_time BETWEEN ...`  → `EXPLAIN` mostrará uso de índices compuestos (status + departure\_time). Para más selectividad, considerar índice compuesto `(status, departure_time)` si la carga crece.

---

## 6) Repositorios (Interface + Eloquent)

```php
// laravel/app/Repositories/Contracts/ReservationRepositoryInterface.php
<?php
namespace App\Repositories\Contracts;

use App\Domain\Entities\Reservation;

interface ReservationRepositoryInterface {
    public function createWithPassengers(array $reservationData, array $passengers): Reservation;
    public function find(int $id): ?Reservation;
    public function updateStatus(Reservation $reservation, string $status): Reservation;
    public function list(array $filters): iterable;
}
```

```php
// laravel/app/Repositories/Eloquent/ReservationRepository.php
<?php
namespace App\Repositories\Eloquent;

use App\Domain\Entities\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;

class ReservationRepository implements ReservationRepositoryInterface {
    public function createWithPassengers(array $data, array $passengers): Reservation {
        $reservation = Reservation::create($data);
        $reservation->passengers()->createMany($passengers);
        return $reservation->load('passengers');
    }
    public function find(int $id): ?Reservation { return Reservation::with('passengers')->find($id); }
    public function updateStatus(Reservation $r, string $status): Reservation { $r->update(['status'=>$status]); return $r; }
    public function list(array $filters): iterable {
        return Reservation::with('passengers')
            ->when($filters['status'] ?? null, fn($q,$s)=>$q->where('status',$s))
            ->when($filters['from'] ?? null, fn($q,$f)=>$q->where('departure_time','>=',$f))
            ->when($filters['to'] ?? null, fn($q,$t)=>$q->where('departure_time','<=',$t))
            ->orderByDesc('created_at')
            ->paginate(20);
    }
}
```

```php
// laravel/app/Repositories/Contracts/PassengerRepositoryInterface.php
<?php
namespace App\Repositories\Contracts;

use App\Domain\Entities\Passenger;

interface PassengerRepositoryInterface {
    public function find(int $id): ?Passenger;
}
```

```php
// laravel/app/Repositories/Eloquent/PassengerRepository.php
<?php
namespace App\Repositories\Eloquent;

use App\Domain\Entities\Passenger;
use App\Repositories\Contracts\PassengerRepositoryInterface;

class PassengerRepository implements PassengerRepositoryInterface {
    public function find(int $id): ?Passenger { return Passenger::find($id); }
}
```

---

## 7) Servicios (Aplicación) y Publicador (Pub/Sub)

```php
// laravel/app/Services/EventPublisher.php
<?php
namespace App\Services;

use Illuminate\Support\Facades\Redis;

class EventPublisher {
    public function __construct(private string $channel = '') {
        $this->channel = $this->channel ?: config('database.redis.options.prefix','').env('EVENT_CHANNEL','skylink:events');
    }
    public function publish(string $event, array $data): void {
        Redis::publish($this->channel, json_encode(['event'=>$event,'data'=>$data]));
    }
}
```

```php
// laravel/app/Services/ReservationService.php
<?php
namespace App\Services;

use App\Domain\Entities\Reservation;
use App\Repositories\Contracts\ReservationRepositoryInterface;

class ReservationService {
    public function __construct(
        private ReservationRepositoryInterface $reservations,
        private EventPublisher $publisher
    ) {}

    public function create(array $payload): Reservation {
        $reservation = $this->reservations->createWithPassengers([
            'flight_number'=>$payload['flight_number'],
            'departure_time'=>$payload['departure_time'],
            'status'=>'PENDING'
        ], $payload['passengers'] ?? []);

        $this->publisher->publish('reservation.created', $reservation->toArray());
        return $reservation;
    }

    public function updateStatus(int $id, string $status): Reservation {
        $reservation = $this->reservations->find($id);
        abort_unless($reservation, 404, 'Reservation not found');
        $reservation = $this->reservations->updateStatus($reservation, $status);
        $this->publisher->publish('reservation.updated', $reservation->load('passengers')->toArray());
        return $reservation;
    }
}
```

---

## 8) Controladores + Requests

```php
// laravel/app/Http/Requests/StoreReservationRequest.php
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
```

```php
// laravel/app/Http/Requests/UpdateReservationStatusRequest.php
<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationStatusRequest extends FormRequest {
    public function rules(): array {
        return ['status'=>['required','in:PENDING,CONFIRMED,CANCELLED,CHECKED_IN']];
    }
}
```

```php
// laravel/app/Http/Controllers/ReservationController.php
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
```

```php
// laravel/app/Http/Controllers/PassengerController.php
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
```

```php
// laravel/routes/api.php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PassengerController;

Route::post('/reservations', [ReservationController::class,'store']);
Route::patch('/reservations/{id}/status', [ReservationController::class,'updateStatus']);
Route::get('/reservations', [ReservationController::class,'index']);

Route::get('/passengers/{id}', [PassengerController::class,'show']);
```

```php
// laravel/app/Providers/AppServiceProvider.php (bind repos)
<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\{ReservationRepositoryInterface, PassengerRepositoryInterface};
use App\Repositories\Eloquent\{ReservationRepository, PassengerRepository};

class AppServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->bind(ReservationRepositoryInterface::class, ReservationRepository::class);
        $this->app->bind(PassengerRepositoryInterface::class, PassengerRepository::class);
    }
    public function boot(): void {}
}
```

---

## 9) Comando Artisan – simulación de eventos

```php
// laravel/app/Console/Commands/SimulateReservationEvents.php
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
```

---

## 10) Node – package.json y servidor Socket.io

```json
// node-realtime/package.json
{
  "name": "skylink-realtime",
  "type": "module",
  "scripts": {
    "start": "node src/server.js",
    "dev": "node --watch src/server.js",
    "test": "node --test"
  },
  "dependencies": {
    "express": "^4.19.2",
    "ioredis": "^5.4.1",
    "socket.io": "^4.7.5",
    "ws": "^8.18.0"
  }
}
```

```js
// node-realtime/src/server.js
import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import { createSubscriber } from './subscriber.js';

const app = express();
const httpServer = createServer(app);
const io = new Server(httpServer, { cors: { origin: '*' } });

const PORT = process.env.SOCKET_PORT || 4000;
const CHANNEL = process.env.EVENT_CHANNEL || 'skylink:events';

app.get('/health', (_, res) => res.json({ ok: true }));

io.on('connection', (socket) => {
  console.log('WS client connected', socket.id);
});

// Redis Subscriber → broadcast a todos los clientes
const sub = createSubscriber({
  host: process.env.REDIS_HOST || '127.0.0.1',
  port: Number(process.env.REDIS_PORT || 6379),
});

await sub.subscribe(CHANNEL, (message) => {
  try {
    const payload = JSON.parse(message);
    io.emit(payload.event, payload.data);
    console.log('Broadcasted:', payload.event);
  } catch (e) { console.error('Invalid message', e); }
});

httpServer.listen(PORT, () => console.log(`Realtime on :${PORT}`));
```

```js
// node-realtime/src/subscriber.js
import Redis from 'ioredis';

export function createSubscriber({ host, port }) {
  const sub = new Redis({ host, port, maxRetriesPerRequest: null });
  return sub;
}
```

```js
// node-realtime/src/ws-client.js (dashboard simulado en consola)
import { io } from 'socket.io-client';

const url = process.env.SOCKET_URL || 'ws://localhost:4000';
const socket = io(url, { transports: ['websocket'] });

['reservation.created','reservation.updated'].forEach(evt => {
  socket.on(evt, (data) => {
    console.log(JSON.stringify({ event: evt, data }, null, 2));
  });
});
```

---

## 11) Tests (mínimos)

```php
// laravel/tests/Feature/ReservationFlowTest.php
<?php
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use function Pest\Laravel\patchJson;
use App\Domain\Entities\Reservation;

uses(RefreshDatabase::class);

it('creates reservation and updates status', function(){
    $create = postJson('/api/reservations', [
        'flight_number'=>'AR1234',
        'departure_time'=>now()->addDay()->toDateTimeString(),
        'passengers'=>[
            ['first_name'=>'Ana','last_name'=>'Paz','document'=>'DNI1'],
            ['first_name'=>'Luis','last_name'=>'Diaz','document'=>'DNI2']
        ]
    ])->assertCreated()->json();

    $id = $create['id'];

    patchJson("/api/reservations/{$id}/status", ['status'=>'CONFIRMED'])
        ->assertOk()
        ->assertJsonPath('status','CONFIRMED');

    expect(Reservation::find($id)->status)->toBe('CONFIRMED');
});
```

```js
// node-realtime/test/server.test.js (Node built-in test runner)
import test from 'node:test';
import assert from 'node:assert/strict';
import { io as Client } from 'socket.io-client';
import { spawn } from 'node:child_process';

// smoke test: el server arranca y emite un evento manual

test('server boots and emits', async (t) => {
  const proc = spawn('node', ['src/server.js'], { cwd: new URL('..', import.meta.url) });

  await new Promise((res) => setTimeout(res, 1200));

  const socket = Client('ws://localhost:4000', { transports: ['websocket'] });
  let received = false;

  socket.on('reservation.updated', (data) => {
    received = true;
    assert.ok(data);
  });

  // Simula publish a Redis (requiere redis corriendo)
  const { default: Redis } = await import('ioredis');
  const pub = new Redis();
  await pub.publish('skylink:events', JSON.stringify({ event:'reservation.updated', data:{ id: 1, status:'CONFIRMED' }}));
  await new Promise((res)=>setTimeout(res, 800));

  assert.equal(received, true);
  socket.close();
  proc.kill('SIGINT');
});
```

---

## 12) OpenAPI (para IA + Copilot)

```yaml
# openapi.yaml
openapi: 3.0.3
info:
  title: SkyLink API
  version: 1.0.0
servers:
  - url: http://localhost:8000/api
paths:
  /reservations:
    post:
      summary: Crea una reserva con pasajeros
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [flight_number, departure_time, passengers]
              properties:
                flight_number: { type: string }
                departure_time: { type: string, format: date-time }
                passengers:
                  type: array
                  items:
                    type: object
                    properties:
                      first_name: { type: string }
                      last_name: { type: string }
                      document: { type: string }
      responses:
        '201': { description: Creada }
    get:
      summary: Lista reservas
      parameters:
        - in: query
          name: status
          schema: { type: string, enum: [PENDING,CONFIRMED,CANCELLED,CHECKED_IN] }
        - in: query
          name: from
          schema: { type: string, format: date-time }
        - in: query
          name: to
          schema: { type: string, format: date-time }
      responses:
        '200': { description: OK }
  /reservations/{id}/status:
    patch:
      summary: Cambia el estado de una reserva
      parameters:
        - in: path
          name: id
          required: true
          schema: { type: integer }
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [status]
              properties:
                status:
                  type: string
                  enum: [PENDING,CONFIRMED,CANCELLED,CHECKED_IN]
      responses:
        '200': { description: OK }
  /passengers/{id}:
    get:
      summary: Detalle de pasajero
      parameters:
        - in: path
          name: id
          required: true
          schema: { type: integer }
      responses:
        '200': { description: OK }
```

---

## 13) README (cómo correr + decisiones)

````md
# SkyLink – Orchestrator

## Correr rápido (sin Docker)
1. **MySQL** en localhost con DB `skylink` y user/pass `skylink`.
2. **Redis** en localhost (puerto 6379).
3. **Laravel**
   ```bash
   cd laravel
   cp .env.example .env
   composer install
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve # http://localhost:8000
````

4. **Node realtime**

   ```bash
   cd ../node-realtime
   npm i
   npm run start # WS en ws://localhost:4000
   ```
5. **Cliente WS (dashboard simulado)**

   ```bash
   node src/ws-client.js
   ```
6. **Simular eventos externos**

   ```bash
   php laravel/artisan skylink:simulate --interval=5
   ```

## Endpoints

* POST `/api/reservations`
* PATCH `/api/reservations/{id}/status`
* GET `/api/reservations?status=CONFIRMED&from=...&to=...`
* GET `/api/passengers/{id}`

## Arquitectura y patrones

* **Clean Architecture / Capas**: Controladores → Servicios (aplicación) → Repositorios (infra) → Entidades (dominio).
* **SOLID**: Interfaces para repositorios, inyección de dependencias, SRP en servicios.
* **Patrones**: **Pub/Sub** (Redis) entre Laravel y Node; opcional Observer interno en Laravel para auditar.
* **Escalabilidad**: Node sin estado; Redis como bus; horizontalmente escalable.
* **IA obligatoria**: Copilot/ChatGPT para: generar tests iniciales, optimizar queries (sugerencias de índices en migrations), y derivar `openapi.yaml` a partir de controladores.

## ERD (mini)

Reservation (1) — (N) Passenger

Reservation: id, flight\_number, departure\_time, status, timestamps
Passenger: id, reservation\_id (FK), first\_name, last\_name, document
Notification: id, type, payload (json), timestamps

## SQL y EXPLAIN

Consultas críticas usan índices: `status`, `departure_time`, `flight_number`. Si la cardinalidad crece, agregar índice compuesto `(status, departure_time)`.

## Tests

* **Laravel**: `php artisan test` (Feature/ReservationFlowTest).
* **Node**: `npm test` arranca server, publica en Redis y valida recepción via WS.

````

---

## 14) Tips para VS Code + Copilot
- Abre el repo y pide a Copilot: *“Generate PHPUnit tests for edge cases on ReservationService (invalid status, past departure_time).”*
- Pide: *“Create SQL EXPLAIN samples and comment on index selectivity for filters by date range.”*
- Usa *Copilot Chat* con `openapi.yaml` para generar colecciones de pruebas (curl o Thunder Client).

---

## 15) Curl de ejemplo
```bash
# Crear reserva
curl -X POST http://localhost:8000/api/reservations \
  -H 'Content-Type: application/json' \
  -d '{
    "flight_number":"AR1234",
    "departure_time":"2025-09-12T10:00:00",
    "passengers":[{"first_name":"Ana","last_name":"Paz","document":"DNI1"}]
  }'

# Cambiar estado
curl -X PATCH http://localhost:8000/api/reservations/1/status \
  -H 'Content-Type: application/json' \
  -d '{"status":"CONFIRMED"}'
````

---

## 16) Justificación técnica breve

* **Laravel 10** por ecosistema maduro, validación, testing y facilidad para CRUD + comandos Artisan.
* **Node + Socket.io** para websockets performantes y simplicidad de clientes.
* **Redis Pub/Sub** desacopla back (origen de eventos) del canal realtime.
* **Clean + SOLID** favorece mantenibilidad y pruebas unitarias.
* **OpenAPI** facilita generación automática de clientes y pruebas vía IA.
