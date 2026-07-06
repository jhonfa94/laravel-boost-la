# Testing con Pest

## Convenciones

- Pest v4 siempre. Nada de clases PHPUnit nuevas.
- `it('creates an order for a user')` — descripción clara, en inglés,
  que se lee como una frase.
- Arrange-Act-Assert, separados por líneas en blanco.
- Factories SIEMPRE para datos. Nunca IDs hardcodeados, nunca
  datos insertados desde migraciones.
- Un archivo de test por clase/controller. Feature tests por dominio:
  `tests/Feature/Order/CreateOrderTest.php`.

## Qué testear en cada pieza

| Pieza | Test | Dónde |
|---|---|---|
| Endpoint HTTP | feature: happy path + validación + autorización | tests/Feature/{Dominio} |
| Action | unit/feature: datos válidos, inválidos, límites | tests/Unit/Actions |
| ViewModel | unit: estructura del toArray() | tests/Unit/ViewModels |
| Scope/cast con lógica | unit del modelo | tests/Unit/Models |
| Convenciones | arch tests | tests/Architecture |

## Assertions preferidas

`assertSuccessful()`, `assertCreated()`, `assertForbidden()`,
`assertNotFound()`, `assertInvalid(['field'])` — expresivas, no
`assertStatus(200)` genérico cuando existe la semántica.

## Ejemplo

```php
<?php

declare(strict_types=1);

use App\Models\User;

it('creates an order for an authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'total' => 99.50,
    ]);

    $response->assertCreated();
    expect($user->orders()->count())->toBe(1);
});
```

## Reglas duras

- Ejecuta PRIMERO solo el test afectado
  (`composer test:fast -- --filter=nombre`), la suite entera después.
- Nunca cambies una assertion para que un bug pase desapercibido.
- Nunca borres un test porque "molesta".
