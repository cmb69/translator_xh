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

namespace Translator\Model;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    private $root;

    protected function setUp(): void
    {
        vfsStream::setup("test", null, [
            "cmsimple" => ["languages" => []],
            "images" => ["flags" => ["en.gif" => ""]],
            "plugins" => [
                "foo" => ["languages" => []],
                "bar" => ["languages" => []],
            ],
        ]);
        $this->root = vfsStream::url("test/");
    }

    private function sut(): Service
    {
        return new Service(
            $this->root . "images/flags/",
            $this->root . "cmsimple/languages/",
            $this->root . "plugins/"
        );
    }

    public function testFlagIconPath()
    {
        $actual = $this->sut()->flagIconPath("en");
        $this->assertEquals($this->root . "images/flags/en.gif", $actual);

        $actual = $this->sut()->flagIconPath("de");
        $this->assertNull($actual);
    }

    public function testPlugins()
    {
        $actual = $this->sut()->plugins();
        $this->assertEquals(["bar", "foo"], $actual);
    }

    public function testModules()
    {
        $actual = $this->sut()->modules();
        $this->assertEquals(["CORE", "bar", "foo"], $actual);
    }

    /**
     * @dataProvider dataForFilename
     */
    public function testFilename(string $module, string $language, string $expected)
    {
        $actual = $this->sut()->filename($module, $language);
        $this->assertEquals($this->root . $expected, $actual);
    }

    public function dataForFilename(): array
    {
        return [
            ["CORE", "en", "cmsimple/languages/en.php"],
            ["pagemanager", "da", "plugins/pagemanager/languages/da.php"],
        ];
    }
}
