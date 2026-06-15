#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

BOOTSTRAP_PORT="${DNS_PANEL_BOOTSTRAP_PORT:-8080}"
CERT_MODE="${DNS_PANEL_CERT_MODE:-acme}"
CERT_DAYS="${DNS_PANEL_CERT_DAYS:-825}"
CERT_RENEW_DAYS="${DNS_PANEL_CERT_RENEW_DAYS:-30}"
CERT_DIR="${DNS_PANEL_CERT_DIR:-certs}"
CERT_KEY="${CERT_DIR}/bootstrap.key"
CERT_CRT="${CERT_DIR}/bootstrap.crt"
CERT_PEM="${CERT_DIR}/bootstrap.pem"
CERTBOT_IMAGE="${DNS_PANEL_CERTBOT_IMAGE:-certbot/certbot:latest}"
ACME_EMAIL="${DNS_PANEL_ACME_EMAIL:-jmouriz@tecnologica.ar}"
ACME_CERT_NAME="${DNS_PANEL_ACME_CERT_NAME:-dns-panel-ip}"
ACME_PROFILE="${DNS_PANEL_ACME_PROFILE:-shortlived}"
ACME_DNS_NAMES="${DNS_PANEL_ACME_DNS_NAMES:-terminus.tecnologica.ar}"
ACME_WEBROOT="${DNS_PANEL_ACME_WEBROOT:-/home/juanma/docker/mailcow/data/web}"
ACME_DIR="${DNS_PANEL_ACME_DIR:-$(pwd)/certs/letsencrypt}"
ACME_LIB_DIR="${DNS_PANEL_ACME_LIB_DIR:-$(pwd)/certs/lib-letsencrypt}"
ACME_STAGING="${DNS_PANEL_ACME_STAGING:-0}"
STORAGE_DIR="${DNS_PANEL_STORAGE_DIR:-www/storage}"
PANEL_DB="${STORAGE_DIR}/panel.sqlite"
PANEL_UPLOADS="${STORAGE_DIR}/uploads"
ACTION="${1:-up}"
SCRIPT_PATH="$(pwd)/$(basename "$0")"
CRON_MARKER="dns-panel bootstrap certificate renewal"

detect_ip() {
  if [ -n "${BOOTSTRAP_IP:-}" ]; then
    printf '%s\n' "$BOOTSTRAP_IP"
    return
  fi

  ip -4 route get 1.1.1.1 2>/dev/null \
    | awk '{ for (i = 1; i <= NF; i++) if ($i == "src") { print $(i + 1); exit } }'
}

compose() {
  if docker compose version >/dev/null 2>&1; then
    docker compose "$@"
  elif command -v docker-compose >/dev/null 2>&1; then
    docker-compose "$@"
  else
    echo "No se encontró docker compose ni docker-compose." >&2
    exit 1
  fi
}

certificate_needs_renewal() {
  if [ ! -s "$CERT_PEM" ] || [ ! -s "$CERT_CRT" ] || [ ! -s "$CERT_KEY" ]; then
    return 0
  fi

  if ! openssl x509 -in "$CERT_CRT" -noout -text | grep -q "IP Address:${ip_address}"; then
    return 0
  fi

  if ! openssl x509 -in "$CERT_CRT" -noout -checkend "$((CERT_RENEW_DAYS * 86400))" >/dev/null; then
    return 0
  fi

  return 1
}

generate_certificate() {
  echo "Generando certificado bootstrap para ${ip_address}..."

  openssl req \
    -x509 \
    -newkey rsa:4096 \
    -sha256 \
    -days "$CERT_DAYS" \
    -nodes \
    -keyout "$CERT_KEY" \
    -out "$CERT_CRT" \
    -subj "/CN=${ip_address}" \
    -addext "subjectAltName=IP:${ip_address},DNS:terminus.tecnologica.ar,DNS:localhost,IP:127.0.0.1"

  cat "$CERT_CRT" "$CERT_KEY" > "$CERT_PEM"
  chmod 600 "$CERT_KEY" "$CERT_PEM"
  chmod 644 "$CERT_CRT"
}

certificate_fingerprint() {
  if [ -s "$CERT_PEM" ]; then
    openssl x509 -in "$CERT_PEM" -noout -fingerprint -sha256 2>/dev/null || true
  fi
}

certbot() {
  docker run --rm --network host \
    -v "${ACME_DIR}:/etc/letsencrypt" \
    -v "${ACME_LIB_DIR}:/var/lib/letsencrypt" \
    -v "${ACME_WEBROOT}:/var/www/acme" \
    "$CERTBOT_IMAGE" "$@"
}

path_exists() {
  [ -e "$1" ] || { command -v sudo >/dev/null 2>&1 && sudo -n test -e "$1"; }
}

