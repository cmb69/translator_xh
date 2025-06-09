<?php

namespace Translator;

use ApprovalTests\Approvals;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Plib\CsrfProtector;
use Plib\DocumentStore2 as DocumentStore;
use Plib\FakeRequest;
use Plib\View;

class MainControllerTest extends TestCase
{
    private array $conf;
    /** @var CsrfProtector&Stub */
    private $csrfProtector;
    private DocumentStore $store;
    private View $view;

    protected function setUp(): void
    {
        global $pth, $plugin_cf;
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
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("0123456789ABCDEF");
        $plugin_cf = XH_includeVar("./config/config.php", "plugin_cf");
        $this->conf = $plugin_cf["translator"];
        $this->store = new DocumentStore(vfsStream::url("root/"));
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["translator"]);
    }

    private function sut(): MainController
    {
        return new MainController("./", $this->conf, $this->csrfProtector, $this->store, $this->view);
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
            "url" => "http://example.com/?&action=edit&translator_modules%5B%5D=translator",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testSavesTranslation(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit&translator_modules%5B%5D=translator",
            "post" => ["translator_string_default|translation" => "neue Übersetzung", "translator_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "neue Übersetzung",
            file_get_contents(vfsStream::url("root/plugins/translator/languages/de.php"))
        );
        $this->assertSame("http://example.com/?&translator_modules%5B0%5D=translator", $response->location());
    }

    public function testSavingIsCsrfProtected(): void
    {
        $this->csrfProtector->method("check")->willReturn(false);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit&translator_modules%5B%5D=translator",
            "post" => ["translator_string_default|translation" => "neue Übersetzung", "translator_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertSame(403, $response->status());
    }

    public function testDeliversZip(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=zip&translator_lang=de&translator_filename=test"
                . "&translator_modules[]=translator",
        ]);
        $response = $this->sut()($request);
        $this->assertSame("application/zip", $response->contentType());
        $this->assertSame("test.zip", $response->attachment());
    }
}
