<?php

namespace Translator\Model;

use PHPUnit\Framework\TestCase;

class LocalizationTest extends TestCase
{
    public function testUnserializesCore(): void
    {
        $tx = XH_includeVar(__DIR__ . "/../../../../cmsimple/languages/en.php", "tx");
        $count = array_reduce($tx, fn ($carry, $array) => $carry + count($array));
        $contents = file_get_contents(__DIR__ . "/../../../../cmsimple/languages/en.php");
        $actual = Localization::fromString($contents, "./cmsimple/languages/en.php");
        $this->assertSame($count, count($actual->texts()));
    }

    public function testUnserializesPlugin(): void
    {
        $plugin_tx = XH_includeVar(__DIR__ . "/../../languages/en.php", "plugin_tx");
        $count = count($plugin_tx["translator"]);
        $contents = file_get_contents(__DIR__ . "/../../languages/en.php");
        $actual = Localization::fromString($contents, "./plugins/translator/languages/en.php");
        $this->assertSame($count, count($actual->texts()));
    }

    public function testSerializesCore(): void
    {
        $contents = file_get_contents(__DIR__ . "/../../../../cmsimple/languages/en.php");
        $expected = Localization::fromString($contents, "./cmsimple/languages/en.php");
        $actual = Localization::fromString($expected->toString(), "./cmsimple/languages/en.php");
        $this->assertEquals($expected, $actual);
    }

    public function testSerializesPlugin(): void
    {
        $contents = file_get_contents(__DIR__ . "/../../languages/en.php");
        $expected = Localization::fromString($contents, "./plugins/translator/languages/en.php");
        $actual = Localization::fromString($expected->toString(), "./plugins/translator/languages/en.php");
        $this->assertEquals($expected, $actual);
    }
}
