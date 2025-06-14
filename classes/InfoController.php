<?php

/*
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

use Plib\Response;
use Plib\SystemChecker;
use Plib\View;
use Translator\Model\Service;

class InfoController
{
    private string $languageFolder;
    private string $pluginsFolder;
    private Service $service;
    private SystemChecker $systemChecker;
    private View $view;

    public function __construct(
        string $languageFolder,
        string $pluginsFolder,
        Service $service,
        SystemChecker $systemChecker,
        View $view
    ) {
        $this->languageFolder = $languageFolder;
        $this->pluginsFolder = $pluginsFolder;
        $this->service = $service;
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    public function __invoke(): Response
    {
        return Response::create($this->view->render("info", [
            "logo" => $this->pluginsFolder . "translator/translator.png",
            "version" => Plugin::VERSION,
            "checks" => $this->checks()
        ]))->withTitle("Translator " . $this->view->esc(Plugin::VERSION));
    }

    /** @return list<string> */
    private function checks()
    {
        $checks = [
            $this->checkPhpVersion("7.4.0"),
            $this->checkExtension("zlib"),
            $this->checkXhVersion("1.7.0"),
            $this->checkPlibVersion("1.10"),
            $this->checkWritabilty($this->languageFolder),
            $this->checkWritabilty($this->pluginsFolder . "translator/css/"),
            $this->checkWritabilty($this->pluginsFolder . "translator/config/"),
        ];
        foreach ($this->service->plugins() as $plugin) {
            $checks[] = $this->checkWritabilty($this->pluginsFolder . "$plugin/languages/");
        }
        return $checks;
    }

    private function checkPhpVersion(string $version): string
    {
        $state = $this->systemChecker->checkVersion(PHP_VERSION, $version) ? "success" : "fail";
        return $this->view->message(
            $state,
            "syscheck_message",
            $this->view->plain("syscheck_phpversion", $version),
            $this->view->plain("syscheck_$state")
        );
    }

    private function checkExtension(string $name): string
    {
        $state = $this->systemChecker->checkExtension($name) ? "success" : "fail";
        return $this->view->message(
            $state,
            "syscheck_message",
            $this->view->plain("syscheck_extension", $name),
            $this->view->plain("syscheck_$state")
        );
    }

    private function checkXhVersion(string $version): string
    {
        $state = $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $version") ? "success" : "fail";
        return $this->view->message(
            $state,
            "syscheck_message",
            $this->view->plain("syscheck_xhversion", $version),
            $this->view->plain("syscheck_$state")
        );
    }

    private function checkPlibVersion(string $version): string
    {
        $state = $this->systemChecker->checkPlugin("plib", $version) ? "success" : "fail";
        return $this->view->message(
            $state,
            "syscheck_message",
            $this->view->plain("syscheck_plibversion", $version),
            $this->view->plain("syscheck_$state")
        );
    }

    private function checkWritabilty(string $filename): string
    {
        $state = $this->systemChecker->checkWritability($filename) ? "success" : "warning";
        return $this->view->message(
            $state,
            "syscheck_message",
            $this->view->plain("syscheck_writable", $filename),
            $this->view->plain("syscheck_$state")
        );
    }
}
