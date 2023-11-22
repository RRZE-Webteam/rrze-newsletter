# RRZE Newsletter

WordPress-Plugin für die Erstellung von E-Mail-Newslettern und deren anschließendes Versenden.

## Beschreibung

Mit dem Plugin kann man mit dem WordPress-Blockeditor (Gutenberg) auffällige E-Mail-Newsletter erstellen, die dann über einen Mailserver gesendet werden. Außerdem kann man Entwürfe speichern, wiederverwendbare Layouts erstellen und auf der Website veröffentlichen. Mehrere Mailinglisten können direkt mit dem Plugin verwaltet werden. Eine E-Mail-Warteschlangenfunktion ist ebenfalls in das Plugin integriert.

## Einstellungen

Dashboard / Einstellungen / Newsletters

## Tags

Das Plugin bietet Tags für dynamische Informationen. Einfach einen Tag in den Newsletter einfügen, um personalisierte oder dynamische Inhalte hinzuzufügen. Die folgende Liste zeigt alle verfügbaren Tags.

-   `{{=FNAME}}` Der Vorname des Abonnenten, falls dieser in der Mailingliste verfügbar ist
-   `{{=LNAME}}` Der Nachname des Abonnenten, falls dieser in der Mailingliste verfügbar ist
-   `{{=NAME}}` Der vollständige Name des Abonnenten (generiert aus FNAME und LNAME)
-   `{{=EMAIL}}` Die E-Mail-Adresse des Abonnenten
-   `{{=UNSUB}}` Die URL zum Abbestellen des Newsletters
-   `{{=UPDATE}}` Die URL zum Ändern des Newsletter-Abonnementes
-   `{{=ARCHIVE}}` Der Link des Newsletters-Archive (die Ansicht ist identisch mit dem per E-Mail erhaltenen Newsletter)
-   `{{=DATE}}` Das Datum, an dem der Newsletter versendet wurde
-   `{{=CURRENT_YEAR}}` Das laufende Jahr, in dem der Newsletter versendet wurde
-   `{{EMAIL_ONLY}}` Nur in der E-Mail anzeigen (nur geeignet für If-Konstrukte)

**Syntax**

-   `{{=TAG}}`: Ausgabe des TAG-Wertes (String)
-   `{{TAG}}`: Prüft, ob ein TAG als belegt gilt (Boolean)

**IF-Konstrukte**

-   IF-Konstrukt: `{{TAG}} <markup> {{/TAG}}`
-   IF-NOT-Konstrukt: `{{!TAG}} <markup> {{/!TAG}}`
-   IF-ELSE-Konstrukt: `{{TAG}} <markup> {{:TAG}} <alternatives markup> {{/TAG}}`

**Beispiel**

```
Guten Tag{{FNAME}} {{=FNAME}}{{/FNAME}},
```

## Hinweis für Entwickler

**PHP-Version:** `~8.1.0`

**Composer-Version:** `~2.6.5`

**Node-Version:** `~21.2.0`

**Übersetzung: Erstellen der .pot-Datei (WP-CLI)**

```shell
wp i18n make-pot ./ languages/rrze-newsletter.pot --domain=rrze-newsletter --exclude=node_modules,vendor,build
```

Hinweis: Die Verwendung von [Poedit](https://poedit.net) für die Übersetzung der jeweiligen Sprachen wird empfohlen.

**Übersetzung: Erstellen der .json-Datei (de_DE u. de_DE_formal)**

```shell
npx https://github.com/mikeedwards/po2json languages/rrze-newsletter-de_DE.po languages/rrze-newsletter-de_DE-rrze-newsletter.json -f jed
npx https://github.com/mikeedwards/po2json languages/rrze-newsletter-de_DE_formal.po languages/rrze-newsletter-de_DE_formal-rrze-newsletter.json -f jed
```