write_acme_pem() {
  local fullchain="${ACME_DIR}/live/${ACME_CERT_NAME}/fullchain.pem"
  local privkey="${ACME_DIR}/live/${ACME_CERT_NAME}/privkey.pem"

  if ! path_exists "$fullchain" || ! path_exists "$privkey"; then
    echo "No se encontraron archivos ACME para ${ACME_CERT_NAME}." >&2
    return 1
  fi

  if [ -r "$fullchain" ] && [ -r "$privkey" ]; then
    cat "$fullchain" "$privkey" > "$CERT_PEM"
  elif command -v sudo >/dev/null 2>&1; then
    sudo -n sh -c "cat '$fullchain' '$privkey' > '$CERT_PEM'"
    sudo -n chown "$(id -u):$(id -g)" "$CERT_PEM"
  else
    echo "No hay permisos para leer el certificado ACME." >&2
    return 1
  fi

  chmod 600 "$CERT_PEM"
}

issue_acme_certificate() {
  local staging_args=()
  local identifier_args=(--ip-address "$ip_address")

  if [ "$ACME_STAGING" = "1" ]; then
    staging_args=(--staging)
  fi

  for dns_name in ${ACME_DNS_NAMES//,/ }; do
    if [ -n "$dns_name" ]; then
      identifier_args+=(-d "$dns_name")
    fi
  done

  mkdir -p "$ACME_DIR" "$ACME_LIB_DIR"

  certbot certonly \
    "${staging_args[@]}" \
    --non-interactive \
    --agree-tos \
    --email "$ACME_EMAIL" \
    --webroot \
    -w /var/www/acme \
    "${identifier_args[@]}" \
    --cert-name "$ACME_CERT_NAME" \
    --preferred-profile "$ACME_PROFILE"

  write_acme_pem
}

renew_acme_certificate() {
  mkdir -p "$ACME_DIR" "$ACME_LIB_DIR"

  certbot renew \
    --cert-name "$ACME_CERT_NAME" \
    --preferred-profile "$ACME_PROFILE"

  write_acme_pem
}

ensure_certificate() {
  mkdir -p "$CERT_DIR"
  chmod 700 "$CERT_DIR"

  if [ "$CERT_MODE" = "acme" ]; then
    if path_exists "${ACME_DIR}/live/${ACME_CERT_NAME}/fullchain.pem"; then
      renew_acme_certificate
    else
      issue_acme_certificate
    fi
    return 0
  fi

  if certificate_needs_renewal; then
    generate_certificate
    return 0
  fi

  echo "Usando certificado bootstrap autofirmado existente para ${ip_address}."
  return 1
}

restart_if_running() {
  if docker ps --format '{{.Names}}' | grep -qx 'dns-panel'; then
    compose restart panel
  fi
}

read_secret() {
  local prompt="$1"
  local value

  if [ ! -t 0 ]; then
    echo "No se puede pedir $prompt en modo no interactivo." >&2
    return 1
  fi

  read -r -s -p "$prompt" value
  echo >&2
  printf '%s\n' "$value"
}

read_value() {
  local prompt="$1"
  local default="$2"
  local value

  if [ ! -t 0 ]; then
    printf '%s\n' "$default"
    return
  fi

  read -r -p "$prompt [$default]: " value
  printf '%s\n' "${value:-$default}"
}

resolve_pdns_api_key() {
  if [ -n "${PDNS_API_KEY:-}" ]; then
    printf '%s\n' "$PDNS_API_KEY"
    return
  fi

  if docker ps --format '{{.Names}}' | grep -qx 'power-dns'; then
    docker exec power-dns sh -lc "grep '^api-key=' /etc/pdns/pdns.conf | cut -d= -f2-"
  fi
}

ensure_panel_installation() {
  if [ -s "$PANEL_DB" ]; then
    return 0
  fi

  echo "No existe ${PANEL_DB}; se inicializará la configuración del panel."

  local admin_user="${DNS_PANEL_ADMIN_USER:-}"
  local admin_display_name="${DNS_PANEL_ADMIN_DISPLAY_NAME:-}"
  local admin_password="${DNS_PANEL_ADMIN_PASSWORD:-}"
  local admin_password_confirm="${DNS_PANEL_ADMIN_PASSWORD:-}"
  local pdns_api_key
  local pdns_url="${PDNS_URL:-http://170.78.75.49:8081}"
  local pdns_server_id="${PDNS_SERVER_ID:-localhost}"
  local panel_title="${DNS_PANEL_TITLE:-DNS Control Panel}"
  local panel_subtitle="${DNS_PANEL_SUBTITLE:-PowerDNS Administration}"
  local panel_logo="${DNS_PANEL_LOGO:-}"

  admin_user="${admin_user:-$(read_value 'Usuario administrador' 'admin')}"
  admin_display_name="${admin_display_name:-$(read_value 'Nombre visible del administrador' 'Administrator')}"

  if [ -z "$admin_password" ]; then
    admin_password="$(read_secret 'Clave del administrador: ')"
    admin_password_confirm="$(read_secret 'Confirmar clave del administrador: ')"
  fi

  if [ -z "$admin_password" ]; then
    echo "La clave del administrador no puede estar vacía." >&2
    exit 1
  fi

  if [ "$admin_password" != "$admin_password_confirm" ]; then
    echo "Las claves del administrador no coinciden." >&2
    exit 1
  fi

  pdns_api_key="$(resolve_pdns_api_key)"
  if [ -z "$pdns_api_key" ]; then
    echo "No se pudo detectar PDNS_API_KEY. Ejecutá export PDNS_API_KEY=... antes del bootstrap." >&2
    exit 1
  fi

  mkdir -p "$PANEL_UPLOADS"

  docker run --rm \
    -v "$(pwd)/${STORAGE_DIR}:/var/www/localhost/htdocs/storage" \
    -e PDNS_API_KEY="$pdns_api_key" \
    -e PDNS_URL="$pdns_url" \
    -e PDNS_SERVER_ID="$pdns_server_id" \
    -e DNS_PANEL_ADMIN_USER="$admin_user" \
    -e DNS_PANEL_ADMIN_DISPLAY_NAME="$admin_display_name" \
    -e DNS_PANEL_ADMIN_PASSWORD="$admin_password" \
    -e DNS_PANEL_TITLE="$panel_title" \
    -e DNS_PANEL_SUBTITLE="$panel_subtitle" \
    -e DNS_PANEL_LOGO="$panel_logo" \
    dns-panel-panel:latest \
    php /usr/local/bin/bootstrap-install-panel.php

  echo "Panel inicializado. Recordá guardar la clave del usuario ${admin_user}."
}

install_renewal_cron() {
  if ! command -v crontab >/dev/null 2>&1; then
    echo "No se encontró crontab; no se pudo instalar renovación automática." >&2
    return 0
  fi

  local tmp
  tmp="$(mktemp)"

  crontab -l 2>/dev/null | grep -v "$CRON_MARKER" > "$tmp" || true
  {
    cat "$tmp"
    printf '17 3 * * * cd %s && BOOTSTRAP_IP=%s DNS_PANEL_CERT_MODE=%s DNS_PANEL_ACME_EMAIL=%s DNS_PANEL_ACME_CERT_NAME=%s DNS_PANEL_ACME_PROFILE=%s DNS_PANEL_ACME_DNS_NAMES=%s DNS_PANEL_ACME_WEBROOT=%s %s renew >> bootstrap-renew.log 2>&1 # %s\n' \
      "$(pwd)" "$ip_address" "$CERT_MODE" "$ACME_EMAIL" "$ACME_CERT_NAME" "$ACME_PROFILE" "$ACME_DNS_NAMES" "$ACME_WEBROOT" "$SCRIPT_PATH" "$CRON_MARKER"
  } | crontab -

  rm -f "$tmp"
  echo "Renovación automática instalada en crontab."
}

ip_address="$(detect_ip)"

if [ -z "$ip_address" ]; then
  echo "No se pudo detectar la IP bootstrap. Ejecutá: BOOTSTRAP_IP=170.78.75.49 ./bootstrap.sh" >&2
  exit 1
fi

if ! command -v openssl >/dev/null 2>&1; then
  echo "OpenSSL no está instalado en el host; es necesario para generar el certificado bootstrap." >&2
  exit 1
fi

case "$ACTION" in
  up)
    ensure_certificate || true
    ;;
  renew)
    before="$(certificate_fingerprint)"
    if ensure_certificate; then
      after="$(certificate_fingerprint)"
      if [ "$before" != "$after" ]; then
        restart_if_running
      fi
    fi
    exit 0
    ;;
  install-renewal)
    install_renewal_cron
    exit 0
    ;;
  *)
    echo "Uso: $0 [up|renew|install-renewal]" >&2
    exit 1
    ;;
