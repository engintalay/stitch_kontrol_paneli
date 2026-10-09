#!/usr/bin/env bash
# setup_permissions.sh — Veritabanı dosyaları ve hassas dizinler için
# güvenli izinleri uygular. Deploy sonrası veya izinler bozulduğunda çalıştırılmalıdır.
#
# Not: Git dosya izinlerini (640/750 gibi) saklamaz, sadece executable bit'i takip
# eder. Bu nedenle bu betik deploy sürecinin bir parçası olarak çalıştırılmalıdır.

set -euo pipefail
cd "$(dirname "$0")"

echo "Veritabanı dosyaları için izinler ayarlanıyor (640: sahip rw, grup r, diğer -)..."
chmod 640 *.db 2>/dev/null || true
chmod 640 yapay_zeka_sohbet_botu/chat.db 2>/dev/null || true
chmod 640 medya_tarayıcı/album/thumbnails.db 2>/dev/null || true

echo "Modül dizinleri için izinler ayarlanıyor (750: sahip rwx, grup rx, diğer -)..."
chmod 750 kontrol_paneli medya_tarayıcı sistem_monitörü yapay_zeka_sohbet_botu 2>/dev/null || true

echo "Tamamlandı. Güncel izinler:"
ls -la *.db
ls -ld kontrol_paneli medya_tarayıcı sistem_monitörü yapay_zeka_sohbet_botu
