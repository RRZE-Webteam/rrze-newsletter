# Versand- und Queue-Invarianten

Stand: Plugin-Version 3.3.1

Dieses Dokument beschreibt den fachlichen Vertrag des Versandpfads. Es trennt
bewusst zwischen dem aktuell beobachteten Verhalten und den Eigenschaften, die
der Versand dauerhaft garantieren soll. Die Invarianten sind die Grundlage für
Tests, Bugfixes und spätere Refactorings.

## Begriffe

- **Newsletter**: Der redaktionelle Quell-Post vom Typ `newsletter`.
- **Versandvorkommen**: Ein einzelner geplanter Versand eines Newsletters. Ein
  wiederkehrender Newsletter besitzt mehrere Versandvorkommen.
- **Queue-Eintrag**: Der empfängerbezogene Snapshot vom Typ
  `newsletter_queue`.
- **Eingereiht**: Alle vorgesehenen Queue-Einträge eines Versandvorkommens
  wurden vollständig angelegt.
- **Vom Mail-Transport angenommen**: `wp_mail()` hat `true` zurückgegeben.
  Das ist keine Bestätigung, dass die E-Mail beim Empfänger zugestellt wurde.
- **Zugestellt**: Eine externe Zustellbestätigung liegt vor. Diesen Zustand
  kann das Plugin aktuell nicht feststellen.

Der Begriff „versendet“ sollte in Code, Oberfläche und Logs nie gleichzeitig
für „eingereiht“, „vom Mail-Transport angenommen“ und „zugestellt“ verwendet
werden.

## Beteiligte Komponenten

| Aufgabe | Implementierung |
| --- | --- |
| Versand beim Veröffentlichen auslösen | `Main::maybeSetQueue()` |
| Newsletter und gerendertes HTML lesen | `CPT\Newsletter::getData()` |
| Empfänger ermitteln und Queue aufbauen | `Mail\Queue::add()` |
| Fällige Queue-Einträge auswählen | `Mail\Queue::get()` |
| Queue-Einträge versenden | `Mail\Queue::process()` |
| SMTP und `wp_mail()` konfigurieren | `Mail\SMTP::send()` |
| Verarbeitung regelmäßig auslösen | `Cron` und `Events` |
| Queue-Post-Type und Zustände registrieren | `CPT\NewsletterQueue` |
| Personalisierte Archivansicht ausgeben | `Archive` |

## Aktueller Ablauf

```text
Newsletter: draft/future
        |
        | transition_post_status -> publish
        v
Newsletter-Meta: send
        |
        | Queue::add()
        +---- Rendering fehlerhaft / keine Empfänger ---> error
        |
        +---- RSS/ICS-Bedingung nicht erfüllt ----------> skipped
        |
        +---- pro eindeutiger E-Mail ein Queue-Post ----> sent
                                                          (bedeutet aktuell:
                                                           Queue-Aufbau beendet)

Queue-Post: mail-queued
        |
        | WP-Cron, standardmäßig alle fünf Minuten
        +---- wp_mail() == true ------------------------> mail-sent
        |
        +---- Fehler, weitere Versuche erlaubt ---------> mail-queued
        |
        +---- Fehler, Versuchslimit erreicht -----------> mail-error
```

Bei einem wiederkehrenden Newsletter setzt `Queue::maybeSetRecurrence()` den
Quell-Post während des Queue-Aufbaus auf das nächste Datum und den Post-Status
`future`. Das aktuelle Versandvorkommen wird danach weiter verarbeitet oder
aufgrund leerer bedingter Inhalte übersprungen.

## Persistierte Daten

Ein Queue-Eintrag enthält aktuell:

- `post_title`: Betreff
- `post_date` / `post_date_gmt`: frühester Versandzeitpunkt
- `post_content`: Base64-kodierter, personalisierter HTML-Snapshot
- `post_excerpt`: personalisierte Textversion
- `rrze_newsletter_queue_newsletter_id`: Quell-Newsletter
- `rrze_newsletter_queue_from_email`: Absenderadresse
- `rrze_newsletter_queue_from_name`: Absendername
- `rrze_newsletter_queue_from`: formatierter Absender
- `rrze_newsletter_queue_replyto`: Reply-To-Adresse
- `rrze_newsletter_queue_to`: Empfänger
- `rrze_newsletter_queue_retries`: Anzahl bereits vorgemerkter Wiederholungen
- optional `rrze_newsletter_queue_error`: letzter Versandfehler
- optional `rrze_newsletter_queue_sent_date_gmt`: Annahmezeitpunkt durch
  `wp_mail()`

