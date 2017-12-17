<?php

/*
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

use Pfw\CsrfTestCase;

class CsrfTest extends CsrfTestCase
{
    /**
     * @return array
     */
    public function dataForAttack()
    {
        return array(
            array( // generate ZIP
                array(
                    'translator_modules[]' => 'CORE',
                    'translator_filename' => 'foo'
                ),
                'translator&admin=plugin_main&action=zip&translator_lang=de'
            ),
            array( // save editor
                array(),
                'translator&admin=plugin_main&action=save&translator_from=en&translator_to=de&translator_module=CORE'
            )
        );
    }
}
