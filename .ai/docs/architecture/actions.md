# Actions

Toda la lógica de negocio de escritura vive en Actions. Ni en
controladores, ni en modelos, ni en helpers sueltos.

## Reglas

- Ubicación: `app/Actions/{Dominio}/`.
- **Sufijo `Action`** (simetría con `ViewModel`): verbo + sustantivo +
  sufijo → `CreateOrderAction`, `RegisterClickAction`,
  `ExpireShortUrlAction`. Hay arch test.
- Clase `final readonly` SIEMPRE — tenga o no dependencias,
  criterio único (hay arch test) — con un único punto de entrada:
  `__invoke()`.
- **Principio de datos puros**: recibe un DTO tipado (o primitivos),
  NUNCA el Request. Así la Action se reutiliza desde HTTP, comandos,
  jobs o tests sin montar nada.
- Updates y transiciones de estado reciben **modelo + DTO**:
  `__invoke(Order $order, UpdateOrderData $data)`. Si no hay payload
  (marcar pagado, archivar…), solo el modelo. El principio se
  mantiene: nunca el Request.
- Retorna modelo, colección o primitivo. **Nunca** una Response.
- Una Action = una responsabilidad.
- Dependencias por constructor (inyección), nunca Facades.

## Action simple

```php
<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\DataTransferObjects\Order\CreateOrderData;
use App\Models\Order;

final readonly class CreateOrderAction
{
    public function __invoke(CreateOrderData $data): Order
    {
        return Order::create([
            'user_id' => $data->userId,
            'total' => $data->total,
            'status' => 'pending',
        ]);
    }
}
```

## Actions compuestas

Cuando el caso de uso agrupa pasos, una Action compuesta **inyecta y
orquesta** Actions simples. La compuesta coordina; las simples
ejecutan. No dupliques lógica entre ellas:

```php
<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\DataTransferObjects\User\RegisterUserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class RegisterUserAction
{
    public function __construct(
        private CreateUserAction $createUser,
        private AssignDefaultRoleAction $assignDefaultRole,
        private SendWelcomeNotificationAction $sendWelcomeNotification,
    ) {}

    public function __invoke(RegisterUserData $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = ($this->createUser)($data->toCreateUserData());
            ($this->assignDefaultRole)($user);

            return $user;
        });

        ($this->sendWelcomeNotification)($user);

        return $user;
    }
}
```

## Errores y transacciones

- Si la Action agrupa varias escrituras, la transacción va DENTRO de
  la Action (`DB::transaction`).
- **Separa lo transaccional de los efectos colaterales**: BD dentro
  de la transacción; emails, notificaciones y llamadas externas
  DESPUÉS de commitear (o a cola). Un fallo de email no debe hacer
  rollback de un pedido, ni un rollback debe dejar un email enviado.
- Excepciones de dominio específicas cuando el caso lo pida; nunca
  `try/catch` vacíos.

## Criterios de decisión

| Situación | Usa |
|---|---|
| Una escritura clara | Action simple |
| Caso de uso con varios pasos | Action compuesta que orquesta simples |
| Lógica compleja reutilizada por varias Actions | Service inyectado en las Actions (ver .ai/docs/architecture/services.md) |
| ¿Dudas entre Action y Service? | Action |

## ❌ Incorrecto

```php
// Lógica en el controlador
public function store(Request $request)
{
    $order = Order::create($request->all()); // mass assignment + sin DTO
    Mail::send(...);                          // efecto colateral suelto
}

// Action que recibe el Request: ya no es reutilizable fuera de HTTP
public function __invoke(Request $request): Order

// Sin sufijo: rompe la convención (y el arch test)
final class CreateOrder
```

## Verificación y testing

- `composer test:arch`: final, readonly, invocable, sufijo `Action`, sin `Illuminate\Http`.
- Tests en `tests/Unit/Actions/`: datos válidos, inválidos y límites.
  Las compuestas se testean con sus simples mockeadas o reales según
  el caso.
