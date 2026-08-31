#!/usr/bin/env sh
set -eu

project_root=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
output_dir=${1:-"$project_root/release"}

mkdir -p "$output_dir"
rm -f \
  "$output_dir/eno-workbench.zip" \
  "$output_dir/eno-workbench.tar.gz" \
  "$output_dir/eno-workbench-content-importer.zip" \
  "$output_dir/eno-workbench-content-importer.tar.gz" \
  "$output_dir/SHA256SUMS"

(
  cd "$project_root/wordpress-theme"
  zip -qr "$output_dir/eno-workbench.zip" eno-workbench
  tar -czf "$output_dir/eno-workbench.tar.gz" eno-workbench
)

(
  cd "$project_root/content-importer"
  zip -qr "$output_dir/eno-workbench-content-importer.zip" \
    eno-workbench-content-importer.php README.md content
)

tar -czf "$output_dir/eno-workbench-content-importer.tar.gz" \
  -C "$project_root" content-importer

(
  cd "$output_dir"
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum \
      eno-workbench.zip \
      eno-workbench.tar.gz \
      eno-workbench-content-importer.zip \
      eno-workbench-content-importer.tar.gz > SHA256SUMS
  else
    shasum -a 256 \
      eno-workbench.zip \
      eno-workbench.tar.gz \
      eno-workbench-content-importer.zip \
      eno-workbench-content-importer.tar.gz > SHA256SUMS
  fi
)

printf 'WordPress release artifacts: %s\n' "$output_dir"
