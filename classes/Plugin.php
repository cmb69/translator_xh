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

use Plib\DocumentStore2 as DocumentStore;
use Plib\Request;
use Plib\SystemChecker;
use Plib\View;

class Plugin
{
    public const VERSION = '1.0beta8';

    public static function infoController(): InfoController
    {
        global $pth;
        return new InfoController(
            $pth['folder']['language'],
            $pth["folder"]["plugins"],
            new SystemChecker(),
            self::view()
        );
    }

    public static function mainController(): MainController
    {
        global $pth, $plugin_cf;
        return new MainController(
            $pth["folder"]["plugins"] . "translator/",
            $plugin_cf["translator"],
            new DocumentStore($pth["folder"]["base"]),
            self::view()
        );
    }

    private static function view(): View
    {
        global $pth, $plugin_tx;
        return new View($pth["folder"]["plugins"] . "translator/views/", $plugin_tx["translator"]);
    }

    public function run(): void
    {
        global $o, $admin, $action;

        XH_registerStandardPluginMenuItems(true);
        if (XH_wantsPluginAdministration('translator')) {
            $o .= print_plugin_admin('on');
            switch ($admin) {
                case '':
                    $o .= self::InfoController()->defaultAction();
                    break;
                case 'plugin_main':
                    $controller = self::mainController();
                    switch ($action) {
                        case 'plugin_text':
                            $o .= $controller->defaultAction(Request::current());
                            break;
                        case 'edit':
                            $o .= $controller->editAction(Request::current());
                            break;
                        case 'save':
                            $o .= $controller->saveAction(Request::current());
                            break;
                        case 'zip':
                            $o .= $controller->zipAction(Request::current());
                            break;
                    }
                    break;
                default:
                    $o .= plugin_admin_common();
            }
        }
    }
}
