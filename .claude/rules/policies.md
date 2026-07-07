---
paths:
  - "app/Policies/**"
---

Estás tocando Policies. Lee `.ai/docs/architecture/authorization.md`:
final + sufijo `Policy`, auto-descubrimiento por convención de nombres,
una ability = un método `bool` que consume los helpers de dominio (nunca
compara enums a mano); se conecta vía middleware `can`, `authorize()`
del FormRequest o `@can`. Sin `viewAny`: el scoping vive en el ViewModel.
