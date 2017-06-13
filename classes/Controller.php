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

class Controller
{
    /**
     * @var Model
     */
    private $model;

    /**
     * @var Views
     */
    private $views;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->model = new Model();
        $this->views = new Views($this->model);
        $this->dispatch();
    }

    /**
     * @return array
     */
    private function systemChecks()
    {
        global $pth, $plugin_tx;

        $ptx = $plugin_tx['translator'];
        $requiredPhpVersion = '5.4.0';
        $requiredXhVersion = '1.6.3';
        $checks = array();
        $checks[sprintf($ptx['syscheck_phpversion'], $requiredPhpVersion)]
            = version_compare(PHP_VERSION, $requiredPhpVersion, 'ge')
                ? 'ok' : 'fail';
        foreach (array('zlib') as $extension) {
            $checks[sprintf($ptx['syscheck_extension'], $extension)]
                = extension_loaded($extension) ? 'ok' : 'fail';
        }
        $checks[sprintf($ptx['syscheck_xhversion'], $requiredXhVersion)]
            = version_compare($this->model->xhVersion(), $requiredXhVersion, 'ge')
                ? 'ok' : 'warn';
        $folders = array();
        foreach ($this->model->plugins() as $plugin) {
            $folders[] = $pth['folder']['plugins'] . $plugin . '/languages/';
        }
        $furtherFolders = array(
            $pth['folder']['language'],
            $pth['folder']['plugins'] . 'translator/config',
            $pth['folder']['plugins'] . 'translator/css',
            $this->model->downloadFolder()
        );
        $folders = array_merge($folders, $furtherFolders);
        foreach ($folders as $folder) {
            $checks[sprintf($ptx['syscheck_writable'], $folder)]
                = is_writable($folder) ? 'ok' : 'warn';
        }
        return $checks;
    }

    /**
     * @return string
     */
    private function info()
    {
        global $pth;

        return $this->views->about($pth['folder']['plugin'] . 'translator.png')
            . tag('hr') . $this->views->systemCheck($this->systemChecks());
    }

    /**
     * @return void
     */
    private function dispatch()
    {
        global $o, $translator, $admin, $action;

        if (isset($translator) && $translator == 'true') {
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
}
