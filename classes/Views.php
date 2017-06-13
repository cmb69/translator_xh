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
        $view = new View('info');
        $view->logo = $iconPath;
        $view->version = Plugin::VERSION;
        $view->checks = (new SystemCheckService)->getChecks();
        return (string) $view;
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
                   value="$module"$checked>
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
        global $_XH_csrfProtection;

        $view = new View('main');
        $view->action = $action;
        $modules = [];
        foreach ($this->model->modules() as $module) {
            $modules[] = new HtmlString($this->module($module, $url, $modules));
        }
        $view->modules = $modules;
        $view->filename = $filename;
        $view->csrfTokenInput = new HtmlString($_XH_csrfProtection->tokenInput());
        return (string) $view;
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
        global $_XH_csrfProtection;

        $view = new View('editor');
        $view->action = $action;
        $view->moduleName = ucfirst($module);
        $view->sourceLabel = new HtmlString($this->languageLabel($sourceLanguage));
        $view->destinationLabel = new HtmlString($this->languageLabel($destinationLanguage));
        $view->rows = new HtmlString($this->editorRows($module, $sourceLanguage, $destinationLanguage));
        $view->csrfTokenInput = new HtmlString($_XH_csrfProtection->tokenInput());
        return (string) $view;
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
    $ptx[label_download_url]<br>
    <input id="translator_download_link" type="text" readonly="readonly"
           value="$url">
</p>

EOT;
        return $o;
    }
}
