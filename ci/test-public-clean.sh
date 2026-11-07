#!/bin/sh
# Probes for ci/check-public-clean.sh: each probe must exit 1, the clean tree must exit 0.
# The listed-token probe uses a throwaway hash list, so no real name is written here.
set -eu
src=$(cd "$(dirname "$0")" && pwd)
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
export GIT_AUTHOR_NAME=probe GIT_AUTHOR_EMAIL=probe@example.com
export GIT_COMMITTER_NAME=probe GIT_COMMITTER_EMAIL=probe@example.com
export GIT_CONFIG_COUNT=1 GIT_CONFIG_KEY_0=commit.gpgsign GIT_CONFIG_VALUE_0=false
if command -v sha256sum >/dev/null 2>&1; then SHA=sha256sum; else SHA='shasum -a 256'; fi
fails=0

run() { # expected exit, label, file text, [title], [commit message]
  rm -rf "$tmp/r" && mkdir -p "$tmp/r/ci" && cd "$tmp/r"
  git init -q . && cp "$src/check-public-clean.sh" ci/
  printf 'name %s\nkey %s\n' "$(printf 'zzprobe-name' | $SHA | cut -c1-64)" \
    "$(printf 'zzk' | $SHA | cut -c1-64)" > "$tmp/hashes"
  printf '%s\n' "Plain text, UTF-8, ISO-8859-1 and GPL-2 are fine." > a.txt
  git add -A && git commit -qm "base" && base=$(git rev-parse HEAD)
  printf '%s\n' "$3" >> a.txt && git add -A && git commit -qm "${5:-change}"
  set +e
  CI_MERGE_REQUEST_TITLE="${4:-}" CI_MERGE_REQUEST_DIFF_BASE_SHA=$base \
    PUBLIC_CLEAN_HASHES="$tmp/hashes" sh ci/check-public-clean.sh >/dev/null 2>&1
  got=$?
  set -e
  if [ "$got" -ne "$1" ]; then echo "FAIL $2: exit $got, want $1"; fails=1; else echo "ok   $2"; fi
}

run 0 "clean tree" "nothing internal here"
run 1 "ticket key" "see ABC-123"
run 1 "listed key, lower case" "see zzk-42"
run 1 "tracker url" "https://linear.app/acme/issue/x"
run 1 "host url" "https://gitlab.com/acme/repo"
run 1 "mr reference" "fixed in !2385"
run 1 "listed name" "calls Zzprobe-Name-svc"
run 1 "attribution" "co-authored-by: someone"
run 1 "mr title" "nothing internal here" "fix: per !12"
run 1 "commit message" "nothing internal here" "" "fix: ABC-9 thing"
run 0 "clean title and message" "nothing internal here" "fix: plain" "fix: plain"
exit "$fails"
