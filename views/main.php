<?php

use Plib\View;

/**
 * @var View $this
 * @var string $action
 * @var list<object{module:string,name:string,url:string,checked:string}> $modules
 * @var string $filename
 * @var string $csrf_token
 */
?>

<form id="translator_list" action="<?=$this->esc($action)?>" method="post">
    <h1>Translator – <?=$this->text('menu_main')?></h1>
    <ul>
<?php foreach ($modules as $module):?>
        <li>
            <input type="checkbox" name="translator_modules[]" value="<?=$this->esc($module->module)?>" <?=$this->esc($module->checked)?>>
            <a href="<?=$this->esc($module->url)?>"><?=$this->esc($module->name)?></a>
        </li>
<?php endforeach?>
    </ul>
    <p style="display: none">
        <button id="translator_select_all" type="button"><?=$this->text('label_select_all')?></button>
        <button id="translator_deselect_all" type="button" disabled="disabled"><?=$this->text('label_deselect_all')?></button>
    </p>
    <p>
        <?=$this->text('label_filename')?>
        <input type="text" name="translator_filename" value="<?=$this->esc($filename)?>">.zip
        <input type="submit" class="submit" value="<?=$this->text('label_generate')?>">
    </p>
    <input type="hidden" name="translator_token" value="<?=$this->esc($csrf_token)?>">
</form>
