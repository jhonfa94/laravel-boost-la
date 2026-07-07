---
paths:
  - "app/Http/Requests/**"
---

Estás tocando FormRequests. Lee `.ai/docs/architecture/dtos.md`
(sección toDto y trait ValidatesAndTransforms): cada FormRequest de
escritura expone `toDto()`; el controller nunca usa `validated()`.
