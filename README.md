# PS SEO

**Deutsch** | [English](README.en.md)

[![Version](https://img.shields.io/badge/Version-1.0.0-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-bis%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.4-03768e?style=flat-square)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

Moderne SEO-Werkzeuge für ClassicPress: PS SEO verwaltet Metadaten, Social-Media-Vorschauen, strukturierte Daten, Weiterleitungen und Sitemaps in einem schlanken, modularen Plugin.

## Funktionen

- SEO-Titel und Meta-Beschreibungen für Inhalte und Taxonomie-Archive
- individuelle Canonical-URLs sowie `noindex`, `nofollow`, `noarchive` und `nosnippet`
- konfigurierbare Titelvorlagen für Startseite, Beiträge, Seiten, Archive, Suche und 404-Seiten
- Open-Graph-Metadaten und Twitter Cards mit Bild-Fallbacks
- zentraler JSON-LD-Graph für Website, Betreiber, Breadcrumbs und Artikel
- Product-, Offer- und ProductGroup-Schema für MarketPress-Produkte und -Varianten
- Übernahme von Preis, Währung, Verfügbarkeit und SKU aus MarketPress
- optionale Produktleistungsdaten aus PS Stats
- Ausschluss von noindex-Inhalten, noindex-Taxonomien und weitergeleiteten Inhalten aus Sitemaps
- Sitemap unter `/ps-sitemap.xml` und automatischer Eintrag in `robots.txt`
- SEO-Metabox für alle öffentlichen Inhaltstypen und SEO-Felder für Taxonomie-Archive
- einfache Live-Inhaltsanalyse im Editor
- englische Übersetzung für Backend und Inhaltsanalyse

## Anforderungen

- ClassicPress 2.7 oder neuer
- PHP 8.0 oder neuer

MarketPress und PS Stats sind optional. Die zugehörigen Schema- und Leistungsdaten werden nur verwendet, wenn die jeweiligen Plugins aktiv sind.

## Installation

1. Lade das Plugin-Verzeichnis nach `wp-content/plugins/` hoch oder installiere es über die ClassicPress-Pluginverwaltung.
2. Aktiviere **PS SEO** unter **Plugins**.
3. Öffne **Einstellungen → PS SEO**, um Funktionen, Titelvorlagen, Social-Media-Angaben und Schema-Daten zu konfigurieren.
4. Bearbeite Inhalte oder Taxonomie-Archive, um individuelle SEO-Angaben zu hinterlegen.

Nach der Aktivierung ist die Sitemap unter `https://deine-domain.tld/ps-sitemap.xml` verfügbar.

## Konfiguration

### Globale Einstellungen

Unter **Einstellungen → PS SEO** kannst du einzelne Funktionsbereiche aktivieren oder deaktivieren und Folgendes konfigurieren:

- Titelvorlagen mit Platzhaltern wie `%site_name%`, `%separator%`, `%title%`, `%archive%` und `%search%`
- Standardbild, Twitter-Benutzername, Twitter-Card-Typ und Facebook-App-ID
- Betreiber als Organisation oder Person, Logo, Kontaktangaben und Social-Media-Profile

### SEO-Metabox

Die Metabox steht für öffentliche Inhaltstypen bereit. Sie ermöglicht individuelle Angaben für Titel, Beschreibung, Canonical-URL, Robots-Direktiven, Social-Media-Vorschau und Weiterleitung.

### Sitemap

PS SEO erweitert die Core-Sitemap von ClassicPress und veröffentlicht sie unter `/ps-sitemap.xml`. Inhalte, Taxonomie-Begriffe oder Weiterleitungen mit `noindex` werden daraus ausgeschlossen.

## Entwicklung

Die JavaScript-Tests lassen sich mit folgendem Befehl ausführen:

```bash
npm run test:js
```

## Lizenz

PS SEO wird unter der [GNU General Public License, Version 2 oder neuer](https://www.gnu.org/licenses/gpl-2.0.html) veröffentlicht.
