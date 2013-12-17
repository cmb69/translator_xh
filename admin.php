<?php

/**
 * Back-end functionality of Translator_XH.
 *
 * PHP versions 4 and 5
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
 * Returns the plugin version information view.
 *
 * @return string
 *
 * @global array The paths of system files and folders.
 */
function Translator_version()
{
    global $pth;

    $iconPath = $pth['folder']['plugins'] . 'translator/translator.png';
    return '<h1>Translator_XH</h1>' . PHP_EOL
        . tag(
            'img src="' . $iconPath . '" alt="Translate" width="128"'
            . ' height="128" style="float:left; margin-right: 16px"'
        )
        . '<p>Version: ' . TRANSLATOR_VERSION . '</p>' . PHP_EOL
        . '<p>Copyright &copy; 2011-2013 Christoph M. Becker</p>' . PHP_EOL
        . '<p style="text-align: justify">This program is free software:'
        . ' you can redistribute it and/or modify'
        . ' it under the terms of the GNU General Public License as published by'
        . ' the Free Software Foundation, either version 3 of the License, or'
        . ' (at your option) any later version.</p>' . PHP_EOL
        . '<p style="text-align: justify">This program is distributed'
        . ' in the hope that it will be useful,'
        . ' but WITHOUT ANY WARRANTY; without even the implied warranty of'
        . ' MERCHAN&shy;TABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the'
        . ' GNU General Public License for more details.</p>' . PHP_EOL
        . '<p style="text-align: justify">You should have received a copy of'
        . ' the GNU General Public License along with this program.  If not, see'
        . ' <a href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/'
        . '</a>.</p>' . PHP_EOL;
}


/**
 * Returns the system check view.
 *
 * @return string
 *
 * @global array The paths of system files and folders.
 * @global array The localization of the core.
 * @global array The localization of the plugins.
 * @global array The translator model.
 */
function Translator_systemCheck()
{
    global $pth, $tx, $plugin_tx, $_Translator;

    $requiredVersion = '4.3.0';
    $ptx = $plugin_tx['translator'];
    $imgdir = $pth['folder']['plugins'] . 'translator/images/';
    $ok = tag('img src="' . $imgdir . 'ok.png" alt="ok"');
    $warn = tag('img src="' . $imgdir . 'warn.png" alt="warning"');
    $fail = tag('img src="' . $imgdir . 'fail.png" alt="failure"');
    $o = tag('hr') . '<h4>' . $ptx['syscheck_title'] . '</h4>'
        . (version_compare(PHP_VERSION, $requiredVersion) >= 0 ? $ok : $fail)
        . '&nbsp;&nbsp;' . sprintf($ptx['syscheck_phpversion'], $requiredVersion)
        . tag('br') . tag('br') . PHP_EOL;
    foreach (array('zlib') as $ext) {
        $o .= (extension_loaded($ext) ? $ok : $fail)
            . '&nbsp;&nbsp;' . sprintf($ptx['syscheck_extension'], $ext)
            . tag('br') . PHP_EOL;
    }
    $o .= (!get_magic_quotes_runtime() ? $ok : $fail)
        . '&nbsp;&nbsp;' . $ptx['syscheck_magic_quotes'] . tag('br') . PHP_EOL;
    $o .= (strtoupper($tx['meta']['codepage']) == 'UTF-8' ? $ok : $warn)
        . '&nbsp;&nbsp;' . $ptx['syscheck_encoding']
        . tag('br') . tag('br') . PHP_EOL;
    $func = create_function(
        '$plugin',
        'return \'' . $pth['folder']['plugins'] . '\' . $plugin . \'/languages/\';'
    );
    $folders = array_map($func, $_Translator->plugins());
    array_unshift($folders, $pth['folder']['language']);
    array_push($folders, $_Translator->downloadFolder());
    foreach ($folders as $folder) {
        $o .= (is_writable($folder) ? $ok : $warn)
            . '&nbsp;&nbsp;' . sprintf($ptx['syscheck_writable'], $folder)
            . tag('br') . PHP_EOL;
    }
    return $o;
}

/**
 * Returns an absolute URL.
 *
 * @param string $url A relative URL.
 *
 * @return string
 *
 * @global string The script name.
 */
