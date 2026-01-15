# Projekt: 8D Reporting System - Dokumentace

## Kontext a motivace

V rámci řešení zákaznických reklamací a interních neshod používáme metodiku **8D (8 Disciplines)** pro systematické řešení problémů. Současný proces zahrnuje ruční vyplňování Word šablon, což je:
- Časově náročné
- Náchylné k chybám
- Obtížně verzovatelné
- Komplikované při sdílení a prezentaci

## Cíl projektu

Vytvořit **automatizovaný systém** pro generování 8D reportů:
1. **AI asistent (Claude)** v projektu průvodí procesem 8D metodiky
2. **Výstupy z chatů** se strukturovaně zapisují do JSON souborů
3. **HTML šablona** načítá JSON a zobrazuje data v čitelné, profesionální podobě

## Architektura řešení

```
┌─────────────────────────────────────────────────────────────┐
│  Claude AI (8D Project)                                      │
│  - Průvodce procesem D1 až D8                                │
│  - Generuje strukturované výstupy                            │
│  - Zapisuje JSON přes MCP Desktop Commander                  │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  JSON Soubory (8D Cases)                                     │
│  - Lokace: C:\Projects\eydio\8D_PC-XXX_v1.0.json            │
│  - Nebo: SharePoint Foundation 2010                          │
│  - Nebo: SharePoint Online                                   │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  HTML/JS Viewer Template                                     │
│  - Načítá JSON z konfigurovatelné lokace                     │
│  - Renderuje 8D report v lidské podobě                       │
│  - Export do PDF (volitelně)                                 │
└─────────────────────────────────────────────────────────────┘
```

## Workflow řešení reklamace

### Fáze 1: Průchod 8D procesem v Claude

```
Uživatel zahájí nový chat pro reklamaci PC-XXX
    ↓
D1: Sestavení týmu
    → Claude vygeneruje JSON fragment pro D1
    → Zapíše do C:\Projects\eydio\8D_PC-XXX_v1.0.json
    ↓
D2: Popis problému + analýza je/není
    → Claude aktualizuje JSON (přidá D2)
    ↓
D3: Okamžitá opatření
    → Claude aktualizuje JSON (přidá D3)
    ↓
D4: Analýza kořenových příčin (5-Why, Ishikawa)
    → Claude aktualizuje JSON (přidá D4)
    ↓
D5: Výběr a ověření nápravných opatření
    → Claude aktualizuje JSON (přidá D5)
    ↓
D6: Realizace a validace opatření
    → Claude aktualizuje JSON (přidá D6)
    ↓
D7: Zabránění opakování (Lessons Learned)
    → Claude aktualizuje JSON (přidá D7)
    ↓
D8: Závěr a ocenění týmu
    → Claude finalizuje JSON (přidá D8, status: "closed")
```

### Fáze 2: Prezentace 8D reportu

```
Uživatel otevře HTML viewer
    ↓
Viewer načte konfiguraci (config.json):
    - data_source: "local|sharepoint_foundation|sharepoint_online"
    - path: "C:\Projects\eydio" nebo SharePoint URL
    ↓
Viewer zobrazí seznam dostupných 8D případů
    ↓
Uživatel vybere PC-XXX
    ↓
Viewer načte 8D_PC-XXX_v1.0.json
    ↓
Renderuje report v přehledné HTML struktuře
    ↓
Možnost exportu do PDF (volitelně)
```

## JSON Struktura

Kompletní struktura je definována v samostatném souboru `8D_structure.json` (viz výše).

### Klíčové vlastnosti:
- **Monolitický soubor** - jeden JSON pro celý 8D případ
- **Inkrementální naplňování** - po každém D kroku se aktualizuje
- **Verzování** - `meta.verze` + datum poslední aktualizace
- **Reference mezi kroky** - např. D6 validuje opatření z D5 přes `opatreni_id`
- **Rekurzivní struktury** - D4 příčiny mohou mít libovolnou hloubku (5-Why → 7-Why)

