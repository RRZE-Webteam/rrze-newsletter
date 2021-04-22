# RRZE Newsletter

WordPress-Plugin für die Erstellung von E-Mail-Newslettern und deren anschließendes Versenden.

## Beschreibung

Das Plugin erstellt mithilfe des Blockeditors E-Mail-Newsletter, die dann über einen Mailserver gesendet werden. Mit dem Plugin kann man auffällige Newsletter nur mit den in WordPress verfügbaren Bearbeitungswerkzeugen erstellen und Entwürfe speichern, wiederverwendbare Layouts erstellen und auf der Website veröffentlichen. Die Mailinglisten werden mit den Funktionen für Taxonomien in WordPress verwaltet. Eine E-Mail-Warteschlangenfunktion ist in das Plugin integriert.

## Einstellungen

Dashboard / Einstellungen / Newsletters

## Tags

Das Plugin bietet Tags für dynamische Informationen. Einfach einen Tag in den Newsletter einfügen, um personalisierte oder dynamische Inhalte hinzuzufügen. Die folgende Liste zeigt alle verfügbaren Tags.

-   `{{=FNAME}}` Der Vorname des Abonnenten, falls dieser in der Mailingliste verfügbar ist.
-   `{{=LNAME}}` Der Nachname des Abonnenten, falls dieser in der Mailingliste verfügbar ist.
-   `{{=NAME}}` Der vollständige Name des Abonnenten (generiert aus FNAME und LNAME).
-   `{{=EMAIL}}` Die E-Mail-Adresse des Abonnenten.
-   `{{=UNSUB}}` Die URL zum Abbestellen des Newsletters.
-   `{{=PERMALINK}}` Der Permalink des Newsletters.
-   `{{=DATE}}` Das Datum, an dem der Newsletter verschickt wurde.
-   `{{=CURRENT_YEAR}}` Zeigt das aktuelle Jahr an.

Tags unterstützen auch IF-Konstrukte:

-   IF-Konstrukt: `{{TAG}} markup {{/TAG}}`
-   IF-NOT-Konstrukt: `{{!TAG}} markup {{/!TAG}}`
-   IF-ELSE-Konstrukt: `{{TAG}} markup {{:TAG}} alternatives markup {{/TAG}}`

Beispiel:

```
Guten Tag{{FNAME}} {{=FNAME}}{{/FNAME}},
```

## Hinweis für Entwickler

Empfohlene Node-Version: `node@15`

Installation der Node-Module

```shell
 npm install --legacy-peer-deps
```

Update der Node-Module

```shell
 npm update --legacy-peer-deps
```

Dev-Modus

```shell
 npm start
```

Build-Modus

```shell
 npm run build
```

Übersetzung: Erstellen der .pot-Datei (WP-CLI)
Hinweis: Die Verwendung von Poedit für die Übersetzung der jeweiligen Sprachen wird empfohlen.

```shell
 wp i18n make-pot ./ languages/rrze-newsletter.pot --domain=rrze-newsletter --exclude=node_modules,dist
```

Übersetzung: Erstellen der .json-Datei (de_DE u. de_DE_formal)

```shell
 npx po2json languages/rrze-newsletter-de_DE.po languages/rrze-newsletter-de_DE-rrze-newsletter.json -f jed1.x -p
 npx po2json languages/rrze-newsletter-de_DE_formal.po languages/rrze-newsletter-de_DE_formal-rrze-newsletter.json -f jed1.x -p
```
