<?php

/**
 * The model class of Translator_XH.
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
    protected $specialModules = array('CORE', 'CORE-LANGCONFIG', 'pluginloader');

    /**
     * Returns the path of the download folder.
     *
     * @return string
     *
     * @global array The paths of system files and folders.
     * @global array The configuration of the plugins.
     */
    public function downloadFolder()
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
    public function plugins()
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
     * Returns all translatable modules.
     *
     * @return array
     */
    public function modules()
    {
        $modules = array_merge(
            array('CORE', 'CORE-LANGCONFIG'),
            $this->plugins()
        );
        return $modules;
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
    public function filename($module, $language)
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
    protected function moduleVarname($module)
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
    public function readLanguage($module, $lang)
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
     * Returns the copyright header for a translated language file.
     *
     * @return string
     *
     * @global array The configuration of the plugins.
     *
     * @todo Use utf8_wordwrap() ;)
     */
    public function copyrightHeader()
    {
        global $plugin_cf;

        $pcf = $plugin_cf['translator'];
        $o = '';
        if (!empty($pcf['translation_author'])
            && !empty($pcf['translation_license'])
        ) {
            $year = date('Y');
            $license = wordwrap($pcf['translation_license'], 75, PHP_EOL . ' * ');
            $o .= <<<EOT
/*
 * Copyright (c) $year $pcf[translation_author]
 *
 * $license
 */
EOT;
        }
        return $o;
    }

    /**
     * Returns an array element definition line.
     *
     * @param string $varname A variable name.
     * @param string $key1    A key of the array.
     * @param string $key2    A key of the subarray.
     * @param mixed  $value   A value.
     *
     * @return string
     */
    public function elementDefinition($varname, $key1, $key2, $value)
    {
        $value = addcslashes($value, "\r\n\t\v\f\\\$\"");
        return <<<EOT
\${$varname}['$key1']['$key2']="$value";
EOT;
    }

    /**
     * Returns the PHP code for a language file.
     *
     * @param string $module A module name.
     * @param array  $texts  A language array.
     *
     * @return string
     */
    public function phpCode($module, $texts)
    {
        $o = '<?php' . PHP_EOL . PHP_EOL
            . $this->copyrightHeader() . PHP_EOL . PHP_EOL;
        if (in_array($module, $this->specialModules)) {
            $varname = $this->moduleVarname($module);
            foreach ($texts as $key => $val) {
                $keys = explode('_', $key, 2);
                $o .= $this->elementDefinition($varname, $keys[0], $keys[1], $val)
                    . PHP_EOL;
            }
            if ($module == 'pluginloader') {
                $specialKeys = array('cntopen', 'cntwriteto', 'notreadable');
                foreach ($specialKeys as $key2) {
                    $o .= <<<EOT
\$pluginloader_tx['error']['$key2']=\$tx['error']['$key2'];

EOT;
                }
            }
        } else {
            foreach ($texts as $key => $val) {
                $o .= $this->elementDefinition('plugin_tx', $module, $key, $val)
                    . PHP_EOL;
            }
        }
        $o .= PHP_EOL . '?>' . PHP_EOL;
        return $o;
    }

    /**
     * Writes a language file and returns whether that succeeded.
     *
     * @param string $module A module name.
     * @param string $lang   A language code.
     * @param array  $texts  A language array.
     *
     * @return bool
     */
    public function writeLanguage($module, $lang, $texts)
    {
        $filename = $this->filename($module, $lang);
        $contents = $this->phpCode($module, $texts);
        return file_put_contents($filename, $contents) !== false;
    }

    /**
     * Returns a ZIP archive.
     *
     * @param string $module   A module name.
     * @param string $language A language code.
     *
     * @return string
     *
     * @throws Exception
     *
     * @global array The paths of system files and folders.
     */
    public function zipArchive($modules, $language)
    {
        global $pth;

        include_once $pth['folder']['plugins'] . 'translator/zip.lib.php';
        $zip = new zipfile();
        foreach ($modules as $module) {
            $source = $_Translator->filename($module, $language);
            $destination = ltrim($source, './');
            if (file_exists($source)) {
                $contents = file_get_contents($source);
            } else {
                throw new Exception('missing language: ' . $source);
            }
            $zip->addFile($contents, $destination);
        }
        $contents = $zip->file();
        return $contents;
    }
}

?>
