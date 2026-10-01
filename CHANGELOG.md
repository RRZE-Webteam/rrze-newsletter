# Änderungsprotokoll

## 3.4.0

### Neue Funktionen

- **Medien und Text:** Der Gutenberg-Block steht für Newsletter zur Verfügung.
  Bilder und Text werden für die E-Mail-Ausgabe aufbereitet; die gewählte
  Anordnung und das Stapeln auf schmalen Bildschirmen werden berücksichtigt.
- **Automatische E-Mail-Abstände:** Einheitliche Abstände können global oder pro
  Newsletter aktiviert werden. Im Expertenmodus bleiben manuelle Abstände
  wirksam. Der Editor zeigt die automatische Regelung an, ohne die gespeicherten
  Blockwerte zu überschreiben.
- **Kontrastschutz:** Schwer lesbare Texte auf eindeutig bestimmbaren, einfarbigen
  Hintergründen werden in der E-Mail-Ausgabe korrigiert. Der Schutz ist pro
  Newsletter abschaltbar und erfasst auch später eingesetzte RSS-/Kalenderinhalte.
  Bildhintergründe, Transparenz und nicht sicher auflösbare Farben werden nicht
  automatisch verändert.
- **E-Mail-Vorschau im Editor:** Das Vorschau-Menü und die Vorschau-Links nach dem
  Speichern zeigen das generierte E-Mail-HTML. RSS- und Kalenderblöcke werden beim
  Öffnen aufgelöst. Ungespeicherte Änderungen werden kenntlich gemacht; die Vorschau
  verändert weder Versandstatus noch Feed-Bedingungen.

### Verbesserungen und Fehlerbehebungen

- Automatische Newsletter können nach verlorenen Cron-Ereignissen oder
  unterbrochener Verarbeitung wieder aufgenommen werden. Eine Sperre pro
  Newsletter schützt die Erstellung wiederkehrender Versandläufe.
- Überarbeitete Vorlagenauswahl, besser angeordnete Editor-Einstellungen und
  ergänzte deutsche Übersetzungen, einschließlich der Hinweise zu Abständen,
  Kontrastschutz und Vorschau.
- Verbesserte E-Mail-Darstellung von Bildern, Buttons, Spalten, verschachtelten
  Gruppen, Hintergrundfarben und Social-Media-Symbolen.
- Responsive Stilbearbeitung ist im Newsletter-Editor deaktiviert. Die
  Gerätevorschau und bereits gespeicherte responsive Stile bleiben erhalten.
- Fehler bei täglichen Wiederholungen am Enddatum, der Entfernung von
  Auszugsfiltern und fehlgeschlagenen Bestätigungs-E-Mails behoben.
- PHP-8.5-Kompatibilität im RSS-/ICS-Pfad: SimplePie-Registry statt veralteter
  Setter, Freigabe von cURL-Handles ohne `curl_close()` und ICS-Parser 3.5.1 mit
  korrigierter Zeitzonenprüfung.

### Hinweise zum Update

- Mindestanforderungen bleiben **WordPress 6.8** und **PHP 8.2**.
- Build-Dateien und PHP-Produktionsabhängigkeiten sind im Repository enthalten;
  auf dem Zielsystem ist kein eigener Build erforderlich.
- Automatische Abstände sind global standardmäßig deaktiviert. Bereits
  gespeichertes E-Mail-HTML wird dadurch nicht rückwirkend neu erzeugt.
- Nach Änderungen an Layout oder E-Mail-Stilen den Newsletter erneut speichern
  und die Vorschau prüfen. Bereits erzeugte Queue-Einträge und versandte E-Mails
  werden nicht nachträglich umgeschrieben.
