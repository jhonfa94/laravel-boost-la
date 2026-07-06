# Filtros escalables: FilterValue + Filter + Enums + Pipeline

Sistema de filtrado componible sobre el query builder de Eloquent,
aplicado con `Pipeline` **en la capa apropiada**: normalmente el
ViewModel de listado (lectura) o un servicio si el filtrado se
reutiliza desde varios contextos.

## Cuándo usar filtros (detecta el caso)

**No siempre son necesarios** — hay que detectar los casos. Señales
de que sí:

- Un listado con filtrado variable desde el cliente (búsqueda,
  estados, rangos, tags…).
- Condiciones de filtrado que se repiten o crecen con el tiempo.
- Un `when()` que ya se encadena 3+ veces en un query.

Un listado fijo sin parámetros no necesita esta arquitectura: un
scope del Builder basta. Pero cuando el filtrado es variable y crece,
este sistema escala sin tocar lo ya escrito.

## Estructura

```
app/Filters/
├── FilterValue.php          ← value object con getters tipados
├── Filter.php               ← clase abstracta: handle(Builder, Closure)
├── SorterFilter.php         ← ordenación con lista blanca
├── Shared/                  ← filtros reutilizables entre dominios
└── {Dominio}/               ← filtros del dominio
app/Enums/Filters/
└── {Dominio}Filters.php     ← enum factory del dominio
```

## La pieza clave: composición por dominio

Un dominio agrupa TODOS sus filtros, de los más simples a los más
complejos, y se crean **múltiples filtros reutilizando los que ya
existen**. Ejemplo conceptual: un dominio de artículos puede tener un
filtro sencillo solo de texto Y OTRO complejo (título + categoría +
tags + fechas) conviviendo en el mismo dominio; el complejo compone
filtros compartidos (`Shared/`) y específicos. El cliente elige qué
claves envía; el enum del dominio resuelve qué filtros se montan.

## Capa 1: FilterValue — value object tipado

```php
<?php

declare(strict_types=1);

namespace App\Filters;

final class FilterValue
{
    public function __construct(private readonly mixed $value) {}

    public function isEmpty(): bool
    {
        if (is_array($this->value)) {
            return array_filter($this->value, fn ($item): bool => ! empty($item)) === [];
        }

        return empty($this->value);
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getStringValue(): string
    {
        return (string) ($this->value ?? '');
    }

    public function getBooleanValue(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
    }

    // Getters análogos según necesidad del proyecto:
    // getRangeValue(): array{min, max}
    // getDateRangeValue(): array{from: ?Carbon, to: ?Carbon}
    // getDateValue(): ?Carbon · getSorterValue(): array{column, direction}
    // getArrayValue(): array
}
```

## Capa 2: Filter — contrato único

```php
<?php

declare(strict_types=1);

namespace App\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class Filter
{
    public function __construct(protected readonly FilterValue $filter) {}

    /**
     * @param  Builder<Model>  $items
     * @return Builder<Model>
     */
    abstract public function handle(Builder $items, Closure $next): Builder;
}
```

Cada filtro concreto: una condición, early return si el valor está
vacío (`return $next($items)`), y nada más. Es lo que los hace
componibles.

**Tip de contravarianza**: `handle()` NO puede estrecharse al Builder
del dominio (`OrderBuilder`) — PHP prohíbe que un hijo exija un
parámetro más específico que su padre. Los filtros escriben sus
condiciones directamente sobre el builder (`where`, `whereDate`,
`when`). Si la misma condición existe como scope del Builder,
conviven: el scope sirve al resto de la app, la condición del filtro
al pipeline — duplicación mínima, asumida y testeada por separado.

## Capa 3: Enum como factory del dominio

El enum del dominio declara qué claves de filtrado existen y monta el
filtro de cada una con `match` + `new FilterValue($value)`. Los casos
del enum son el contrato público del filtrado de ese dominio.

```php
public function create(mixed $value): Filter
{
    return match ($this) {
        self::Query => new QueryFilter(new FilterValue($value)),
        // un caso por clave de filtrado del dominio,
        // reutilizando filtros Shared/ donde aplique
    };
}
```

## Capa 4: Pipeline sobre el builder, en la capa apropiada

```php
$filters = collect($validatedFilters)
    ->map(fn ($value, $key) => ArticleFilters::from($key)->create($value))
    ->toArray();

$query = app(Pipeline::class)
    ->send(Article::query())
    ->through($filters)
    ->thenReturn();

assert($query instanceof ArticleBuilder);

return $query->recentFirst()->paginate(15)->withQueryString();
```

El `assert` recupera el Builder tipado tras el Pipeline
(`thenReturn()` devuelve `mixed`): PHPStan vuelve a conocer los scopes
del dominio y puedes encadenar con el nivel máximo en verde.

¿Dónde vive esto? **En la capa apropiada al caso**:

- **ViewModel de listado** — el caso más habitual: el ViewModel
  recibe los filtros validados, aplica el pipeline sobre el builder y
  pagina.
- **Servicio de filtrado** — cuando el mismo filtrado se consume
  desde varios contextos (web + API + export, por ejemplo).

## Ordenación: SorterFilter

Abstracto en `app/Filters/SorterFilter.php`, específicos por dominio.
Lista blanca de columnas ordenables (nunca `order_by` libre del
request), dirección validada, default explícito.

## Reglas

- Un filtro = una condición. Componer, no condicionar.
- Filtro vacío → `return $next($items)` sin tocar el query.
- Compartidos en `Shared/`; específicos en la carpeta de su dominio.
- Los valores llegan del FormRequest validado, no de `$request->all()`.
- El listado que filtra siempre pagina.

## ❌ Incorrecto

```php
// Controller con 15 ->when() encadenados, sin paginar
$articles = Article::query()
    ->when($request->q, fn ($q) => $q->where(...))
    ->when($request->category, fn ($q) => $q->where(...))
    // ... x10, imposible de testear por separado
    ->get();

// Crear esta arquitectura para un listado fijo sin parámetros:
// ahí basta un scope del Builder
```
