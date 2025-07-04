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
use Translator\Model\Service;

class MainControllerTest extends TestCase
{
    private array $conf;
    private Service $service;
    /** @var CsrfProtector&Stub */
    private $csrfProtector;
    private DocumentStore $store;
    private View $view;

    protected function setUp(): void
    {
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
        $this->service = new Service(
            vfsStream::url("root/userfiles/images/flags/"),
            vfsStream::url("root/cmsimple/languages/"),
            vfsStream::url("root/plugins/")
        );
        $this->csrfProtector = $this->createStub(CsrfProtector::class);
        $this->csrfProtector->method("token")->willReturn("0123456789ABCDEF");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["translator"];
        $this->store = new DocumentStore(vfsStream::url("root/"));
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["translator"]);
    }

    private function sut(): MainController
    {
        return new MainController("./", $this->conf, $this->service, $this->csrfProtector, $this->store, $this->view);
    }

    public function testRendersOverview(): void
    {
        $request = new FakeRequest();
        $response = $this->sut()($request);
        $this->assertSame("Translator – Translations", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testRendersEditor(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit&translator_modules%5B%5D=translator",
        ]);
        $response = $this->sut()($request);
        $this->assertSame("Translator – Translator", $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testEditingNoModuleReportsError(): void
    {
        $request = new FakeRequest(["url" => "http://example.com/?&action=edit"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You must select at least one module!", $response->output());
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

    public function testAddsCopyrightHeaderWhenConfigured(): void
    {
        $this->conf["translation_author"] = "Christoph M. Becker";
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit&translator_modules%5B%5D=translator",
            "time" => strtotime("2025-06-09T12:03:41+00:00"),
            "post" => ["translator_string_default|translation" => "neue Übersetzung", "translator_do" => ""],
        ]);
        $this->sut()($request);
        $contents = file_get_contents(vfsStream::url("root/plugins/translator/languages/de.php"));
        $this->assertStringContainsString("Copyright (c) 2025 Christoph M. Becker", $contents);
        $this->assertStringContainsString("This work is licensed under the GNU General Public License v3.", $contents);
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

    public function testSavingNoModuleRedirectsToOverview(): void
    {
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit",
            "post" => ["translator_string_default|translation" => "neue Übersetzung", "translator_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertSame("http://example.com/", $response->location());
    }

    public function testReportsFailureToSave(): void
    {
        vfsStream::setQuota(0);
        $this->csrfProtector->method("check")->willReturn(true);
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=edit&translator_modules%5B%5D=translator",
            "post" => ["translator_string_default|translation" => "neue Übersetzung", "translator_do" => ""],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "The file &quot;vfs://root/plugins/translator/languages/de.php&quot; could not be saved!",
            $response->output()
        );
    }

    public function testDeliversZip(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=zip&translator_filename=test"
                . "&translator_modules[]=translator",
        ]);
        $response = $this->sut()($request);
        $this->assertSame("application/zip", $response->contentType());
        $this->assertSame("test.zip", $response->attachment());
    }

    public function testZippingNoModuleReportsError(): void
    {
        $request = new FakeRequest([
            "url" => "http://example.com/?&action=zip&translator_filename=test",
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You must select at least one module!", $response->output());
    }
}
