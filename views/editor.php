<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var string $script
 * @var string $moduleName
 * @var string $from_label
 * @var string $to_label
 * @var list<object{key:string,displayKey:string,className:string,fromtext:string,totext:string}> $rows
 * @var string $csrf_token
 * @var string $error
 */
?>

<script type="module" src="<?=$this->esc($script)?>"></script>
<article class="translator_edit">
  <h1>Translator – <?=$moduleName?></h1>
<?if ($error):?>
  <?=$this->raw($error)?>
<?endif?>
  <form method="post">
    <button class="submit" name="translator_do"><?=$this->text('label_save')?></button>
    <div class="translator_translation">
      <p></p>
      <p><?=$this->text('label_translate_from')?> <?=$this->raw($from_label)?></p>
      <p><?=$this->text('label_translate_to')?> <?=$this->raw($to_label)?></p>
<?foreach ($rows as $row):?>
      <div class="translator_key"><?=$this->esc($row->displayKey)?></div>
      <div class="translator_from">
        <textarea rows="1" cols="40" disabled><?=$this->esc($row->fromtext)?></textarea>
      </div>
      <div class="translator_to">
        <textarea name="translator_string_<?=$this->esc($row->key)?>" class="<?=$this->esc($row->className)?>" rows="1" cols="40"><?=$this->esc($row->totext)?></textarea>
      </div>
<?endforeach?>
    </div>
    <button class="submit" name="translator_do"><?=$this->text('label_save')?></button>
    <input type="hidden" name="translator_token" value="<?=$this->esc($csrf_token)?>">
  </form>
<article>
