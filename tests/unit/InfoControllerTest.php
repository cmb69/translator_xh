<?php

namespace Translator;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Plib\FakeSystemChecker;
use Plib\SystemChecker;
use Plib\View;

class InfoControllerTest extends TestCase
{
    private SystemChecker $systemChecker;
    private View $view;

    protected function setUp(): void
    {
        global $pth;
        $pth = ["folder" => ["downloads" => "", "plugins" => ""]];
        $this->systemChecker = new FakeSystemChecker();
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["translator"]);
    }

    private function sut(): InfoController
    {
        return new InfoController("./cmsimple/languages/", "./plugins/", $this->systemChecker, $this->view);
    }

    public function testRendersPluginInfo(): void
    {
        $output = $this->sut()->defaultAction();
        Approvals::verifyHtml($output);
    }
}
