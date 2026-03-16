# WP Plugin Öffnungszeiten

## Datenbasis
- Wochentag-Standard (Sonntag bis Samstag)
- Pro Tag können mehrere Zeitintervalle gepflegt werden
- Tagesstatus kann auf „Geschlossen“ gesetzt werden

## Shortcodes
- `[igw_wp_open_zeit_text]` (Alias: `[open_zeit_text]`)
  - Ausgabe als `<h2>` mit Status-`<span>`
  - Attribute: `datetime`, `open_text`, `closed_text`, `class`
- `[igw_wp_open_zeit_short]` (Alias: `[open_zeit_short]`)
  - Ausgabe als Tabelle mit Spalten **Tag** und **Zeiten**
  - Gruppiert aufeinanderfolgende offene Tage mit identischen Zeitstrings
- `[igw_wp_open_zeit_tage]` (Alias: `[open_zeit_tage]`)
  - Ausgabe als Tabelle mit Spalten **Tag** und **Zeiten**
  - Dynamische aktuelle Woche basierend auf `start_of_week` (immer 7 Zeilen)

## Regeln
- Zeitintervalle müssen im Format `HH:MM` vorliegen
- Endzeit muss größer als Startzeit sein (`start < end`)
- Zeitintervalle eines Tages dürfen sich nicht überschneiden

## Zeitzone
Alle Berechnungen laufen mit WordPress Site-Timezone (`current_datetime`, `wp_date`).
