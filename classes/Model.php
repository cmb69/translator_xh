<?php

/**
 * The model class of Translator_XH.
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

/**
 * The model class of Translator_XH.
 *
 * @category CMSimple_XH
 * @package  Translator
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Translator_XH
 */
class Translator_Model
{
    /**
     * Returns the path of the download folder.
     *
     * @return string
     *
     * @global array The paths of system files and folders.
     * @global array The configuration of the plugins.
     */
    function downloadFolder()
    {
        global $pth, $plugin_cf;

        $path = $pth['folder']['base'];
        if ($plugin_cf['translator']['folder_download'] != '') {
            $path .= rtrim($plugin_cf['translator']['folder_download'] , '/')
                . '/';
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }

    /**
     * Returns all internationalized plugins.
     *
     * @return array
     *
     * @global array The paths of system files and folders.
     */
    function plugins()
    {
        global $pth;

        $plugins = array();
        $dir = $pth['folder']['plugins'];
        $handle = opendir($dir);
        if ($handle) {
            while (($entry = readdir($handle)) !== false) {
                if ($entry[0] != '.' && is_dir($dir . $entry . '/languages/')) {
                    $plugins[] = $entry;
                }
            }
            closedir($handle);
        }
        sort($plugins);
        return $plugins;
    }

    /**
     * Returns the path of a language file.
     *
     * @param string $module   A module name.
     * @param string $language A language code.
     *
     * @return string
     *
     * @global array The paths of system files and folders.
     */
    function filename($module, $language)
    {
        global $pth;

        $filename = ($module == 'CORE'|| $module == 'CORE-LANGCONFIG')
            ? $pth['folder']['language']
            : $pth['folder']['plugins'] . $module . '/languages/';
        $filename .= $language;
        if ($module == 'CORE-LANGCONFIG') {
            $filename .= 'config';
        }
        $filename .= '.php';
        return $filename;
    }

    /**
     * Reads a language file and returns the value of the variable defined in it.
     *
     * @param string $module A module name.
     * @param string $lang   A language code.
     *
     * @return array
     *
     * @global array The paths of system files and folders.
     * @global array The localization of the core.
     *
     * @todo Refactor to return $texts.
     */
    function readLanguage($module, $lang)
    {
        global $pth;
        // The pluginloaders language file uses $tx.
        if ($module == 'pluginloader') {
            global $tx;
        }

        $texts = array();
        $filename = $this->filename($module, $lang);
        if (file_exists($filename)) {
            include $filename;
            $specialModules = array('CORE', 'CORE-LANGCONFIG', 'pluginloader');
            if (in_array($module, $specialModules)) {
                switch ($module) {
                case 'CORE':
                    $varname = 'tx';
                    break;
                case 'CORE-LANGCONFIG':
                    $varname = 'txc';
                    break;
                default:
                    $varname = 'pluginloader_tx';
                }
                $varname = ($module == 'CORE')
                    ? 'tx'
                    : (($module == 'CORE-LANGCONFIG') ? 'txc' : 'pluginloader_tx');
                foreach ($$varname as $key1 => $val1) {
                    foreach ($val1 as $key2 => $val2) {
                        if ($module != 'pluginloader'
                            || $key1 != 'error' || $key2 == 'plugin_error'
                        ) {
                            $texts[$key1 . '_' . $key2] = $val2;
                        }
                    }
                }
            } else {
                foreach ($plugin_tx[$module] as $key => $val) {
                    $texts[$key] = $val;
                }
            }
        }
        return $texts;
    }
}

?>
