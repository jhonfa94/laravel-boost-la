---
paths:
  - "app/Models/**"
  - "database/migrations/**"
---

Estás tocando modelos o migraciones. Lee
`.ai/docs/architecture/models.md`: organización
Concerns → Base → Builder → Final; `$fillable` explícito, `casts()`
método, relaciones tipadas. Migraciones: `down()` siempre; nada
destructivo sin aprobación explícita.
