#!/bin/sh
# Fails on internal references in tracked files, the MR title and MR commit messages.
# Generic patterns only; named tokens are compared by sha256 of the lowercase token against
# ci/public-clean.sha256, so no name is written in this tree.
set -eu
here=$(cd "$(dirname "$0")" && pwd)
HASHES=${PUBLIC_CLEAN_HASHES:-$here/public-clean.sha256}
KEY='(^|[^A-Za-z0-9_])[A-Z][A-Z0-9]{1,9}-[0-9]+'
ALLOW='(UTF|ISO|GPL|LGPL|AGPL|SHA|RFC|CVE|AES|TLS|HTTP|WCAG)-[0-9]+'
PAT='linear\.app|gitlab\.(com|io)|atlassian\.net|\.dev\.fungies\.|(^|[[:space:](,;])![0-9]{1,6}([^0-9]|$)|co-authored-by|generated with|anthropic'
if command -v sha256sum >/dev/null 2>&1; then SHA=sha256sum; else SHA='shasum -a 256'; fi
fail=0

generic() { # lines on stdin; prints the lines with a ticket key, tracker host, MR ref or trailer
  awk -v k="$KEY" -v a="$ALLOW" -v p="$PAT" '{ s = $0; gsub(a, "", s); if (s ~ k || tolower($0) ~ p) print }'
}
named() { # text on stdin; prints each token whose hash is listed (key = prefix of word-number)
  tr 'A-Z' 'a-z' | grep -oE '[a-z0-9]+(-[a-z0-9]+)+' | sort -u |
    awk -F- '{ t = $1; for (i = 2; i <= NF; i++) { t = t "-" $i; print "name " t }
               if ($2 ~ /^[0-9]+$/) print "key " $1 }' | sort -u |
    while read -r kind tok; do
      h=$(printf '%s' "$tok" | $SHA | cut -c1-64)
      if grep -qx "$kind $h" "$HASHES"; then echo "$tok"; fi
    done
}
check() { # label, text
  hits=$(printf '%s\n' "$2" | generic)
  names=$(printf '%s\n' "$2" | named)
  if [ -n "$hits$names" ]; then
    echo "internal reference in $1:"
    [ -z "$hits" ] || printf '%s\n' "$hits" | head -5
    [ -z "$names" ] || printf 'listed token: %s\n' $names
    fail=1
  fi
}

hits=$(git grep -In '' -- . ':!ci/check-public-clean.sh' ':!ci/test-public-clean.sh' | generic)
if [ -n "$hits" ]; then
  echo "internal reference in tracked files:"
  printf '%s\n' "$hits" | head -20
  fail=1
fi
names=$(git grep -Ih '' -- . | named)
if [ -n "$names" ]; then
  echo "listed token in tracked files:"
  for t in $names; do git grep -nIiF -e "$t" -- . | head -5; done
  fail=1
fi
[ -z "${CI_MERGE_REQUEST_TITLE:-}" ] || check "MR title" "$CI_MERGE_REQUEST_TITLE"
if [ -n "${CI_MERGE_REQUEST_DIFF_BASE_SHA:-}" ]; then
  check "commit messages" "$(git log --format=%B "$CI_MERGE_REQUEST_DIFF_BASE_SHA..HEAD")"
fi
[ "$fail" -eq 0 ] && echo "public-clean: ok"
exit "$fail"
