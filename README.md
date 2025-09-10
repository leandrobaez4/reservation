# SkyLink Orchestrator

Proyecto fullstack para gestión de reservas y notificaciones en tiempo real.

## Stack
- **Laravel 10** (API REST, dominio, pub/sub Redis)
- **Node.js + Socket.io** (realtime, dashboard simulado)
- **MySQL** (persistencia)
- **Redis** (event bus)

## Instalación rápida

### 1. Backend Laravel
```bash
cd laravel
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve # http://localhost:8000
```

### 2. Node Realtime
```bash
cd ../node-realtime
npm install
npm run start # WS en ws://localhost:4000
```

### 3. Dashboard simulado
```bash
node src/ws-client.js
```

### 4. Simular eventos externos
```bash
php laravel/artisan skylink:simulate --interval=5
```


## Endpoints principales
- POST `/api/reservations` (crear reserva)
- PATCH `/api/reservations/{id}/status` (cambiar estado)
- GET `/api/reservations?status=CONFIRMED&from=...&to=...` (listar)
- GET `/api/passengers/{id}` (detalle pasajero)

### Ejemplos curl
```bash
# Crear reserva
curl -X POST http://localhost:8000/api/reservations \
	-H 'Content-Type: application/json' \
	-d '{"flight_number":"AR1234","departure_time":"2025-09-12T10:00:00","passengers":[{"first_name":"Ana","last_name":"Paz","document":"DNI1"}]}'

# Cambiar estado
curl -X PATCH http://localhost:8000/api/reservations/1/status \
	-H 'Content-Type: application/json' \
	-d '{"status":"CONFIRMED"}'
```


## Diagrama ERD

Reservation (1) — (N) Passenger

Reservation: id, flight_number, departure_time, status, timestamps
Passenger: id, reservation_id (FK), first_name, last_name, document
Notification: id, type, payload (json), timestamps

## OpenAPI

El proyecto incluye especificación OpenAPI en el archivo `openapi.yaml`.
Puedes usarlo para generar clientes, documentación interactiva o pruebas automáticas.

Ubicación: `/openapi.yaml`

## Arquitectura
- **Clean Architecture**: Controladores → Servicios → Repositorios → Entidades
- **Pub/Sub**: Redis conecta Laravel y Node para eventos en tiempo real
- **SOLID**: Interfaces, inyección de dependencias, SRP

## Notificaciones
- Cada reserva creada o actualizada genera un evento en Redis y una notificación en la base de datos.
- El dashboard simulado muestra los eventos en tiempo real.

## Tests
- Laravel: `php artisan test`
- Node: `npm test`

## Requisitos
- PHP >=8.1
- Node.js >=18
- MySQL
- Redis

## Autor
leandrobaez4
