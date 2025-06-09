# Translator_XH

Translator_XH is an advanced tool for translating the CMSimple_XH core and
plugins.  It is mainly intended for translators, who want to publish their
translations.  If you only want to translate a few language strings,
the built-in language forms of CMSimple_XH should be sufficient.

- [Requirements](#requirements)
- [Download](#download)
- [Installation](#installation)
- [Settings](#settings)
- [Usage](#usage)
<!-- - [Limitations](#limitations) -->
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Credits](#credits)

## Requirements

Translator_XH is a plugin for [CMSimple_XH](https://cmsimple-xh.org/).
It requires CMSimple_XH ≥ 1.7.0, and PHP ≥ 7.4.0 with the zlib extension.
Translator_XH also requires [Plib_XH](https://github.com/cmb69/plib_xh) ≥ 1.10;
if that is not already installed (see `Settings` → `Info`),
get the [lastest release](https://github.com/cmb69/plib_xh/releases/latest),
and install it.

## Download

The [lastest release](https://github.com/cmb69/translator_xh/releases/latest)
is available for download on Github.

## Installation

The installation is done as with many other CMSimple_XH plugins.

1. Backup the data on your server.
1. Unzip the distribution on your computer.
1. Upload the whole folder `translator/` to your server into
   the `plugins/` folder of CMSimple_XH.
1. Set write permissions for the subfolders `config/`, `css/` and
   `languages/`.  The `languages/` folders of the core and the plugins which shall
   be translated need write permissions too.
1. Check under `Plugins` → `Translator` in the back-end of the website
   if all requirements are fulfilled.

## Settings

The configuration of the plugin is done as with many other CMSimple_XH plugins
in the back-end of the website. Go to `Plugins` → `Translator`.

You can change the default settings of Translator_XH under `Config`.
Hints for the options will be displayed when hovering over the help icons
with your mouse.

Localization is done under `Language`.  You can translate the character
strings to your own language if there is no appropriate language file
available, or customize them according to your needs.  You might prefer to do
this with the advanced facilities of Translator_XH – so have a look at its
[usage](#usage).

You can customize the look of Translator_XH under `Stylesheet`.

## Usage

Go to `Plugins` → `Translator` → `Translations` in the back-end of the website
to see a list of all available modules.
Click `Edit` to edit any of these modules.

If you want to store the translated modules as properly arranged ZIP
archive, select the modules you want to include, enter the file name
and press the `Download` button.

## Troubleshooting

Report bugs and ask for support either on
[Github](https://github.com/cmb69/translator_xh/issues)
or in the [CMSimple_XH Forum](https://cmsimpleforum.com/).

## License

Translator_XH is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Translator_XH is distributed in the hope that it will be useful,
but *without any warranty*; without even the implied warranty of
*merchantibility* or *fitness for a particular purpose*. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Translator_XH.  If not, see <https://www.gnu.org/licenses/>.

Copyright © Christoph M. Becker

Polish translation © Kamil Krzes<br>
Russian translation © Lybomyr Kydray

## Credits

Translator_XH was inspired by *Tata* and *oldnema*.
Many thanks for their efforts to make CMSimple_XH and its plugins available for
users speaking the Slovak and Czech language, respectively.

This plugin uses `zip.lib.php` by Garvin Hicking.
Many thanks to making this fine PHP class freely available.

The plugin logo was designed by [carlosjj](https://carlosjj.deviantart.com/).
Many thanks for making this icon freely available.

Many thanks to the community at the [CMSimple_XH forum](https://www.cmsimpleforum.com/)
for tips, suggestions and testing.
Particularly I want to thank *oldnema*, *Tata* and *svasti*
for their encouraging feedback.

And last but not least many thanks to [Peter Harteg](https://www.harteg.dk),
the “father” of CMSimple,
and all developers of [CMSimple_XH](https://www.cmsimple-xh.org)
without whom this amazing CMS would not exist.
