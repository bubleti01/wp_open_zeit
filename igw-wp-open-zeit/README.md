# WP Plugin Öffnungszeiten

## Priorität der Daten
1. Ausnahme (exaktes Datum)
2. Ferien (Datumsbereich inkl. Enddatum)
3. Wochentag-Standard

## Shortcodes
- `[igw_wp_open_zeit_text]` (Alias: `[open_zeit_text]`)
  - Ausgabe als `<h2>` mit Status-`<span>`
  - Attribute: `datetime`, `open_text`, `closed_text`, `class`
- `[igw_wp_open_zeit_short]` (Alias: `[open_zeit_short]`)
  - Ausgabe als Tabelle mit Spalten **Tag** und **Zeiten**
  - Nur Weekly-Daten, gruppiert aufeinanderfolgende offene Tage mit identischen Zeitstrings
- `[igw_wp_open_zeit_tage]` (Alias: `[open_zeit_tage]`)
  - Ausgabe als Tabelle mit Spalten **Tag** und **Zeiten**
  - Dynamische aktuelle Woche basierend auf `start_of_week` (immer 7 Zeilen)

## Regeln
- Ferien dürfen sich nicht überlappen.
- Pro Datum maximal eine Ausnahme.
- Ausnahme darf nicht in Ferien liegen.
- Ferienbereich darf kein Ausnahme-Datum enthalten.

## Zeitzone
Alle Berechnungen laufen mit WordPress Site-Timezone (`current_datetime`, `wp_date`).
