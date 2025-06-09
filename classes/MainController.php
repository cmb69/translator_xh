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

    private function renderMainView(Request $request, string $error = ""): string
    {
        $to = ($this->conf["translate_to"] == "") ? $request->language() : $this->conf["translate_to"];
        $filename = $this->sanitize($request->get("translator_filename") ?? "lang_$to");
        $modules = $this->sanitize($request->getArray("translator_modules") ?? []);
        $script = $this->pluginFolder . "translator.min.js";
        if (!file_exists($script)) {
            $script = $this->pluginFolder . "translator.js";
        }
        return $this->view->render("main", [
            "script" => $script,
            "modules" => $this->getModules($modules),
            "filename" => $filename,
            "error" => $error,
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
            $error = $this->view->message("fail", "error_no_module");
            return Response::create($this->renderMainView($request, $error));
        }
        return Response::create($this->renderEditorView($request, $modules));
    }

    /**
     * @param non-empty-list<string> $modules
     * @param ?array<string,string> $totexts
     */
    private function renderEditorView(
        Request $request,
        array $modules,
        string $error = "",
        ?array $totexts = null
    ): string {
        $module = $this->sanitize($modules[array_key_first($modules)]);
        $from = $this->conf["translate_from"];
        $to = ($this->conf["translate_to"] == "") ? $request->language() : $this->conf["translate_to"];
        return $this->view->render("editor", [
            "moduleName" => ucfirst($module),
            "from_label" => $this->languageLabel($from),
            "to_label" => $this->languageLabel($to),
            "rows" => $this->getEditorRows($module, $from, $to, $totexts),
            "csrf_token" => $this->csrfProtector->token(),
            "error" => $error,
        ]);
    }

    /**
     * @param ?array<string,string> $totexts
     * @return list<object{key:string,displayKey:string,className:string,fromtext:string,totext:string}>
     */
    private function getEditorRows(string $module, string $fromlang, string $tolang, ?array $totexts): array
    {
        $froml10n = Localization::read($module, $fromlang, $this->store);
        $fromtexts = $froml10n->texts();
        if ($totexts === null) {
            $tol10n = Localization::read($module, $tolang, $this->store);
            $totexts = $tol10n->texts();
        }
        if ($this->conf["sort_load"]) {
            ksort($fromtexts);
        }
        $rows = [];
        foreach ($fromtexts as $key => $fromtext) {
            $rows[] = $this->getEditorRow($key, $fromtext, $totexts);
        }
        return $rows;
    }

    /**
     * @param array<string,string> $totexts
     * @return object{key:string,displayKey:string,className:string,fromtext:string,totext:string}
     */
    private function getEditorRow(string $key, string $fromtext, array $totexts)
    {
        if (isset($totexts[$key])) {
            $totext = $totexts[$key];
        } elseif ($this->view->plain("default_translation") != "") {
            $totext = $this->view->plain("default_translation");
        } else {
            $totext = $fromtext;
        }
        return (object) [
            "key" => $key,
            "displayKey" => strtr($key, "_|", "  "),
            "className" => isset($totexts[$key]) ? "" : "translator_new",
            "fromtext" => $fromtext,
            "totext" => $totext,
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
        $fromlang = $this->conf["translate_from"];
        $tolang = ($this->conf["translate_to"] == "") ? $request->language() : $this->conf["translate_to"];
        $tol10n = Localization::modify($module, $tolang, $this->store);
        $froml10n = Localization::read($module, $fromlang, $this->store);
        $fromtexts = $froml10n->texts();
        if ($this->conf["sort_save"]) {
            ksort($fromtexts);
        }
        $totexts = [];
        foreach (array_keys($fromtexts) as $key) {
            $value = $request->post("translator_string_$key");
            if ($value != "" && $value != $this->view->plain("default_translation")) {
                $totexts[$key] = $value;
            }
        }
        if ($tol10n === null) {
            $error = $this->view->message("fail", "error_save", $this->model->filename($module, $tolang));
            return Response::create($this->renderEditorView($request, $modules, $error, $totexts));
        }
        $tol10n->setTexts($totexts);
        $tol10n->setCopyright($this->copyright($request));
        if (!$this->store->commit()) {
            $error = $this->view->message("fail", "error_save", $this->model->filename($module, $tolang));
            return Response::create($this->renderEditorView($request, $modules, $error, $totexts));
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
            $error = $this->view->message("fail", "error_no_module");
            return Response::create($this->renderMainView($request, $error));
        }
        $modules = $this->sanitize($modules);
        $contents = $this->model->zipArchive($modules, $language);
        if ($contents === null) {
            $error = $this->view->message("fail", "error_zip");
            return Response::create($this->renderMainView($request, $error));
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
}
