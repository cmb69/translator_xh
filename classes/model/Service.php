<?php

/**
 * Copyright (c) Christoph M. Becker
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

namespace Translator\Model;

use zipfile;

class Service
{
    private string $flagFolder;
    private string $languageFolder;
    private string $pluginFolder;

    public function __construct(string $flagFolder, string $languageFolder, string $pluginFolder)
    {
        $this->flagFolder = $flagFolder;
        $this->languageFolder = $languageFolder;
        $this->pluginFolder = $pluginFolder;
    }

    public function flagIconPath(string $language): ?string
    {
        $filename = $this->flagFolder . $language . ".gif";
        if (!is_file($filename)) {
            return null;
        }
        return $filename;
    }

    /** @return list<string> */
    public function plugins(): array
    {
        $plugins = [];
        if (($dir = opendir($this->pluginFolder)) !== false) {
            while (($entry = readdir($dir)) !== false) {
                if ($entry[0] !== "." && is_dir("{$this->pluginFolder}$entry/languages/")) {
                    $plugins[] = $entry;
                }
            }
            closedir($dir);
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
        return ($module === "CORE" ? $this->languageFolder : "{$this->pluginFolder}$module/languages/")
            . "$language.php";
    }

    public function copyrightHeader(string $author, string $license, string $year): string
    {
        if ($author === "" || $license === "") {
            return "";
        }
        $license = wordwrap($license, 75, "\n * ");
        return <<<EOT
            /**
             * Copyright (c) $year $author
             *
             * $license
             */
            EOT . "\n\n";
    }

    /** @param list<string> $modules */
    public function zipArchive(array $modules, string $language): string
    {
        include_once __DIR__ . "/../../zip.lib.php";
        $zip = new zipfile();
        foreach ($modules as $module) {
            $source = $this->filename($module, $language);
            $destination = ltrim($source, "./");
            if (is_readable($source)) {
                $contents = file_get_contents($source);
                $zip->addFile($contents, $destination);
            }
        }
        return $zip->file();
    }
}