## Konfigurace HTML Vieweru

HTML viewer bude mít konfigurační soubor `config.json`:

```json
{
  "data_source": "local|sharepoint_foundation|sharepoint_online",
  "paths": {
    "local": "C:\\Projects\\eydio",
    "sharepoint_foundation": "http://ocm-oiles.ad-oiles.navi/G8D/Dokumenty/",
    "sharepoint_online": "https://oilesjp.sharepoint.com/sites/Kvalita/8D/"
  },
  "active_source": "local",
  "language": "cs",
  "theme": "light|dark"
}
```

### Podporované zdroje dat:

#### 1. Lokální PC
```
Cesta: C:\Projects\eydio\
Přístup: Přímý file system read (File API)
Formát: 8D_PC-XXX_v1.0.json
```

#### 2. SharePoint Foundation 2010
```
URL: http://ocm-oiles.ad-oiles.navi/G8D/Dokumenty/
Přístup: REST API nebo WebDAV
Autentizace: Windows Authentication (NTLM)
```

#### 3. SharePoint Online
```
URL: https://oilesjp.sharepoint.com/sites/Kvalita/8D/
Přístup: Microsoft Graph API
Autentizace: OAuth 2.0
Příklad souboru: template.json
```

## Technické požadavky na HTML Viewer

### Základní funkce (MVP):
- [ ] Načtení config.json
- [ ] Přepínání mezi zdroji dat (local/SP Foundation/SP Online)
- [ ] Seznam dostupných 8D případů
- [ ] Načtení a parsování JSON
- [ ] Renderování D1-D8 sekce s must_have položkami
- [ ] Responsivní design (desktop/tablet/mobile)

### Rozšířené funkce (Nice to have):
- [ ] Zobrazení nice_to_have položek (volitelně zapínat/vypínat)
- [ ] Filtrování případů (status, zákazník, datum)
- [ ] Vyhledávání v případech
- [ ] Export do PDF, zatím čistě přes tisk z prohlížeče do PDF za plné účásti uživatele
- [ ] Timeline view (časová osa průběhu 8D)
- [ ] Dashboard s přehledem všech případů
- [ ] Srovnání více 8D případů

## Technologický stack HTML Vieweru

### Backend:
```
PHP: Server-side logika
  - Načítání JSON souborů z různých zdrojů
  - Autentizace pro SharePoint
  - API endpointy pro frontend
  - Generování PDF (TCPDF/mPDF)
```

### Frontend:
```
HTML: Struktura vieweru
CSS: Styling (založeno na Dashio template)
JavaScript: Interaktivita a AJAX komunikace
```

### Design template:
```
Základní design: C:\Projects\eydio\dashio-template
  - UI komponenty z Dashio
  - Responzivní layout
  - Připravené CSS/JS moduly
```

### Struktura projektu:
```
8d-viewer/
├── index.php (main entry point)
├── config.php (konfigurace zdrojů dat)
├── api/
│   ├── load-cases.php (načtení seznamu 8D případů)
│   ├── load-case.php (načtení konkrétního případu)
│   ├── export-pdf.php (export do PDF)
│   └── sharepoint-connector.php (pro SP integrace)
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── navigation.php
├── assets/
│   ├── css/ (z dashio-template)
│   ├── js/ (z dashio-template + custom)
│   └── img/
├── templates/
│   ├── case-list.php (seznam případů)
│   ├── case-detail.php (detail 8D případu)
│   └── d1-d8-sections.php (renderování jednotlivých D kroků)
└── data/
    └── (zde budou JSON soubory nebo symlink)
```

## Konfigurace (config.php)

```php
<?php
return [
    'data_source' => 'local', // local|sharepoint_foundation|sharepoint_online
    'paths' => [
        'local' => 'C:/Projects/eydio/',
        'sharepoint_foundation' => 'http://ocm-oiles.ad-oiles.navi/G8D/Dokumenty/',
        'sharepoint_online' => 'https://oilesjp.sharepoint.com/sites/Kvalita/8D/'
    ],
    'language' => 'cs',
    'theme' => 'light', // light|dark
    'pdf' => [
        'library' => 'tcpdf', // tcpdf|mpdf
        'orientation' => 'P',
        'format' => 'A4'
    ]
];
```

