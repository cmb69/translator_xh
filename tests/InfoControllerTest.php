<?php

namespace Translator;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Plib\FakeSystemChecker;
use Plib\SystemChecker;
use Plib\View;
use Translator\Model\Service;

class InfoControllerTest extends TestCase
{
    private Service $service;
    private SystemChecker $systemChecker;
    private View $view;

    protected function setUp(): void
    {
        $this->service = new Service("", "", "");
        $this->systemChecker = new FakeSystemChecker();
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["translator"]);
    }

    private function sut(): InfoController
    {
        return new InfoController(
            "./cmsimple/languages/",
            "./plugins/",
            $this->service,
            $this->systemChecker,
            $this->view
        );
    }

    public function testRendersPluginInfo(): void
    {
        $response = $this->sut()();
        $this->assertSame("Translator 1.0beta8", $response->title());
        Approvals::verifyHtml($response->output());
    }
}
