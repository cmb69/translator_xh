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

use zipfile;

class Model
{
    /** @return string|false */
    public function flagIconPath(string $language)
    {
        global $pth;

        $filename = $pth["folder"]["flags"] . $language . ".gif";
        if (!file_exists($filename)) {
            $filename = false;
        }
        return $filename;
    }

    /** @return list<string> */
    public function plugins(): array
    {
        global $pth;

        $plugins = array();
        $dir = $pth["folder"]["plugins"];
        $handle = opendir($dir);
        if ($handle) {
            while (($entry = readdir($handle)) !== false) {
                if ($entry[0] != "." && is_dir($dir . $entry . "/languages/")) {
                    $plugins[] = $entry;
                }
            }
            closedir($handle);
        }
        sort($plugins);
        return $plugins;
    }

    /** @return list<string> */
    public function modules(): array
    {
        $modules = $this->plugins();
        array_unshift($modules, "CORE");
        return $modules;
    }

    public function filename(string $module, string $language): string
    {
        global $pth;

        $filename = ($module == "CORE")
            ? $pth["folder"]["language"]
            : $pth["folder"]["plugins"] . $module . "/languages/";
        $filename .= $language;
        $filename .= ".php";
        return $filename;
    }

    /** @param list<string> $modules */
    public function zipArchive(array $modules, string $language): string
    {
        include_once __DIR__ . "/../zip.lib.php";
        $zip = new zipfile();
        foreach ($modules as $module) {
            $source = $this->filename($module, $language);
            $destination = ltrim($source, "./");
            if (is_readable($source)) {
                $contents = file_get_contents($source);
                $zip->addFile($contents, $destination);
            }
        }
        $contents = $zip->file();
        return $contents;
    }
}
