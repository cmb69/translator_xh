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

use Plib\CsrfProtector;
use Plib\DocumentStore2 as DocumentStore;
use Plib\Request;
use Plib\Response;
use Plib\Url;
use Plib\View;
use Translator\Model\Localization;

class MainController
{
    private string $pluginFolder;

    private Model $model;

    /** @var array<string,string> */
    private array $conf;

    /** @var CsrfProtector */
    private $csrfProtector;

    private DocumentStore $store;

    private View $view;

    /** @param array<string,string> $conf */
    public function __construct(
        string $pluginFolder,
        array $conf,
        CsrfProtector $csrfProtector,
        DocumentStore $store,
        View $view
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->model = new Model();
        $this->conf = $conf;
        $this->csrfProtector = $csrfProtector;
        $this->store = $store;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->get("action")) {
            default:
                return $this->defaultAction($request);
            case "edit":
                return $this->editAction($request);
            case "zip":
                return $this->zipAction($request);
        }
    }

    private function defaultAction(Request $request): Response
    {
        return Response::create($this->renderMainView($request));
    }

    private function renderMainView(Request $request): string
    {
        $filename = $this->sanitize($request->get("translator_filename") ?? "");
        $modules = $this->sanitize($request->getArray("translator_modules") ?? []);
        $script = $this->pluginFolder . "translator.min.js";
        if (!file_exists($script)) {
            $script = $this->pluginFolder . "translator.js";
        }
        return $this->view->render("main", [
            "script" => $script,
            "modules" => $this->getModules($modules),
            "filename" => $filename,
        ]);
    }

    /**
     * @param list<string> $modules
     * @return list<object{module:string,name:string,checked:string}>
     */
    private function getModules(array $modules): array
    {
        $res = [];
        foreach ($this->model->modules() as $module) {
            $name = ucfirst($module);
            $checked = in_array($module, $modules) ? "checked" : "";
            $res[] = (object) [
                "module" => $module,
                "name" => $name,
                "checked" => $checked,
            ];
        }
        return $res;
    }

    private function editAction(Request $request): Response
    {
        if ($request->post("translator_do") !== null) {
            return $this->saveAction($request);
        }
        $modules = $request->getArray("translator_modules");
        if (empty($modules)) {
            // TODO error
            return Response::create($this->renderMainView($request));
        }
        return Response::create($this->renderEditorView($request, $modules));
    }

    /** @param non-empty-list<string> $modules */
    private function renderEditorView(
        Request $request,
        array $modules
    ): string {
        $module = $this->sanitize($modules[array_key_first($modules)]);
        $from = $this->conf["translate_from"];
        $to = ($this->conf["translate_to"] == "") ? $request->language() : $this->conf["translate_to"];
        return $this->view->render("editor", [
            "moduleName" => ucfirst($module),
            "sourceLabel" => $this->languageLabel($from),
            "destinationLabel" => $this->languageLabel($to),
            "rows" => $this->getEditorRows($module, $from, $to),
            "csrf_token" => $this->csrfProtector->token(),
        ]);
    }

    /** @return list<object{key:string,displayKey:string,className:string,sourceText:string,destinationText:string}> */
    private function getEditorRows(string $module, string $sourceLanguage, string $destinationLanguage): array
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
     * @param array<string,string> $destinationTexts
     * @return object{key:string,displayKey:string,className:string,sourceText:string,destinationText:string}
     */
    private function getEditorRow(string $key, string $sourceText, array $destinationTexts)
    {
        if (isset($destinationTexts[$key])) {
            $destinationText = $destinationTexts[$key];
        } elseif ($this->view->plain("default_translation") != "") {
            $destinationText = $this->view->plain("default_translation");
        } else {
            $destinationText = $sourceText;
        }
        return (object) [
            "key" => $key,
            "displayKey" => strtr($key, "_|", "  "),
            "className" => isset($destinationTexts[$key]) ? "" : "translator_new",
            "sourceText" => $sourceText,
            "destinationText" => $destinationText,
        ];
    }

    private function languageLabel(string $language): string
    {
        $filename = $this->model->flagIconPath($language);
        if ($filename !== false) {
            return '<img src="' . $filename . '" alt="' . $language . '" title="' . $language . '">';
        } else {
            return $language;
        }
    }

    private function saveAction(Request $request): Response
    {
        if (!$this->csrfProtector->check($request->post("translator_token"))) {
            return Response::error(403);
        }
        $modules = $request->getArray("translator_modules");
        if (empty($modules)) {
            return Response::redirect($request->url()->without("action")->absolute());
        }
        $module = $this->sanitize($modules[array_key_first($modules)]);
        $sourceLanguage = $this->conf["translate_from"];
        $destinationLanguage = ($this->conf["translate_to"] == "") ? $request->language() : $this->conf["translate_to"];
        $destinationL10n = Localization::modify($module, $destinationLanguage, $this->store);
        if ($destinationL10n === null) {
            return Response::create($this->saveMessage(false, $this->model->filename($module, $destinationLanguage))
                . $this->renderEditorView($request, $modules));
        }
        $destinationTexts = [];
        $sourceL10n = Localization::read($module, $sourceLanguage, $this->store);
        $sourceTexts = $sourceL10n->texts();
        if ($this->conf["sort_save"]) {
            ksort($sourceTexts);
        }
        foreach (array_keys($sourceTexts) as $key) {
            $value = $request->post("translator_string_$key");
            if ($value != "" && $value != $this->view->plain("default_translation")) {
                $destinationTexts[$key] = $value;
            }
        }
        $destinationL10n->setTexts($destinationTexts);
        $destinationL10n->setCopyright($this->copyright($request));
        if (!$this->store->commit()) {
            $filename = $this->model->filename($module, $destinationLanguage);
            return Response::create($this->saveMessage(false, $filename)
                . $this->renderEditorView($request, $modules));
        }
        return Response::redirect($request->url()->without("action")->absolute());
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

    private function zipAction(Request $request): Response
    {
        $language = ($this->conf["translate_to"] == "") ? $request->language() : $this->conf["translate_to"];
        $modules = $request->getArray("translator_modules");
        if (empty($modules)) {
            return Response::create($this->view->message("warning", "message_no_module")
                . $this->renderMainView($request));
        }
        $modules = $this->sanitize($modules);
        $contents = $this->model->zipArchive($modules, $language);
        if ($contents === null) {
            return Response::create($this->view->message("fail", "error_zip")
                . $this->renderMainView($request));
        }
        $filename = $this->sanitize($request->get("translator_filename") ?? "");
        return Response::create($contents)->withContentType("application/zip")
            ->withAttachment("$filename.zip")->withLength(strlen($contents));
    }

    /**
     * @param string|list<string> $input
     * @phpstan-return ($input is string ? string : list<string>)
     */
    private function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([$this, "sanitize"], $input);
        } else {
            return (string) preg_replace('/[^a-z0-9_-]/i', "", $input);
        }
    }

    private function saveMessage(bool $success, string $filename): string
    {
        $type = $success ? "success" : "fail";
        return $this->view->message($type, "message_save_{$type}", $filename);
    }
}
