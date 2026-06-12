# Vad Gör Kungen?

Displays the Swedish royal court's official schedule — what the king (and other royals) are doing today and in the coming weeks. Data is scraped daily from [kungahuset.se](https://www.kungahuset.se/mediecenter/officiella-program) and served as a static site on GitHub Pages.

## How it works

- A **GitHub Actions workflow** runs daily at 06:00 UTC, scrapes up to 5 pages of official programs from kungahuset.se, and commits the results to `data.json`.
- **`index.html`** reads `data.json` and renders today's event in a full-screen hero section, with upcoming events listed below.

## Local setup (with MySQL)

```bash
cp config/config_example.json config/config.json
# Fill in DB credentials in config/config.json
mysql -u root < db/royal_events.sql
```

Run the scraper manually and store results in the database:

```bash
php src/cron.php
```

Regenerate `data.json` without a database (for static/GitHub Pages use):

```bash
php src/generate.php
```
