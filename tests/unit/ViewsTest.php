<?php

require_once './classes/Model.php';
require_once './classes/Views.php';

define('TRANSLATOR_VERSION', 'test');

function tag($string)
{
    return $string;
}

class ViewsTest extends PHPUnit_Framework_TestCase
{
    protected $views;

    public function setUp()
    {
        global $cf/*, $plugin_tx*/;

        $cf = array('xhtml' => array('endtags' => ''));
        //$plugin_tx = array('translator' => array('label_download_url' => ''));
        $model = $this->getMockBuilder('Translator_Model')
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->expects($this->any())
              ->method('modules')
              ->will($this->returnValue(array('foo')));
        $model->expects($this->any())
              ->method('readLanguage')
              ->will($this->returnValue(array('a' => 'one')));
        $this->views = new Translator_Views($model);
    }

    public function dataForMessage()
    {
        return array(
            array('success', ''),
            array('info', ''),
            array('warning', 'cmsimplecore_warning'),
            array('fail', 'cmsimplecore_warning')
        );
    }

    /**
     * @dataProvider dataForMessage
     */
    public function testMessage($type, $class)
    {
        $message = 'foo bar';
        $matcher = array(
            'tag' => 'p',
            'attributes' => array('class' => $class),
            'content' => $message
        );
        $actual = $this->views->message($type, $message);
        $this->assertTag($matcher, $actual);
    }

    public function testSaveMessage()
    {
        global $plugin_tx;

        $plugin_tx['translator']['message_save_success'] = 'OK';
        $matcher = array(
            'tag' => 'p',
            'attributes' => array('class' => ''),
            'content' => 'OK'
        );
        $actual = $this->views->saveMessage(true, '');
        $this->assertTag($matcher, $actual);
    }

    public function testAboutShowsVersionInfo()
    {
        $matcher = array('tag' => 'p', 'content' => 'Version: test');
        $actual = $this->views->about('');
        $this->assertTag($matcher, $actual);
    }

    public function testAboutShowsPluginIcon()
    {
        $icon = 'icon.png';
        $matcher = array('tag' => 'img', 'attributes' => array('src' => $icon));
        $actual = $this->views->about($icon);
        $this->assertTag($matcher, $actual);
    }

    public function testSystemCheck()
    {
        $checks = array('everything' => 'ok');
        $matcher = array(
            'tag' => 'ul',
            'children' => array(
                'count' => 1,
                'only' => array('tag' => 'li')
            )
        );
        $actual = $this->views->systemCheck($checks);
        $this->assertTag($matcher, $actual);
    }

    public function testMain()
    {
        $matcher = array('tag' => 'form');
        $actual = $this->views->main('', '', '', array());
        $this->assertTag($matcher, $actual);
    }

    public function testEditor()
    {
        $matcher = array('tag' => 'form');
        $actual = $this->views->editor('', 'foo', 'en', 'de');
        $this->assertTag($matcher, $actual);
    }

    public function testDownloadUrl()
    {
        $matcher = array('tag' => 'input');
        $actual = $this->views->downloadUrl('');
        $this->assertTag($matcher, $actual);
    }
}

?>
