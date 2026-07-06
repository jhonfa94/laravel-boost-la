# DTOs (Data Transfer Objects)

Los datos viajan tipados entre capas. Los arrays asociativos no
cruzan fronteras de capa: ni del controller a la Action, ni de la
Action al modelo.

## Reglas

- Ubicación: `app/DataTransferObjects/{Dominio}/`.
- `final readonly class` con constructor promotion: inmutables,
  propiedades públicas tipadas.
- Sin lógica de negocio. Como mucho, factorías de construcción
  estáticas y `toArray()` para persistencia.
- Sufijo `Data`: `CreateUserData`, `UpdateProfileData`.
- Defaults y nullables en el constructor; DTOs anidados cuando el
  dato es compuesto.
- Nacen del FormRequest vía `toDto()` — el controller nunca toca
  `validated()` directamente.
- Validación compleja → **Rule object** en `app/Rules/` (`final`,
  implementa `ValidationRule`; hay arch test); el FormRequest la usa
  en `rules()`.

## Anatomía

```php
<?php

declare(strict_types=1);

namespace App\DataTransferObjects\User;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $role = 'viewer',
        public ?UserSettingsData $settings = null,
    ) {}
}
```

Inmutabilidad garantizada: `$dto->name = 'Jane'` lanza error. El DTO
es un snapshot de datos que no cambia.

## El puente: FormRequest::toDto()

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\DataTransferObjects\User\CreateUserData;
use Illuminate\Foundation\Http\FormRequest;

final class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ];
    }

    public function toDto(): CreateUserData
    {
        return new CreateUserData(
            name: $this->validatedString('name'),
            email: $this->validatedString('email'),
            password: $this->validatedString('password'),
        );
    }
}
```

## Trait ValidatesAndTransforms

Con muchos FormRequests, el trait estandariza la extracción tipada
desde `validated()`. Vive en `app/Http/Requests/Concerns/`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

trait ValidatesAndTransforms
{
    public function validatedString(string $key, ?string $default = null): ?string
    {
        $value = $this->validated($key);

        return is_string($value) ? $value : $default;
    }

    public function validatedInt(string $key, ?int $default = null): ?int
    {
        $value = $this->validated($key);

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public function validatedBool(string $key, bool $default = false): bool
    {
        return filter_var($this->validated($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function validatedArray(string $key, array $default = []): array
    {
        $value = $this->validated($key);

        return is_array($value) ? $value : $default;
    }
}
```

## En el controller

```php
public function store(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
{
    $user = $action($request->toDto());

    return redirect()->route('users.show', $user);
}
```

## Updates parciales

Para updates, DTO propio (`UpdateUserData`) con nullables y, si hace
falta distinguir "no enviado" de "null", factoría estática que lea
`$request->has()`.

## ❌ Incorrecto

```php
// Array crudo cruzando capas
$action->execute($request->all());

// Controller accediendo a validated() y montando el array a mano
$data = $request->validated();
$user = User::create($data);
```

## Verificación

`composer test:arch` comprueba que todo `App\DataTransferObjects` es
final y readonly.
