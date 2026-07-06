# Arquitectura y convenciones del proyecto

Dos flujos, sin excepciones — **Escritura**: Request → FormRequest (valida) → `toDto()` → Controller → Action `__invoke(DTO)` · **Lectura**: web → ViewModel (extiende la base Arrayable) directo a Blade; API → Resources (vía ViewModel si hay varios tipos de datos).

La convención completa de cada pieza vive en `.ai/docs/` — **léela ANTES de crear la pieza**:

| Pieza | Referencia |
|---|---|
| Controller | .ai/docs/architecture/controllers.md |
| Action / transacciones / Service | .ai/docs/architecture/actions.md · services.md |
| DTO / FormRequest toDto() / Rule de validación | .ai/docs/architecture/dtos.md |
| ViewModel / Resource / vista Blade | .ai/docs/architecture/viewmodels.md · views.md |
| Componente Livewire (UI reactiva) | .ai/docs/architecture/livewire.md |
| Model (Base/Builder/Concerns) / migración | .ai/docs/architecture/models.md |
| Filtros (FilterValue/Enum/Pipeline) / Policy | .ai/docs/architecture/filters.md · authorization.md |
| Comando / Job / Notificación / HTTP externo | .ai/docs/architecture/background.md · external-services.md |
| Tests Pest / Arch tests / Pipeline QA + Sonar | .ai/docs/testing/pest.md · arch-tests.md · .ai/docs/qa/pipeline.md |

Reglas duras (verificadas por arch tests y Pint): `declare(strict_types=1)`, clases `final` por defecto, DTOs `readonly`, sin Facades en lógica de negocio, `env()` solo en `config/`, early return. QA antes de dar nada por terminado: `vendor/bin/sail composer qa` en verde; cierre importante con la skill `qa` (incluye Sonar).
