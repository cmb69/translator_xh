<?php

/**
 * The views.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Translator
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2011-2013 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Translator_XH
 */

/**
 * The views class.
 *
 * @category CMSimple_XH
 * @package  Translator
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Translator_XH
 */
class Translator_Views
{
    /**
     * Returns a string with special (X)HTML characters escaped as entities.
     *
     * @param string $string A string.
     *
     * @return string (X)HTML.
     */
    protected function hsc($string)
    {
        if (function_exists('XH_hsc')) {
            return XH_hsc($string);
        } else {
            return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
        }
    }

    /**
     * Returns a string with ETAGCs adjusted to the configured markup language.
     *
     * @param string $string A string.
     *
     * @return string (X)HTML.
     *
     * @global array The configuration of the core.
     */
    protected function xhtml($string)
    {
        global $cf;

        if (!$cf['xhtml']['endtags']) {
            $string = str_replace(' />', '>', $string);
        }
        return $string;
    }

    /**
     * Returns the about view.
     *
     * @param string $iconPath A file path.
     *
     * @return string (X)HTML.
     */
    public function about($iconPath)
    {
        $version = TRANSLATOR_VERSION;
        $o = <<<EOT
<!-- Translator_XH: About -->
<h1>Translator_XH</h1>
<img src="$iconPath" alt="Plugin icon" width="128" height="128"
     style="float: left; margin-right: 16px" />
<p>Version: $version</p>
<p>Copyright &copy; 2011-2013 Christoph M. Becker</p>
<p style="text-align: justify">
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.</p>
<p style="text-align: justify">
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHAN&shy;TABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.</p>
<p style="text-align: justify">
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see
    <a href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/</a>.</p>

EOT;
        return $this->xhtml($o);
    }

    /**
     * Returns a <li> element for a module.
     *
     * @param string $module  A module name.
     * @param string $url     A URL to link to.
     * @param array  $modules An array of checked modules.
     *
     * @return string XHTML.
     */
    protected function module($module, $url, $modules)
    {
        $name = ucfirst($module);
        $checked = in_array($module, $modules)
            ? ' checked="checked"'
            : '';
        return <<<EOT
        <li>
            <input type="checkbox" name="translator-plugins[]"
                   value="$module"$checked />
            <a href="$url$module">$name</a>
        </li>

EOT;
    }

    /**
     * Returns the main administration view.
     *
     * @param string $action   A URL to submit to.
     * @param string $url      A URL to link to.
     * @param string $filename A language pack file name.
     * @param array  $modules  An array of module names.
     *
     * @return string (X)HTML.
     *
     * @global array  The localization of the plugins.
     * @global object The translator model.
     */
    public function main($action, $url, $filename, $modules)
    {
        global $plugin_tx, $_Translator;

        $ptx = $plugin_tx['translator'];
        $o = <<<EOT
<!-- Translator_XH: Administration -->
<form id="translator-list" action="$action" method="post">
    <h1>$ptx[label_plugins]</h1>
    <ul>

EOT;
        foreach ($_Translator->modules() as $plugin) {
            $o .= $this->module($plugin, $url, $modules);
        }
        $o .= <<<EOT
    </ul>
    $ptx[label_filename]
    <input type="text" name="translator-filename" value="$filename" />.zip
    <input type="submit" class="submit" value="$ptx[label_generate]" />
</form>

EOT;
        return $this->xhtml($o);
    }

    /**
     * Returns a single table row of the translation editor.
     *
     * @param string $key              A language key.
     * @param array  $sourceText       An array of original texts.
     * @param array  $destinationTexts An array of translated texts.
     *
     * @return string XHTML.
     *
     * @global array The configuration of the plugins.
     */
    protected function editorRow($key, $sourceText, $destinationTexts)
    {
        global $plugin_cf;

        $pcf = $plugin_cf['translator'];
        if (isset($destinationTexts[$key])) {
            $destinationText = $destinationTexts[$key];
        } elseif ($pcf['default_translation'] != '') {
            $destinationText = $pcf['default_translation'];
        } else {
            $destinationText = $sourceText;
        }
        $class = isset($destinationTexts[$key]) ? '' : ' class="new"';
        $sourceText = $this->hsc($sourceText);
        $destinationText = $this->hsc($destinationText);
        return <<<EOT
        <tr>
            <td class="key">$key</td>
            <td class="from">
                <textarea rows="2" cols="40" readonly="readonly"
                    >$sourceText</textarea>
            </td>
            <td class="to">
                <textarea name="translator-$key"$class rows="2" cols="40"
                    >$destinationText</textarea>
            </td>
        </tr>

EOT;
    }

    /**
     * Returns all table rows of the translation editor.
     *
     * @param string $module              A module name.
     * @param string $sourceLanguage      A language code.
     * @param string $destinationLanguage A language code.
     *
     * @return string XHTML.
     *
     * @global array            The configuration of the plugins.
     * @global Translator_Model The translator model.
     */
    protected function editorRows($module, $sourceLanguage, $destinationLanguage)
    {
        global $plugin_cf, $_Translator;

        $pcf = $plugin_cf['translator'];
        $sourceTexts = $_Translator->readLanguage($module, $sourceLanguage);
        $destinationTexts = $_Translator->readLanguage(
            $module, $destinationLanguage
        );
        if ($pcf['sort_load']) {
            ksort($sourceTexts);
        }
        $o = '';
        foreach ($sourceTexts as $key => $sourceText) {
            $o .= $this->editorRow($key, $sourceText, $destinationTexts);
        }
        return $o;
    }

    /**
     * Returns the translation editor view.
     *
     * @param string $action              A URL to submit to.
     * @param string $module              A module name.
     * @param string $sourceLanguage      A language code.
     * @param string $destinationLanguage A language code.
     *
     * @return string (X)HTML.
     *
     * @global array            The configuration of the plugins.
     * @global array            The localization of the plugins.
     * @global Translator_Model The translator model.
     */
    public function editor($action, $module, $sourceLanguage, $destinationLanguage)
    {
        global $plugin_cf, $plugin_tx;

        $pcf = $plugin_cf['translator'];
        $ptx = $plugin_tx['translator'];
        $moduleName = ucfirst($module);
        $sourceLabel = Translator_languageMarker($sourceLanguage);
        $destinationLabel = Translator_languageMarker($destinationLanguage);
        $rows = $this->editorRows($module, $sourceLanguage, $destinationLanguage);
        $o = <<<EOT
<!-- Translation_XH: Translation Editor -->
<form id="translator" method="post" action="$action">
    <h1>$moduleName</h1>
    <input type="submit" class="submit" value="$ptx[label_save]" />
    <table>
        <tr>
            <th></th>
            <th>$ptx[label_translate_from] $sourceLabel</th>
            <th>$ptx[label_translate_to] $destinationLabel</th>
        </tr>
$rows
    </table>
    <input type="submit" class="submit" value="$ptx[label_save]" />
</form>

EOT;
        return $this->xhtml($o);
    }

    /**
     * Returns the download URL view.
     *
     * @param string $url A URL.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    public function downloadUrl($url)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['translator'];
        $o = <<<EOT
<p>
    $ptx[label_download_url]<br />
    <input id="translator-download-link" type="text" disabled="disabled"
           value="$url" />
</p>

EOT;
        return $this->xhtml($o);
    }
}

?>
