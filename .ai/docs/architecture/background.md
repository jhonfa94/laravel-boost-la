# Trabajo en segundo plano: comandos, scheduler, jobs y notificaciones

El principio que lo gobierna todo: **comandos y jobs son adaptadores
de entrada, igual que los controllers** — finos, sin lógica. La
lógica vive SIEMPRE en Actions, venga la petición de HTTP, del CLI o
de la cola.

## Comandos artisan

- Ubicación: `app/Console/Commands/{Name}Command.php`. `final`,
  sufijo `Command`.
- Fino: valida la entrada del CLI si la hay, delega en una Action e
  informa del resultado (recuento, exit code).
- La programación vive en `routes/console.php`:
  `Schedule::command('domains:check-expiring')->daily()`.

## Jobs

- Ubicación: `app/Jobs/{Dominio}/`. `final`, sufijo `Job`,
  `implements ShouldQueue` (hay arch test).
- **Payload por constructor, dependencias por `handle()`**: el
  constructor recibe lo que se serializa (modelos, ids); `handle()`
  recibe por inyección lo que se resuelve al ejecutar (la Action).
- Thin: `handle()` delega en una Action. Un job con lógica es un
  controller gordo con otro disfraz.
- Encolar sin Facade: inyecta `Illuminate\Contracts\Bus\Dispatcher`
  y llama `dispatch()`.
- Si el encolado ocurre dentro de una transacción → `afterCommit()`:
  un rollback no debe dejar jobs huérfanos en la cola.

## Notificaciones

- Ubicación: `app/Notifications/{Dominio}/`. `final`, sufijo
  `Notification`.
- **Una sola frontera de cola**: si el job que la envía ya es
  `ShouldQueue`, la notificación NO lo es — evita el doble encolado.
  Una notificación `ShouldQueue` solo cuando se envía desde flujo
  síncrono.
- Sin destinatario User (avisos a emails registrados): on-demand —
  `(new AnonymousNotifiable)->route('mail', $email)->notify(...)`.

## Efectos y reintentos

- La marca de "ya procesado/avisado" se escribe DESPUÉS del efecto,
  nunca al encolar: si el envío falla y el job reintenta, no queda
  registro fantasma.
- Dedupe con significado de dominio: guarda el dato que identifica
  el aviso (p. ej. la fecha de vencimiento avisada), no un boolean —
  si el dato cambia (renovación), el aviso se re-arma solo.

## Testing

| Qué | Cómo |
|---|---|
| Schedule registrado | assertear comando + expresión cron sobre el Schedule |
| Comando | `travelTo()` / `freezeTime()` + `Queue::fake()` — el tiempo SIEMPRE controlado |
| Job | `Notification::fake()` + `assertSentOnDemand()` |
| Dedupe | mismo dato → no repite; dato nuevo → re-avisa |

## ❌ Incorrecto

```php
// Lógica de negocio en el comando o en el handle() del job
public function handle(): void
{
    $domains = Domain::where(...)->get(); // esto es de una Action

// Notificación ShouldQueue enviada desde un job ya encolado: doble cola
final class WarningNotification extends Notification implements ShouldQueue

// Marcar como avisado al encolar: el reintento del job pierde el aviso
$domain->update(['warned_at' => now()]);
$this->dispatcher->dispatch(new SendWarningJob($domain));
```
