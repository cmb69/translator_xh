<?php

/**
 * Copyright (C) 2011-2017 Christoph M. Becker
 *
 * This file is part of Translator_XH.
 *
 * Translator_XH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Translator_XH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Translator_XH.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Translator;

use PHPUnit_Framework_TestCase;

define('TRANSLATOR_VERSION', 'test');

class ViewsTest extends PHPUnit_Framework_TestCase
{
    private $views;

    public function setUp()
    {
        global $cf/*, $plugin_tx*/;

        $cf = array('xhtml' => array('endtags' => ''));
        //$plugin_tx = array('translator' => array('label_download_url' => ''));
        $model = $this->getMockBuilder('Translator\Model')
                      ->disableOriginalConstructor()
                      ->getMock();
        $model->expects($this->any())
              ->method('modules')
              ->will($this->returnValue(array('foo')));
        $model->expects($this->any())
              ->method('readLanguage')
              ->will($this->returnValue(array('a' => 'one')));
        $this->views = new Views($model);
    }

    public function dataForMessage()
    {
        return array(
            array('success', 'xh_success'),
            array('info', 'xh_info'),
            array('warning', 'xh_warning'),
            array('fail', 'xh_fail')
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
        @$this->assertTag($matcher, $actual);
    }

    public function testSaveMessage()
    {
        global $plugin_tx;

        $plugin_tx['translator']['message_save_success'] = 'OK';
        $matcher = array(
            'tag' => 'p',
            'attributes' => array('class' => 'xh_success'),
            'content' => 'OK'
        );
        $actual = $this->views->saveMessage(true, '');
        @$this->assertTag($matcher, $actual);
    }

    public function testAboutShowsVersionInfo()
    {
        $matcher = array('tag' => 'p', 'content' => 'Version: test');
        $actual = $this->views->about('');
        @$this->assertTag($matcher, $actual);
    }

    public function testAboutShowsPluginIcon()
    {
        $icon = 'icon.png';
        $matcher = array('tag' => 'img', 'attributes' => array('src' => $icon));
        $actual = $this->views->about($icon);
        @$this->assertTag($matcher, $actual);
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
        @$this->assertTag($matcher, $actual);
    }

    public function testMain()
    {
        $matcher = array('tag' => 'form');
        $actual = $this->views->main('', '', '', array());
        @$this->assertTag($matcher, $actual);
    }

    public function testEditor()
    {
        $matcher = array('tag' => 'form');
        $actual = $this->views->editor('', 'foo', 'en', 'de');
        @$this->assertTag($matcher, $actual);
    }

    public function testDownloadUrl()
    {
        $matcher = array('tag' => 'input');
        $actual = $this->views->downloadUrl('');
        @$this->assertTag($matcher, $actual);
    }
}
