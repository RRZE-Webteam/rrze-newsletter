# RRZE Newsletter

WordPress-Plugin für die Erstellung von E-Mail-Newslettern und deren anschließendes Versenden.

## Beschreibung

Das Plugin erstellt mithilfe des Blockeditors E-Mail-Newsletter, die dann über einen Mailserver gesendet werden. Mit dem Plugin kann man auffällige Newsletter nur mit den in WordPress verfügbaren Bearbeitungswerkzeugen erstellen und Entwürfe speichern, wiederverwendbare Layouts erstellen und auf der Website veröffentlichen. Die Mailinglisten werden mit den Funktionen für Taxonomien in WordPress verwaltet. Eine E-Mail-Warteschlangenfunktion ist in das Plugin integriert.

## Einstellungen

Dasboard / Einstellungen / Newsletters

## Hinweis für Entwickler

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

Übersetzung: Erstellen der .pot-Datei (WP_CLI)
Hinweis: Die Verwendung von Poedit für die Übersetzung der jeweiligen Sprachen wird empfohlen.

```shell
 wp i18n make-pot ./ languages/rrze-newsletter.pot --domain=rrze-newsletter --exclude=node_modules,dist  
```

Übersetzung: Erstellen der .json-Datei (de_DE u. de_DE_formal)

```shell
 npx po2json languages/rrze-newsletter-de_DE.po languages/rrze-newsletter-de_DE-rrze-newsletter.json -f jed1.x -p 
 npx po2json languages/rrze-newsletter-de_DE_formal.po languages/rrze-newsletter-de_DE_formal-rrze-newsletter.json -f jed1.x -p  
```
