<?php

namespace Translator;

use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        global $pth, $plugin_cf, $plugin_tx;
        $pth = ["folder" => ["plugins" => ""]];
        $plugin_cf = ["translator" => []];
        $plugin_tx = ["translator" => []];
    }

    public function testMakesInfoController(): void
    {
        $this->assertInstanceOf(InfoController::class, Plugin::infoController());
    }

    public function testMakesMainController(): void
    {
        $this->assertInstanceOf(MainController::class, Plugin::MainController());
    }
}