Der Snapshot enthält personenbezogene Daten. Queue-Einträge werden außerdem
für die personalisierte Archivansicht verwendet.

## Verbindliche Invarianten

### Q1 – Ein Versandvorkommen wird höchstens einmal eingereiht

Für die Kombination aus Newsletter und Versandvorkommen darf der Queue-Aufbau
nicht mehrfach erfolgreich abgeschlossen werden. Wiederholte Hooks, Requests
oder Cron-Aufrufe dürfen keine doppelten Queue-Einträge erzeugen.

**Aktueller Stand:** Nicht technisch garantiert. Der Status `send` reduziert
das Risiko, es existiert aber weder eine eindeutige Versandvorkommen-ID noch
eine atomare Idempotenzprüfung.

### Q2 – Pro Versandvorkommen und E-Mail existiert höchstens ein Eintrag

Eine Adresse, die mehreren ausgewählten Mailinglisten angehört, erhält den
Newsletter nur einmal. E-Mail-Adressen werden vor dem Vergleich normalisiert.
Welche Namensdaten bei widersprüchlichen Listeneinträgen gewinnen, muss
deterministisch festgelegt sein.

**Aktueller Stand:** Die Deduplizierung erfolgt über den E-Mail-Schlüssel im
`$recipient`-Array. Die Auswahl der Namensdaten hängt jedoch von der
Reihenfolge der Mailinglisten ab.

### Q3 – Abmeldungen werden korrekt und listenbezogen berücksichtigt

Eine globale Abmeldung unterdrückt alle Sendungen. Eine listenbezogene
Abmeldung unterdrückt nur die betroffene Mailingliste. Die Reihenfolge der
Mailinglisten darf das Ergebnis nicht verändern.

**Aktueller Stand:** Nicht garantiert. `Queue::add()` akkumuliert
listenbezogene Abmeldungen in derselben Variable wie globale Abmeldungen.
Damit kann eine Abmeldung aus Liste A den Empfang über Liste B abhängig von
der Verarbeitungsreihenfolge ebenfalls unterdrücken.

### Q4 – Ein Queue-Eintrag ist vor `mail-queued` vollständig

Ein als `mail-queued` sichtbarer Eintrag besitzt bereits Quell-ID, Empfänger,
Absender, Betreff, HTML, Textversion und Retry-Zähler. Teilweise angelegte
Einträge dürfen vom Worker nicht gefunden werden.

**Aktueller Stand:** Nicht garantiert. Der Post wird sofort als `mail-queued`
angelegt und erst danach mit Inhalt und Meta-Daten vervollständigt. Fehler
während dieser Schritte können unvollständige Queue-Einträge hinterlassen.

### Q5 – Der Queue-Aufbau hat ein eindeutiges Gesamtergebnis

Der Newsletter darf nur dann als vollständig eingereiht gelten, wenn für alle
vorgesehenen Empfänger ein vollständiger Queue-Eintrag existiert. Null oder nur
teilweise angelegte Einträge müssen als Fehler beziehungsweise Teilerfolg
sichtbar sein und dürfen nicht als vollständiger Erfolg erscheinen.

**Aktueller Stand:** Verletzt. Fehler von `wp_insert_post()` und
`wp_update_post()` werden übersprungen; am Ende erhält der Newsletter trotzdem
den Status `sent`.

### Q6 – Queue-Inhalte sind unveränderliche Versand-Snapshots

Nach erfolgreichem Einreihen ändern spätere Bearbeitungen am Newsletter oder
an Mailinglisten weder Inhalt noch Empfänger bestehender Queue-Einträge.

**Aktueller Stand:** Weitgehend erfüllt. HTML, Text und Versanddaten werden pro
Empfänger gespeichert. Administrative Direktänderungen an Queue-Posts sind
von dieser Aussage ausgenommen und sollten verhindert oder protokolliert sein.

