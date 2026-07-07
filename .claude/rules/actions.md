---
paths:
  - "app/Actions/**"
---

Estás tocando Actions. Lee `.ai/docs/architecture/actions.md` antes de
crear o modificar: final + sufijo `Action` + `__invoke(DTO)`, datos puros (nunca
Request), transacción dentro, efectos colaterales tras commit.
Verifica con `./vendor/bin/sail composer test:arch`.
