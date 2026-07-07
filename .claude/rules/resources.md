---
paths:
  - "app/Http/Resources/**"
---

Estás tocando API Resources. Lee `.ai/docs/architecture/viewmodels.md`
(sección API): los Resources solo existen en contexto API; con varios
tipos de datos los consume un ViewModel. Nunca exponer campos
internos (ids foráneos crudos, tokens, timestamps que no se usan).
