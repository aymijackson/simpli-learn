#!/usr/bin/env bash
#
# Re-deploy front-end assets from this Laravel app into the web root on a
# cPanel server, where the app lives in a folder *next to* the domain's
# document root rather than inside it:
#
#   /home/<user>/
#   ├── e-library/                  <- this app (APP_DIR)
#   └── simpli-learn.hitmoh.com/    <- document root (PUBLIC_DIR)
#
# Run it on the server from anywhere:
#
#   bash deploy/deploy-assets.sh                       # default sibling folder
#   bash deploy/deploy-assets.sh --public-dir ../other.example.com
#   DEPLOY_PUBLIC_DIR=/home/me/public_html bash deploy/deploy-assets.sh
#
# The destination is resolved in this order:
#   1. --public-dir <path>
#   2. DEPLOY_PUBLIC_DIR environment variable
#   3. DEPLOY_PUBLIC_DIR in the app's .env
#   4. ../simpli-learn.hitmoh.com (sibling of the app folder)
# Relative paths are resolved against the app folder, not the current dir.
#
# What it does:
#   - replaces each folder in public/ (css/, js/, ...) in PUBLIC_DIR, so
#     files deleted from the app are deleted on the site too
#   - copies the loose files in public/ (favicon, robots.txt, .htaccess, ...)
#     but never overwrites PUBLIC_DIR/index.php
#   - removes a leftover Vite build/ folder from earlier deploys
#   - writes PUBLIC_DIR/index.php pointing at APP_DIR if it's missing
#     (or with --force-index)
#   - creates PUBLIC_DIR/storage as a real folder (the server has no symlink
#     support) and copies existing uploads into it; set PUBLIC_DISK_ROOT in
#     .env so new uploads are written there
#
# There is no build step: the assets in public/ are committed as-is and
# Tailwind runs in the browser (see resources/views/partials/assets.blade.php).

set -euo pipefail

DEFAULT_PUBLIC_DIR_NAME="simpli-learn.hitmoh.com"

usage() {
    cat <<EOF
Usage: $(basename "$0") [options]

Options:
  -d, --public-dir <path>  Destination document root
                           (default: ../${DEFAULT_PUBLIC_DIR_NAME} next to the app)
      --force-index        Regenerate PUBLIC_DIR/index.php even if it exists
  -n, --dry-run            Show what would happen without changing anything
  -h, --help               Show this help
EOF
}

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PUBLIC_DIR_ARG=""
FORCE_INDEX=0
DRY_RUN=0

while [[ $# -gt 0 ]]; do
    case "$1" in
        -d|--public-dir) PUBLIC_DIR_ARG="${2:?--public-dir needs a path}"; shift 2 ;;
        --public-dir=*)  PUBLIC_DIR_ARG="${1#*=}"; shift ;;
        --force-index)   FORCE_INDEX=1; shift ;;
        -n|--dry-run)    DRY_RUN=1; shift ;;
        -h|--help)       usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage >&2; exit 1 ;;
    esac
done

