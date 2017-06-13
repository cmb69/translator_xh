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

class MainController
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
     * @var array
     */
    private $conf;

    /**
     * @var array
     */
    private $lang;

    /**
     * @return void
     */
    public function __construct()
    {
        global $plugin_cf, $plugin_tx;

        $this->model = new Model();
        $this->views = new Views($this->model);
        $this->conf = $plugin_cf['translator'];
        $this->lang = $plugin_tx['translator'];
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        global $pth, $sn, $sl, $hjs;

        $filename = $pth['folder']['plugins'] . 'translator/translator.min.js';
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
        echo $this->views->main($action, $url, $filename, $modules);
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
        echo $this->views->editor($url, $module, $from, $to);
    }

    /**
     * @return void
     */
    public function saveAction()
    {
        global $_XH_csrfProtection;

        if (isset($_XH_csrfProtection)) {
            $_XH_csrfProtection->check();
        }
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
        $o = $this->views->saveMessage($saved, $filename);
        $o .= $this->defaultAction();
        echo $o;
    }

    /**
     * @return void
     */
    public function zipAction()
    {
        global $_XH_csrfProtection;

        if (isset($_XH_csrfProtection)) {
            $_XH_csrfProtection->check();
        }
        $language = $this->sanitizedName($_GET['translator_lang']);
        if (empty($_POST['translator_modules'])) {
            echo XH_message('warning', $this->lang['message_no_module'])
                . $this->defaultAction();
            return;
        }
        $modules = $this->sanitizedName($_POST['translator_modules']);
        try {
            $contents = $this->model->zipArchive($modules, $language);
        } catch (Exception $exception) {
            echo XH_message('fail', $exception->getMessage())
                . $this->defaultAction();
            return;
        }
        $filename = $this->sanitizedName($_POST['translator_filename']);
        $filename = $this->model->downloadFolder() . $filename . '.zip';
        $saved = file_put_contents($filename, $contents) !== false;
        $o = $this->views->saveMessage($saved, $filename)
            . $this->defaultAction();
        if ($saved) {
            $url = $this->baseUrl() . $filename;
            $url = $this->model->canonicalUrl($url);
            $o .= $this->views->downloadUrl($url);
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
}
