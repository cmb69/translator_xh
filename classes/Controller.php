<?php

/**
 * The controller.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Translator
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2011-2017 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Translator_XH
 */

namespace Translator;

/**
 * The controller class.
 *
 * @category CMSimple_XH
 * @package  Translator
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Translator_XH
 */
class Controller
{
    /**
     * The translator model.
     *
     * @var Model
     */
    protected $model;

    /**
     * The translator views.
     *
     * @var Views
     */
    protected $views;

    /**
     * Initializes a new instance.
     *
     * @global array The paths of system files and folders.
     */
    public function __construct()
    {
        $this->model = new Model();
        $this->views = new Views($this->model);
        $this->dispatch();
    }

    /**
     * Returns the absolute URL of the folder of the requested index.php.
     *
     * @return string
     *
     * @global string The script name.
     */
    protected function baseUrl()
    {
        global $sn;

        return 'http'
            . (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
            . '://' . $_SERVER['HTTP_HOST']
            . preg_replace('/index\.php$/', '', $sn);
    }

    /**
     * Returns a sanitized name resp. an array of sanitized names.
     *
     * Sanitizing means, that all invalid characters are stripped; valid
     * characters are the 26 roman letters, the 10 arabic digits, the hyphen
     * and the underscore.
     *
     * @param mixed $input A name resp. an array of names.
     *
     * @return mixed
     */
    protected function sanitizedName($input)
    {
        if (is_array($input)) {
            return array_map(array($this, 'sanitizedName'), $input);
        } else {
            return preg_replace('/[^a-z0-9_-]/i', '', $input);
        }
    }

    /**
     * Returns the system checks.
     *
     * @return array
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the core.
     * @global array The localization of the plugins.
     */
    protected function systemChecks()
    {
        global $pth, $tx, $plugin_tx;

        $ptx = $plugin_tx['translator'];
        $requiredPhpVersion = '5.1.0';
        $requiredXhVersion = '1.5';
        $checks = array();
        $checks[sprintf($ptx['syscheck_phpversion'], $requiredPhpVersion)]
            = version_compare(PHP_VERSION, $requiredPhpVersion, 'ge')
                ? 'ok' : 'fail';
        foreach (array('pcre', 'zlib') as $extension) {
            $checks[sprintf($ptx['syscheck_extension'], $extension)]
                = extension_loaded($extension) ? 'ok' : 'fail';
        }
        $checks[$ptx['syscheck_magic_quotes']]
            = !get_magic_quotes_runtime() ? 'ok' : 'fail';
        $checks[sprintf($ptx['syscheck_xhversion'], $requiredXhVersion)]
            = version_compare($this->model->xhVersion(), $requiredXhVersion, 'ge')
                ? 'ok' : 'warn';
        $checks[$ptx['syscheck_encoding']]
            = (strtoupper($tx['meta']['codepage']) == 'UTF-8') ? 'ok' : 'warn';
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
     * Returns available modules view.
     *
     * @return string
     *
     * @global array  The paths of system files and folders.
     * @global string The script name.
     * @global string The current language.
     * @global string The document fragment to insert into the head element.
     * @global array  The configuration of the plugins.
     */
    protected function administrate()
    {
        global $pth, $sn, $sl, $hjs, $plugin_cf;

        $pcf = $plugin_cf['translator'];
        $filename = $pth['folder']['plugins'] . 'translator/translator.js';
        $hjs .= '<script type="text/javascript" src="' . $filename
            . '"></script>' . PHP_EOL;
        $language = ($pcf['translate_to'] == '')
            ? $sl
            : $pcf['translate_to'];
        $action = $sn . '?&translator&admin=plugin_main&action=zip'
            . '&translator_lang=' . $language;
        $url = $sn . '?&translator&admin=plugin_main&action=edit'
            . ($pcf['translate_fullscreen'] ? '&print' : '')
            . '&translator_from=' . $pcf['translate_from']
            . '&translator_to=' . $language . '&translator_module=';
        $filename = isset($_POST['translator_filename'])
            ? $this->sanitizedName($_POST['translator_filename'])
            : '';
        $modules = isset($_POST['translator_modules'])
            ? $this->sanitizedName($_POST['translator_modules'])
            : array();
        return $this->views->main($action, $url, $filename, $modules);
    }

    /**
     * Returns the plugin info view.
     *
     * @return string (X)HTML.
     *
     * @global array The paths of system files and folders.
     */
    protected function info()
    {
        global $pth;

        return $this->views->about($pth['folder']['plugin'] . 'translator.png')
            . tag('hr') . $this->views->systemCheck($this->systemChecks());
    }

    /**
     * Returns the translation editor view.
     *
     * @return string
     *
     * @global string The script name.
     */
    protected function edit()
    {
        global $sn;

        $module = $this->sanitizedName($_GET['translator_module']);
        $from = $this->sanitizedName($_GET['translator_from']);
        $to = $this->sanitizedName($_GET['translator_to']);
        $url = $sn . '?&translator&admin=plugin_main&action=save'
            . '&translator_from=' . $from . '&translator_to=' . $to
            . '&translator_module=' . $module;
        return $this->views->editor($url, $module, $from, $to);
    }

    /**
     * Saves the translated language file and returns the main administration view.
     *
     * @return string
     *
     * @global array             The configuration of the plugins.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    protected function save()
    {
        global $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        $pcf = $plugin_cf['translator'];
        $ptx = $plugin_tx['translator'];
        if (isset($_XH_csrfProtection)) {
            $_XH_csrfProtection->check();
        }
        $module = $this->sanitizedName($_GET['translator_module']);
        $sourceLanguage = $this->sanitizedName($_GET['translator_from']);
        $destinationLanguage = $this->sanitizedName($_GET['translator_to']);
        $destinationTexts = array();
        $sourceTexts = $this->model->readLanguage($module, $sourceLanguage);
        if ($pcf['sort_save']) {
            ksort($sourceTexts);
        }
        foreach ($sourceTexts as $key => $dummy) {
            $value = stsl($_POST['translator_string_' . $key]);
            if ($value != '' && $value != $ptx['default_translation']) {
                $destinationTexts[$key] = $value;
            }
        }
        $saved = $this->model->writeLanguage(
            $module, $destinationLanguage, $destinationTexts
        );
        $filename = $this->model->filename($module, $destinationLanguage);
        $o = $this->views->saveMessage($saved, $filename);
        $o .= $this->administrate();
        return $o;
    }

    /**
     * Creates a ZIP file with the language files of the selected modules, and
     * returns the main administration view.
     *
     * @return string
     *
     * @global array             The paths of system files and folders.
     * @global string            The script name.
     * @global array             The localization of the plugins.
     * @global XH_CSRFProtection The CSRF protector.
     */
    protected function zip()
    {
        global $pth, $sn, $plugin_tx, $_XH_csrfProtection;

        $ptx = $plugin_tx['translator'];
        if (isset($_XH_csrfProtection)) {
            $_XH_csrfProtection->check();
        }
        $language = $this->sanitizedName($_GET['translator_lang']);
        if (empty($_POST['translator_modules'])) {
            return $this->views->message('warning', $ptx['message_no_module'])
                . $this->administrate();
        }
        $modules = $this->sanitizedName($_POST['translator_modules']);
        try {
            $contents = $this->model->zipArchive($modules, $language);
        } catch (Exception $exception) {
            return $this->views->message('fail', $exception->getMessage())
                . $this->administrate();
        }
        $filename = $this->sanitizedName($_POST['translator_filename']);
        $filename = $this->model->downloadFolder() . $filename . '.zip';
        $saved = file_put_contents($filename, $contents) !== false;
        $o = $this->views->saveMessage($saved, $filename)
            . $this->administrate();
        if ($saved) {
            $url = $this->baseUrl() . $filename;
            $url = $this->model->canonicalUrl($url);
            $o .= $this->views->downloadUrl($url);
        }
        return $o;
    }

    /**
     * Handles the plugin administration.
     *
     * @return void
     *
     * @global string The document fragment for the contents area.
     * @global array  The paths of system files and folders.
     * @global string Whether the plugin administration is requested.
     * @global string The value of the admin G/P paramter.
     * @global string The value of the action G/P parameter.     *
     */
    protected function dispatch()
    {
        global $o, $pth, $translator, $admin, $action;

        if (isset($translator) && $translator == 'true') {
            $o .= print_plugin_admin('on');
            switch ($admin) {
            case '':
                $o .= $this->info();
                break;
            case 'plugin_main':
                switch ($action) {
                case 'plugin_text':
                    $o .= $this->administrate();
                    break;
                case 'edit':
                    $o .= $this->edit();
                    break;
                case 'save':
                    $o .= $this->save();
                    break;
                case 'zip':
                    $o .= $this->zip();
                    break;
                }
                break;
            default:
                $o .= plugin_admin_common($action, $admin, 'translator');
            }
        }
    }
}

?>
