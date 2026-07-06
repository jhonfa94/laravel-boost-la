# Models organizados: Base / Builders / Concerns / Final

Los modelos NO acumulan cientos de líneas mezclando configuración,
relaciones, scopes y lógica. Se organizan en cuatro capas con
ubicaciones fijas. Para un modelo recién nacido y pequeño basta la
capa final; en cuanto crece, se extraen las capas en este orden:
**Concerns → Base → Builder → Final limpio**.

## Capa 1: Concerns — atributos y relaciones

`app/Models/Concerns/{Model}/HasAttributes.php` (solo accessors) y
`HasRelations.php` (solo relaciones):

```php
<?php

declare(strict_types=1);

namespace App\Models\Concerns\Order;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasRelations
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

## Capa 2: Base — configuración centralizada

`app/Models/Base/{Model}.php`, abstracta. Toda la configuración de
Eloquent visible de un vistazo:

```php
<?php

declare(strict_types=1);

namespace App\Models\Base;

use App\Models\Concerns\Order\HasAttributes;
use App\Models\Concerns\Order\HasRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property numeric-string $total
 * @property string $currency
 * @property string|null $notes
 * @property Carbon|null $shipped_at
 */
abstract class Order extends Model
{
    use HasAttributes;
    use HasRelations;

    protected $fillable = [
        'user_id', 'status', 'total', 'currency', 'notes', 'shipped_at',
    ];

    protected $hidden = [
        'billing_address',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'shipped_at' => 'datetime',
        ];
    }
}
```

**Tip Larastan**: los casts NO se infieren a través de la Base
abstracta — sin el bloque `@property`, PHPStan nivel 8 no sabe que
`$order->total` existe ni de qué tipo es. Documenta las columnas como
`@property` en la Base (y de regalo: autocompletado en el IDE).

## Capa 3: Builder — scopes con autocompletado

`app/Models/Builders/{Model}Builder.php` extiende `Builder`; cada
scope es un método tipado que retorna `self`:

```php
<?php

declare(strict_types=1);

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

final class OrderBuilder extends Builder
{
    public function pending(): self
    {
        return $this->where('status', 'pending');
    }

    public function forUser(int $userId): self
    {
        return $this->where('user_id', $userId);
    }

    public function recentFirst(): self
    {
        return $this->orderBy('created_at', 'desc');
    }
}
```

## Capa 4: Final — coordinación y lógica del dominio

`app/Models/{Model}.php` extiende su Base, conecta su Builder con
`newEloquentBuilder()` y solo contiene lógica esencial del dominio:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Builders\OrderBuilder;

final class Order extends Base\Order
{
    public function newEloquentBuilder($query): OrderBuilder
    {
        return new OrderBuilder($query);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }
}
```

Uso: `Order::query()->pending()->forUser($id)->recentFirst()` — con
autocompletado completo del Builder.

## Reglas

- El modelo final y su Builder son `final`; la Base es `abstract`
  (hay arch tests para ambos).
- Asignación masiva explícita siempre — atributo `#[Fillable([...])]`
  (forma moderna) o propiedad `$fillable`; nunca `$guarded = []`.
- `casts()` como método (Laravel 11+), no propiedad.
- Relaciones y accessors SIEMPRE con return types.
- Accessors que NO disparen queries (N+1 disfrazado).
- Eager loading (`with()`) en cualquier relación que se lea en listados.
- Las escrituras orquestadas viven en Actions, no en el modelo.

## Migraciones

- `down()` SIEMPRE implementado y simétrico.
- Nada destructivo (drop/truncate/change con pérdida) sin aprobación explícita.
- `foreignId()->constrained()` para FKs; índices en columnas de WHERE/ORDER BY/JOIN.
- Cada modelo nace con su factory y su migración.

## ❌ Incorrecto

```php
class Order extends Model
{
    protected $guarded = [];            // mass assignment abierto

    public function getTotalAttribute()  // accessor con query: N+1
    {
        return $this->items()->sum('price');
    }

    // + 300 líneas de scopes, relaciones y helpers mezclados
}
```
