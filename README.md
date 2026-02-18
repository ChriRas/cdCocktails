# cdCocktails – Scroll-Galerie + Bild-Processing


## Überblick
Mobile-first Cocktailkarte:
- schwarze Optik mit weißer Schrift
- Vorschaubilder als **1-spaltige** Scroll-Liste
- Klick auf ein Bild öffnet Vollansicht (Lightbox), dort kann man **links/rechts swipen**
- Inhalte sind **folder-driven** über `cocktail-images/`
- Ausgabe ist **nur WebP** (Full + Thumbnails)


## Ordnerstruktur (Produktion)
Alles liegt unter `cocktail-images/`:
- `incoming/`  → Upload-Ziel (Originale, optional `info.yml`)
- `full/`      → generierte WebP-Bilder für Vollansicht (max. 1440px)
- `thumbs/`    → generierte WebP-Thumbnails (1-spaltig, portrait)
- `data/info.yml` → Event-Infos (aus `incoming/info.yml` kopiert)

## WebP & Dateinamen
Beim Processing werden alle Uploads nach **WebP** konvertiert.
Dabei werden Dateinamen automatisch normalisiert:
- lowercase
- Leerzeichen/Unterstriche → `-`
- Sonderzeichen entfernt
- Kollisionen werden mit `-1`, `-2`, … gelöst

## Upload-Batches / Reset-Regel
Wenn **neue Uploads** in `cocktail-images/incoming/` liegen **und**
bereits erzeugte Dateien in `full/` oder `thumbs/` **älter als 5 Minuten** sind,
wird ein **neuer Upload-Batch** angenommen. Dann wird vor dem Processing:
- `full/*` gelöscht
- `thumbs/*` gelöscht
- `data/info.yml` gelöscht

Damit ist sichergestellt, dass ein kompletter neuer Satz Cocktails sauber ersetzt wird.

---

## Für Entwickler (Docker)
Docker wird für Entwicklung/Testing genutzt. Produktion kann auch ohne Docker laufen.

### Dependencies installieren
```bash
docker compose run --rm composer install
```

### App starten
```bash
docker compose up -d
```
Dann: http://localhost:8080

### Bild-Processing (manuell)
```bash
docker compose run --rm imgproc
```

### Cron (Host), z.B. alle 2 Minuten
```cron
*/2 * * * * cd /pfad/zum/projekt && docker compose run --rm imgproc >/dev/null 2>&1
```