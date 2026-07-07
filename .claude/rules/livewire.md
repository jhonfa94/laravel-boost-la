---
paths:
  - "app/Livewire/**"
  - "resources/views/livewire/**"
---

Estás tocando un componente Livewire. Lee `.ai/docs/architecture/livewire.md`:
clase con nombre en `App\Livewire` (`--class`, nunca sfc/mfc), `final`,
orquestador fino. NO consulta (lectura en `#[Computed]` que delega en un
Service que devuelve el Builder; el componente solo pagina/cachea) ni
persiste (escritura por Form object → `toDto()` → la misma Action del
flujo clásico). Una parte es Livewire o clásica, nunca mezcla. Verifica
con `./vendor/bin/sail composer test:arch`.
