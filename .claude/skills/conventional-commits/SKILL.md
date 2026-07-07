---
name: conventional-commits
description: Genera y ejecuta commits siguiendo Conventional Commits. Usar cuando se pida un commit, un mensaje de commit o cerrar una tarea con commit.
argument-hint: "[contexto opcional del cambio]"
allowed-tools: Bash(git status *), Bash(git diff *), Bash(git log *), Bash(git add *), Bash(git commit *)
---

# Conventional Commits

## Contexto actual

- Estado: !`git status --short`
- Cambios: !`git diff --stat HEAD`
- Últimos mensajes: !`git log --oneline -5`

## Pasos

1. Analiza el diff real (usa `git diff` completo si el stat no basta).
2. Tipo: `feat` | `fix` | `docs` | `test` | `refactor` | `chore` | `build` | `ci`. Scope solo si aclara (ej: `feat(orders):`).
3. Subject imperativo, en inglés, sin punto final, máximo ~70 caracteres.
4. Body solo si hay contexto que no cabe en el subject.
5. **No commitees si el QA no está verificado en esta conversación**: ejecuta antes `./vendor/bin/sail composer qa` o pide confirmación explícita.
6. Propón el mensaje en bloque de código. Si el usuario pidió commitear directamente, ejecuta `git add` (solo de los archivos del cambio) + `git commit`.

$ARGUMENTS
