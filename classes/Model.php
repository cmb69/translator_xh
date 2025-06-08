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
     * @param list<string> $modules
     * @param string $language
     * @return string
     * @throws Exception
     */
    public function zipArchive(array $modules, $language)
    {
        include_once __DIR__ . "/../zip.lib.php";
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
