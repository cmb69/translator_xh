<?php

use Plib\View;

/**
 * @var View $this
 * @var string $version
 * @var list<object{state:string,label:string,stateLabel:string}> $checks
 */
?>

<h1>Translator <?=$this->esc($version)?></h1>
<div class="pfw_syscheck">
  <h2><?=$this->text('syscheck_title')?></h2>
<?foreach ($checks as $check):?>
  <p class="xh_<?=$this->esc($check->state)?>"><?=$this->text('syscheck_message', $check->label, $check->stateLabel)?></p>
<?endforeach?>
</div>
