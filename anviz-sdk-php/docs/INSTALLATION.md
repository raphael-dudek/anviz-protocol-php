# Installation Guide

## Anforderungen

- PHP >= 7.4
- ext-sockets aktiviert
- Composer

## Lokale Installation

### 1. Repository klonen

```bash
git clone https://github.com/yourusername/anviz-sdk-php.git
cd anviz-sdk-php
```

### 2. Dependencies installieren

```bash
composer install
```

### 3. Logs-Verzeichnis erstellen

```bash
mkdir -p logs
chmod 777 logs
```

### 4. Beispiel ausführen

```bash
php examples/01_connect.php
```

## Docker Installation

### Voraussetzungen

- Docker
- Docker Compose

### 1. Projekt klonen

```bash
git clone https://github.com/yourusername/anviz-sdk-php.git
cd anviz-sdk-php
```

### 2. Container starten

```bash
docker-compose up -d
```

### 3. Dependencies installieren

```bash
docker-compose exec anviz-sdk composer install
```

### 4. Beispiel ausführen

```bash
docker-compose exec anviz-sdk php examples/01_connect.php
```

### Container-Befehle

```bash
# Shell öffnen
docker-compose exec anviz-sdk /bin/sh

# Logs anzeigen
docker-compose logs -f anviz-sdk

# Container stoppen
docker-compose down

# Container neu bauen
docker-compose up -d --build
```

## Socket-Erweiterung aktivieren

### Linux (Ubuntu/Debian)

```bash
sudo apt-get install php-sockets
sudo service apache2 restart
# oder
sudo service php-fpm restart
```

### Linux (CentOS/RHEL)

```bash
sudo yum install php-sockets
sudo systemctl restart httpd
```

### Windows

1. Öffne `php.ini`
2. Suche nach `extension=sockets`
3. Entferne das Semikolon am Anfang (`;`)
4. Speichere und starte Apache/PHP neu

### macOS

```bash
# Mit Homebrew
brew install php@8.1-sockets
```

## Umgebungsvariablen

Erstelle eine `.env`-Datei im Projektverzeichnis:

```env
ANVIZ_HOST=192.168.1.100
ANVIZ_PORT=5010
ANVIZ_DEVICE_ID=1
ANVIZ_TIMEOUT=5
LOG_LEVEL=INFO
```

Die Beispiele verwenden diese automatisch, wenn Sie sie setzen.

## Troubleshooting

### "Socket konnte nicht erstellt werden" oder "Call to undefined function socket_create()"

- Stellen Sie sicher, dass die PHP-Erweiterung `sockets` aktiviert ist.
- Prüfen Sie dies mit dem Befehl: `php -m | findstr sockets` (Windows) oder `php -m | grep sockets` (Linux).
- Falls die Erweiterung fehlt: Folgen Sie den Anweisungen unter "Socket-Erweiterung aktivieren".
- Starten Sie Ihren Webserver oder Ihre PHP-Konsole nach der Änderung der `php.ini` neu.

### "Verbindung fehlgeschlagen"

- IP-Adresse und Port prüfen
- Gerät ist online? Ping test: `ping 192.168.1.100`
- Firewall prüfen - Port 5010 muss offen sein
- Auf dem gleichen Netzwerk wie das Gerät?

### "Timeout"

- Timeout in AnvizClient erhöhen: `new AnvizClient($host, $port, $id, 10)`
- Gerät reagiert zu langsam?
- Netzwerkverbindung überprüfen

### "Ungültige CRC"

- Netzwerkprobleme - Verbindung neu aufbauen
- Datenübertragungsfehler
- Gerät nicht kompatibel?
