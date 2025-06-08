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
use Plib\DocumentStore2 as DocumentStore;
use Plib\Request;
use Plib\Url;
use stdClass;
use Plib\View;
use Translator\Model\Localization;
use XH\CSRFProtection;

class MainController
{
    private string $pluginFolder;

    /**
     * @var Model
     */
    private $model;

    /** @var array<string,string> */
    private array $conf;

    /**
     * @var CSRFProtection
     */
    private $csrfProtector;

    private DocumentStore $store;
    private View $view;

    /** @param array<string,string> $conf */
    public function __construct(
        string $pluginFolder,
        array $conf,
        DocumentStore $store,
        View $view
    ) {
        global $_XH_csrfProtection;

        $this->pluginFolder = $pluginFolder;
        $this->model = new Model();
        $this->conf = $conf;
        $this->csrfProtector = $_XH_csrfProtection;
        $this->store = $store;
        $this->view = $view;
    }

    public function defaultAction(Request $request): string
    {
        global $hjs;

        $filename = $this->pluginFolder . "translator.min.js";
        if (!file_exists($filename)) {
            $filename = $this->pluginFolder . "translator.js";
        }
        $hjs .= '<script type="text/javascript" src="' . $filename
            . '"></script>' . PHP_EOL;
        $language = ($this->conf["translate_to"] == '')
            ? $request->language()
            : $this->conf["translate_to"];
        $action = $request->url()->with("action", "zip")->with("translator_lang", $language)->relative();
        $url = $request->url()->with("action", "edit")->with("translator_from", $this->conf["translate_from"])
            ->with("translator_to", $language);
        if ($this->conf["translate_fullscreen"]) {
            $url = $url->with("print");
        }
        $filename = isset($_POST['translator_filename'])
            ? $this->sanitizedName($_POST['translator_filename'])
            : '';
        $modules = isset($_POST['translator_modules'])
            ? $this->sanitizedName($_POST['translator_modules'])
            : array();
        return $this->prepareMainView($action, $url, $filename, $modules);
    }

    /**
     * @param string $action
     * @param string $filename
     * @param list<string> $modules
     * @return string
     */
    private function prepareMainView($action, Url $url, $filename, array $modules)
    {
        return $this->view->render("main", [
            'action' => $action,
            'modules' => $this->getModules($url, $modules),
            'filename' => $filename,
            'csrfTokenInput' => $this->csrfProtector->tokenInput(),
        ]);
    }

    /**
     * @param list<string> $modules
     * @return list<object{module:string,name:string,url:string,checked:string}>
     */
    private function getModules(Url $url, array $modules)
    {
        $modules = [];
        foreach ($this->model->modules() as $module) {
            $name = ucfirst($module);
            $checked = in_array($module, $modules) ? 'checked' : '';
            $modules[] = (object) [
                "module" => $module,
                "name" => $name,
                "url" => $url->with("translator_module", $module)->relative(),
                "checked" => $checked,
            ];
        }
        return $modules;
    }

    public function editAction(Request $request): string
    {
        $module = $this->sanitizedName($_GET['translator_module']);
        $from = $this->sanitizedName($_GET['translator_from']);
        $to = $this->sanitizedName($_GET['translator_to']);
        $url = $request->url()->with("action", "save")->with("translator_from", $from)
            ->with("translator_to", $to)->with("translator_module", $module)->relative();
        return $this->prepareEditorView($url, $module, $from, $to);
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
        $sourceL10n = Localization::read($module, $sourceLanguage, $this->store);
        $sourceTexts = $sourceL10n->texts();
        $destinationL10n = Localization::read($module, $destinationLanguage, $this->store);
        $destinationTexts = $destinationL10n->texts();
        if ($this->conf["sort_load"]) {
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
        } elseif ($this->view->plain("default_translation") != '') {
            $destinationText = $this->view->plain("default_translation");
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
            return '<img src="' . $filename . '" alt="' . $language . '" title="' . $language . '">';
        } else {
            return $language;
        }
    }

    public function saveAction(Request $request): string
    {
        $this->csrfProtector->check();
        $module = $this->sanitizedName($_GET['translator_module']);
        $sourceLanguage = $this->sanitizedName($_GET['translator_from']);
        $destinationLanguage = $this->sanitizedName($_GET['translator_to']);
        $destinationL10n = Localization::modify($module, $destinationLanguage, $this->store);
        if ($destinationL10n === null) {
            return $this->saveMessage(false, $this->model->filename($module, $destinationLanguage))
                . $this->defaultAction($request);
        }
        $destinationTexts = [];
        $sourceL10n = Localization::read($module, $sourceLanguage, $this->store);
        $sourceTexts = $sourceL10n->texts();
        if ($this->conf["sort_save"]) {
            ksort($sourceTexts);
        }
        foreach (array_keys($sourceTexts) as $key) {
            $value = $_POST['translator_string_' . $key];
            if ($value != '' && $value != $this->view->plain("default_translation")) {
                $destinationTexts[$key] = $value;
            }
        }
        $destinationL10n->setTexts($destinationTexts);
        $destinationL10n->setCopyright($this->copyright($request));
        $saved = $this->store->commit();
        $filename = $this->model->filename($module, $destinationLanguage);
        return $this->saveMessage($saved, $filename) . $this->defaultAction($request);
    }

    private function copyright(Request $request): string
    {
        if ($this->conf["translation_author"] && $this->conf["translation_license"]) {
            $year = date("Y", $request->time());
            $license = wordwrap($this->conf["translation_license"], 75, "\n * ");
            return <<<EOT
                /**
                 * Copyright (c) $year {$this->conf["translation_author"]}
                 *
                 * $license
                 */
                EOT . "\n\n";
        }
        return "";
    }

    public function zipAction(Request $request): string
    {
        $this->csrfProtector->check();
        $language = $this->sanitizedName($_GET['translator_lang']);
        if (empty($_POST['translator_modules'])) {
            return $this->view->message("warning", "message_no_module") . $this->defaultAction($request);
        }
        $modules = $this->sanitizedName($_POST['translator_modules']);
        try {
            $contents = $this->model->zipArchive($modules, $language);
        } catch (Exception $exception) {
            return XH_message('fail', $exception->getMessage()) . $this->defaultAction($request);
        }
        $filename = $this->sanitizedName($_POST['translator_filename']);
        $filename = $this->model->downloadFolder() . $filename . '.zip';
        $saved = file_put_contents($filename, $contents) !== false;
        $o = $this->saveMessage($saved, $filename);
        $o .= $this->defaultAction($request);
        if ($saved) {
            $o .= $this->view->render("download", [
                "url" => $request->url()->path($filename)->absolute(),
            ]);
        }
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
     * @param bool $success
     * @param string $filename
     * @return string
     */
    private function saveMessage($success, $filename)
    {
        $type = $success ? 'success' : 'fail';
        return $this->view->message($type, "message_save_{$type}", $filename);
    }
}
