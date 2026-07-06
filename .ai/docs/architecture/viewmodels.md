# ViewModels

Capa de presentación de lectura. Cada vista o respuesta tiene un
modelo dedicado que centraliza TODA la información que necesita,
fuera del controller y fuera de la vista.

## La clase base (app/ViewModels/ViewModel.php)

Los ViewModels NO implementan `Arrayable` uno a uno: **extienden la
clase base** `App\ViewModels\ViewModel`, que lo hace por ellos con
reflexión — cada método público se convierte automáticamente en una
clave camelCase del array:

```php
<?php

declare(strict_types=1);

namespace App\ViewModels;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * @implements Arrayable<string, mixed>
 */
abstract class ViewModel implements Arrayable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return collect((new ReflectionClass($this))->getMethods(ReflectionMethod::IS_PUBLIC))
            ->reject(fn (ReflectionMethod $method): bool => in_array($method->getName(), ['__construct', 'toArray'], true))
            ->mapWithKeys(fn (ReflectionMethod $method): array => [
                Str::camel($method->getName()) => $this->{$method->getName()}(),
            ])
            ->all();
    }
}
```

Cómo funciona: reflexión sobre los métodos públicos → excluye
`__construct` y `toArray` → ejecuta cada método → clave camelCase con
su resultado. Los métodos `private`/`protected` NO aparecen en el
array: son helpers internos del ViewModel.

El cierre con `->all()` (y no `->toArray()`) importa: conserva
intactos los valores que devuelve cada método — modelos, colecciones,
paginadores — para que lleguen vivos a Blade. `->toArray()` los
convertiría recursivamente en arrays y un `$order->total` en la vista
dejaría de funcionar.

## ViewModel concreto

```php
<?php

declare(strict_types=1);

namespace App\ViewModels\Dashboard;

use App\Models\Order;
use App\ViewModels\ViewModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

final class DashboardViewModel extends ViewModel
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function totalOrders(): int
    {
        return Order::query()->whereBelongsTo($this->request->user())->count();
    }

    public function recentOrders(): Collection
    {
        return Order::query()
            ->whereBelongsTo($this->request->user())
            ->latest()
            ->limit(5)
            ->get();
    }
}
```

Se resuelven por el contenedor: inyección en el método del controller
(`public function __invoke(DashboardViewModel $viewModel)`) o
`app(DashboardViewModel::class)`. Pueden recibir el `Request`
inyectado (filtros, paginación, usuario autenticado).

Cuando el ViewModel necesita el **modelo de la ruta** (el Upsert con
`?Order`), se instancia con `new` en el controller — el contenedor no
puede inyectar el route binding en su constructor. Ambas formas son
canónicas.

## Las dos salidas

### Web (Blade)

`view()` acepta cualquier `Arrayable`: el ViewModel se retorna directo
y cada método público llega a Blade como variable (`$totalOrders`,
`$recentOrders`):

```php
public function __invoke(DashboardViewModel $viewModel): View
{
    return view('dashboard', $viewModel);
}
```

### API (Resources)

En contexto API los métodos del ViewModel devuelven Resources —
combinas ViewModel + Resources, por ejemplo para un backoffice:

```php
final class PostListViewModel extends ViewModel
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function posts(): ResourceCollection
    {
        $posts = Post::query()
            ->with('category', 'author')
            ->paginate(15);

        return PostResource::collection($posts)->resource;
    }
}
```

```php
public function index(PostListViewModel $viewModel): JsonResponse
{
    return response()->json($viewModel->toArray());
}
```

Los Resources solo existen en contexto API. En web no hay Resources:
el ViewModel entrega los datos a Blade.

## Cuándo usar ViewModel

- La vista/respuesta necesita **más de un tipo de dato** → ViewModel siempre.
- Respuesta API simple de un solo tipo → puede bastar un Resource directo.
- Listados con filtros/ordenación/paginación → ViewModel de listado: cuando el caso lo pide, ahí se aplica el pipeline de filtros sobre el builder (ver .ai/docs/architecture/filters.md).
- Formularios create+edit → un único ViewModel Upsert (mismo formulario, con o sin modelo).

## Reglas

- Ubicación: `app/ViewModels/{Dominio}/`. Sufijo `ViewModel`.
- Clase `final` que **extiende** `App\ViewModels\ViewModel` (hay arch test).
- Un método público = un dato expuesto. Helpers en `private`.
- Solo lecturas: nunca escribe, nunca llama Actions.
- Nada de lógica de negocio: solo dar forma a datos para presentación.

## ❌ Incorrecto

```php
// Controller montando arrays a mano para la vista
return view('dashboard', [
    'totalOrders' => Order::count(),     // query en el controller
    'recent' => Order::latest()->get(),  // sin límite, sin scope
]);

// ViewModel implementando Arrayable por su cuenta, sin la base
final class DashboardViewModel implements Arrayable { ... }
```