esac

echo "Levantando dns-panel..."
export DNS_PANEL_BOOTSTRAP_PORT="$BOOTSTRAP_PORT"

docker build --network host -t dns-panel-panel:latest .
ensure_panel_installation
compose up -d --no-build

if [ "${DNS_PANEL_INSTALL_RENEWAL:-1}" = "1" ]; then
  install_renewal_cron
fi

echo
echo "Panel DNS disponible en:"
echo "  https://${ip_address}:${BOOTSTRAP_PORT}/"
echo
echo "Redirección HTTP en el mismo puerto:"
echo "  http://${ip_address}:${BOOTSTRAP_PORT}/ -> https://${ip_address}:${BOOTSTRAP_PORT}/"
echo
echo "Certificado:"
if [ "$CERT_MODE" = "acme" ]; then
  echo "  ACME/Let's Encrypt para IP (${ACME_PROFILE})"
  echo "  se revisa automáticamente todos los días"
else
  echo "  autofirmado, válido por ${CERT_DAYS} días"
  echo "  se renueva automáticamente cuando quedan menos de ${CERT_RENEW_DAYS} días"
fi
echo
if [ "$CERT_MODE" != "acme" ]; then
  echo "Nota: este certificado es autofirmado para bootstrap. El navegador puede pedir confirmación manual."
fi