log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33mWARN:\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[1;31mERROR:\033[0m %s\n' "$*" >&2; exit 1; }
run()  { if [[ $DRY_RUN -eq 1 ]]; then echo "  [dry-run] $*"; else "$@"; fi; }

# Read a single key from .env without sourcing it (values may contain
# characters that aren't valid shell).
env_value() {
    [[ -f "$APP_DIR/.env" ]] || return 0
    { grep -E "^[[:space:]]*$1=" "$APP_DIR/.env" || true; } | tail -n1 | cut -d= -f2- \
        | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' -e 's/^"\(.*\)"$/\1/' -e "s/^'\(.*\)'$/\1/"
}

# --- Resolve destination ----------------------------------------------------

PUBLIC_DIR="${PUBLIC_DIR_ARG:-${DEPLOY_PUBLIC_DIR:-$(env_value DEPLOY_PUBLIC_DIR)}}"
PUBLIC_DIR="${PUBLIC_DIR:-../${DEFAULT_PUBLIC_DIR_NAME}}"
PUBLIC_DIR="${PUBLIC_DIR/#\~/$HOME}"
[[ "$PUBLIC_DIR" == /* ]] || PUBLIC_DIR="$APP_DIR/$PUBLIC_DIR"

[[ -d "$PUBLIC_DIR" ]] || die "Destination folder does not exist: $PUBLIC_DIR
Create it in cPanel (Domains -> document root) or pass --public-dir."
PUBLIC_DIR="$(cd "$PUBLIC_DIR" && pwd)"

[[ "$PUBLIC_DIR" != "$APP_DIR" && "$PUBLIC_DIR" != "$APP_DIR/public" ]] \
    || die "Destination must not be the app folder or its public/ folder: $PUBLIC_DIR"

log "App folder:  $APP_DIR"
log "Destination: $PUBLIC_DIR"
[[ $DRY_RUN -eq 1 ]] && log "Dry run - nothing will be changed"

# --- Public folders and files ----------------------------------------------
# Each folder is copied into a staging folder and swapped in, so the site
# never serves a half-copied folder and files removed from the app don't
# linger on the site.

log "Deploying public files"
shopt -s dotglob nullglob
for item in "$APP_DIR/public/"*; do
    name="$(basename "$item")"
    case "$name" in
        storage|hot|build|index.php) continue ;;
    esac

    if [[ -d "$item" ]]; then
        dest="$PUBLIC_DIR/$name"
        stage="$PUBLIC_DIR/.$name.new.$$"
        old="$PUBLIC_DIR/.$name.old.$$"
        run rm -rf "$stage"
        run cp -R "$item" "$stage"
        if [[ -e "$dest" ]]; then
            run mv "$dest" "$old"
        fi
        run mv "$stage" "$dest"
        run rm -rf "$old"
        echo "  $name/ ($(find "$item" -type f | wc -l | tr -d ' ') files)"
    else
        run cp "$item" "$PUBLIC_DIR/"
        echo "  $name"
    fi
done
shopt -u dotglob nullglob

# Earlier deploys used Vite, which wrote to build/. Only remove it if it
# really is a Vite build, so a folder someone else put there is left alone.
if [[ -f "$PUBLIC_DIR/build/manifest.json" ]]; then
    log "Removing old Vite build/ folder"
    run rm -rf "$PUBLIC_DIR/build"
fi

# --- index.php --------------------------------------------------------------
# The stock public/index.php uses __DIR__.'/../', which only works when the
# app is the parent folder. Write one that points at the sibling app folder
# and tells Laravel its public path is this document root (so asset()
# cache-busting reads file times from here).

relative_path() {
    if realpath --relative-to=/ / >/dev/null 2>&1; then
        realpath --relative-to="$2" "$1"
    else
        echo "$1"
    fi
}

INDEX="$PUBLIC_DIR/index.php"
if [[ ! -f "$INDEX" || $FORCE_INDEX -eq 1 ]]; then
    REL_APP="$(relative_path "$APP_DIR" "$PUBLIC_DIR")"
    if [[ "$REL_APP" == /* ]]; then
        APP_EXPR="'$REL_APP'"
    else
        APP_EXPR="__DIR__.'/$REL_APP'"
    fi
    if [[ -f "$INDEX" ]]; then
        log "Backing up existing index.php to index.php.bak"
        run cp "$INDEX" "$INDEX.bak"
    fi
    log "Writing index.php (app at $REL_APP)"
    if [[ $DRY_RUN -eq 0 ]]; then
        cat > "$INDEX" <<PHP
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Generated by deploy/deploy-assets.sh - the app lives outside the web root.
\$appPath = $APP_EXPR;

// Determine if the application is in maintenance mode...
if (file_exists(\$maintenance = \$appPath.'/storage/framework/maintenance.php')) {
    require \$maintenance;
}

// Register the Composer autoloader...
require \$appPath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application \$app */
\$app = require_once \$appPath.'/bootstrap/app.php';

\$app->usePublicPath(__DIR__);

\$app->handleRequest(Request::capture());
PHP
    fi
else
    log "Keeping existing index.php (use --force-index to regenerate)"
fi

# --- storage folder ---------------------------------------------------------
# The server doesn't allow symlinks, so storage:link can't be used. Instead
# the app's "public" disk writes straight into PUBLIC_DIR/storage, which
# requires PUBLIC_DISK_ROOT in .env (see config/filesystems.php). Any files
# already uploaded to the default location are copied across, never
# overwriting what's in the web root.

STORAGE_DIR="$PUBLIC_DIR/storage"
OLD_STORAGE="$APP_DIR/storage/app/public"

if [[ -L "$STORAGE_DIR" ]]; then
    warn "$STORAGE_DIR is a symlink - leaving it alone"
else
    if [[ ! -d "$STORAGE_DIR" ]]; then
        log "Creating $STORAGE_DIR"
        run mkdir -p "$STORAGE_DIR"
    fi
    if [[ -d "$OLD_STORAGE" ]] && [[ -n "$(ls -A "$OLD_STORAGE" 2>/dev/null | grep -v '^\.gitignore$')" ]]; then
        log "Copying existing uploads from storage/app/public (no overwrite)"
        run cp -Rn "$OLD_STORAGE/." "$STORAGE_DIR/"
    fi

    DISK_ROOT="$(env_value PUBLIC_DISK_ROOT)"
    if [[ -z "$DISK_ROOT" ]]; then
        warn "PUBLIC_DISK_ROOT is not set in .env - uploads will not be visible on the site.
      Add this to $APP_DIR/.env, then run: php artisan config:clear
      PUBLIC_DISK_ROOT=$(relative_path "$STORAGE_DIR" "$APP_DIR")"
    else
        [[ "$DISK_ROOT" == /* ]] || DISK_ROOT="$APP_DIR/$DISK_ROOT"
        if [[ ! -d "$DISK_ROOT" ]] || [[ "$(cd "$DISK_ROOT" && pwd)" != "$STORAGE_DIR" ]]; then
            [[ $DRY_RUN -eq 1 && ! -d "$DISK_ROOT" ]]                 || warn "PUBLIC_DISK_ROOT ($(env_value PUBLIC_DISK_ROOT)) does not point at $STORAGE_DIR"
        fi
    fi
fi

log "Done."
