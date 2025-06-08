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

use Plib\Document2 as Document;
use Plib\DocumentStore2 as DocumentStore;

final class Localization implements Document
{
    private string $module;
    /** @var array<string,string> */
    private array $texts;
    private string $copyright = "";

    public static function new(string $key): self
    {
        return new self(self::module($key), []);
    }

    public static function fromString(string $contents, string $key): ?self
    {
        $module = self::module($key);
        $tx = self::eval($module, $contents);
        if (!is_array($tx)) {
            return null;
        }
        if ($module === "CORE") {
            $texts = [];
            foreach ($tx as $key1 => $val1) {
                foreach ($val1 as $key2 => $val2) {
                    $texts["$key1|$key2"] = $val2;
                }
            }
            return new self("CORE", $texts);
        }
        if (!array_key_exists($module, $tx)) {
            return null;
        }
        $texts = [];
        foreach ($tx[$module] as $key => $val) {
            $key = (string) preg_replace('/_/', '|', $key, 1);
            $texts[$key] = $val;
        }
        return new self($module, $texts);
    }

    private static function module(string $key): string
    {
        if (preg_match('/plugins\/([^\/]+)\/languages/', $key, $matches)) {
            return $matches[1];
        }
        return "CORE";
    }

    /** @return mixed */
    private static function eval(string $module, string $contents)
    {
        $tx = $plugin_tx = [];
        eval(preg_replace('/^\s*<\?php|\?>\s*$/', "", $contents));
        return $module === "CORE" ? $tx : $plugin_tx;
    }

    public static function read(string $module, string $language, DocumentStore $store): self
    {
        $key = self::key($module, $language);
        $that = $store->read($key, self::class);
        if ($that === null) {
            $that = self::new($key);
        }
        return $that;
    }

    public static function modify(string $module, string $language, DocumentStore $store): ?self
    {
        $key = self::key($module, $language);
        $that = $store->update($key, self::class);
        if ($that === null) {
            $that = $store->create($key, self::class);
        }
        return $that;
    }

    private static function key(string $module, string $language): string
    {
        if ($module === "CORE") {
            return "cmsimple/languages/$language.php";
        }
        return "plugins/$module/languages/$language.php";
    }

    /** @param array<string,string> $texts */
    private function __construct(string $module, array $texts)
    {
        $this->module = $module;
        $this->texts = $texts;
    }

    /** @return array<string,string> */
    public function texts(): array
    {
        return $this->texts;
    }

    /** @param array<string,string> $texts */
    public function setTexts(array $texts): void
    {
        $this->texts = $texts;
    }

    public function setCopyright(string $copyright): void
    {
        $this->copyright = $copyright;
    }

    public function toString(): ?string
    {
        $res = "<?php\n\n" . $this->copyright;
        if ($this->module === "CORE") {
            foreach ($this->texts as $key => $val) {
                $keys = explode("|", $key, 2);
                $res .= $this->line("tx", $keys[0], $keys[1], $val);
            }
        } else {
            foreach ($this->texts as $key => $val) {
                $key = str_replace("|", "_", $key);
                $res .= $this->line("plugin_tx", $this->module, $key, $val);
            }
        }
        return $res;
    }

    private function line(string $varname, string $key1, string $key2, string $value): string
    {
        $value = addcslashes($value, "\r\n\t\v\f\\\$\"");
        return "\${$varname}['$key1']['$key2'] = \"$value\";\n";
    }
}
