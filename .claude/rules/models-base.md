---
paths:
  - "app/Models/Base/**"
  - "app/Models/Builders/**"
  - "app/Models/Concerns/**"
---

Estás en las capas del modelo organizado. Lee
`.ai/docs/architecture/models.md`: Base = abstracta, SOLO
configuración (fillable/hidden/casts) + use de Concerns; Builders =
scopes tipados que retornan `self`; Concerns = traits por modelo
(`HasAttributes` solo accessors, `HasRelations` solo relaciones).
Nada de lógica de dominio en estas capas: eso va al modelo final.
