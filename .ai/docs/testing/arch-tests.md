# Arch tests: la arquitectura que se verifica sola

Cada convención de `.ai/docs/architecture/` tiene su test en
`tests/Architecture/ArchTest.php`. Si una regla cambia aquí, cambia
allí — nacen y mueren juntas.

## Mapa regla ↔ test

| Convención (doc) | Arch test |
|---|---|
| strict_types en todo el código | `App` → `toUseStrictTypes()` |
| Sin dd/dump/var_dump/ray | funciones de debug → `not->toBeUsed()` |
| `env()` solo en config/ | `env` → `not->toBeUsedIn('App')` |
| Actions finales, readonly, invocables y con sufijo `Action` (actions.md) | `App\Actions` → `toBeFinal()` + `toBeReadonly()` + `toBeInvokable()` + `toHaveSuffix('Action')` |
| DTOs final + readonly (dtos.md) | `App\DataTransferObjects` → `toBeFinal()` + `toBeReadonly()` |
| Controllers finales sin Eloquent (controllers.md) | `App\Http\Controllers` → `toBeFinal()` + `not->toUse(Builder)` (ignora el Controller base) |
| ViewModels extienden la base (viewmodels.md) | `App\ViewModels` → `toExtend(ViewModel::class)` (ignora la base) |
| ViewModels finales y con sufijo `ViewModel` (viewmodels.md) | `App\ViewModels` → `toBeFinal()` + `toHaveSuffix('ViewModel')` (ignora la base abstracta) |
| Models Base abstractos (models.md) | `App\Models\Base` → `toBeAbstract()` |
| Models y Builders finales (models.md) | `App\Models` → `toBeFinal()` (ignora `Base` y `Concerns`) |
| Filtros extienden Filter (filters.md) | `App\Filters` → `toExtend(Filter::class)` (ignora Filter y FilterValue) |
| Policies finales con sufijo (authorization.md) | `App\Policies` → `toBeFinal()` + `toHaveSuffix('Policy')` |
| Services finales, readonly y con sufijo (services.md) | `App\Services` → `toBeFinal()` + `toBeReadonly()` + `toHaveSuffix('Service')` |
| Resources finales con sufijo (viewmodels.md) | `App\Http\Resources` → `toBeFinal()` + `toHaveSuffix('Resource')` |
| Jobs finales, encolables y con sufijo (background.md) | `App\Jobs` → `toBeFinal()` + `toImplement(ShouldQueue)` + `toHaveSuffix('Job')` |
| Notifications finales con sufijo (background.md) | `App\Notifications` → `toBeFinal()` + `toHaveSuffix('Notification')` |
| Rules de validación finales (dtos.md) | `App\Rules` → `toBeFinal()` |
| Providers finales | `App\Providers` → `toBeFinal()` |

## Cómo se ejecutan

- `composer test:arch` — solo arquitectura (rápido; lo piden las rules).
- `composer test` y `composer qa` — la suite completa incluye el
  testsuite Architecture declarado en `phpunit.xml`.

## Regla de oro

Si quieres una convención nueva: primero el doc en `.ai/docs/`, luego
su arch test, luego el código. Una convención sin test es una opinión.
Las clases base (`ViewModel`, `Filter`, `Models\Base\*`) se excluyen
con `ignoring()` — son la infraestructura de la convención, no casos
de ella.
