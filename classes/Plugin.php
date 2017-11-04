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

use Pfw\View\HtmlView;
use Pfw\SystemCheckService;

class Plugin
{
    const VERSION = '@TRANSLATOR_VERSION@';
    /**
     * @return void
     */
    public function run()
    {
        global $o, $admin, $action;

        XH_registerStandardPluginMenuItems(true);
        if (XH_wantsPluginAdministration('translator')) {
            $o .= print_plugin_admin('on');
            switch ($admin) {
                case '':
                    $o .= $this->info();
                    break;
                case 'plugin_main':
                    $controller = new MainController;
                    ob_start();
                    switch ($action) {
                        case 'plugin_text':
                            $controller->defaultAction();
                            break;
                        case 'edit':
                            $controller->editAction();
                            break;
                        case 'save':
                            $controller->saveAction();
                            break;
                        case 'zip':
                            $controller->zipAction();
                            break;
                    }
                    $o .= ob_get_clean();
                    break;
                default:
                    $o .= plugin_admin_common($action, $admin, 'translator');
            }
        }
    }

    /**
     * @return string
     */
    private function info()
    {
        global $pth;

        ob_start();
        (new HtmlView('translator'))
            ->template('info')
            ->data([
                'logo' => "{$pth['folder']['plugin']}translator.png",
                'version' => Plugin::VERSION,
                'checks' => $this->getSystemChecks()
            ])
            ->render();
        return ob_get_clean();
    }

    /**
     * @return SystemCheck[]
     */
    private function getSystemChecks()
    {
        global $pth;

        $model = new Model;
        $systemCheckService = (new SystemCheckService)
            ->minPhpVersion('5.4.0')
            ->extension('zlib')
            ->minXhVersion('1.6.3')
            ->writable($model->downloadFolder())
            ->writable($pth['folder']['language'])
            ->writable("{$pth['folder']['plugins']}translator/css/")
            ->writable("{$pth['folder']['plugins']}translator/config/");
        foreach ($model->plugins() as $plugin) {
            $systemCheckService->writable("{$pth['folder']['plugins']}$plugin/languages/");
        }
        return $systemCheckService->getChecks();
    }
}
