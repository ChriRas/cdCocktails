# cdCocktails - Scroll Gallery + Image Processing

## Overview
Mobile-first cocktail menu with:
- dark visual style with light text
- a single-column scrolling gallery of preview images
- fullscreen lightbox view with swipe navigation
- folder-driven content from `cocktail-images/`
- WebP-only output for full images and thumbnails

## Production Folder Structure
Everything is stored under `cocktail-images/`:
- `incoming/`: upload target (original images and optional `info.yml`)
- `full/`: generated WebP files for fullscreen view (max 1440px)
- `thumbs/`: generated WebP thumbnails for gallery view
- `data/info.yml`: event metadata copied from `incoming/info.yml`

## WebP and Filenames
During processing, uploaded images are converted to WebP.
Filenames are normalized:
- lowercase
- spaces and underscores to `-`
- remove special characters
- resolve collisions using `-1`, `-2`, ...

## Upload Batch Reset Rule
If there are new uploads in `cocktail-images/incoming/` and processed files in `full/` or `thumbs/` are older than 5 minutes, the run is treated as a new batch. Before processing, it clears:
- `full/*`
- `thumbs/*`
- `data/info.yml`

This guarantees a clean replacement for a complete new cocktail set.

Image processing uses the PHP `imagick` extension (no `magick` CLI required).
The image root path is configured through `IMAGES_ROOT` in `.env`.

## Development (Docker)

### Rebuild and Restart Environment
```bash
docker compose down
docker compose up -d --build
```

### Install Dependencies
```bash
docker compose run --rm -e COMPOSER_IGNORE_PLATFORM_REQ=ext-imagick composer composer install
```

### Start Application
```bash
docker compose up -d --build
```
Then open: http://localhost:8081

### Check imagick Module in Container
```bash
docker compose run --rm imgproc php -m | grep -i imagick
```

### Run Image Processing in Docker
```bash
docker compose run --rm imgproc
```

### Run Image Processing Locally
```bash
php bin/console app:images:process
```

### Cron Example (Host)
```cron
*/2 * * * * cd /path/to/project && php bin/console app:images:process >/dev/null 2>&1
```

## Task Commands
This project ships with a `Taskfile.yml` for common workflows.

| Command | Description |
| --- | --- |
| `task start` | Start Docker containers |
| `task stop` | Stop and remove Docker containers |
| `task build` | Build Docker images |
| `task test` | Run PHPUnit tests in Docker |
| `task phpstan` | Run PHPStan static analysis in Docker |
| `task image-process` | Run image processing command in Docker |
| `task composer -- <args>` | Run Composer in Docker, e.g. `task composer -- install` |

## Quality Checks

### Unit Tests
```bash
docker compose run --rm imgproc vendor/bin/phpunit --testdox
```

### Static Analysis (PHPStan)
```bash
docker compose run --rm imgproc vendor/bin/phpstan analyse --memory-limit=512M
```
