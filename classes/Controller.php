<?php

/**
 * The controller.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Translator
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2011-2013 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Translator_XH
 */

/**
 * The controller class.
 *
 * @category CMSimple_XH
 * @package  Translator
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Translator_XH
 */
class Translator_Controller
{
    /**
     * The translator model.
     *
     * @var Translator_Model
     */
    protected $model;

    /**
     * The translator views.
     *
     * @var Translator_Views
     */
    protected $views;

    /**
     * Initializes a new instance.
     *
     * @global array The paths of system files and folders.
     */
    public function __construct()
    {
        global $pth;

        include_once $pth['folder']['plugin_classes'] . 'Model.php';
        include_once $pth['folder']['plugin_classes'] . 'Views.php';
        $this->model = new Translator_Model();
        $this->views = new Translator_Views($this->model);
        $this->dispatch();
    }

    /**
     * Returns the CMSimple_XH version.
     *
     * Unfortunately, we can't use CMSIMPLE_XH_VERSION directly, as this is set
     * by CMSimple v4.
     *
     * @return string
     */
    protected function xhVersion()
    {
        $version = CMSIMPLE_XH_VERSION;
        if (strpos($version, 'CMSimple_XH') === 0) {
            $version = substr($version, strlen('CMSimple_XH '));
        } else {
            $version = '0';
        }
        return $version;
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
        $requiredPhpVersion = '5.0.0';
        $requiredXhVersion = '1.5';
        $checks = array();
        $checks[sprintf($ptx['syscheck_phpversion'], $requiredPhpVersion)]
            = version_compare(PHP_VERSION, $requiredPhpVersion, 'ge')
                ? 'ok' : 'fail';
        foreach (array('zlib') as $extension) {
            $checks[sprintf($ptx['syscheck_extension'], $extension)]
                = extension_loaded($extension) ? 'ok' : 'fail';
        }
        $checks[$ptx['syscheck_magic_quotes']]
            = !get_magic_quotes_runtime() ? 'ok' : 'fail';
        $checks[sprintf($ptx['syscheck_xhversion'], $requiredXhVersion)]
            = version_compare($this->xhVersion(), $requiredXhVersion, 'ge')
                ? 'ok' : 'warn';
        $checks[$ptx['syscheck_encoding']]
            = (strtoupper($tx['meta']['codepage']) == 'UTF-8') ? 'ok' : 'warn';
        $folders = array();
        foreach ($this->model->plugins() as $plugin) {
            $folders[] = $pth['folder']['plugins'] . $plugin . '/languages/';
        }
        array_unshift($folders, $pth['folder']['language']);
        array_push($folders, $this->model->downloadFolder());
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
     * @global array  The configuration of the plugins.
     * @global array  The localization of the plugins.
     *
     * @todo Improve variable names.
     */
    protected function administration()
    {
        global $pth, $sn, $sl, $plugin_cf, $plugin_tx;

        $pcf = $plugin_cf['translator'];
        $ptx = $plugin_tx['translator'];
        $lang = ($pcf['translate_to'] == '')
            ? $sl
            : $pcf['translate_to'];
        $action = $sn . '?translator&amp;admin=plugin_main&amp;action=zip'
            . '&amp;translator_lang=' . $lang;
        $url = $sn . '?translator&amp;admin=plugin_main&amp;action=edit'
            . ($pcf['translate_fullscreen'] ? '&amp;print' : '')
            . '&amp;translator_from=' . $pcf['translate_from']
            . '&amp;translator_to=' . $lang . '&amp;translator_module=';
        $filename = isset($_POST['translator_filename'])
            ? $_POST['translator_filename']
            : '';
        $modules = isset($_POST['translator_modules'])
            ? $_POST['translator_modules']
            : array();
        return $this->views->main($action, $url, $filename, $modules);
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

        $module = $_GET['translator_module'];
        $from = $_GET['translator_from'];
        $to = $_GET['translator_to'];
        $url = $sn . '?translator&amp;admin=plugin_main&amp;action=save'
            . '&amp;translator_from=' . $from . '&amp;translator_to=' . $to
            . '&amp;translator_module=' . $module;
        return $this->views->editor($url, $module, $from, $to);
    }

    /**
     * Saves the translated language file and returns the main administration view.
     *
     * @return string
     *
     * @global array The configuration of the plugins.
     */
    protected function save()
    {
        global $plugin_cf;

        $pcf = $plugin_cf['translator'];
        $module = $_GET['translator_module'];
        $sourceLanguage = $_GET['translator_from'];
        $destinationLanugage = $_GET['translator_to'];
        $destinationTexts = array();
        $sourceTexts = $this->model->readLanguage($module, $sourceLanguage);
        if ($pcf['sort_save']) {
            ksort($sourceTexts);
        }
        foreach ($sourceTexts as $key => $dummy) {
            $value = stsl($_POST['translator_string_' . $key]);
            if ($value != '' || $value != $pcf['default_translation']) {
                $destinationTexts[$key] = $value;
            }
        }
        $saved = $this->model->writeLanguage(
            $module, $destinationLanguage, $destinationTexts
        );
        if (!$saved) {
            e(
                'cntsave', 'language',
                $this->model->filename($module, $destinationLanguage)
            );
        }
        return $this->administration();
    }

    /**
     * Creates a ZIP file with the language files of the selected modules, and
     * returns the main administration view.
     *
     * @return string
     *
     * @global array  The paths of system files and folders.
     * @global string The (X)HTML fragment containing error messages.
     * @global string The script name.
     * @global array  The localization of the plugins.
     */
    protected function zip()
    {
        global $pth, $e, $sn, $plugin_tx;

        $language = $_GET['translator_lang'];
        if (empty($_POST['translator_modules'])) {
            $e .= '<li>' . $plugin_tx['translator']['error_no_module'] . '</li>'
                . PHP_EOL;
            return $this->administration();
        }
        try {
            $contents = $this->model->zipArchive(
                $_POST['translator_modules'], $language
            );
        } catch (Exception $exception) {
            $e .= '<li>' . $exception->getMessage() . '</li>' . PHP_EOL;
            return $this->administration();
        }
        $ok = true;
        $filename = $this->model->downloadFolder() . $_POST['translator_filename']
            . '.zip';
        if (file_put_contents($filename, $contents) === false) {
            e('cntsave', 'file', $filename);
            $ok = false;
        }
        $o = $this->administration();
        if ($ok) {
            $url = 'http://' . $_SERVER['SERVER_NAME'] . $sn . $filename; // TODO
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
                $o .= $this->views->about(
                    $pth['folder']['plugin'] . 'translator.png'
                );
                $o .= tag('hr') . $this->views->systemCheck($this->systemChecks());
                break;
            case 'plugin_main':
                switch ($action) {
                case 'plugin_text':
                    $o .= $this->administration();
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
