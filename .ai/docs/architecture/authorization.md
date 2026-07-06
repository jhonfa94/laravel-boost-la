# Policies: autorización en la frontera HTTP

Toda decisión de "¿puede este usuario hacer esto con este recurso
AHORA?" vive en una Policy. Una sola pieza responde con un `bool`, el
framework la convierte en 403 uniforme, y la matriz completa de
permisos queda testeable. Nada de `if` de permisos repetidos en
controllers, FormRequests o vistas.

## Reglas

- Ubicación: `app/Policies/{Model}Policy.php`.
- **Auto-descubrimiento**: Laravel resuelve `App\Models\Order` ↔
  `App\Policies\OrderPolicy` por convención de nombres. Nada que
  registrar en providers; si el nombre no sigue la convención, la
  Policy no existe para el framework.
- Clase `final` con **sufijo `Policy`**. Hay arch test.
- Una ability = un método público que retorna `bool`. Las abilities
  sin instancia (p. ej. `create`) reciben solo el `User`.
- Las abilities combinan **rol, propiedad y estado** en una sola
  respuesta, SIEMPRE consumiendo los helpers de dominio de los
  modelos (`$user->isAdmin()`, `$order->isOwnedBy($user)`,
  `$order->canBeCancelled()`…). La Policy NUNCA compara enums ni
  columnas a mano: los helpers del modelo son la única fuente de
  verdad y la ability se lee como la regla de negocio que implementa.
- Autorizar y acotar son problemas distintos: una Policy decide sobre
  UN recurso. El scoping de listados (qué subconjunto ve cada rol)
  vive en la query del ViewModel, no en un `viewAny`.

## Ejemplo

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

final class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->isOwnedBy($user);
    }

    public function update(User $user, Order $order): bool
    {
        return $order->isOwnedBy($user) && $order->isEditable();
    }

    public function cancel(User $user, Order $order): bool
    {
        return $order->isOwnedBy($user) && $order->canBeCancelled();
    }
}
```

## Cómo se conecta

Una regla por tipo de endpoint, sin duplicar comprobaciones:

| Contexto | Mecanismo |
|---|---|
| Ruta sin payload (show, transiciones, formulario create) | middleware `can` en la ruta: `->can('cancel', 'order')` / `->can('create', Order::class)`. El route model binding resuelve el modelo y el middleware corta con 403 antes de entrar al controller. |
| Endpoint con payload (store, update) | `authorize()` del FormRequest: `$this->user()->can('update', $this->route('order'))`. Autorización y validación viajan juntas en la pieza que ya intercepta la petición. |
| Blade | `@can('cancel', $order)` alrededor de botones y formularios. La MISMA Policy decide UI y HTTP; imposible que diverjan. |

Cada comprobación vive en UN sitio: si la ruta ya lleva `can`, ni el
controller ni el FormRequest la repiten.

## Decisión de frontera

La Policy garantiza las precondiciones (quién y en qué estado) en la
frontera HTTP. Las Actions asumen ese contrato cumplido y quedan
puras y reutilizables desde comandos, jobs o tests. Guardas de
dominio dentro de la pieza solo cuando esta se invoque TAMBIÉN fuera
de HTTP (un comando de consola, un job) — ese día, ni uno antes. La
lógica de negocio interna (p. ej. una transición automática de estado
al actuar) no es autorización: vive en la Action.

## Verificación y testing

Dos niveles complementarios — correctitud y conexión:

1. **Unit de la matriz** — `tests/Unit/Policies/{Model}PolicyTest.php`
   con datasets Pest: para cada ability, las combinaciones rol ×
   propiedad × estado con su resultado esperado. Es la especificación
   ejecutable de la tabla de permisos; instancia la Policy
   directamente, sin HTTP.
2. **Feature HTTP** — cada endpoint cubre happy path + las negaciones
   relevantes con `assertForbidden()`. Esto verifica que la Policy
   está **conectada** (middleware `can` / `authorize()`),
   no solo que es correcta. Una Policy perfecta sin conectar autoriza
   todo.

`composer test:arch` verifica: final + sufijo `Policy`.

## ❌ Incorrecto

```php
// Comparar enums/columnas a mano: duplica la fuente de verdad del modelo
public function cancel(User $user, Order $order): bool
{
    return $user->role === UserRole::Admin
        && $order->status === OrderStatus::Pending; // usa los helpers
}

// Autorización manual repetida en el controller: 403 inconsistente
public function __invoke(Request $request, Order $order)
{
    if (! $request->user()->isAdmin()) {
        abort(403);
    }
}

// viewAny para "autorizar" un listado: el scoping va en el ViewModel
public function viewAny(User $user): bool
```
