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
     * The translator model.
     *
     * @var Translator_Model
     */
    protected $model;

    /**
     * Initializes a new instance.
     *
     * @param Translator_Model $model A translator model.
     */
    public function __construct(Translator_Model $model)
    {
        $this->model = $model;
    }

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
     * Returns a message.
     *
     * @param string $type    A message type ('success', 'info', 'warning', 'fail').
     * @param string $message A message.
     *
     * @return string (X)HTML.
     */
    public function message($type, $message)
    {
        if (function_exists('XH_message')) {
            return XH_message($type, $message);
        } else {
            $class = in_array($type, array('warning', 'fail'))
                ? 'cmsimplecore_warning'
                : '';
            return '<p class="' . $class . '">' . $message . '</p>' . PHP_EOL;
        }
    }

    /**
     * Returns a message after saving a file.
     *
     * @param bool   $success  Whether saving succeeded.
     * @param string $filename A filename.
     *
     * @return string (X)HTML.
     *
     * @global array The localization of the plugins.
     */
    public function saveMessage($success, $filename)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['translator'];
        $type = $success ? 'success' : 'fail';
        $message = sprintf($ptx['message_save_' . $type], $filename);
        return $this->message($type, $message);
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
     * Returns the system check view.
     *
     * @param array $checks An array of system checks.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the plugins.
     */
    public function systemCheck($checks)
    {
        global $pth, $plugin_tx;

        $ptx = $plugin_tx['translator'];
        $imgdir = $pth['folder']['plugins'] . 'translator/images/';
        $o = <<<EOT
<!-- Translator_XH: System Check -->
<h4>$ptx[syscheck_title]</h4>
<ul style="list-style: none">

EOT;
        foreach ($checks as $check => $state) {
            $o .= <<<EOT
    <li><img src="$imgdir$state.png" alt="$state" /> $check</li>

EOT;
        }
        $o .= <<<EOT
</ul>

EOT;
        return $this->xhtml($o);
    }

    /**
     * Returns a language label.
     *
     * @param string $language A language code.
     *
     * @return string (X)HTML.
     */
    protected function languageLabel($language)
    {
        $filename = $this->model->flagIconPath($language);
        if ($filename !== false) {
            return tag(
                'img src="' . $filename . '" alt="' . $language
                . '" title="' . $language . '"'
            );
        } else {
            return $language;
        }
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
            <input type="checkbox" name="translator_modules[]"
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
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    public function main($action, $url, $filename, $modules)
    {
        global $plugin_tx, $_XH_csrfProtection;

        $ptx = $plugin_tx['translator'];
        $csrfTokenInput = isset($_XH_csrfProtection)
            ? $_XH_csrfProtection->tokenInput()
            : '';
        $o = <<<EOT
<!-- Translator_XH: Administration -->
<form id="translator_list" action="$action" method="post">
    <h1>Translator &ndash; $ptx[menu_main]</h1>
    <ul>

EOT;
        foreach ($this->model->modules() as $module) {
            $o .= $this->module($module, $url, $modules);
        }
        $o .= <<<EOT
    </ul>
    <p>
        <button id="translator_select_all" type="button">Select all</button>
        <button id="translator_deselect_all" type="button" disabled="disabled"
            >Deselect all</button>
    </p>
    <p>
        $ptx[label_filename]
        <input type="text" name="translator_filename" value="$filename" />.zip
        <input type="submit" class="submit" value="$ptx[label_generate]" />
        $csrfTokenInput
    </p>
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
     * @global array The localization of the plugins.
     */
    protected function editorRow($key, $sourceText, $destinationTexts)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['translator'];
        if (isset($destinationTexts[$key])) {
            $destinationText = $destinationTexts[$key];
        } elseif ($ptx['default_translation'] != '') {
            $destinationText = $ptx['default_translation'];
        } else {
            $destinationText = $sourceText;
        }
        $class = isset($destinationTexts[$key]) ? '' : ' class="translator_new"';
        $displayKey = str_replace('_', ' ', $key);
        $sourceText = $this->hsc($sourceText);
        $destinationText = $this->hsc($destinationText);
        return <<<EOT
        <tr>
            <td class="translator_key">$displayKey</td>
            <td class="translator_from">
                <textarea rows="2" cols="40" readonly="readonly"
                    >$sourceText</textarea>
            </td>
            <td class="translator_to">
                <textarea name="translator_string_$key"$class rows="2" cols="40"
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
     * @global array The configuration of the plugins.
     */
    protected function editorRows($module, $sourceLanguage, $destinationLanguage)
    {
        global $plugin_cf;

        $pcf = $plugin_cf['translator'];
        $sourceTexts = $this->model->readLanguage($module, $sourceLanguage);
        $destinationTexts = $this->model->readLanguage(
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
     * @global array             The configuration of the plugins.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    public function editor($action, $module, $sourceLanguage, $destinationLanguage)
    {
        global $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        $pcf = $plugin_cf['translator'];
        $ptx = $plugin_tx['translator'];
        $moduleName = ucfirst($module);
        $sourceLabel = $this->languageLabel($sourceLanguage);
        $destinationLabel = $this->languageLabel($destinationLanguage);
        $rows = $this->editorRows($module, $sourceLanguage, $destinationLanguage);
        $csrfTokenInput = isset($_XH_csrfProtection)
            ? $_XH_csrfProtection->tokenInput()
            : '';
        $o = <<<EOT
<!-- Translator_XH: Translation Editor -->
<form id="translator" method="post" action="$action">
    <h1>Translator &ndash; $moduleName</h1>
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
    $csrfTokenInput
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
    <input id="translator_download_link" type="text" readonly="readonly"
           value="$url" />
</p>

EOT;
        return $this->xhtml($o);
    }
}

?>