### Q7 – Ein Queue-Eintrag wird gleichzeitig von höchstens einem Worker bearbeitet

Bevor eine E-Mail an `wp_mail()` übergeben wird, muss der Worker den Eintrag
atomar beanspruchen. Ein zweiter Worker darf denselben Eintrag nicht parallel
versenden. Ein abgebrochener Worker muss seinen Claim nach einer definierten
Zeit freigeben können.

**Aktueller Stand:** Nicht garantiert. `Queue::get()` liest `mail-queued`, und
der Status ändert sich erst nach dem Versand. Überlappende Cron-Requests können
deshalb denselben Eintrag auswählen.

### Q8 – Terminale Queue-Zustände werden nicht erneut verarbeitet

`mail-sent` und `mail-error` sind terminal. Nur `mail-queued` darf regulär vom
Worker ausgewählt werden. Ein manueller Retry muss als explizite neue
Zustandsänderung erkennbar sein.

**Aktueller Stand:** Für den normalen Worker erfüllt; `Queue::get()` selektiert
nur `mail-queued`.

### Q9 – Retry-Zählung und Fehlerbehandlung sind eindeutig

`max_retries = N` bedeutet: ein initialer Versuch plus höchstens `N`
Wiederholungen. Jeder fehlgeschlagene Aufruf wird nachvollziehbar erfasst. Auch
wenn WordPress keinen `WP_Error` liefert, darf die Fehlerbehandlung selbst
nicht abbrechen.

**Aktueller Stand:** Die Anzahl entspricht bereits „initialer Versuch plus N
Retries“. `Queue::process()` setzt beim Fehler jedoch voraus, dass
`SMTP::getError()` immer ein Objekt liefert.

### Q10 – `mail-sent` bedeutet nur Annahme durch den Mail-Transport

Ein Queue-Eintrag darf `mail-sent` erhalten, wenn `wp_mail()` `true`
zurückgibt. Dieser Status darf in Dokumentation und UI nicht als garantierte
Zustellung bezeichnet werden.

**Aktueller Stand:** Technisch erfüllt, sprachlich nicht ausreichend
präzisiert.

### Q11 – Fälligkeit, Limits und Zeitangaben sind eindeutig

Es werden nur Einträge mit einem vergangenen `post_date_gmt` verarbeitet. Pro
Worker-Lauf werden höchstens `mail_queue_send_limit` Einträge ausgewählt und
höchstens 60 Sekunden gearbeitet. Persistierte Versandzeitpunkte werden
explizit in GMT geschrieben.

**Aktueller Stand:** Auswahl und Limits sind implementiert. Der
Annahmezeitpunkt wird mit `date()` statt mit der expliziten WordPress-GMT-API
erzeugt und verlässt sich damit auf die Prozess-Zeitzone.

### Q12 – Wiederkehrende Newsletter erzeugen genau ein nächstes Vorkommen

Das nächste Datum wird pro aktuellem Versandvorkommen höchstens einmal gesetzt.
Ein übersprungenes Vorkommen darf das nächste nicht verlieren. Fehler beim
Queue-Aufbau müssen eine definierte Entscheidung haben: erneut versuchen oder
zum nächsten Vorkommen wechseln.

**Aktueller Stand:** Das nächste Datum wird vor der Skip-Prüfung gesetzt. Das
Verhalten bei teilweise fehlgeschlagenem Queue-Aufbau ist nicht definiert.

### Q13 – Zustände und Fehler sind beobachtbar

Für jedes Versandvorkommen müssen mindestens folgende Zahlen bestimmbar sein:
vorgesehene Empfänger, vollständig eingereiht, vom Transport angenommen,
wartend und endgültig fehlgeschlagen. Ein Newsletter-Gesamtstatus muss aus
diesen Zahlen nachvollziehbar abgeleitet werden können.

**Aktueller Stand:** Einzelne Queue-Einträge sind im Backend sichtbar. Die
Gesamtzahlen und ein eindeutiger Status pro Versandvorkommen werden nicht
persistiert; der Newsletter-Status `sent` ist dafür nicht ausreichend.

