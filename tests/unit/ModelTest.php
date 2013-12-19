<?php

require_once 'vfsStream/vfsStream.php';

require_once './classes/Model.php';

class ModelTest extends PHPUnit_Framework_TestCase
{
    protected $basePath;

    public function setUp()
    {
        global $pth, $tx;

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
        $this->basePath = vfsStream::url('test') . '/';
        $pth = array(
            'folder' => array(
                'base' => $this->basePath,
                'downloads' => $this->basePath . 'downloads/',
                'flags' => $this->basePath . 'images/flags/',
                'language' => $this->basePath . 'cmsimple/languages/',
                'plugins' => $this->basePath . 'plugins/'
            )
        );
        mkdir($pth['folder']['flags'], 0777, true);
        file_put_contents($pth['folder']['flags'] . 'en.gif', '');
        mkdir($pth['folder']['language'], 0777, true);
        mkdir($pth['folder']['plugins'], 0777, true);
        mkdir($pth['folder']['plugins'] . 'pluginloader/languages', 0777, true);
        mkdir($pth['folder']['plugins'] . 'foo/languages', 0777, true);
        mkdir($pth['folder']['plugins'] . 'bar/languages', 0777, true);
        $this->texts = array(
            'a' => 'one',
            'b' => 'two'
        );
        $tx = array(
            'error' => array(
                'cntopen' => '', 'cntwriteto' => '', 'notreadable' => ''
            )
        );
        $this->model = new Translator_Model();
    }

    public function testCanonicalUrl()
    {
        $url = 'http://example.com/./foo/bar/../baz/./index.html';
        $expected = 'http://example.com/foo/baz/index.html';
        $actual = $this->model->canonicalUrl($url);
        $this->assertEquals($expected, $actual);
    }

    public function testFlagIconPath()
    {
        $expected = $this->basePath . 'images/flags/en.gif';
        $actual = $this->model->flagIconPath('en');
        $this->assertEquals($expected, $actual);

        $actual = $this->model->flagIconPath('de');
        $this->assertFalse($actual);
    }

    public function testDownloadFolder()
    {
        global $pth;

        $expected = $this->basePath . 'downloads/';
        $actual = $this->model->downloadFolder();
        $this->assertEquals($expected, $actual);
    }

    public function testPlugins()
    {
        global $pth;

        $expected = array('bar', 'foo', 'pluginloader');
        $actual = $this->model->plugins();
        $this->assertEquals($expected, $actual);
    }

    public function testModules()
    {
        global $pth;

        $expected = array('CORE', 'CORE-LANGCONFIG', 'bar', 'foo', 'pluginloader');
        $actual = $this->model->modules();
        $this->assertEquals($expected, $actual);
    }

    public function dataForFilename()
    {
        return array(
            array('CORE', 'en', 'cmsimple/languages/en.php'),
            array('CORE-LANGCONFIG', 'de', 'cmsimple/languages/deconfig.php'),
            array('pagemanager', 'da', 'plugins/pagemanager/languages/da.php')
        );
    }

    /**
     * @dataProvider dataForFilename
     */
    public function testFilename($module, $language, $expected)
    {
        global $pth;

        $expected = $this->basePath . $expected;
        $actual = $this->model->filename($module, $language);
        $this->assertEquals($expected, $actual);
    }

    public function dataForWritingAndReading()
    {
        return array(
            array(
                'CORE', 'en',
                array(
                    'a_1' => 'one', 'a_2' => 'two',
                    'b_1' => 'three', 'b_2' => 'four'
                )
            ),
            array(
                'CORE-LANGCONFIG', 'en',
                array(
                    'a_1' => 'one', 'a_2' => 'two',
                    'b_1' => 'three', 'b_2' => 'four'
                )
            ),
            array(
                'pluginloader', 'en',
                array(
                    'a_1' => 'one', 'a_2' => 'two',
                    'b_1' => 'three', 'b_2' => 'four'
                )
            ),
            array(
                'foo', 'en',
                array('a' => 'one', 'b' => 'two')
            )
        );
    }

    /**
     * @dataProvider dataForWritingAndReading
     */
    public function testWritingAndReading($module, $language, $array)
    {
        $success = $this->model->writeLanguage($module, $language, $array);
        $this->assertTrue($success);
        $texts = $this->model->readLanguage($module, $language);
        $this->assertEquals($array, $texts);
    }
}

?>
