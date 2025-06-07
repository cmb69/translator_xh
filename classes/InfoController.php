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
    private SystemChecker $systemChecker;
    private View $view;

    public function __construct(SystemChecker $systemChecker, View $view)
    {
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        global $pth;

        echo $this->view->render("info", [
            'logo' => "{$pth['folder']['plugin']}translator.png",
            'version' => Plugin::VERSION,
            'checks' => $this->checks()
        ]);
    }

    /** @return list<object{state:string,label:string,stateLabel:string}> */
    private function checks()
    {
        global $pth;

        $model = new Model();
        $checks = [
            $this->checkPhpVersion("7.4.0"),
            $this->checkExtension("zlib"),
            $this->checkXhVersion("1.6.3"),
            $this->checkWritabilty($model->downloadFolder()),
            $this->checkWritabilty($pth['folder']['language']),
            $this->checkWritabilty("{$pth['folder']['plugins']}translator/css/"),
            $this->checkWritabilty("{$pth['folder']['plugins']}translator/config/"),
        ];
        foreach ($model->plugins() as $plugin) {
            $checks[] = $this->checkWritabilty("{$pth['folder']['plugins']}$plugin/languages/");
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
