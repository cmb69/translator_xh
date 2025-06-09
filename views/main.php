<?php

use Plib\View;

/**
 * @var View $this
 * @var string $script
 * @var list<object{module:string,name:string,checked:string}> $modules
 * @var string $filename
 * @var string $error
 */
?>

<script type="module" src="<?=$this->esc($script)?>"></script>
<form id="translator_list" method="get">
  <h1>Translator – <?=$this->text('menu_main')?></h1>
<?if ($error):?>
  <?=$this->raw($error)?>
<?endif?>
  <input type="hidden" name="selected" value="translator">
  <input type="hidden" name="admin" value="plugin_main">
  <ul>
<?foreach ($modules as $module):?>
    <li>
      <label>
        <input type="checkbox" name="translator_modules[]" value="<?=$this->esc($module->module)?>" <?=$this->esc($module->checked)?>>
        <span><?=$this->esc($module->name)?></span>
      </label>
    </li>
<?endforeach?>
  </ul>
  <p>
    <button id="translator_select_all" type="button" style="display: none"><?=$this->text('label_select_all')?></button>
    <button id="translator_deselect_all" type="button" disabled="disabled" style="display: none"><?=$this->text('label_deselect_all')?></button>
    <button name="action" value="edit"><?=$this->text("label_edit")?></button>
  </p>
  <p>
    <?=$this->text('label_filename')?>
    <input type="text" name="translator_filename" value="<?=$this->esc($filename)?>" required>.zip
    <button class="submit" name="action" value="zip"><?=$this->text('label_generate')?></button>
  </p>
</form>
