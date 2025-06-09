<?php

use Plib\View;

/**
 * @var View $this
 * @var string $moduleName
 * @var string $from_label
 * @var string $to_label
 * @var list<object{key:string,displayKey:string,className:string,fromtext:string,totext:string}> $rows
 * @var string $csrf_token
 * @var string $error
 */
?>

<form id="translator" method="post">
  <h1>Translator – <?=$moduleName?></h1>
<?php if ($error):?>
  <?=$this->raw($error)?>
<?php endif?>
  <button class="submit" name="translator_do"><?=$this->text('label_save')?></button>
  <div>
    <p></p>
    <p><?=$this->text('label_translate_from')?> <?=$this->raw($from_label)?></p>
    <p><?=$this->text('label_translate_to')?> <?=$this->raw($to_label)?></p>
<?php foreach ($rows as $row):?>
    <div class="translator_key"><?=$this->esc($row->displayKey)?></div>
    <div class="translator_from">
      <textarea rows="2" cols="40" readonly="readonly"><?=$this->esc($row->fromtext)?></textarea>
    </div>
    <div class="translator_to">
      <textarea name="translator_string_<?=$this->esc($row->key)?>" class="<?=$this->esc($row->className)?>" rows="2" cols="40"><?=$this->esc($row->totext)?></textarea>
    </div>
<?php endforeach?>
  </div>
  <button class="submit" name="translator_do"><?=$this->text('label_save')?></button>
  <input type="hidden" name="translator_token" value="<?=$this->esc($csrf_token)?>">
</form>
