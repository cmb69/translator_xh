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

class Model
{
    /**
     * @var array
     */
    private $specialModules = array('CORE', 'CORE-LANGCONFIG', 'pluginloader');

    /**
     * @return string
     */
    public function xhVersion()
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
     * @param string $url
     * @return string
     */
    public function canonicalUrl($url)
    {
        $parts = explode('/', $url);
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
        return implode('/', $parts);
    }

    /**
     * @param string $language
     * @return string
     */
    public function flagIconPath($language)
    {
        global $pth;

        $filename = $pth['folder']['flags'] . $language . '.gif';
        if (!file_exists($filename)) {
            $filename = false;
        }
        return $filename;
    }

    /**
     * @return string
     */
    public function downloadFolder()
    {
        global $pth;

        return $pth['folder']['downloads'];
    }

    /**
     * @return array
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
     * @return array
     */
    public function modules()
    {
        $modules = $this->plugins();
        if (version_compare($this->xhVersion(), '1.6', 'lt')) {
            array_unshift($modules, 'CORE-LANGCONFIG');
        }
        array_unshift($modules, 'CORE');
        return $modules;
    }

    /**
     * @param string $module
     * @param string $language
     * @return string
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
     * @param string $module
     * @return string
     */
    private function moduleVarname($module)
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
     * @param string $module
     * @param string $lang
     * @return array
     */
    public function readLanguage($module, $lang)
    {
        global $pth;

        if ($module == 'pluginloader') {
            $tx = $GLOBALS['tx'];
        }

        $texts = array();
        $filename = $this->filename($module, $lang);
        if (file_exists($filename)) {
            $varname = $this->moduleVarname($module);
            if (function_exists('XH_includeVar')) {
                $$varname = XH_includeVar($filename, $varname);
            } else {
                include $filename;
            }
            if (in_array($module, $this->specialModules)) {
                foreach ($$varname as $key1 => $val1) {
                    foreach ($val1 as $key2 => $val2) {
                        if ($module != 'pluginloader'
                            || $key1 != 'error' || $key2 == 'plugin_error'
                        ) {
                            $texts[$key1 . '|' . $key2] = $val2;
                        }
                    }
                }
            } else {
                foreach ($plugin_tx[$module] as $key => $val) {
                    $key = preg_replace('/_/', '|', $key, 1);
                    $texts[$key] = $val;
                }
            }
        }
        return $texts;
    }

    /**
     * @return string
     * @todo Use utf8_wordwrap() ;)
     */
    private function copyrightHeader()
    {
        global $plugin_cf;

        $pcf = $plugin_cf['translator'];
        $o = '';
        if ($pcf['translation_author'] != '' && $pcf['translation_license'] != '') {
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
     * @param string $varname
     * @param string $key1
     * @param string $key2
     * @return string
     */
    private function elementDefinition($varname, $key1, $key2, $value)
    {
        $value = addcslashes($value, "\r\n\t\v\f\\\$\"");
        return <<<EOT
\${$varname}['$key1']['$key2']="$value";
EOT;
    }

    /**
     * @param string $module
     * @return string
     */
    private function phpCode($module, array $texts)
    {
        $o = '<?php' . PHP_EOL . PHP_EOL
            . $this->copyrightHeader();
        if (in_array($module, $this->specialModules)) {
            $varname = $this->moduleVarname($module);
            foreach ($texts as $key => $val) {
                $keys = explode('|', $key, 2);
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
                $key = str_replace('|', '_', $key);
                $o .= $this->elementDefinition('plugin_tx', $module, $key, $val)
                    . PHP_EOL;
            }
        }
        $o .= PHP_EOL . '?>' . PHP_EOL;
        return $o;
    }

    /**
     * @param string $module
     * @param string $lang
     * @return bool
     */
    public function writeLanguage($module, $lang, array $texts)
    {
        $filename = $this->filename($module, $lang);
        $contents = $this->phpCode($module, $texts);
        if (function_exists('XH_writeFile')) {
            $func = 'XH_writeFile';
        } else {
            $func = 'file_put_contents';
        }
        return $func($filename, $contents) !== false;
    }

    /**
     * @param string $language
     * @return string
     * @throws Exception
     */
    public function zipArchive(array $modules, $language)
    {
        global $pth;

        include_once $pth['folder']['plugins'] . 'translator/zip.lib.php';
        $zip = new zipfile();
        foreach ($modules as $module) {
            $source = $this->filename($module, $language);
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
