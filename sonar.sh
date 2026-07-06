#!/usr/bin/env bash
set -euo pipefail

if [[ ! -f .env ]]; then
    echo "Error: .env not found in $(pwd)" >&2
    exit 1
fi

SONAR_TOKEN=$(awk -F= '/^SONAR_TOKEN=/{sub(/^SONAR_TOKEN=/,""); gsub(/["\r]/,""); print; exit}' .env)

if [[ -z "${SONAR_TOKEN:-}" ]]; then
    echo "Error: SONAR_TOKEN not found in .env" >&2
    exit 1
fi

export SONAR_TOKEN

docker run --rm \
    --add-host=host.docker.internal:host-gateway \
    -e SONAR_TOKEN \
    -v "$(pwd):/usr/src" \
    -w /usr/src \
    sonarsource/sonar-scanner-cli
