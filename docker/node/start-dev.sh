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

# O volume node_modules persiste entre reinícios. Remover o cache otimizado
# evita referências antigas como /node_modules/.vite/deps/* com hash vencido.
rm -rf node_modules/.vite

exec npm run dev -- --host 0.0.0.0 --force
