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

use Exception;
use zipfile;

class Model
{
    /**
     * @var list<string>
     */
    private $specialModules = array('CORE');

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
                    array_splice($parts, $i - 1, 2);
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
     * @return string|false
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
     * @return list<string>
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
     * @return list<string>
     */
    public function modules()
    {
        $modules = $this->plugins();
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

        $filename = ($module == 'CORE')
            ? $pth['folder']['language']
            : $pth['folder']['plugins'] . $module . '/languages/';
        $filename .= $language;
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
            default:
                $varname = 'plugin_tx';
        }
        return $varname;
    }

    /**
     * @param string $module
     * @param string $lang
     * @return array<string,string>
     */
    public function readLanguage($module, $lang)
    {
        $texts = array();
        $filename = $this->filename($module, $lang);
        if (file_exists($filename)) {
            $varname = $this->moduleVarname($module);
            $$varname = XH_includeVar($filename, $varname);
            if (in_array($module, $this->specialModules)) {
                foreach ($$varname as $key1 => $val1) {
                    foreach ($val1 as $key2 => $val2) {
                        if ($key1 != 'error' || $key2 == 'plugin_error') {
                            $texts[$key1 . '|' . $key2] = $val2;
                        }
                    }
                }
            } else {
                foreach (${$varname}[$module] as $key => $val) {
                    $key = (string) preg_replace('/_/', '|', $key, 1);
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
     * @param string $value
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
     * @param array<string,mixed> $texts
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
     * @param array<string,mixed> $texts
     * @return bool
     */
    public function writeLanguage($module, $lang, array $texts)
    {
        $filename = $this->filename($module, $lang);
        $contents = $this->phpCode($module, $texts);
        return XH_writeFile($filename, $contents) !== false;
    }

    /**
     * @param list<string> $modules
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
