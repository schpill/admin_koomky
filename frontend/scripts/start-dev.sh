#!/bin/sh
set -eu

cd /app

if [ ! -f pnpm-lock.yaml ]; then
  echo "pnpm-lock.yaml not found in /app"
  exit 1
fi

# Avoid stale/partial Next artifacts when sharing the .next volume.
# This prevents "Cannot find module './<chunk>.js'" errors after interrupted builds.
if [ -d .next ]; then
  rm -rf .next/*
fi

mkdir -p node_modules

LOCK_HASH_FILE="node_modules/.pnpm-lock.sha256"
CURRENT_LOCK_HASH="$(sha256sum pnpm-lock.yaml | awk '{print $1}')"
NEEDS_INSTALL=0

if [ ! -f node_modules/.modules.yaml ]; then
  NEEDS_INSTALL=1
fi

if [ ! -f "$LOCK_HASH_FILE" ]; then
  NEEDS_INSTALL=1
fi

if [ "$NEEDS_INSTALL" -eq 0 ] && [ "$(cat "$LOCK_HASH_FILE")" != "$CURRENT_LOCK_HASH" ]; then
  NEEDS_INSTALL=1
fi

if [ "$NEEDS_INSTALL" -eq 1 ]; then
  echo "Installing frontend dependencies..."
  pnpm install --frozen-lockfile
  echo "$CURRENT_LOCK_HASH" > "$LOCK_HASH_FILE"
else
  echo "Dependencies already up to date. Skipping pnpm install."
fi

exec pnpm dev
