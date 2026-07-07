---
paths:
  - "app/ViewModels/**"
---

Estás tocando ViewModels. Lee `.ai/docs/architecture/viewmodels.md`:
extienden `App\ViewModels\ViewModel` (base con reflexión, NO
implementan Arrayable por su cuenta), un método público = un dato,
helpers en private, solo lecturas. Web → Blade directo; API →
Resources. Verifica con `./vendor/bin/sail composer test:arch`.
