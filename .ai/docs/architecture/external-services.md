# Servicios externos (HTTP)

Consumo de APIs y servicios de terceros: cliente tipado, timeouts
explícitos, fallos aislados y tests que no tocan la red.

## Reglas

- **Cliente HTTP por contrato**: inyecta
  `Illuminate\Http\Client\Factory` — nunca la Facade `Http::` en
  lógica de negocio. Factory es singleton del contenedor, así que
  `Http::fake()` sigue interceptando en los tests.
- **Timeouts SIEMPRE explícitos**: `timeout()` + `connectTimeout()`.
  Sin timeout, un tercero caído te tumba el worker.
- Configuración en `config/`: credenciales de terceros en
  `config/services.php`; integraciones con dominio propio, en su
  archivo (`config/domains.php`). `env()` solo ahí (hay arch test).
- ¿Dónde vive la llamada? En la **Action** que la consume mientras
  haya UN consumidor; se extrae a **Service** cuando la reutilización
  exista (services.md) — ese día, ni uno antes.
- `ConnectionException` se captura donde se hace la llamada, con
  early return del resultado de fallo. Si se procesan varios
  elementos, **el fallo de uno no rompe el ciclo del resto**.
- Nunca exponer el error crudo del tercero al usuario.

## Ejemplo

```php
final readonly class CheckDomainStatusAction
{
    public function __construct(
        private Factory $http,
        private Repository $config,
    ) {}

    public function __invoke(Domain $domain): bool
    {
        try {
            return $this->http
                ->timeout((int) $this->config->get('domains.status_check.timeout'))
                ->connectTimeout((int) $this->config->get('domains.status_check.connect_timeout'))
                ->get("https://{$domain->name}")
                ->successful();
        } catch (ConnectionException) {
            return false;
        }
    }
}
```

## Testing

- **OBLIGATORIO**: todo test que toque el cliente HTTP usa
  `Http::fake()`. Sin excepciones — un test que llama a un tercero
  real es un test roto que aún no ha fallado.
- La red de seguridad va de serie: el `TestCase` base llama
  `Http::preventStrayRequests()`, así que cualquier petición sin
  fake **falla la suite por construcción**. No se desactiva.
- Cobertura mínima del fake: respuesta correcta, error 5xx y
  `Http::failedConnection()` para timeout/DNS/conexión rechazada.
- `Http::assertSent()` verificando la URL (y headers si aplican).

## ❌ Incorrecto

```php
// Facade en lógica de negocio + sin timeout
$response = Http::get("https://{$domain->name}");

// Timeout hardcodeado o leído con env() fuera de config/
->timeout((int) env('CHECK_TIMEOUT', 5))

// try/catch envolviendo el bucle entero: un dominio caído
// aborta el chequeo de todos los demás
```
