---
name: qa
description: Ejecuta el pipeline completo de calidad del proyecto y entrega un veredicto GO/NO-GO. Usar cuando se pida QA, verificación, "está listo", o antes de un commit importante.
allowed-tools: Bash(./vendor/bin/sail composer *), Bash(./sonar.sh), Bash(curl *), Bash(git status *), Bash(git diff *), Bash(sleep *)
---

# QA del proyecto

## Pasos

1. `./vendor/bin/sail composer qa` — si falla, para: reporta qué herramienta falló y el error exacto.
2. Si rector:dry propone cambios: `./vendor/bin/sail composer rector` y repite el paso 1.
3. `./sonar.sh` para enviar el análisis.
4. Espera ~20s (el análisis es asíncrono) y consulta los issues abiertos:
   `SONAR_TOKEN=$(awk -F= '/^SONAR_TOKEN=/{print $2; exit}' .env) && curl -s -u "$SONAR_TOKEN:" "http://localhost:9000/api/issues/search?componentKeys=boost-lab&statuses=OPEN&resolved=false&ps=50"`
5. Veredicto.

## Formato de salida

**GO** o **NO-GO**, seguido de:

- Comandos ejecutados y resultado.
- Issues de Sonar abiertos (tipo, archivo, regla) o "0 issues".
- Si NO-GO: la primera corrección que aplicarías.
