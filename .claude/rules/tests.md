---
paths:
  - "tests/**"
---

Estás tocando tests. Lee `.ai/docs/testing/pest.md`. Factories siempre,
ejecuta primero solo el test afectado, y nunca cambies una assertion
para ocultar un bug. HTTP externo: SIEMPRE `Http::fake()` — el
TestCase base llama `Http::preventStrayRequests()` y una petición
real sin fake falla la suite (es deliberado, no lo desactives).
