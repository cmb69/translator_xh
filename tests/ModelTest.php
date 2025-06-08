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

use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

define('CMSIMPLE_XH_VERSION', 'CMSimple_XH 1.5.9');

class ModelTest extends TestCase
{
    private $basePath;

    public function setUp(): void
    {
        global $pth, $tx, $plugin_cf;

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
        $plugin_cf = XH_includeVar("./config/config.php", "plugin_cf");
        $this->model = new Model();
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
        $expected = $this->basePath . 'downloads/';
        $actual = $this->model->downloadFolder();
        $this->assertEquals($expected, $actual);
    }

    public function testPlugins()
    {
        $expected = array('bar', 'foo');
        $actual = $this->model->plugins();
        $this->assertEquals($expected, $actual);
    }

    public function testModules()
    {
        $expected = array('CORE', 'bar', 'foo');
        $actual = $this->model->modules();
        $this->assertEquals($expected, $actual);
    }

    public function dataForFilename()
    {
        return array(
            array('CORE', 'en', 'cmsimple/languages/en.php'),
            array('pagemanager', 'da', 'plugins/pagemanager/languages/da.php')
        );
    }

    /**
     * @dataProvider dataForFilename
     */
    public function testFilename($module, $language, $expected)
    {
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
                    'a|1' => 'one', 'a|2' => 'two',
                    'b|1' => 'three', 'b|2' => 'four'
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
