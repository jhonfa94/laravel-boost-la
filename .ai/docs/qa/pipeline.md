# Pipeline de calidad completo

El ciclo que cierra cualquier trabajo importante. Sin verde, no hay
commit que valga.

## El ciclo

```
1. ./vendor/bin/sail composer qa
   └─ rector:dry → pint --dirty → phpstan L8 → pest (+coverage clover)
2. ¿rector:dry propone cambios? → ./vendor/bin/sail composer rector → repetir 1
3. ./sonar.sh                       ← envía análisis + coverage a Sonar
4. Consultar issues abiertos (API):
   SONAR_TOKEN=$(awk -F= '/^SONAR_TOKEN=/{print $2; exit}' .env) \
   && curl -s -u "$SONAR_TOKEN:" \
      "http://localhost:9000/api/issues/search?componentKeys=boost-lab&statuses=OPEN&resolved=false"
5. ¿Issues? → corregir (respetando .ai/docs/) → volver a 1
6. 0 issues → commit (skill conventional-commits)
```

## Notas

- El análisis de Sonar es asíncrono: tras `sonar.sh`, espera ~20s
  antes de consultar la API.
- El dashboard humano: http://localhost:9000/dashboard?id=boost-lab
- Scripts individuales: `composer pint`, `composer phpstan`,
  `composer rector`, `composer test`, `composer test:fast`,
  `composer test:arch`.
- Todo corre vía Sail: `./vendor/bin/sail composer ...`.

## Reglas

- No se commitea con cualquier paso en rojo.
- Un issue de Sonar no se silencia ("won't fix") sin decisión humana.
- Corregir un issue rompiendo la arquitectura no es corregir: las
  convenciones de `.ai/docs/` mandan también durante las correcciones.
