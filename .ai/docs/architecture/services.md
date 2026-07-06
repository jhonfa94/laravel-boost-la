# Services

Lógica reutilizada desde varios contextos. Un Service NO es el cajón
de sastre de siempre: se extrae cuando la reutilización **existe**,
no antes.

## Cuándo (y cuándo no)

| Situación | Usa |
|---|---|
| Lógica de escritura de un caso de uso | Action (siempre) |
| Lógica compleja compartida por varias Actions | Service inyectado en ellas |
| Lectura/filtrado consumido desde varios contextos (web + API + export) | Service |
| ¿Dudas entre Action y Service? | Action |

El caso canónico: un servicio de filtrado que comparten el ViewModel
del listado web y el endpoint API del mismo dominio. Mientras solo
existía el listado web, el Pipeline vivía en el ViewModel; al aparecer
el segundo consumidor, se extrae — ese es el momento, ni uno antes.

## Reglas

- Ubicación: `app/Services/{Dominio}/` (o `app/Services/` si es transversal).
- `final readonly class`, sufijo `Service` (hay arch test).
- Dependencias por constructor, nunca Facades. Sin estado mutable.
- Un Service = una responsabilidad. Retorna tipos claros (Builder,
  colección, DTO…), **nunca** una Response.

## Ejemplo: servicio de filtrado compartido

```php
<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\Filters\OrderFilters;
use App\Filters\Filter;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;

final readonly class FilterOrdersService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Order>
     */
    public function apply(array $filters): Builder
    {
        $pipes = collect($filters)
            ->map(fn (mixed $value, string $key): Filter => OrderFilters::from($key)->create($value))
            ->values()
            ->all();

        return app(Pipeline::class)
            ->send(Order::query())
            ->through($pipes)
            ->thenReturn();
    }
}
```

Los ViewModels (web y API) lo inyectan por constructor y cada uno
decide su salida: paginar para Blade, Resources para la API. El
filtrado se escribe y se testea UNA vez.

## ❌ Incorrecto

```php
// Service prematuro: un solo consumidor → esa lógica era una Action
// o vivía en el ViewModel de listado

// Cajón de sastre
final readonly class OrderService
{
    public function create(...) {}   // esto es una Action
    public function filter(...) {}
    public function export(...) {}   // responsabilidades mezcladas
}

// Service con Facades o que retorna una Response
```

## Verificación

`composer test:arch`: todo `App\Services` es final, readonly y con
sufijo `Service`.