function Translator_absoluteUrl($url)
{
    global $sn;

    $parts = explode('/', $sn . $url);
    $i = 0;
    while ($i < count($parts)) {
        switch ($parts[$i]) {
        case '.':
            array_splice($parts, $i, 1);
            break;
        case '..':
            array_splice($parts, $i-1, 2);
            $i--;
            break;
        default:
            $i++;
        }
    }
    return $_SERVER['SERVER_NAME'] . implode('/', $parts);
}

/**
 * Writes a language file.
 *
 * @param string $plugin A plugin name.
 * @param string $lang   A language code.
 * @param array  &$texts Input param.
 *
 * @return void
 *
 * @global array  The paths of system files and folders.
 * @global array  The configuration of the plugins.
 * @global object The translator model.
 *
 * @todo What's with that "Input param"?
 * @todo Use utf8_wordwrap() ;)
 */
function Translator_writeLanguage($plugin, $lang, &$texts)
{
    global $pth, $plugin_cf, $_Translator;

    $pcf = $plugin_cf['translator'];

    $o = '<?php' . PHP_EOL . PHP_EOL;
    if (!empty($pcf['translation_author'])
        && !empty($pcf['translation_license'])
    ) {
        $o .= '/*' . PHP_EOL
            . ' * Copyright (c) ' . date('Y') . ' ' . $pcf['translation_author']
            . PHP_EOL
            . ' *' . PHP_EOL
            . ' * ' . wordwrap($pcf['translation_license'], 75, PHP_EOL . ' * ')
            . PHP_EOL . ' */' . PHP_EOL . PHP_EOL;
    }
    if (in_array($plugin, array('CORE', 'CORE-LANGCONFIG', 'pluginloader'))) {
        $tx_name = ($plugin == 'CORE')
            ? 'tx'
            : (($plugin == 'CORE-LANGCONFIG') ? 'txc' : 'pluginloader_tx');
        foreach ($texts as $key => $val) {
            $keys = explode('_', $key, 2);
            $o .= '$' . $tx_name . '[\'' . $keys[0] . '\'][\'' . $keys[1] . '\']="'
                . addcslashes($val, "\r\n\t\v\f\\\$\"") . '";' . PHP_EOL;
        }
        if ($plugin == 'pluginloader') {
            foreach (array('cntopen', 'cntwriteto', 'notreadable') as $k2) {
                $o .= '$pluginloader_tx[\'error\'][\'' . $k2 . '\']='
                    . '$tx[\'error\'][\'' . $k2 . '\'].\' \';' . PHP_EOL;
            }
        }
    } else {
        foreach ($texts as $key => $val) {
            $o .= '$plugin_tx[\'' . $plugin . '\'][\'' . $key . '\']="'
                . addcslashes($val, "\r\n\t\v\f\\\$\"") . '";' . PHP_EOL;
        }
    }
    $o .= PHP_EOL . '?>' . PHP_EOL;

    $fn = $_Translator->filename($plugin, $lang);
    if (($fh = fopen($fn, 'w')) === false || fwrite($fh, $o) === false) {
        e('cntsave', 'language', $fn);
    }
    if ($fh !== false) {
        fclose($fh);
    }
}

/**
 * Returns available plugins view.
 *
 * @return string
 *
 * @global array  The paths of system files and folders.
 * @global string The script name.
 * @global string The current language.
 * @global array  The localization of the core.
 * @global array  The configuration of the plugins.
 * @global array  The localization of the plugins.
 * @global object The translator model.
 */