## API Endpointy

### 1. Načtení seznamu případů
```
GET /api/load-cases.php
Response: 
{
  "status": "success",
  "cases": [
    {
      "filename": "8D_PC-123_v1.0.json",
      "meta": {
        "cislo_pripadu": "PC-123",
        "nazev": "Vadné sheety",
        "status": "closed",
        "datum_vzniku": "2025-01-15"
      }
    }
  ]
}
```

### 2. Načtení konkrétního případu
```
GET /api/load-case.php?file=8D_PC-123_v1.0.json
Response: {celý JSON}
```

### 3. Export do PDF
```
POST /api/export-pdf.php
Body: { "case_id": "PC-123" }
Response: Binary PDF nebo odkaz na vygenerovaný soubor
```

## Integrace s Dashio Template

Dashio komponenty které využiješ:
- **Cards** - pro jednotlivé D sekce
- **Tables** - pro seznam případů, opatření, příčiny
- **Timeline** - pro časovou osu průběhu 8D
- **Badges** - pro status (in_progress, closed)
- **Accordion** - pro must_have/nice_to_have rozbalování
- **Modal** - pro detail opatření, příčin
- **Tabs** - pro přepínání mezi D1-D8

## PHP Session pro multi-source

```php
<?php
session_start();
$_SESSION['active_source'] = $_POST['source'] ?? 'local';
$config = include('config.php');
$active_path = $config['paths'][$_SESSION['active_source']];
```

## MCP Desktop Commander - Zápis JSON

Claude bude používat MCP tool `write_file` pro zápis JSON:

```javascript
// Příklad volání z Claude
desktop-commander:write_file({
  path: "C:\\Projects\\eydio\\8D_PC-123_v1.0.json",
  content: JSON.stringify(data, null, 2),
  mode: "rewrite"
})
```

### Strategie verzování:
- Po každém D kroku: inkrementální update
- Major změny: zvýšení verze (v1.0 → v1.1)
- Archiv starších verzí: `archive/8D_PC-123_v1.0_20250115.json`

## Příklad use case

**Situace:** Zákazník nahlásil vadné sheety P/N CZ/DA110-0O-2K

```
1. Zahájení řešení (D1)
   → Claude: "Pojďme sestavit tým pro PC-123"
   → Vytvoří 8D_PC-123_v1.0.json s vyplněným D1

2. Popis problému (D2)
   → Claude: "Popišme problém pomocí je/není analýzy"
   → Aktualizuje JSON, přidá D2

3. Okamžitá opatření (D3)
   → Claude: "Jaká okamžitá opatření zavedeme?"
   → Aktualizuje JSON, přidá D3

... (D4-D8 pokračují obdobně)

8. Závěr (D8)
   → Claude: "Ukončeme proces, oceníme tým"
   → Finalizuje JSON, status: "closed"

9. Prezentace zákazníkovi
   → Otevřeš HTML viewer
   → Načteš PC-123
   → Export do PDF
   → Odešleš zákazníkovi
```

## Další kroky implementace

### Pro Claude AI instrukce:
- [x] Definovat JSON strukturu
- [ ] Aktualizovat project instructions pro automatický zápis JSON
- [ ] Přidat template pro generování JSON fragmentů po každém D kroku

### Pro HTML Viewer:
- [ ] Vytvořit základní HTML strukturu
- [ ] Implementovat config.json loader
- [ ] Implementovat data loader pro local files
- [ ] Implementovat renderer pro D1-D8 sekce
- [ ] (Později) Přidat SharePoint connectors
- [ ] (Později) Přidat PDF export

---

**Připraveno pro implementaci v AI IDE (Cursor/Windsurf)**
