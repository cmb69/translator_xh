<form id="translator_list" action="<?=$action?>" method="post">
    <h1>Translator – <?=$this->text('menu_main')?></h1>
    <ul>
<?php foreach ($modules as $module):?>
        <li>
            <input type="checkbox" name="translator_modules[]" value="<?=$module->module?>" <?=$module->checked?>>
            <a href="<?=$module->url?><?=$module->module?>"><?=$module->name?></a>
        </li>
<?php endforeach?>
    </ul>
    <p style="display: none">
        <button id="translator_select_all" type="button"><?=$this->text('label_select_all')?></button>
        <button id="translator_deselect_all" type="button" disabled="disabled"><?=$this->text('label_deselect_all')?></button>
    </p>
    <p>
        <?=$this->text('label_filename')?>
        <input type="text" name="translator_filename" value="<?=$filename?>">.zip
        <input type="submit" class="submit" value="<?=$this->text('label_generate')?>">
    </p>
    <?=$csrfTokenInput?>
</form>
