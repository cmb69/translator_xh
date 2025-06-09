<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var string $script
 * @var list<object{module:string,id:string,name:string,checked:string}> $modules
 * @var string $filename
 * @var string $error
 */
?>

<script type="module" src="<?=$this->esc($script)?>"></script>
<article class="translator_translations">
  <h1>Translator – <?=$this->text('menu_main')?></h1>
<?if ($error):?>
  <?=$this->raw($error)?>
<?endif?>
  <form method="get">
    <input type="hidden" name="selected" value="translator">
    <input type="hidden" name="admin" value="plugin_main">
    <ul>
<?foreach ($modules as $module):?>
      <li>
        <input type="checkbox" id="<?=$this->esc($module->id)?>" name="translator_modules[]" value="<?=$this->esc($module->module)?>" <?=$this->esc($module->checked)?>>
        <label for="<?=$this->esc($module->id)?>"><?=$this->esc($module->name)?></label>
      </li>
<?endforeach?>
    </ul>
    <p class="translator_controls">
      <button class="translator_edit" name="action" value="edit"><?=$this->text("label_edit")?></button>
      <button class="translator_select_all" type="button" style="display: none"><?=$this->text('label_select_all')?></button>
      <button class="translator_deselect_all" type="button" style="display: none"><?=$this->text('label_deselect_all')?></button>
      <label>
        <?=$this->text('label_filename')?>
        <input type="text" name="translator_filename" value="<?=$this->esc($filename)?>" required>.zip
      </label>
      <button class="translator_download submit" name="action" value="zip" formtarget="_blank"><?=$this->text('label_download')?></button>
    </p>
  </form>
</article>
