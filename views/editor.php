<?php

use Plib\View;

/**
 * @var View $this
 * @var string $action
 * @var string $moduleName
 * @var string $sourceLabel
 * @var string $destinationLabel
 * @var list<object{key:string,displayKey:string,className:string,sourceText:string,destinationText:string}> $rows
 * @var string $csrf_token
 */
?>

<form id="translator" method="post" action="<?=$this->esc($action)?>">
  <h1>Translator – <?=$moduleName?></h1>
  <input type="submit" class="submit" value="<?=$this->text('label_save')?>">
  <div>
    <p></p>
    <p><?=$this->text('label_translate_from')?> <?=$this->raw($sourceLabel)?></p>
    <p><?=$this->text('label_translate_to')?> <?=$this->raw($destinationLabel)?></p>
<?php foreach ($rows as $row):?>
    <div class="translator_key"><?=$this->esc($row->displayKey)?></div>
    <div class="translator_from">
      <textarea rows="2" cols="40" readonly="readonly"><?=$this->esc($row->sourceText)?></textarea>
    </div>
    <div class="translator_to">
      <textarea name="translator_string_<?=$this->esc($row->key)?>" class="<?=$this->esc($row->className)?>" rows="2" cols="40"><?=$this->esc($row->destinationText)?></textarea>
    </div>
<?php endforeach?>
  </div>
  <input type="submit" class="submit" value="<?=$this->text('label_save')?>">
  <input type="hidden" name="translator_token" value="<?=$this->esc($csrf_token)?>">
</form>
