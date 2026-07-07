---
paths:
  - "app/Filters/**"
  - "app/Enums/Filters/**"
---

Estás tocando filtros. Lee `.ai/docs/architecture/filters.md`. No
siempre son necesarios: detecta el caso (listado con filtrado variable
sí; listado fijo → scope del Builder). Filtro = clase que extiende
`Filter` con `handle(Builder, Closure)`, valores vía `FilterValue`,
factory en el Enum del dominio, Pipeline sobre el builder en el
ViewModel de listado o servicio. Vacío → `return $next($items)`.
Listados siempre paginan.
