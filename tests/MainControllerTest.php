<?php

namespace Translator;

use ApprovalTests\Approvals;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Plib\DocumentStore2 as DocumentStore;
use Plib\FakeRequest;
use Plib\View;
use XH\CSRFProtection;

class MainControllerTest extends TestCase
{
    private array $conf;
    /** @var CSRFProtection&MockObject */
    private $cSRFProtection;
    private DocumentStore $store;
    private View $view;

    protected function setUp(): void
    {
        global $pth, $plugin_cf, $_XH_csrfProtection;
        vfsStream::setup("root", null, [
            "plugins" => [
                "translator" => ["languages" => [
                    "en.php" => <<<'EOS'
                        <?php

                        $plugin_tx['translator']['default_translation'] = "*** NEW LANGUAGE STRING ***";
                        EOS,
                    "de.php" => <<<'EOS'
                        <?php

                        $plugin_tx['translator']['default_translation'] = "*** NEUER SPRACH-TEXT ***";
                        EOS,
                ]],
            ],
            "userfiles" => [
                "downloads" => [],
                "images" => ["flags" => ["de.gif" => "", "en.gif" => ""]],
            ]
        ]);
        $pth = ["folder" => [
            "downloads" => vfsStream::url("root/userfiles/downloads/"),
            "flags" => vfsStream::url("root/userfiles/images/flags/"),
            "plugins" => vfsStream::url("root/plugins/"),
        ]];
        $this->cSRFProtection = $this->createMock(CSRFProtection::class);
        $this->cSRFProtection->expects($this->any())->method("tokenInput")->willReturn(
            '<input type="hidden" name="csrf_token" value="0123456789ABCDEF">'
        );
        $_XH_csrfProtection = $this->cSRFProtection;
        $plugin_cf = XH_includeVar("./config/config.php", "plugin_cf");
        $this->conf = $plugin_cf["translator"];
        $this->store = new DocumentStore(vfsStream::url("root/"));
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["translator"]);
    }

    private function sut(): MainController
    {
        return new MainController("./", $this->conf, $this->store, $this->view);
    }

    public function testRendersOverview(): void
    {
        $request = new FakeRequest();
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testRendersEditor(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit&translator_module=translator"
                . "&translator_from=en&translator_to=de",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testSavesTranslation(): void
    {
        $this->cSRFProtection->expects($this->once())->method("check");
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=save&translator_module=translator"
                . "&translator_from=en&translator_to=de",
            "post" => ["translator_string_default|translation" => "neue Übersetzung"],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "neue Übersetzung",
            file_get_contents(vfsStream::url("root/plugins/translator/languages/de.php"))
        );
        $this->assertStringContainsString(
            "The file &quot;vfs://root/plugins/translator/languages/de.php&quot; has been successfully saved.",
            $response->output()
        );
    }

    public function testCreatesZip(): void
    {
        $this->cSRFProtection->expects($this->once())->method("check");
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=zip&translator_lang=de",
            "post" => [
                "translator_modules" => ["translator"],
                "translator_filename" => "test",
            ],
        ]);
        $response = $this->sut()($request);
        $this->assertFileExists(vfsStream::url("root/userfiles/downloads/test.zip"));
        $this->assertStringContainsString(
            "The file &quot;vfs://root/userfiles/downloads/test.zip&quot; has been successfully saved.",
            $response->output()
        );
        $this->assertStringContainsString(
            "http://example.com/vfs://root/userfiles/downloads/test.zip",
            $response->output()
        );
    }
}
