---
paths:
  - "app/Console/**"
  - "app/Jobs/**"
  - "app/Notifications/**"
  - "routes/console.php"
---

Estás tocando trabajo en segundo plano. Lee
`.ai/docs/architecture/background.md`: comandos y jobs son adaptadores
finos que delegan en Actions; payload por constructor, dependencias
por `handle()`; una sola frontera de cola (job ShouldQueue →
notificación NO); la marca de procesado se escribe tras el efecto,
nunca al encolar. Tests con tiempo controlado y fakes.
