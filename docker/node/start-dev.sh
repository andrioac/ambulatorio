#!/bin/sh
set -eu

STAMP="node_modules/.ambulatorio-package-lock.sha256"
LOCK_HASH="$(sha256sum package-lock.json | awk '{print $1}')"
INSTALLED_HASH=""

if [ -f "$STAMP" ]; then
    INSTALLED_HASH="$(cat "$STAMP")"
fi

if [ ! -x node_modules/.bin/vite ] || [ "$LOCK_HASH" != "$INSTALLED_HASH" ]; then
    echo "Dependências do frontend ausentes ou desatualizadas; executando npm ci..."
    npm ci --no-audit --no-fund
    printf '%s' "$LOCK_HASH" > "$STAMP"
fi

exec npm run dev -- --host 0.0.0.0
