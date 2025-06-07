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

use Plib\SystemChecker;
use Plib\View;

class InfoController
{
    private string $languageFolder;
    private string $pluginsFolder;
    private SystemChecker $systemChecker;
    private View $view;

    public function __construct(
        string $languageFolder,
        string $pluginsFolder,
        SystemChecker $systemChecker,
        View $view
    ) {
        $this->languageFolder = $languageFolder;
        $this->pluginsFolder = $pluginsFolder;
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    public function defaultAction(): string
    {
        return $this->view->render("info", [
            "logo" => $this->pluginsFolder . "translator/translator.png",
            "version" => Plugin::VERSION,
            "checks" => $this->checks()
        ]);
    }

    /** @return list<object{state:string,label:string,stateLabel:string}> */
    private function checks()
    {
        $model = new Model();
        $checks = [
            $this->checkPhpVersion("7.4.0"),
            $this->checkExtension("zlib"),
            $this->checkXhVersion("1.6.3"),
            $this->checkWritabilty($model->downloadFolder()),
            $this->checkWritabilty($this->languageFolder),
            $this->checkWritabilty($this->pluginsFolder . "translator/css/"),
            $this->checkWritabilty($this->pluginsFolder . "translator/config/"),
        ];
        foreach ($model->plugins() as $plugin) {
            $checks[] = $this->checkWritabilty($this->pluginsFolder . "$plugin/languages/");
        }
        return $checks;
    }

    /** @return object{state:string,label:string,stateLabel:string} */
    private function checkPhpVersion(string $version)
    {
        $state = $this->systemChecker->checkVersion(PHP_VERSION, $version) ? "success" : "fail";
        return (object) [
            "state" => $state,
            "label" => $this->view->plain("syscheck_phpversion", $version),
            "stateLabel" => $this->view->plain("syscheck_$state"),
        ];
    }

    /** @return object{state:string,label:string,stateLabel:string} */
    private function checkExtension(string $name)
    {
        $state = $this->systemChecker->checkExtension($name) ? "success" : "fail";
        return (object) [
            "state" => $state,
            "label" => $this->view->plain("syscheck_extension", $name),
            "stateLabel" => $this->view->plain("syscheck_$state"),
        ];
    }

    /** @return object{state:string,label:string,stateLabel:string} */
    private function checkXhVersion(string $version)
    {
        $state = $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $version") ? "success" : "fail";
        return (object) [
            "state" => $state,
            "label" => $this->view->plain("syscheck_xhversion", $version),
            "stateLabel" => $this->view->plain("syscheck_$state"),
        ];
    }

    /** @return object{state:string,label:string,stateLabel:string} */
    private function checkWritabilty(string $filename)
    {
        $state = $this->systemChecker->checkWritability($filename) ? "success" : "warning";
        return (object) [
            "state" => $state,
            "label" => $this->view->plain("syscheck_writable", $filename),
            "stateLabel" => $this->view->plain("syscheck_$state"),
        ];
    }
}
