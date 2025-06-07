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

use Exception;
use stdClass;
use Plib\View;
use XH\CSRFProtection;

class MainController
{
    /**
     * @var Model
     */
    private $model;

    /**
     * @var array<string,string>
     */
    private $conf;

    /**
     * @var array<string,string>
     */
    private $lang;

    /**
     * @var CSRFProtection
     */
    private $csrfProtector;

    private View $view;

    /**
     * @return void
     */
    public function __construct(View $view)
    {
        global $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        $this->model = new Model();
        $this->conf = $plugin_cf['translator'];
        $this->lang = $plugin_tx['translator'];
        $this->csrfProtector = $_XH_csrfProtection;
        $this->view = $view;
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        global $pth, $sn, $sl, $hjs;

        $filename = "{$pth['folder']['plugins']}translator/translator.min.js";
        if (!file_exists($filename)) {
            $filename = "{$pth['folder']['plugins']}translator/translator.js";
        }
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
        echo $this->prepareMainView($action, $url, $filename, $modules);
    }

    /**
     * @param string $action
     * @param string $url
     * @param string $filename
     * @param list<string> $modules
     * @return string
     */
    private function prepareMainView($action, $url, $filename, array $modules)
    {
        return $this->view->render("main", [
            'action' => $action,
            'modules' => $this->getModules($url, $modules),
            'filename' => $filename,
            'csrfTokenInput' => $this->csrfProtector->tokenInput(),
        ]);
    }

    /**
     * @param string $url
     * @param list<string> $modules
     * @return list<object{module:string,name:string,url:string,checked:string}>
     */
    private function getModules($url, array $modules)
    {
        $modules = [];
        foreach ($this->model->modules() as $module) {
            $name = ucfirst($module);
            $checked = in_array($module, $modules) ? 'checked' : '';
            $modules[] = (object) compact('module', 'name', 'url', 'checked');
        }
        return $modules;
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
        echo $this->prepareEditorView($url, $module, $from, $to);
    }

    /**
     * @param string $action
     * @param string $module
     * @param string $sourceLanguage
     * @param string $destinationLanguage
     * @return string
     */
    private function prepareEditorView($action, $module, $sourceLanguage, $destinationLanguage)
    {
        return $this->view->render("editor", [
            'action' => $action,
            'moduleName' => ucfirst($module),
            'sourceLabel' => $this->languageLabel($sourceLanguage),
            'destinationLabel' => $this->languageLabel($destinationLanguage),
            'rows' => $this->getEditorRows($module, $sourceLanguage, $destinationLanguage),
            'csrfTokenInput' => $this->csrfProtector->tokenInput(),
        ]);
    }

    /**
     * @param string $module
     * @param string $sourceLanguage
     * @param string $destinationLanguage
     * @return stdClass[]
     */
    private function getEditorRows($module, $sourceLanguage, $destinationLanguage)
    {
        $sourceTexts = $this->model->readLanguage($module, $sourceLanguage);
        $destinationTexts = $this->model->readLanguage($module, $destinationLanguage);
        if ($this->conf['sort_load']) {
            ksort($sourceTexts);
        }
        $rows = [];
        foreach ($sourceTexts as $key => $sourceText) {
            $rows[] = $this->getEditorRow($key, $sourceText, $destinationTexts);
        }
        return $rows;
    }

    /**
     * @param string $key
     * @param string $sourceText
     * @param array<string,string> $destinationTexts
     * @return stdClass
     */
    private function getEditorRow($key, $sourceText, array $destinationTexts)
    {
        if (isset($destinationTexts[$key])) {
            $destinationText = $destinationTexts[$key];
        } elseif ($this->lang['default_translation'] != '') {
            $destinationText = $this->lang['default_translation'];
        } else {
            $destinationText = $sourceText;
        }
        $className = isset($destinationTexts[$key]) ? '' : 'translator_new';
        $displayKey = strtr($key, '_|', '  ');
        return (object) compact('key', 'displayKey', 'className', 'sourceText', 'destinationText');
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
        $this->csrfProtector->check();
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
        echo $this->saveMessage($saved, $filename);
        $this->defaultAction();
    }

    /**
     * @return void
     */
    public function zipAction()
    {
        $this->csrfProtector->check();
        $language = $this->sanitizedName($_GET['translator_lang']);
        if (empty($_POST['translator_modules'])) {
            echo XH_message('warning', $this->lang['message_no_module']);
            $this->defaultAction();
            return;
        }
        $modules = $this->sanitizedName($_POST['translator_modules']);
        try {
            $contents = $this->model->zipArchive($modules, $language);
        } catch (Exception $exception) {
            echo XH_message('fail', $exception->getMessage());
            $this->defaultAction();
            return;
        }
        $filename = $this->sanitizedName($_POST['translator_filename']);
        $filename = $this->model->downloadFolder() . $filename . '.zip';
        $saved = file_put_contents($filename, $contents) !== false;
        $o = $this->saveMessage($saved, $filename);
        $this->defaultAction();
        if ($saved) {
            $o .= $this->view->render("download", [
                'url' => $this->model->canonicalUrl($this->baseUrl() . $filename)
            ]);
        }
        echo $o;
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
        $type = $success ? 'success' : 'fail';
        return XH_message($type, $this->lang["message_save_{$type}"], $filename);
    }
}
