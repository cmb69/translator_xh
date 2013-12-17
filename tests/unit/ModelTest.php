<?php

require_once 'vfsStream/vfsStream.php';

require_once './classes/Model.php';

class ModelTest extends PHPUnit_Framework_TestCase
{
    protected $basePath;

    public function setUp()
    {
        global $pth;

        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory(''));
        $this->basePath = vfsStream::url('');
        $pth = array(
            'folder' => array(
                'language' => $this->basePath . 'cmsimple/languages/',
                'plugins' => $this->basePath . 'plugins/'
            )
        );
        $this->model = new Translator_Model();
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
}

?>
