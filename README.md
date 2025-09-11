## Swagger UI

Puedes visualizar y probar la API importando `openapi.yaml` en [Swagger Editor online](https://editor.swagger.io/) o localmente:

1. Instala Swagger UI:
	```bash
	npm install -g swagger-ui-watcher
	swagger-ui-watcher openapi.yaml
	```
2. Abre el navegador en `http://localhost:3200` para ver la documentación interactiva.
# SkyLink Orchestrator

Proyecto fullstack para gestión de reservas y notificaciones en tiempo real.

## Stack
- **Laravel 10** (API REST, dominio, eventos y listeners)
- **MySQL** (persistencia)

## Instalación rápida

### 1. Backend Laravel
```bash
cd laravel

# Copiar configuración
cp .env.example .env

# Instalar dependencias de PHP
composer install

# Configurar aplicación
php artisan key:generate

# Configurar base de datos y colas
# Editar .env: cambiar QUEUE_CONNECTION=sync por QUEUE_CONNECTION=database

# Ejecutar migraciones con datos de prueba
php artisan migrate --seed

# Iniciar servidor de desarrollo
php artisan serve # http://localhost:8000
```

### 2. Worker de Colas (para ver eventos)
En una terminal separada:
```bash
cd laravel
php artisan queue:work --verbose
```
Este comando procesa las colas y muestra los eventos de reservas en tiempo real.

### 4. Simular eventos externos
```bash
php laravel/artisan tinker
# Desde tinker puedes disparar eventos manualmente para pruebas
```


## Endpoints principales
- POST `/api/reservations` (crear reserva)
- PATCH `/api/reservations/{id}/status` (cambiar estado)
- GET `/api/reservations?status=CONFIRMED&from=...&to=...` (listar)
- GET `/api/passengers/{id}` (detalle pasajero)

### Ejemplo en Swagger UI (POST /api/reservations)


En Swagger, para agregar pasajeros y todos los campos posibles al crear una reserva, usa el siguiente ejemplo en el campo `requestBody`:

```json
{
	"flight_number": "AR2025",
	"departure_time": "2025-09-22T15:30:00",
	"status": "PENDING",
	"passengers": [
		{
			"first_name": "Juan",
			"last_name": "Martinez",
			"document": "DNI4"
		},
		{
			"first_name": "Sofia",
			"last_name": "Gomez",
			"document": "DNI5"
		},
		{
			"first_name": "Pedro",
			"last_name": "Alvarez",
			"document": "DNI6"
		}
	]
}
```

Puedes agregar también los campos opcionales según el esquema OpenAPI. Solo copia este JSON en el campo de ejemplo de Swagger UI para el endpoint POST `/api/reservations`.

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
- **Eventos y Listeners**: Sistema nativo de Laravel para manejo de eventos
- **SOLID**: Interfaces, inyección de dependencias, SRP

## Eventos y Colas

El sistema utiliza eventos de Laravel con listeners en cola para procesar acciones de reservas:

- **ReservationCreated**: Se dispara al crear una reserva
- **ReservationStatusChanged**: Se dispara al cambiar el estado de una reserva

### Ver eventos en tiempo real
1. Configura la cola en `.env`: `QUEUE_CONNECTION=database`
2. Ejecuta el worker: `php artisan queue:work --verbose`
3. Crea o actualiza reservas usando los endpoints
4. Observa los payloads de eventos en la terminal del worker

### Logs
Todos los eventos se registran en `storage/logs/laravel.log` con información detallada.

## Notificaciones
- Cada reserva creada o actualizada genera un evento y una notificación en la base de datos.
- Los eventos se procesan en cola para mejor rendimiento.

## Tests

### Ejecutar todos los tests
```bash
cd laravel
php artisan test
```

### Tests disponibles

**Feature Tests** (`tests/Feature/ReservationFlowTest.php`):
- ✅ Crear reserva con pasajeros y validar estructura JSON
- ✅ Actualizar estado de reserva
- ✅ Validaciones de campos requeridos
- ✅ Filtrado de reservas por estado
- ✅ Manejo de errores 404
- ✅ Verificación de eventos disparados

**Unit Tests**:
- ✅ `ReservationStatusTest`: Validación del enum ReservationStatus
- ✅ `ReservationServiceTest`: Lógica de negocio del servicio

### Ejecutar tests específicos
```bash
# Solo tests de feature
php artisan test --testsuite=Feature

# Solo tests unitarios
php artisan test --testsuite=Unit

# Test específico
php artisan test --filter=test_creates_reservation_with_passengers

# Con cobertura (requiere xdebug)
php artisan test --coverage
```

### Estructura de tests
```
tests/
├── Feature/
│   └── ReservationFlowTest.php    # Tests de endpoints completos
├── Unit/
│   ├── ReservationStatusTest.php  # Tests del enum
│   └── ReservationServiceTest.php # Tests del servicio
└── TestCase.php
```

## Requisitos
- PHP >=8.1
- MySQL

## Autor
leandrobaez4
