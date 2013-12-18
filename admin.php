<?php

/**
 * Back-end functionality of Translator_XH.
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

/*
 * Prevent direct access.
 */
if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * The version number of the plugin.
 */
define('TRANSLATOR_VERSION', '@TRANSLATOR_VERSION@');

/**
 * Returns the system checks.
 *
 * @return array
 *
 * @global array            The paths of system files and folders.
 * @global array            The localization of the core.
 * @global array            The localization of the plugins.
 * @global Translator_Model The translator model.
 */
function Translator_systemChecks()
{
    global $pth, $tx, $plugin_tx, $_Translator;

    $ptx = $plugin_tx['translator'];
    $requiredVersion = '5.0.0';
    $checks = array();
    $checks[sprintf($ptx['syscheck_phpversion'], $requiredVersion)]
        = version_compare(PHP_VERSION, $requiredVersion, 'ge') ? 'ok' : 'fail';
    foreach (array('zlib') as $extension) {
        $checks[sprintf($ptx['syscheck_extension'], $extension)]
            = extension_loaded($extension) ? 'ok' : 'fail';
    }
    $checks[$ptx['syscheck_magic_quotes']]
        = !get_magic_quotes_runtime() ? 'ok' : 'fail';
    $checks[$ptx['syscheck_encoding']]
        = (strtoupper($tx['meta']['codepage']) == 'UTF-8') ? 'ok' : 'warn';
    $folders = array();
    foreach ($_Translator->plugins() as $plugin) {
        $folders[] = $pth['folder']['plugins'] . $plugin . '/languages/';
    }
    array_unshift($folders, $pth['folder']['language']);
    array_push($folders, $_Translator->downloadFolder());
    foreach ($folders as $folder) {
        $checks[sprintf($ptx['syscheck_writable'], $folder)]
            = is_writable($folder) ? 'ok' : 'warn';
    }
    return $checks;
}

/**
 * Returns available plugins view.
 *
 * @return string
 *
 * @global array  The paths of system files and folders.
 * @global string The script name.
 * @global string The current language.
 * @global array  The configuration of the plugins.
 * @global array  The localization of the plugins.
 * @global object The translator model.
 * @global object The translator views.
 */
function Translator_administration()
{
    global $pth, $sn, $sl, $plugin_cf, $plugin_tx, $_Translator, $_Translator_views;

    $pcf = $plugin_cf['translator'];
    $ptx = $plugin_tx['translator'];
    $lang = empty($pcf['translate_to'])
        ? $sl
        : $pcf['translate_to'];
    $action = $sn . '?translator&amp;admin=plugin_main&amp;action=zip&amp;lang='
        . $lang;
    $url = $sn . '?translator&amp;admin=plugin_main&amp;action=edit'
        . ($pcf['translate_fullscreen'] ? '&amp;print' : '')
        . '&amp;from=' . $pcf['translate_from'] . '&amp;to='
        . $lang . '&amp;plugin=';
    $fn = isset($_POST['translator-filename']) ? $_POST['translator-filename'] : '';
    $modules = isset($_POST['translator-plugins'])
        ? $_POST['translator-plugins']
        : array();
    return $_Translator_views->main($action, $url, $fn, $modules);
}

/**
 * Returns a language marker.
 *
 * @param string $language A language code.
 *
 * @return string (X)HTML.
 */
function Translator_languageMarker($language)
{
    global $pth;

    $filename = $pth['folder']['flags'] . $language . '.gif';
    if (file_exists($filename)) {
        return tag(
            'img src="' . $filename . '" alt="' . $language
            . '" title="' . $language . '"'
        );
    } else {
        return $language;
    }
}

/**
 * Returns the translation editor view.
 *
 * @param string $plugin A plugin name.
 * @param string $from   A language code to translate from.
 * @param string $to     A language code to translate to.
 *
 * @return string
 *
 * @global string           The script name.
 * @global Translator_Views The translator views.
 */
