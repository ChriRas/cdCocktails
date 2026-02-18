#!/usr/bin/env sh
set -eu

ROOT="/data"
IN="$ROOT/incoming"
FULL="$ROOT/full"
TH="$ROOT/thumbs"
DATA="$ROOT/data"

INFO_IN="$IN/info.yml"
INFO_OUT="$DATA/info.yml"

mkdir -p "$FULL" "$TH" "$DATA"

# --- Helper: check if incoming has new image uploads (any file except info.yml) ---
has_new_uploads() {
  find "$IN" -maxdepth 1 -type f ! -name "info.yml" \
    \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" -o -iname "*.webp" -o -iname "*.gif" \) \
    -print -quit | grep -q .
}

# --- Helper: check if processed folders contain files older than 5 minutes ---
processed_older_than_5min() {
  find "$FULL" "$TH" -type f -mmin +5 -print -quit | grep -q .
}

# Wenn neue Uploads vorhanden sind UND die aktuellen full/thumbs älter als 5min sind,
# dann handelt es sich um einen neuen Upload-Batch => alles alte löschen
if has_new_uploads && processed_older_than_5min; then
  rm -f "$FULL"/* 2>/dev/null || true
  rm -f "$TH"/* 2>/dev/null || true
  rm -f "$INFO_OUT" 2>/dev/null || true
fi

# info.yml übernehmen (wenn vorhanden)
if [ -f "$INFO_IN" ]; then
  cp -f "$INFO_IN" "$INFO_OUT"
  rm -f "$INFO_IN"
fi

# --- filename normalization (slugify) ---
# - lowercase
# - spaces/underscores -> -
# - remove non [a-z0-9-]
# - collapse multiple -
slugify() {
  # shellcheck disable=SC2001
  echo "$1" \
    | tr '[:upper:]' '[:lower:]' \
    | sed -E 's/[ _]+/-/g; s/[^a-z0-9-]+//g; s/-+/-/g; s/^-+//; s/-+$//'
}

# Generate unique target name if collision
unique_name() {
  base="$1"  # without extension
  n=0
  candidate="$base"
  while [ -e "$FULL/$candidate.webp" ] || [ -e "$TH/$candidate.webp" ]; do
    n=$((n+1))
    candidate="${base}-${n}"
  done
  echo "$candidate"
}

# --- Process incoming images -> WEBP only, then delete originals ---
for f in "$IN"/*; do
  [ -f "$f" ] || continue
  bn="$(basename "$f")"
  [ "$bn" = "info.yml" ] && continue

  case "$bn" in
    *.jpg|*.jpeg|*.png|*.webp|*.gif|*.JPG|*.JPEG|*.PNG|*.WEBP|*.GIF) ;;
    *) continue ;;
  esac

  name_no_ext="${bn%.*}"
  slug="$(slugify "$name_no_ext")"
  [ -z "$slug" ] && slug="bild"

  uniq="$(unique_name "$slug")"

  out_full="$FULL/$uniq.webp"
  out_th="$TH/$uniq.webp"

  # Full: max width/height 1440, keep aspect, optimize
  magick "$f" -auto-orient -strip -resize "1440x1440>" -quality 82 "$out_full"

  # Thumbs: 1-spaltig => ruhig etwas breiter, portrait crop 900x1200
  magick "$f" -auto-orient -strip -resize "1080x1080>" -quality 78 "$out_th"

  rm -f "$f"
done

echo "Done."