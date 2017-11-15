<?php

/*
 * Copyright 2011-2017 Christoph M. Becker
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

use Pfw\View\View;
use Pfw\SystemCheckService;

class InfoController
{
    /**
     * @return void
     */
    public function defaultAction()
    {
        global $pth;

        (new View('translator'))
            ->template('info')
            ->data([
                'logo' => "{$pth['folder']['plugin']}translator.png",
                'version' => Plugin::VERSION,
                'checks' => $this->getSystemChecks()
            ])
            ->render();
    }

    /**
     * @return SystemCheck[]
     */
    private function getSystemChecks()
    {
        global $pth;

        $model = new Model;
        $systemCheckService = (new SystemCheckService)
            ->minPhpVersion('5.4.0')
            ->extension('zlib')
            ->minXhVersion('1.6.3')
            ->writable($model->downloadFolder())
            ->writable($pth['folder']['language'])
            ->writable("{$pth['folder']['plugins']}translator/css/")
            ->writable("{$pth['folder']['plugins']}translator/config/");
        foreach ($model->plugins() as $plugin) {
            $systemCheckService->writable("{$pth['folder']['plugins']}$plugin/languages/");
        }
        return $systemCheckService->getChecks();
    }
}
