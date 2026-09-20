# Contributing Guide

Contributions sind willkommen!

## Development Setup

```bash
git clone <repo>
cd anviz-sdk-php
composer install
composer require --dev phpunit/phpunit phpstan/phpstan
```

## Code Standards

- PSR-12 Coding Standard
- PHPDoc für alle öffentlichen Methoden
- Type Hints für Parameter und Return Values
- Unit Tests für neue Features

## Checklist vor Pull Request

- [ ] Code folgt PSR-12
- [ ] PHPDoc ist vollständig
- [ ] Tests geschrieben und erfolgreich
- [ ] Keine Breaking Changes
- [ ] Dokumentation aktualisiert

## Testing

```bash
composer test
composer lint
composer cs-check
```

## Reporting Issues

Bitte füge folgende Informationen bei:
- PHP-Version
- Gerätetyp und Firmware
- Minimal reproducible code
- Error Message und Stack Trace
