#!/usr/bin/env sh
set -eu

release_id=${1:?missing release id}
theme_archive=${2:?missing theme archive}
plugin_archive=${3:?missing plugin archive}

case "$release_id" in
  *[!a-f0-9]*)
    echo "Invalid release id: $release_id" >&2
    exit 1
    ;;
esac

for archive in "$theme_archive" "$plugin_archive"; do
  case "$archive" in
    /tmp/eno-ci-*.tar.gz) ;;
    *)
      echo "Refusing unexpected archive path: $archive" >&2
      exit 1
      ;;
  esac
  test -s "$archive"
done

trap 'rm -f "$theme_archive" "$plugin_archive"' EXIT HUP INT TERM

container=$(docker ps \
  --filter label=com.docker.compose.project=personal-blog \
  --filter label=com.docker.compose.service=wordpress \
  --format '{{.ID}}')
test -n "$container"

container_theme_archive="/tmp/eno-theme-$release_id.tar.gz"
container_plugin_archive="/tmp/eno-plugin-$release_id.tar.gz"
docker cp "$theme_archive" "$container:$container_theme_archive"
docker cp "$plugin_archive" "$container:$container_plugin_archive"

docker exec --user root \
  -e ENO_RELEASE_ID="$release_id" \
  -e ENO_THEME_ARCHIVE="$container_theme_archive" \
  -e ENO_PLUGIN_ARCHIVE="$container_plugin_archive" \
  "$container" sh -eu -c '
    theme_root=/var/www/html/wp-content/themes
    plugin_root=/var/www/html/wp-content/plugins
    work_dir="/tmp/eno-release-$ENO_RELEASE_ID"
    theme_stage="$theme_root/.eno-workbench-$ENO_RELEASE_ID"
    plugin_stage="$plugin_root/.eno-workbench-content-importer-$ENO_RELEASE_ID"
    theme_previous="$theme_root/.eno-workbench-previous"
    plugin_previous="$plugin_root/.eno-workbench-content-importer-previous"

    cleanup() {
      rm -rf "$work_dir" "$theme_stage" "$plugin_stage"
      rm -f "$ENO_THEME_ARCHIVE" "$ENO_PLUGIN_ARCHIVE"
    }
    trap cleanup EXIT HUP INT TERM

    rm -rf "$work_dir" "$theme_stage" "$plugin_stage"
    mkdir -p "$work_dir/theme" "$work_dir/plugin"
    tar -xzf "$ENO_THEME_ARCHIVE" -C "$work_dir/theme"
    tar -xzf "$ENO_PLUGIN_ARCHIVE" -C "$work_dir/plugin"

    test -f "$work_dir/theme/eno-workbench/style.css"
    test -f "$work_dir/theme/eno-workbench/functions.php"
    test -f "$work_dir/plugin/content-importer/eno-workbench-content-importer.php"

    mv "$work_dir/theme/eno-workbench" "$theme_stage"
    mv "$work_dir/plugin/content-importer" "$plugin_stage"
    chown -R www-data:www-data "$theme_stage" "$plugin_stage"

    rm -rf "$theme_previous" "$plugin_previous"
    if test -d "$theme_root/eno-workbench"; then
      mv "$theme_root/eno-workbench" "$theme_previous"
    fi
    if ! mv "$theme_stage" "$theme_root/eno-workbench"; then
      test ! -d "$theme_previous" || mv "$theme_previous" "$theme_root/eno-workbench"
      exit 1
    fi

    if test -d "$plugin_root/eno-workbench-content-importer"; then
      mv "$plugin_root/eno-workbench-content-importer" "$plugin_previous"
    fi
    if ! mv "$plugin_stage" "$plugin_root/eno-workbench-content-importer"; then
      rm -rf "$theme_root/eno-workbench"
      test ! -d "$theme_previous" || mv "$theme_previous" "$theme_root/eno-workbench"
      test ! -d "$plugin_previous" || mv "$plugin_previous" "$plugin_root/eno-workbench-content-importer"
      exit 1
    fi

    rm -rf "$theme_previous" "$plugin_previous"
  '

echo "Deployed WordPress release $release_id"
