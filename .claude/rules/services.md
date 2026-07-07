---
paths:
  - "app/Services/**"
---

Estás tocando Services. Lee `.ai/docs/architecture/services.md`:
nacen SOLO cuando la reutilización existe (varias Actions o varios
contextos web/API); `final readonly`, sufijo `Service`, inyección por
constructor, sin Facades. ¿Duda entre Action y Service? Action.
Verifica con `./vendor/bin/sail composer test:arch`.
