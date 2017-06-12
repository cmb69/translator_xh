<?php

/**
 * Copyright (C) 2011-2017 Christoph M. Becker
 *
 * This file is part of Translator_XH.
 *
 * Translator_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Translator_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Translator_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Translator;

class Views
{
    /**
     * @var Model
     */
    private $model;

    /**
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @param string $string
     * @return string
     */
    private function hsc($string)
    {
        return XH_hsc($string);
    }

    /**
     * @param string $string
     * @return string
     */
    private function xhtml($string)
    {
        global $cf;

        if (!$cf['xhtml']['endtags']) {
            $string = str_replace(' />', '>', $string);
        }
        return $string;
    }

    /**
     * @param string $type
     * @param string $message
     * @return string
     */
    public function message($type, $message)
    {
        return XH_message($type, $message);
    }

    /**
     * @param bool $success
     * @param string $filename
     * @return string
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
     * @param string $iconPath
     * @return string
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
    Translator_XH is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.</p>
<p style="text-align: justify">
    Translator_XH is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHAN&shy;TABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.</p>
<p style="text-align: justify">
    You should have received a copy of the GNU General Public License
    along with Translator_XH.  If not, see
    <a href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/</a>.</p>

EOT;
        return $this->xhtml($o);
    }

    /**
     * @param array $checks
     * @return string
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
     * @param string $language
     * @return string
     */
    private function languageLabel($language)
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
     * @param string $module
     * @param string $url
     * @return string
     */
    private function module($module, $url, array $modules)
    {
        $name = ucfirst($module);
        $checked = in_array($module, $modules)
            ? ' checked="checked"'
            : '';
        $url = $this->hsc($url);
        return <<<EOT
        <li>
            <input type="checkbox" name="translator_modules[]"
                   value="$module"$checked />
            <a href="$url$module">$name</a>
        </li>

EOT;
    }

    /**
     * @param string $action
     * @param string $url
     * @param string $filename
     * @return string
     */
    public function main($action, $url, $filename, array $modules)
    {
        global $plugin_tx, $_XH_csrfProtection;

        $ptx = $plugin_tx['translator'];
        $csrfTokenInput = isset($_XH_csrfProtection)
            ? $_XH_csrfProtection->tokenInput()
            : '';
        $action = $this->hsc($action);
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
    <p style="display: none">
        <button id="translator_select_all" type="button"
            >$ptx[label_select_all]</button>
        <button id="translator_deselect_all" type="button" disabled="disabled"
            >$ptx[label_deselect_all]</button>
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
     * @param string $key
     * @param string $sourceText
     * @return string
     */
    private function editorRow($key, $sourceText, array $destinationTexts)
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
        $displayKey = strtr($key, '_|', '  ');
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
     * @param string $module
     * @param string $sourceLanguage
     * @param string $destinationLanguage
     * @return string
     */
    private function editorRows($module, $sourceLanguage, $destinationLanguage)
    {
        global $plugin_cf;

        $pcf = $plugin_cf['translator'];
        $sourceTexts = $this->model->readLanguage($module, $sourceLanguage);
        $destinationTexts = $this->model->readLanguage($module, $destinationLanguage);
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
     * @param string $action
     * @param string $module
     * @param string $sourceLanguage
     * @param string $destinationLanguage
     * @return string
     */
    public function editor($action, $module, $sourceLanguage, $destinationLanguage)
    {
        global $plugin_tx, $_XH_csrfProtection;

        $ptx = $plugin_tx['translator'];
        $moduleName = ucfirst($module);
        $sourceLabel = $this->languageLabel($sourceLanguage);
        $destinationLabel = $this->languageLabel($destinationLanguage);
        $rows = $this->editorRows($module, $sourceLanguage, $destinationLanguage);
        $csrfTokenInput = isset($_XH_csrfProtection)
            ? $_XH_csrfProtection->tokenInput()
            : '';
        $action = $this->hsc($action);
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
     * @param string $url
     * @return string
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