function Translator_administration()
{
    global $pth, $sn, $sl, $tx, $plugin_cf, $plugin_tx, $_Translator;

    $pcf = $plugin_cf['translator'];
    $ptx = $plugin_tx['translator'];
    $lang = empty($pcf['translate_to'])
        ? $sl
        : $pcf['translate_to'];
    $url = $sn . '?translator&amp;admin=plugin_main&amp;action=zip&amp;lang='
        . $lang;
    $o = '<form id="translator-list" action="' . $url . '" method="POST">' . PHP_EOL
        . '<h1>' . $ptx['label_plugins'] . '</h1>' . PHP_EOL
        . '<ul>' . PHP_EOL;
    $url = $sn . '?translator&amp;admin=plugin_main&amp;action=edit'
        . ($pcf['translate_fullscreen'] ? '&amp;print' : '')
        . '&amp;from=' . $pcf['translate_from'] . '&amp;to='
        . $lang . '&amp;plugin=';
    $checked = (isset($_POST['translator-plugins'])
                && in_array('CORE', $_POST['translator-plugins']))
        ? ' checked="checked"'
        : '';
    $o .= '<li>'
        . tag(
            'input type="checkbox" name="translator-plugins[]" value="CORE"'
            . $checked
        )
        . '<a href="' . $url . 'CORE">CORE</a></li>' . PHP_EOL;
    $checked = (isset($_POST['translator-plugins'])
                && in_array('CORE-LANGCONFIG', $_POST['translator-plugins']))
        ? ' checked="checked"'
        : '';
    $o .= '<li>'
        . tag(
            'input type="checkbox" name="translator-plugins[]"'
            . ' value="CORE-LANGCONFIG"' . $checked
        )
        . '<a href="' . $url . 'CORE-LANGCONFIG">CORE-LANGCONFIG</a></li>' . PHP_EOL;
    foreach ($_Translator->plugins() as $plugin) {
        $checked = (isset($_POST['translator-plugins'])
                    && in_array($plugin, $_POST['translator-plugins']))
            ? ' checked="checked"'
            : '';
        $o .= '<li>'
            . tag(
                'input type="checkbox" name="translator-plugins[]" value="'
                . $plugin . '"' . $checked
            )
            . '<a href="' . $url . $plugin . '">' . ucfirst($plugin) . '</a></li>'
            . PHP_EOL;
    }
    $fn = isset($_POST['translator-filename']) ? $_POST['translator-filename'] : '';
    $o .= '</ul>' . PHP_EOL
        . tag(
            'input type="submit" class="submit" value="'
            . ucfirst($tx['action']['save']) . '"'
        )
        . ' ' . $ptx['label_filename'] . '&nbsp;'
        . tag('input type="text" name="translator-filename" value="' . $fn . '"')
        .'.zip</form>' . PHP_EOL;
    return $o;
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
 * @global array            The paths of system files and folders.
 * @global string           The script name.
 * @global array            The localization of the core.
 * @global array            The configuration of the plugins.
 * @global array            The localization of the plugins.
 * @global Translator_Model The translator model.
 */
function Translator_edit($plugin, $from, $to)
{
    global $pth, $sn, $tx, $plugin_cf, $plugin_tx, $_Translator;

    $pcf = $plugin_cf['translator'];
    $ptx = $plugin_tx['translator'];
    $url = $sn . '?translator&amp;admin=plugin_main&amp;action=save&amp;from='
        . $from . '&amp;to=' . $to . '&amp;plugin=' . $plugin;
    $o = '<form id="translator" method="post" action="' . $url . '">' . PHP_EOL
        . '<h1>' . ucfirst($plugin) . '</h1>' . PHP_EOL
        //.'Translator&nbsp;'.tag('input type="text" name="translator"')."\n"
        . tag(
            'input type="submit" class="submit" value="'
            . ucfirst($tx['action']['save']) . '"'
        )
        . PHP_EOL
        . '<table>' . PHP_EOL;
    foreach (array('from', 'to') as $lang) {
        $fn = $pth['folder']['flags'] . $$lang . '.gif';
        $lang_h = $lang . '_h';
        $$lang_h = file_exists($fn)
            ? tag(
                'img src="' . $fn . '" alt="' . $$lang . '" title="' . $$lang . '"'
            )
            : $$lang;
    }
    $o .= '<tr><th></th><th>' . $ptx['label_translate_from'] . '&nbsp;' . $from_h
        . '</th>'
        . '<th>' . $ptx['label_translate_to'] . '&nbsp;' . $to_h . '</th></tr>'
        . PHP_EOL;
    $from_tx = $_Translator->readLanguage($plugin, $from);
    $to_tx = $_Translator->readLanguage($plugin, $to);
    $hints = $_Translator->readLanguage($plugin, 'translation-hints');
    //if ($plugin != 'CORE') {ksort($from_tx);}
    if ($pcf['sort_load']) {
        ksort($from_tx);
    }
    foreach ($from_tx as $key => $from_val) {
        $to_val = isset($to_tx[$key])
            ? $to_tx[$key]
            : (empty($pcf['default_translation'])
                ? $from_tx[$key]
                : $pcf['default_translation']);
        $class = isset($to_tx[$key]) ? '' : ' class="new"';
        $help = isset($hints[$key])
            ? '<a class="pl_tooltip" href="javascript:return false">'
                . tag(
                    'img src="' . $pth['folder']['plugins']
                    . 'translator/images/help.png" alt="Help"'
                )
                . '<span>' . $hints[$key] . '</span></a>'
            : '';
        $o .= '<tr>'
            . '<td class="key">' . $help . $key . '</td>'
            . '<td class="from"><textarea rows="2" cols="40" readonly="readonly">'
            . htmlspecialchars($from_val) . '</textarea></td>'
            . '<td class="to"><textarea name="translator-' . $key . '"' . $class
            . ' rows="2" cols="40">'
            . htmlspecialchars($to_val) . '</textarea></td>'
            . '</tr>' . PHP_EOL;
    }
    $o .= '</table>' . PHP_EOL
        . tag(
            'input type="submit" class="submit" value="'
            . ucfirst($tx['action']['save']) . '"'
        )
        . PHP_EOL
        . '</form>' . PHP_EOL;

    return $o;
}

/**
 * Saves the translated language file and returns the main administration view.
 *
 * @param string $plugin A plugin name.
 * @param string $from   A language code to translate from.
 * @param string $to     A language code to translate to.
 *
 * @return string
 *
 * @global array            The configuration of the plugins.
 * @global Translator_Model The translator model.
 */
function Translator_save($plugin, $from, $to)
{
    global $plugin_cf, $_Translator;

    $pcf = $plugin_cf['translator'];
    $texts = array();
    if ($pcf['sort_save']) {
        foreach ($_POST as $key => $val) {
            $newval = stsl($val);
            if (strpos($key, 'translator-') === 0
                && (empty($pcf['default_translation'])
                || $newval != $pcf['default_translation'])
            ) {
                $texts[substr($key, 11)] = $newval;
            }
        }
    } else {
        $from_tx = $_Translator->readLanguage($plugin, $from);
        foreach ($from_tx as $key => $_) {
            $newval = stsl($_POST['translator-' . $key]);
            if (empty($pcf['default_translation'])
                || $newval != $pcf['default_translation']
            ) {
                $texts[$key] = $newval;
            }
        }
    }
    Translator_writeLanguage($plugin, $to, $texts);
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
 * @global array            The localization of the core.
 * @global array            The configuration of the plugins.
 * @global array            The localization of the plugins.
 * @global Translator_Model The translator model.
 */
function Translator_zip($lang)
{
    global $pth, $e, $tx, $plugin_cf, $plugin_tx, $_Translator;

    if (empty($_POST['translator-plugins'])) {
        $e .= '<li>' . $plugin_tx['translator']['error_no_plugin'] . '</li>'
            . PHP_EOL;
        return Translator_administration();
    }
    include_once $pth['folder']['plugins'] . 'translator/zip.lib.php';
    $zip = new zipfile();
    foreach ($_POST['translator-plugins'] as $plugin) {
        $src = $_Translator->filename($plugin, $lang);
        $dst = ltrim($src, './');
        if (file_exists($src)) {
            $cnt = file_get_contents($src);
        } else {
            e('missing', 'language', $src);
            return Translator_administration();
        }
        $zip->addFile($cnt, $dst);
    }
    $cnt = $zip->file();
    //header('Content-Type: application/save-as');
    //header('Content-Disposition: attachment; filename="'.$lang.'.zip"');
    //header('Content-Length:'.strlen($cnt));
    //header('Content-Transfer-Encoding: binary');
    //echo $cnt;
    //exit;
    $ok = true;
    $fn = $_Translator->downloadFolder() . $_POST['translator-filename'] . '.zip';
    //    if (file_exists($fn)) {
    //      e('alreadyexists', 'file', $fn);
    //      $ok = FALSE;
    //    }
    if (($fh = fopen($fn, 'w')) === false || fwrite($fh, $cnt) === false) {
        e('cntsave', 'file', $fn);
        $ok = false;
    }
    if ($fh !== false) {
        fclose($fh);
    }
    $o = Translator_administration();
    if ($ok) {
        $o .= '<p>' . $plugin_tx['translator']['label_download_url'] . tag('br')
            . tag(
                'input id="translator-download-link" type="text" disabled="disabled"'
                . ' value="http://' . Translator_absoluteUrl($fn) . '"'
            )
            . '</p>' . PHP_EOL;
    }
    return $o;
}

/*
 * Handle the plugin administration.
 */
if (isset($translator) && $translator == 'true') {
    include_once $pth['folder']['plugin_classes'] . 'Model.php';
    $_Translator = new Translator_Model();
    $o .= print_plugin_admin('on');
    switch ($admin) {
    case '':
        $o .= Translator_version() . Translator_systemCheck();
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
