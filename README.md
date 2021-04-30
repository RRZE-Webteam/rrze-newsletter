# RRZE Newsletter

WordPress-Plugin für die Erstellung von E-Mail-Newslettern und deren anschließendes Versenden.

## Beschreibung

Das Plugin verwendet den Blockeditor (Gutenberg), um E-Mail-Newsletter zu erstellen, die dann über einen Mailserver gesendet werden. Mit dem Plugin kann man mit den in WordPress verfügbaren Bearbeitungswerkzeugen auffällige Newsletter erstellen. Außerdem kann man Entwürfe speichern, wiederverwendbare Designs erstellen und auf der Website veröffentlichen. Die Mailinglisten werden mit den Funktionen für Taxonomien in WordPress verwaltet. Eine E-Mail-Warteschlangenfunktion ist in das Plugin integriert.

## Einstellungen

Dashboard / Einstellungen / Newsletters

## Tags

Das Plugin bietet Tags für dynamische Informationen. Einfach einen Tag in den Newsletter einfügen, um personalisierte oder dynamische Inhalte hinzuzufügen. Die folgende Liste zeigt alle verfügbaren Tags.

-   `{{=FNAME}}` Der Vorname des Abonnenten, falls dieser in der Mailingliste verfügbar ist
-   `{{=LNAME}}` Der Nachname des Abonnenten, falls dieser in der Mailingliste verfügbar ist
-   `{{=NAME}}` Der vollständige Name des Abonnenten (generiert aus FNAME und LNAME)
-   `{{=EMAIL}}` Die E-Mail-Adresse des Abonnenten
-   `{{=UNSUB}}` Die URL zum Abbestellen des Newsletters
-   `{{=PERMALINK}}` Der Permalink des Newsletters
-   `{{=DATE}}` Das Datum, an dem das Bulletin gesendet wurde
-   `{{=CURRENT_YEAR}}` Das laufende Jahr
-   `{{=IS_PUBLIC}}` Prüft, ob ein Newsletter öffentlich ist

**IF-Konstrukte**

-   IF-Konstrukt: `{{TAG}} markup {{/TAG}}`
-   IF-NOT-Konstrukt: `{{!TAG}} markup {{/!TAG}}`
-   IF-ELSE-Konstrukt: `{{TAG}} markup {{:TAG}} alternatives markup {{/TAG}}`

**Beispiel**

```
Guten Tag{{FNAME}} {{=FNAME}}{{/FNAME}},
```

## Hinweis für Entwickler

**Empfohlene Node-Version:** `node@15`

**Installation der Node-Module**

```shell
 npm install --legacy-peer-deps
```

**Update der Node-Module**

```shell
 npm update --legacy-peer-deps
```

**Dev-Modus**

```shell
 npm start
```

**Build-Modus**

```shell
 npm run build
```

**Übersetzung: Erstellen der .pot-Datei (WP-CLI)**

```shell
 wp i18n make-pot ./ languages/rrze-newsletter.pot --domain=rrze-newsletter --exclude=node_modules,dist
```

Hinweis: Die Verwendung von [Poedit](https://poedit.net) für die Übersetzung der jeweiligen Sprachen wird empfohlen.

**Übersetzung: Erstellen der .json-Datei (de_DE u. de_DE_formal)**

```shell
 npx po2json languages/rrze-newsletter-de_DE.po languages/rrze-newsletter-de_DE-rrze-newsletter.json -f jed1.x -p
 npx po2json languages/rrze-newsletter-de_DE_formal.po languages/rrze-newsletter-de_DE_formal-rrze-newsletter.json -f jed1.x -p
```
