---
paths:
  - "app/Http/Controllers/**"
---

Estás tocando Controllers. Lee `.ai/docs/architecture/controllers.md`:
final, thin, invocable o resource, return types; escritura delega en
Action vía `toDto()`, lectura en ViewModel/Resource. Sin queries ni
lógica de negocio (hay arch test).
