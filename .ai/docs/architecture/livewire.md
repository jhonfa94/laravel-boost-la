# Livewire: UI reactiva con control

Livewire añade interfaz reactiva al proyecto **sin abrir un tercer
flujo**. El componente es un orquestador fino —como el Controller—:
ni consulta ni persiste. Por defecto Livewire empuja a meter la query
de lectura y la escritura DENTRO del componente; aquí no. La lectura
delega en un colaborador y la escritura termina en una Action, las
mismas que usa el flujo clásico. Livewire es **otra puerta de
entrada, no otra arquitectura**.

> Esta convención solo aplica si el proyecto usa Livewire. Si
> `livewire/livewire` no está instalado, esta pieza no existe: no se
> proponen componentes, se sigue el flujo clásico.

## Cuándo Livewire (decide el modo ANTES de construir)

Un proyecto puede ser **mixto**: partes reactivas (Livewire), partes
clásicas (Controller + Blade) y API (Resources) conviviendo. Antes de
construir una parte, decide su modo:

- **¿Livewire instalado en el proyecto?** Si no → flujo clásico
  siempre. No se introduce Livewire por iniciativa propia (es cambio
  de dependencia: se aprueba, no se asume).
- **¿La parte necesita reactividad en vivo?** Señales de que sí: interacción
  en vivo sin recargar — búsqueda al teclear, filtros instantáneos,
  validación en vivo, listas que se refrescan solas. Si la pantalla
  es estática (un listado que se ve, un formulario que envía y
  redirige), **el flujo clásico basta**: no metas Livewire por moda.

Una parte se reconoce por su ruta: apunta a un **componente** →
Livewire; apunta a un **Controller** → clásico.

### Qué piezas cambian cuando una parte es Livewire

| Pieza | Clásico HTTP | Parte Livewire |
|---|---|---|
| Controller | sí | ❌ el componente ES el entrypoint de la ruta |
| FormRequest | sí | ❌ lo sustituye el **Form object** (valida) |
| ViewModel | inyectado en el Controller | no aplica como tal — la lectura la asumen `#[Computed]` granulares + un Service de filtrado |
| DTO + Action | sí | ✅ **se quedan** — la escritura va por la Action |
| Model / Builder / Filtros / Policy | sí | ✅ **se quedan** |
| Resource | si hay API | ✅ solo si esa parte además expone API |

**Regla de oro**: no mezcles piezas de los dos modos en la misma
parte. Un Controller que renderiza un componente "por si acaso", o un
componente que llama a un Controller, es señal de modo mal elegido.

## Formato: componente con clase (`--class`)

```bash
php artisan make:livewire Post/Index --class --test
```

Genera una **clase con nombre** en `app/` y su Blade aparte:

```
app/Livewire/Post/Index.php                 ← la clase del componente
resources/views/livewire/post/index.blade.php   ← su plantilla
tests/Feature/Livewire/Post/IndexTest.php   ← su test Pest
```

Livewire 4 ofrece tres formatos; aquí solo vale el de clase. Los otros
dos —`--sfc` (PHP + Blade en un archivo) y `--mfc` (archivos
separados)— generan una **clase anónima** (`new class extends
Component`) bajo `resources/views/`: ni los arch tests ni Larastan la
enganchan, porque no tiene nombre ni vive en el autoload de `app/`.
La clase con nombre sí: **una pieza = una clase con nombre en su
archivo**, como el resto del proyecto, en `App\Livewire\{Dominio}`,
`final`, `declare(strict_types=1)` y extiende `Livewire\Component`.

## El componente: orquestador fino

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Post;

