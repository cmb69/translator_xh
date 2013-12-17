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
     * The names of special modules.
     *
     * @var array
     */
    var $specialModules = array('CORE', 'CORE-LANGCONFIG', 'pluginloader');

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
     * Returns the name of the language variable of a module.
     *
     * @param string $module A module name.
     *
     * @return string
     */
    function moduleVarname($module)
    {
        switch ($module) {
        case 'CORE':
            $varname = 'tx';
            break;
        case 'CORE-LANGCONFIG':
            $varname = 'txc';
            break;
        case 'pluginloader':
            $varname = 'pluginloader_tx';
            break;
        default:
            $varname = 'plugin_tx';
        }
        return $varname;
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
            if (in_array($module, $this->specialModules)) {
                $varname = $this->moduleVarname($module);
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

    /**
     * Writes a language file.
     *
     * @param string $module A module name.
     * @param string $lang   A language code.
     * @param array  $texts  A language array.
     *
     * @return void
     *
     * @global array  The configuration of the plugins.
     *
     * @todo Use utf8_wordwrap() ;)
     */
    function writeLanguage($module, $lang, $texts)
    {
        global $plugin_cf;

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
        if (in_array($module, $this->specialModules())) {
            $varname = $this->moduleVarname($module);
            foreach ($texts as $key => $val) {
                $keys = explode('_', $key, 2);
                $o .= '$' . $varname . '[\'' . $keys[0] . '\'][\'' . $keys[1] . '\']="'
                    . addcslashes($val, "\r\n\t\v\f\\\$\"") . '";' . PHP_EOL;
            }
            if ($module == 'pluginloader') {
                foreach (array('cntopen', 'cntwriteto', 'notreadable') as $k2) {
                    $o .= '$pluginloader_tx[\'error\'][\'' . $k2 . '\']='
                        . '$tx[\'error\'][\'' . $k2 . '\'].\' \';' . PHP_EOL;
                }
            }
        } else {
            foreach ($texts as $key => $val) {
                $o .= '$plugin_tx[\'' . $module . '\'][\'' . $key . '\']="'
                    . addcslashes($val, "\r\n\t\v\f\\\$\"") . '";' . PHP_EOL;
            }
        }
        $o .= PHP_EOL . '?>' . PHP_EOL;

        $filename = $this->filename($module, $lang);
        if (($stream = fopen($filename, 'w')) === false
            || fwrite($stream, $o) === false
        ) {
            e('cntsave', 'language', $filename);
        }
        if ($stream !== false) {
            fclose($stream);
        }
    }
}

?>
