---
paths:
  - "resources/views/**"
---

Estás tocando vistas Blade. Lee `.ai/docs/architecture/views.md`:
todo dentro de `<x-layout>` (no repitas html/head/@vite), flash y
errores los pinta el layout, datos desde el ViewModel (cero queries
en Blade), textos visibles bajo `assertSee` — cambiar un copy implica
revisar sus tests, nunca al revés.