use App\Models\Category;
use App\Services\Post\FilterPostsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $categoryId = null;

    #[Url]
    public bool $inStockOnly = false;

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    /**
     * Lista filtrada: depende de los filtros, recalcula al cambiarlos.
     * El Service devuelve el Builder; el componente —consumidor—
     * pagina (la salida la decide el consumidor, ver services.md). El
     * componente no construye la query.
     *
     * @return LengthAwarePaginator<int, \App\Models\Post>
     */
    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return resolve(FilterPostsService::class)->apply([
            'query' => $this->search,
            'category' => $this->categoryId,
            'in_stock' => $this->inStockOnly,
        ])->latest()->paginate(15);
    }

    /**
     * Dato de referencia del desplegable: NO depende de los filtros.
     * `#[Computed]` lo memoiza una vez por request (NO uses `persist`
     * con una colección Eloquent: ver el tip más abajo).
     *
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.post.index');
    }
}
```

- **Propiedades públicas = estado de UI** (término de búsqueda,
  filtros activos, página). No modelos de negocio hidratados ni datos
  sensibles: lo público viaja al navegador.
- `mount()` para el estado inicial cuando haga falta.
- Render dentro del **layout único** (`<x-layout>`, ver views.md): el
  componente de página no repite `<html>`/`@vite`.

## Lectura: un `#[Computed]` por dato

Aquí Livewire diverge del flujo clásico por una razón de rendimiento.
El `ViewModel` base calcula **todos** sus métodos públicos por
reflexión en un solo `toArray()` — perfecto para una carga de página,
pero Livewire **re-renderiza en cada cambio reactivo**. Reusar el
ViewModel de listado entero significaría re-ejecutar **todas** sus
queries (el desplegable, los contadores…) en cada tecla. Por eso en
Livewire **no se reutiliza el ViewModel de listado**: cada dato vive
en su propio `#[Computed]` y solo recalcula el que depende del estado
que cambió. Lo estático va en su propio `#[Computed]` (memoizado por
request) y los inputs en vivo llevan `wire:model.live.debounce`.

El único dato que depende de los filtros —la lista— delega el filtrado
en un **Service** que aplica el Pipeline sobre el Builder y **devuelve
el Builder**; el componente, como consumidor, pagina.

**Tip (`persist`)**: `persist` serializa el valor al cache entre
requests. Sirve para **primitivos** (un `int`, un `string`, un array
plano), **nunca para una colección o modelo Eloquent**. En tests el
cache es `array` (mismo proceso, sin serializar) y la colección
sobrevive, pero en el navegador el cache serializa y la rehidrata
degradada —los modelos se vuelven strings— y `$model->id` peta en la
siguiente interacción. El 500 no lo ve `Livewire::test`, solo el
navegador. Un desplegable de referencia va en un `#[Computed]` normal
(la query es barata); si hace falta cachear, usa un array
plano `[id => nombre]`, no la colección.

**Coherencia con services.md**: en el flujo clásico el Pipeline de
filtros vive en el ViewModel de listado, y un Service solo se extrae
cuando aparece un segundo consumidor. En Livewire ese ViewModel no
existe y el componente no puede alojar la query, así que **el Service
ocupa el hueco del ViewModel de listado**: aquí un Service de un solo
consumidor no es prematuro, es su sitio. Su firma es la de
services.md —`apply(array $filters): Builder`, sin paginar dentro—:

```php
<?php

declare(strict_types=1);

namespace App\Services\Post;

use App\Enums\Filters\PostFilters;
use App\Filters\Filter;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;

final readonly class FilterPostsService
{
    public function __construct(private Pipeline $pipeline) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Post>
     */
    public function apply(array $filters): Builder
    {
        $pipes = collect($filters)
            ->map(fn (mixed $value, string $key): Filter => PostFilters::from($key)->create($value))
            ->values()
            ->all();

        return $this->pipeline
            ->send(Post::query()->with('category'))
            ->through($pipes)
            ->thenReturn();
    }
}
```

**Tip**: la query NO vive en el `#[Computed]`. El componente consume
y, como mucho, ordena y pagina la salida; **nunca construye el
filtrado**. Si ves `Post::query()->where(...)` dentro del componente,
esa lógica es del Builder, los Filtros o el Service.

## Escritura: Form object → `toDto()` → Action

El **Form object** sustituye al FormRequest como frontera de
validación, y expone `toDto()` igual que él. El método de acción del
componente construye el DTO y llama a la **misma Action** del flujo
clásico.

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Post;

use App\DataTransferObjects\Post\CreatePostData;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class PostForm extends Form
{
    #[Validate('required|string|max:120')]
    public string $title = '';

    #[Validate('required|string|min:10')]
    public string $body = '';

    #[Validate('required|integer|exists:categories,id')]
    public ?int $categoryId = null;

    public function toDto(): CreatePostData
    {
        return new CreatePostData(
            title: $this->title,
            body: $this->body,
            categoryId: (int) $this->categoryId,
        );
    }
}
```

```php
final class Create extends Component
{
    public PostForm $form;

    public function save(CreatePostAction $createPost): \Illuminate\Contracts\View\View|\Livewire\Features\SupportRedirects\Redirector
    {
        $this->form->validate();

        $post = $createPost($this->form->toDto());

        return $this->redirectRoute('posts.show', $post);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.post.create');
    }
}
```

**PROHIBIDO** `Post::create()` / `->save()` / `->update()` /
`->delete()` dentro del Form o del componente. La escritura es de la
Action, siempre — la misma que usa el Controller clásico. El Form
valida y arma el DTO; nada más.

## Rutas: el componente es el entrypoint

```php
// routes/web.php — una parte Livewire NO tiene Controller
Route::get('/posts', App\Livewire\Post\Index::class)->name('posts.index');
Route::get('/posts/create', App\Livewire\Post\Create::class)->name('posts.create');
```

## Tests: `Livewire::test` necesario, navegador OBLIGATORIO

`Livewire::test` cubre la lógica (filtrado, validación, que la acción
dispara la Action) pero **no toca el navegador**: corre con cache
`array` y sin render real, así que **no ve** un 500 al interactuar, un
error JS ni un fallo de serialización. Una feature Livewire **no está
terminada** sin un test de navegador que ejecute la interacción.

### Lógica: `Livewire::test`

```php
use App\Livewire\Post\Index;
use Livewire\Livewire;

