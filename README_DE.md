# Translator_XH

Translator_XH ist ein fortgeschrittenes Tool um den CMSimple_XH Systemkern
und Plugins zu übersetzen. Es ist vor allem für Übersetzer gedacht, die ihre
Übersetzungen veröffentlichen möchten. Wenn Sie nur ein paar Sprach-Texte
übersetzen möchten, sollten die eingebaute Sprachformulare von CMSimple_XH
ausreichen.

- [Voraussetzungen](#voraussetzungen)
- [Download](#download)
- [Installation](#installation)
- [Einstellungen](#einstellungen)
- [Verwendung](#verwendung)
<!-- - [Einschränkungen](#einschränkungen) -->
- [Fehlerbehebung](#fehlerbehebung)
- [Lizenz](#lizenz)
- [Danksagung](#danksagung)

## Voraussetzungen

Translator_XH ist ein Plugin für [CMSimple_XH](https://cmsimple-xh.org/de/).
Es benötigt CMSimple_XH ≥ 1.7.0 und PHP ≥ 7.4.0 mit der zlib Erweiterung.
Translator_XH benötigt weiterhin [Plib_XH](https://github.com/cmb69/plib_xh) ≥ 1.10;
ist dieses noch nicht installiert (siehe `Einstellungen` → `Info`),
laden Sie das [aktuelle Release](https://github.com/cmb69/plib_xh/releases/latest)
herunter, und installieren Sie es.

## Download

Das [aktuelle Release](https://github.com/cmb69/translator_xh/releases/latest)
kann von Github herunter geladen werden.

## Installation

Die Installation erfolgt wie bei vielen anderen CMSimple_XH-Plugins auch.

1. Sichern Sie die Daten auf Ihrem Server.
1. Entpacken Sie die ZIP-Datei auf Ihrem Computer.
1. Laden Sie den gesamten Ordner `translator/` auf Ihren Server
   in das `plugins/` Verzeichnis von CMSimple_XH hoch.
1. Vergeben Sie Schreibrechte für die Unterordner `css/`, `config/` und
   `languages/`. Die `languages/` Ordner des Systemkerns und der Plugins, die
   übersetzt werden sollen, benötigen ebenfalls Schreibrechte.
1. Prüfen Sie im Backend der Website unter `Plugins` → `Translator`
   ob alle Voraussetzungen für den Betrieb erfüllt sind.

## Einstellungen

Die Konfiguration des Plugins erfolgt wie bei vielen anderen CMSimple_XH
Plugins auch im Administrationsbereich der Website unter `Plugins` → `Translator`.

Sie können die Original-Einstellungen unter `Konfiguration` ändern.
Beim Überfahren der Hilfe-Icons mit der Maus werden Hinweise zu den
Einstellungen angezeigt.

Die Lokalisierung wird unter `Sprache` vorgenommen. Sie können die
Zeichenketten in Ihre eigene Sprache übersetzen, falls keine entsprechende
Sprachdatei zur Verfügung steht, oder sie entsprechend Ihren Anforderungen
anpassen. Wenn Sie bevorzugen dies mit den gehobenen Möglichkeiten von
Translator_XH zu tun, dann lesen Sie unter [Verwendung](#verwendung)
nach wie es gemacht wird.

Das Aussehen von Translator_XH kann unter `Stylesheet` angepasst werden.

## Verwendung

Gehen Sie zu `Plugins` → `Translator` → `Translations` im Backend der Website
um eine Liste aller verfügbaren Module zu sehen.
Drücken Sie `Bearbeiten` um eines davon zu bearbeiten.

Wollen Sie die übersetzten Module als ZIP-Archiv mit der benötigten
Ordnerstruktur speichern, dann wählen Sie die gewünschten
Module, geben einen Dateinamen ein und drücken Sie den `Herunter laden` Schalter.

## Fehlerbehebung

Melden Sie Programmfehler und stellen Sie Supportanfragen entweder auf
[Github](https://github.com/cmb69/translator_xh/issues) oder im
[CMSimple_XH Forum](https://cmsimpleforum.com/).

## Lizenz

Translator_XH ist freie Software. Sie können es unter den Bedingungen der
GNU General Public License, wie von der Free Software Foundation
veröffentlicht, weitergeben und/oder modifizieren, entweder gemäß
Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren Version.

Die Veröffentlichung von Translator_XH erfolgt in der Hoffnung, dass es
Ihnen von Nutzen sein wird, aber ohne irgendeine Garantie, sogar ohne
die implizite Garantie der Marktreife oder der Verwendbarkeit für einen
bestimmten Zweck. Details finden Sie in der GNU General Public License.

Sie sollten ein Exemplar der GNU General Public License zusammen mit
Translator_XH erhalten haben. Falls nicht, siehe <https://www.gnu.org/licenses/>.

Copyright © Christoph M. Becker

Polnische Übersetung © Kamil Krzes<br>
Russische Übersetzung © Lybomyr Kydray

## Danksagung

Translator_XH wurde von *Tata* und *oldnema* angeregt.
Vielen Dank für Ihre Bemühungen CMSimple_XH und dessen Plugins für
slowakisch bzw. tschechisch sprechende Anwender verfügbar zu machen.

Dieses Plugin verwendet `zip.lib.php` von Garvin Hicking.
Vielen Dank für die freie Verwendbarkeit dieser ausgezeichneten PHP Klasse.

Das Plugin-Icon wurde von [carlosjj](https://carlosjj.deviantart.com/) gestaltet.
Vielen Dank für die freie Verwendbarkeit dieses Icons.

Vielen Dank an die Gemeinschaft im [CMSimple_XH Forum](https://www.cmsimpleforum.com/)
für Tipps, Vorschläge und das Testen.
Besonders möchte ich *oldnema*, *Tata* und *svasti* für Ihr ermutigendes Feedback danken.

Und zu guter letzt vielen Dank an [Peter Harteg](https://www.harteg.dk/),
den „Vater“ von CMSimple, und allen Entwicklern von [CMSimple_XH](https://www.cmsimple-xh.org/de/)
ohne die es dieses phantastische CMS nicht gäbe.
