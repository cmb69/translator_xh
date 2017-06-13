<?php

/**
 * Copyright 2011-2017 Christoph M. Becker
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

class MainController
{
    /**
     * @var Model
     */
    private $model;

    /**
     * @var array
     */
    private $conf;

    /**
     * @var array
     */
    private $lang;

    /**
     * @return void
     */
    public function __construct()
    {
        global $plugin_cf, $plugin_tx;

        $this->model = new Model();
        $this->conf = $plugin_cf['translator'];
        $this->lang = $plugin_tx['translator'];
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        global $pth, $sn, $sl, $hjs;

        $filename = $pth['folder']['plugins'] . 'translator/translator.min.js';
        $hjs .= '<script type="text/javascript" src="' . $filename
            . '"></script>' . PHP_EOL;
        $language = ($this->conf['translate_to'] == '')
            ? $sl
            : $this->conf['translate_to'];
        $action = $sn . '?&translator&admin=plugin_main&action=zip'
            . '&translator_lang=' . $language;
        $url = $sn . '?&translator&admin=plugin_main&action=edit'
            . ($this->conf['translate_fullscreen'] ? '&print' : '')
            . '&translator_from=' . $this->conf['translate_from']
            . '&translator_to=' . $language . '&translator_module=';
        $filename = isset($_POST['translator_filename'])
            ? $this->sanitizedName($_POST['translator_filename'])
            : '';
        $modules = isset($_POST['translator_modules'])
            ? $this->sanitizedName($_POST['translator_modules'])
            : array();
        $this->prepareMainView($action, $url, $filename, $modules)->render();
    }

    /**
     * @param string $action
     * @param string $url
     * @param string $filename
     * @return View
     */
    private function prepareMainView($action, $url, $filename, array $modules)
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
        return $view;
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
        $url = XH_hsc($url);
        return <<<EOT
        <li>
            <input type="checkbox" name="translator_modules[]"
                   value="$module"$checked>
            <a href="$url$module">$name</a>
        </li>

EOT;
    }

    /**
     * @return void
     */
    public function editAction()
    {
        global $sn;

        $module = $this->sanitizedName($_GET['translator_module']);
        $from = $this->sanitizedName($_GET['translator_from']);
        $to = $this->sanitizedName($_GET['translator_to']);
        $url = $sn . '?&translator&admin=plugin_main&action=save'
            . '&translator_from=' . $from . '&translator_to=' . $to
            . '&translator_module=' . $module;
        $this->prepareEditorView($url, $module, $from, $to)->render();
    }

    /**
     * @param string $action
     * @param string $module
     * @param string $sourceLanguage
     * @param string $destinationLanguage
     * @return View
     */
    private function prepareEditorView($action, $module, $sourceLanguage, $destinationLanguage)
    {
        global $_XH_csrfProtection;

        $view = new View('editor');
        $view->action = $action;
        $view->moduleName = ucfirst($module);
        $view->sourceLabel = new HtmlString($this->languageLabel($sourceLanguage));
        $view->destinationLabel = new HtmlString($this->languageLabel($destinationLanguage));
        $view->rows = new HtmlString($this->editorRows($module, $sourceLanguage, $destinationLanguage));
        $view->csrfTokenInput = new HtmlString($_XH_csrfProtection->tokenInput());
        return $view;
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
        $sourceText = XH_hsc($sourceText);
        $destinationText = XH_hsc($destinationText);
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
     * @return void
     */
    public function saveAction()
    {
        global $_XH_csrfProtection;

        if (isset($_XH_csrfProtection)) {
            $_XH_csrfProtection->check();
        }
        $module = $this->sanitizedName($_GET['translator_module']);
        $sourceLanguage = $this->sanitizedName($_GET['translator_from']);
        $destinationLanguage = $this->sanitizedName($_GET['translator_to']);
        $destinationTexts = array();
        $sourceTexts = $this->model->readLanguage($module, $sourceLanguage);
        if ($this->conf['sort_save']) {
            ksort($sourceTexts);
        }
        foreach (array_keys($sourceTexts) as $key) {
            $value = $_POST['translator_string_' . $key];
            if ($value != '' && $value != $this->lang['default_translation']) {
                $destinationTexts[$key] = $value;
            }
        }
        $saved = $this->model->writeLanguage($module, $destinationLanguage, $destinationTexts);
        $filename = $this->model->filename($module, $destinationLanguage);
        $o = $this->saveMessage($saved, $filename);
        $o .= $this->defaultAction();
        echo $o;
    }

    /**
     * @return void
     */
    public function zipAction()
    {
        global $_XH_csrfProtection;

        if (isset($_XH_csrfProtection)) {
            $_XH_csrfProtection->check();
        }
        $language = $this->sanitizedName($_GET['translator_lang']);
        if (empty($_POST['translator_modules'])) {
            echo XH_message('warning', $this->lang['message_no_module'])
                . $this->defaultAction();
            return;
        }
        $modules = $this->sanitizedName($_POST['translator_modules']);
        try {
            $contents = $this->model->zipArchive($modules, $language);
        } catch (Exception $exception) {
            echo XH_message('fail', $exception->getMessage())
                . $this->defaultAction();
            return;
        }
        $filename = $this->sanitizedName($_POST['translator_filename']);
        $filename = $this->model->downloadFolder() . $filename . '.zip';
        $saved = file_put_contents($filename, $contents) !== false;
        $o = $this->saveMessage($saved, $filename)
            . $this->defaultAction();
        if ($saved) {
            $url = $this->baseUrl() . $filename;
            $url = $this->model->canonicalUrl($url);
            $o .= $this->downloadUrl($url);
        }
        echo $o;
    }

    /**
     * @param string $url
     * @return string
     */
    private function downloadUrl($url)
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

    /**
     * Returns a sanitized name resp. an array of sanitized names.
     *
     * Sanitizing means, that all invalid characters are stripped; valid
     * characters are the 26 roman letters, the 10 arabic digits, the hyphen
     * and the underscore.
     *
     * @param mixed $input A name resp. an array of names.
     * @return mixed
     */
    private function sanitizedName($input)
    {
        if (is_array($input)) {
            return array_map(array($this, 'sanitizedName'), $input);
        } else {
            return preg_replace('/[^a-z0-9_-]/i', '', $input);
        }
    }

    /**
     * @return string
     */
    private function baseUrl()
    {
        global $sn;

        return 'http'
            . (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
            . '://' . $_SERVER['HTTP_HOST']
            . preg_replace('/index\.php$/', '', $sn);
    }

    /**
     * @param bool $success
     * @param string $filename
     * @return string
     */
    private function saveMessage($success, $filename)
    {
        global $plugin_tx;

        $ptx = $plugin_tx['translator'];
        $type = $success ? 'success' : 'fail';
        $message = sprintf($ptx['message_save_' . $type], $filename);
        return XH_message($type, $message);
    }
}