it('filtra la lista en vivo al buscar', function (): void {
    Post::factory()->create(['title' => 'Laravel reactivo']);
    Post::factory()->create(['title' => 'Otra cosa']);

    Livewire::test(Index::class)
        ->set('search', 'Laravel')
        ->assertViewHas('posts', fn ($posts) => $posts->count() === 1);
});

it('crea vía la Action y redirige', function (): void {
    $category = Category::factory()->create();

    Livewire::test(Create::class)
        ->set('form.title', 'Nuevo post')
        ->set('form.body', 'Contenido suficiente largo')
        ->set('form.categoryId', $category->id)
        ->call('save')
        ->assertRedirect();

    expect(Post::where('title', 'Nuevo post')->exists())->toBeTrue();
});

it('rechaza datos inválidos', function (): void {
    Livewire::test(Create::class)
        ->set('form.title', '')
        ->call('save')
        ->assertHasErrors('form.title');
});
```

### Navegador (obligatorio): Pest 4 browser

Carga la página, interactúa (filtra, pagina, envía) y comprueba que no
se rompe. Detecta lo que el unitario no ve: excepción de render al
actualizar, error JS, `wire:model` roto.

```php
// tests/Browser/PostCatalogTest.php
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filtra sin romper la interfaz', function (): void {
    $category = Category::factory()->create(['name' => 'Noticias']);
    Post::factory()->count(20)->recycle($category)->create();

    visit('/posts')
        ->assertSee('Noticias')
        ->select('#category', (string) $category->id)   // wire:model.live → update
        ->assertNoJavascriptErrors()
        ->assertDontSee('Internal Server Error');
});
```

Setup del módulo (una vez): `composer require pestphp/pest-plugin-browser --dev`,
`npm install -D playwright && npx playwright install chromium`, y en
`tests/Pest.php`: `pest()->extend(TestCase::class)->in('Browser');`.

### Límite del navegador en tests

El servidor de Pest browser corre in-process con el entorno de testing
(`CACHE_STORE=array`, `:memory:`), que no serializa el cache. Un bug
que solo aparece con el cache de prod —una colección Eloquent en
`persist`— no sale aquí: el test pasa y la app real falla. Dos
defensas:

1. La convención lo evita en origen (ver el tip de `persist`).
2. Antes de cerrar, abre la página real con la config de prod y filtra;
   ahí sí aparece.

`Http::preventStrayRequests()` del TestCase base sigue vigente: ningún
componente toca la red real.

## Reglas (resumen citable)

- Una parte es Livewire **o** clásica, nunca mezcla.
- Componente `final`, `strict_types`, extiende `Component`, **clase
  con nombre en `app/Livewire`** (`--class`, no sfc/mfc).
- El componente **no construye el filtrado** (va por el Service /
  Builder) ni **persiste** (va por la Action). Solo consume: ordena,
  pagina y cachea referencias estáticas en `#[Computed]`.
- Validación en **Form object** con `toDto()`; el componente nunca
  monta el array de datos a mano.
- Propiedades públicas = estado de UI, no datos de negocio.
- Reutiliza Builder, Filtros, DTOs y Actions del dominio: Livewire no
  duplica arquitectura.

## ❌ Incorrecto

```php
final class Index extends Component
{
    public function render()
    {
        // query en el componente: la lectura no es suya
        $posts = Post::query()->where('title', 'like', "%{$this->search}%")->get();

        return view('...', ['posts' => $posts]);
    }

    public function save()
    {
        // validación inline + escritura a pelo: ni Form ni Action
        $this->validate(['title' => 'required']);
        Post::create(['title' => $this->title]);   // salta DTO y Action
    }
}

// Single-file (clase anónima en el .blade.php): fuera de la convención
// Un Controller que renderiza un componente Livewire: modos mezclados
```

## Verificación y arch tests (gated)

Los arch tests de Livewire **solo corren si Livewire está instalado**
(`class_exists(Livewire\Component::class)`); en un proyecto sin
Livewire son inertes, no fallan. Comprueban lo estructural:

- Todo `App\Livewire` (salvo `App\Livewire\Forms`) es `final` y
  extiende `Livewire\Component`.
- Todo `App\Livewire\Forms` es `final` y extiende `Livewire\Form`.
- Los componentes no usan `Illuminate\Http` ni la facade `DB`.

Lo que el arch test NO puede ver —que la lectura delega en el Service
y la escritura termina en la Action, porque son llamadas a método, no
usos de clase— lo garantiza esta convención y lo vigila el
`architecture-auditor` en el cierre. El `composer test:arch` blinda la
forma; el auditor, el flujo.
