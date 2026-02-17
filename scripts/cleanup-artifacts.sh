#!/usr/bin/env sh
set -eu

# Keep generated frontend artifacts out of git state.
ARTIFACT_DIRS="frontend/coverage frontend/playwright-report frontend/test-results"

# Reset tracked artifact files to HEAD (ignore missing paths).
if git rev-parse --verify HEAD >/dev/null 2>&1; then
  git restore --worktree --source=HEAD -- $ARTIFACT_DIRS 2>/dev/null || true
fi

# Remove untracked files/directories inside artifact folders.
for dir in $ARTIFACT_DIRS; do
  [ -d "$dir" ] || continue

  git ls-files --others --exclude-standard -- "$dir" | while IFS= read -r path; do
    [ -n "$path" ] || continue
    [ -e "$path" ] || continue
    find "$path" -depth -delete
  done

  find "$dir" -type d -empty -delete 2>/dev/null || true
done
