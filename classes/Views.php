<?php

/**
 * The views.
 *
 * PHP version 5
 *
 * @category  CMSimple_XH
 * @package   Translator
 * @author    Christoph M. Becker <cmbecker69@gmx.de>
 * @copyright 2011-2013 Christoph M. Becker <http://3-magi.net>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @version   SVN: $Id$
 * @link      http://3-magi.net/?CMSimple_XH/Translator_XH
 */

/**
 * The views class.
 *
 * @category CMSimple_XH
 * @package  Translator
 * @author   Christoph M. Becker <cmbecker69@gmx.de>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://3-magi.net/?CMSimple_XH/Translator_XH
 */
class Translator_Views
{
    /**
     * Returns the about view.
     *
     * @param string $version  A version number.
     * @param string $iconPath A file path.
     *
     * @return string (X)HTML.
     *
     * @todo fix empty elements
     */
    public function about($version, $iconPath)
    {
        return <<<EOT
<!-- Translator_XH: About -->
<h1>Translator_XH</h1>
<img src="$iconPath" alt="Plugin icon" width="128" height="128"
     style="float: left; margin-right: 16px" />
<p>Version: $version</p>
<p>Copyright &copy; 2011-2013 Christoph M. Becker</p>
<p style="text-align: justify">
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.</p>
<p style="text-align: justify">
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHAN&shy;TABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.</p>
<p style="text-align: justify">
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see
    <a href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/</a>.</p>

EOT;
    }

    /**
     * Returns the main administration view.
     *
     * @param string $action A URL to submit to.
     * @param string $url    A URL to link to.
     * @param string $fn     A language pack file name.
     *
     * @return string (X)HTML.
     *
     * @global array  The localization of the plugins.
     * @global object The translator model.
     *
     * @todo fix empty elements
     */
    public function main($action, $url, $fn, $modules)
    {
        global $plugin_tx, $_Translator;

        $ptx = $plugin_tx['translator'];
        $o = <<<EOT
<!-- Translator_XH: Administration -->
<form id="translator-list" action="$action" method="post">
    <h1>$ptx[label_plugins]</h1>
    <ul>

EOT;
        foreach ($_Translator->modules() as $plugin) {
            $name = ucfirst($plugin);
            $checked = in_array($plugin, $modules)
                ? ' checked="checked"'
                : '';
            $o .= <<<EOT
        <li>
            <input type="checkbox" name="translator-plugins[]" value="$plugin"$checked />
            <a href="$url$plugin">$name</a>
        </li>

EOT;
        }
        $o .= <<<EOT
    </ul>
    $ptx[label_filename]
    <input type="text" name="translator-filename" value="$fn" />.zip
    <input type="submit" class="submit" value="$ptx[label_generate]" />
</form>

EOT;
        return $o;
    }
}

?>