function Translator_edit($plugin, $from, $to)
{
    global $sn, $_Translator_views;

    $url = $sn . '?translator&amp;admin=plugin_main&amp;action=save&amp;from='
        . $from . '&amp;to=' . $to . '&amp;plugin=' . $plugin;
    return $_Translator_views->editor($url, $plugin, $from, $to);
}

/**
 * Saves the translated language file and returns the main administration view.
 *
 * @param string $module              A module name.
 * @param string $sourceLanguage      A language code to translate from.
 * @param string $destinationLanguage A language code to translate to.
 *
 * @return string
 *
 * @global array            The configuration of the plugins.
 * @global Translator_Model The translator model.
 */
function Translator_save($module, $sourceLanguage, $destinationLanguage)
{
    global $plugin_cf, $_Translator;

    $pcf = $plugin_cf['translator'];
    $destinationTexts = array();
    $sourceTexts = $_Translator->readLanguage($module, $sourceLanguage);
    if ($pcf['sort_save']) {
        ksort($sourceTexts);
    }
    foreach ($sourceTexts as $key => $dummy) {
        $value = stsl($_POST['translator-' . $key]);
        if ($value != '' || $value != $pcf['default_translation']) {
            $destinationTexts[$key] = $value;
        }
    }
    $saved = $_Translator->writeLanguage(
        $module, $destinationLanguage, $destinationTexts
    );
    if (!$saved) {
        e(
            'cntsave', 'language',
            $_Translator->filename($module, $destinationLanguage)
        );
    }
    return Translator_administration();
}

/**
 * Creates a ZIP file with the language files of the selected plugins, and
 * returns the main administration view.
 *
 * @param string $lang A language code.
 *
 * @return string
 *
 * @global array            The paths of system files and folders.
 * @global string           The (X)HTML fragment containing error messages.
 * @global string           The script name.
 * @global array            The localization of the plugins.
 * @global Translator_Model The translator model.
 * @global object The translator views.
 */
function Translator_zip($lang)
{
    global $pth, $e, $sn, $plugin_tx, $_Translator, $_Translator_views;

    if (empty($_POST['translator-plugins'])) {
        $e .= '<li>' . $plugin_tx['translator']['error_no_plugin'] . '</li>'
            . PHP_EOL;
        return Translator_administration();
    }
    try {
        $cnt = $_Translator->zipArchive($_POST['translator-plugins'], $lang);
    } catch (Exception $ex) {
        $e .= '<li>' . $ex->getMessage() . '</li>' . PHP_EOL;
        return Translator_administration();
    }
    $ok = true;
    $filename = $_Translator->downloadFolder() . $_POST['translator-filename']
        . '.zip';
    if (file_put_contents($filename, $cnt) === false) {
        e('cntsave', 'file', $filename);
        $ok = false;
    }
    $o = Translator_administration();
    if ($ok) {
        $url = 'http://' . $_SERVER['SERVER_NAME'] . $sn . $filename; // TODO
        $url = $_Translator->canonicalUrl($url);
        $o .= $_Translator_views->downloadUrl($url);
    }
    return $o;
}

/*
 * Handle the plugin administration.
 */
if (isset($translator) && $translator == 'true') {
    include_once $pth['folder']['plugin_classes'] . 'Model.php';
    include_once $pth['folder']['plugin_classes'] . 'Views.php';
    $_Translator = new Translator_Model();
    $_Translator_views = new Translator_Views();
    $o .= print_plugin_admin('on');
    switch ($admin) {
    case '':
        $o .= $_Translator_views->about(
            $pth['folder']['plugin'] . 'translator.png'
        );
        $o .= tag('hr') . $_Translator_views->systemCheck();
        break;
    case 'plugin_main':
        switch ($action) {
        case 'plugin_text':
            $o .= Translator_administration();
            break;
        case 'edit':
            $o .= Translator_edit($_GET['plugin'], $_GET['from'], $_GET['to']);
            break;
        case 'save':
            $o .= Translator_save($_GET['plugin'], $_GET['from'], $_GET['to']);
            break;
        case 'zip':
            $o .= Translator_zip($_GET['lang']);
            break;
        }
        break;
    default:
        $o .= plugin_admin_common($action, $admin, $plugin);
    }
}

?>
