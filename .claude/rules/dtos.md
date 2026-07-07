---
paths:
  - "app/DataTransferObjects/**"
---

Estás tocando DTOs. Lee `.ai/docs/architecture/dtos.md`:
`final readonly` con constructor promotion, sufijo `Data`, sin lógica.
Nacen del FormRequest vía `toDto()`.
