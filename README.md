# ACTIO

Webová aplikace pro správu akčních plánů z auditů.

## Požadavky

- PHP 8.0+
- Web server (Apache/Nginx) nebo PHP built-in server

## Instalace

1. **Klonování repozitáře**
   ```bash
   git clone <repository-url>
   cd actio
   ```

2. **Konfigurace**
   ```bash
   cp .env.example .env
   # Upravte .env podle vašeho prostředí
   ```

3. **Inicializace dat**
   ```bash
   cp data/actions.example.json data/actions.json
   cp data/audit_sessions.example.json data/audit_sessions.json
   cp data/audit_log.example.json data/audit_log.json
   cp data/settings.example.json data/settings.json
   ```

4. **Spuštění vývojového serveru**
   ```bash
   php -S localhost:8080 -t public
   ```

5. **Otevřete v prohlížeči**
   ```
   http://localhost:8080
   ```

## Struktura projektu

```
actio/
├── public/          # Document root (Front Controller)
├── src/             # PHP zdrojové soubory
│   ├── Controllers/ # Kontrolery
│   ├── Services/    # Business logika
│   ├── Models/      # Datové modely
│   ├── Core/        # Jádro aplikace
│   └── Helpers/     # Pomocné funkce
├── views/           # PHP šablony
├── data/            # JSON úložiště
├── assets/          # CSS, JS, fonty
├── config/          # Konfigurace
└── dashio-template/ # Zdrojová šablona (reference)
```

## Dokumentace

- [PROJECT_GUIDE.md](PROJECT_GUIDE.md) - Kompletní projektová dokumentace
- [SECURITY_REQUIREMENTS.md](SECURITY_REQUIREMENTS.md) - Bezpečnostní požadavky

## Vývoj

### Dev mode
Pro lokální vývoj nastavte v `.env`:
```env
APP_ENV=development
DEV_MODE=true
```

### Dashio template
Pro referenci spusťte dashio-template:
```bash
cd dashio-template
npm install
npm run dev
```

## Licence

Proprietární software. Všechna práva vyhrazena.
