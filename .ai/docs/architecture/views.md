# Vistas (Blade)

Convención ESTRUCTURAL de las vistas web. El diseño visual es decisión
de cada proyecto; esto define la estructura que no se negocia.

## Reglas

- **UN layout**: componente anónimo
  `resources/views/components/layout.blade.php`, consumido como
  `<x-layout>`. Ninguna vista repite `<html>`, `<head>` ni `@vite`.
- El layout centraliza: assets (`@vite`), navegación, flash de sesión
  (`session('status')`) y errores de validación (`$errors->any()`).
  Las vistas no repiten ese boilerplate.
- Los datos llegan de un ViewModel (ver viewmodels.md): variables
  planas en Blade, **cero queries y cero lógica de negocio** en vistas.
- Formularios create+edit comparten vista upsert — espejo del
  ViewModel Upsert.
- Parciales del dominio con prefijo `_` (`invoices/_form.blade.php`).
- Tailwind utility-first; sin `<style>` sueltos ni estilos inline.
- El `welcome.blade.php` del esqueleto se elimina al montar el layout
  (duplica `<html>` y `@vite`); la home redirige al listado principal
  o se monta sobre `<x-layout>`.
- Los textos visibles están bajo test (`assertSee`): cambiar un copy
  implica revisar sus tests — nunca al revés (no se "arregla" un
  assertSee tocando el test para ocultar el cambio).
- Assets: `vendor/bin/sail npm install` y `npm run build` (o
  `npm run dev` mientras desarrollas). Sin build, `@vite` lanza
  ViteException.

## Esqueleto del layout

```blade
@props(['title' => config('app.name')])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">
    <nav>
        {{-- navegación principal: enlaces con estado activo vía @class y request()->routeIs() --}}
    </nav>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>
</body>
</html>
```

Y cada vista queda en lo suyo:

```blade
<x-layout title="Facturas">
    {{-- solo el contenido de la página --}}
</x-layout>
```

**Tip de dirección de arte**: sin referencia, el modelo produce
Tailwind genérico — el "look IA". Si el proyecto tiene identidad,
dásela: una captura de referencia (Claude Code lee imágenes), la
paleta y tipografía como tokens en el `@theme` de `app.css`, o un
componente ya estilado como patrón a seguir. El modelo replica
dirección; no la inventa.

## ❌ Incorrecto

```blade
{{-- Vista con su propio <html> y @vite duplicado --}}

{{-- Query en Blade --}}
@foreach (App\Models\Order::all() as $order)
```