### Q14 – Personenbezogene Queue-Daten besitzen eine Aufbewahrungsregel

Für Empfängeradresse, personalisierten HTML-/Textinhalt, Fehlerdaten und
Archivzugriff muss eine dokumentierte Aufbewahrungsdauer gelten. Eine Löschung
darf erst erfolgen, wenn die zugehörige Archivfunktion nicht mehr benötigt
wird.

**Aktueller Stand:** Es ist keine automatische Retention oder Bereinigung
erkennbar. Die gewünschte Frist ist eine fachliche und datenschutzrechtliche
Entscheidung.

## Statussemantik für die weitere Arbeit

Vor einer Implementierung sollte entschieden werden, ob bestehende Werte aus
Kompatibilitätsgründen umgedeutet oder neue Werte eingeführt werden. Empfohlen
ist folgende fachliche Trennung:

| Ebene | Empfohlener Zustand | Bedeutung |
| --- | --- | --- |
| Newsletter/Vorkommen | `queueing` | Queue-Aufbau läuft |
| Newsletter/Vorkommen | `queued` | alle Empfänger vollständig eingereiht |
| Newsletter/Vorkommen | `partial` | nur ein Teil konnte eingereiht werden |
| Newsletter/Vorkommen | `skipped` | Bedingung ergab bewusst keinen Versand |
| Newsletter/Vorkommen | `error` | kein erfolgreicher Queue-Aufbau |
| Queue-Eintrag | `mail-queued` | vollständig und fälligkeitsgesteuert wartend |
| Queue-Eintrag | `mail-processing` | exklusiv von einem Worker beansprucht |
| Queue-Eintrag | `mail-sent` | von `wp_mail()` angenommen |
| Queue-Eintrag | `mail-error` | nach allen Versuchen endgültig fehlgeschlagen |

Eine echte Zustellbestätigung würde einen externen Provider-Callback und einen
zusätzlichen Zustand benötigen.

## Erste Akzeptanztests

Diese Szenarien sollten als erste automatisierte Tests aus den Invarianten
abgeleitet werden:

1. Eine Adresse in zwei Listen erzeugt genau einen Queue-Eintrag.
2. Die Reihenfolge zweier Listen verändert die Empfängermenge nicht.
3. Eine listenbezogene Abmeldung in Liste A verhindert nicht den Empfang über
   Liste B.
4. Ein Fehler beim ersten, mittleren oder letzten Queue-Insert erzeugt keinen
   falschen vollständigen Erfolg.
5. Ein unvollständig initialisierter Queue-Post ist niemals `mail-queued`.
6. Zwei parallele Worker übergeben denselben Queue-Eintrag höchstens einmal an
   `wp_mail()`.
7. `max_retries = 0`, `1` und `N` ergeben exakt 1, 2 und `N + 1` Versuche.
8. Ein `wp_mail()`-Fehler ohne `WP_Error` endet kontrolliert und bleibt
   diagnostizierbar.
9. Ein erfolgreich eingereihter Snapshot bleibt nach Änderung des Newsletters
   und der Mailingliste unverändert.
10. Ein übersprungenes wiederkehrendes Vorkommen behält sein korrektes nächstes
    Datum.

## Offene fachliche Entscheidungen

Vor den ersten Queue-Änderungen müssen Maintainer und Product Owner folgende
Punkte festlegen:

1. Soll der bisherige Newsletter-Status `sent` künftig `queued` bedeuten oder
   erst gesetzt werden, wenn alle Queue-Einträge terminal sind?
2. Wie soll ein partieller Queue-Aufbau behandelt werden: fehlende Empfänger
   nachholen oder den gesamten Aufbau verwerfen?
3. Wie lange sollen erfolgreiche, fehlerhafte und personalisierte
   Queue-Einträge aufbewahrt werden?
4. Wie lange muss die personalisierte Archivansicht erreichbar bleiben?
5. Welche Namensdaten gewinnen, wenn dieselbe E-Mail in mehreren Listen mit
   unterschiedlichen Namen vorkommt?
6. Soll ein dauerhaft fehlerhaftes Vorkommen eines wiederkehrenden Newsletters
   die folgende Ausführung blockieren?
