<h1>Translator</h1>
<img src="<?=$logo?>" alt="Plugin icon" class="pfw_logo">
<p>
    Version: <?=$version?>
</p>
<p>
    Copyright © 2011-2017 <a href="http://3-magi.net/">Christoph M. Becker</a>
</p>
<p class="pfw_license">
    Translator_XH is free software: you can redistribute it and/or modify it
    under the terms of the GNU General Public License as published by the Free
    Software Foundation, either version 3 of the License, or (at your option)
    any later version.
</p>
<p class="pfw_license">
    Translator_XH is distributed in the hope that it will be useful, but
    <em>without any warranty</em>; without even the implied warranty of
    <em>merchantability</em> or <em>fitness for a particular purpose</em>. See
    the GNU General Public License for more details.
</p>
<p class="pfw_license">
    You should have received a copy of the GNU General Public License along with
    Translator_XH. If not, see <a
    href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/</a>.
</p>
<div class="pfw_syscheck">
    <h2><?=$this->text('syscheck_title')?></h2>
<?php foreach ($checks as $check):?>
    <p class="xh_<?=$check->getState()?>"><?=$this->text('syscheck_message', $check->getLabel(), $check->getStateLabel())?></p>
<?php endforeach?>
</div>
