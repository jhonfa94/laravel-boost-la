# Controllers

Los controllers son finales, thin, y SOLO coordinan: validar (vía
FormRequest), delegar (Action o ViewModel) y responder. Cero lógica
de negocio, cero queries.

## Reglas

- Ubicación: `app/Http/Controllers/{Dominio}/`.
- Clase `final`. Return types siempre (`View`, `RedirectResponse`,
  `JsonResponse`).
- **Invocables** (`__invoke()`) para acciones que no son CRUD.
- **Resource controllers** (index, create, store, show, edit, update,
  destroy) para CRUD. Máximo 5-7 métodos: si crece, se divide.
- Escritura: `FormRequest` → `toDto()` → Action. El controller nunca
  toca `validated()` ni monta arrays.
- Lectura: ViewModel (web/API) o Resource directo (API simple).
- Nada de Eloquent Builder en el controller (hay arch test).
- Autorización vía Policy/FormRequest, no `if` manuales repetidos.

## Escritura (web)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Order;

use App\Actions\Order\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use Illuminate\Http\RedirectResponse;

final class StoreOrderController extends Controller
{
    public function __invoke(StoreOrderRequest $request, CreateOrderAction $createOrder): RedirectResponse
    {
        $order = $createOrder($request->toDto());

        return redirect()->route('orders.show', $order);
    }
}
```

## Lectura (web, con ViewModel)

```php
final class DashboardController extends Controller
{
    public function __invoke(DashboardViewModel $viewModel): View
    {
        return view('dashboard', $viewModel);
    }
}
```

## Lectura (API)

```php
final class PostController extends Controller
{
    public function index(PostListViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }
}
```

## Respuestas API

- Status codes semánticos: 201 al crear, 204 al borrar, 422 en
  validación (FormRequest lo hace solo), 403/404 vía
  Policy/ModelBinding.
- Nunca retornar modelos o arrays a pelo en API: Resources (o
  ViewModel que los usa).

## ❌ Incorrecto

```php
class OrderController extends Controller
{
    public function store(Request $request)            // sin FormRequest
    {
        $data = $request->all();                        // sin validar ni tipar
        $order = Order::create($data);                  // query + mass assignment
        Mail::to($order->user)->send(new OrderMail());  // lógica de negocio

        return $order;                                  // modelo a pelo
    }
}
```
