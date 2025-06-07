<?php

use Pfw\View\View;

/**
 * @var View $this
 * @var string $action
 * @var string $moduleName
 * @var string $sourceLabel
 * @var string $destinationLabel
 * @var list<object{key:string,displayKey:string,className:string,sourceText:string,destinationText:string}> $rows
 * @var string $csrfTokenInput
 */
?>

<form id="translator" method="post" action="<?=$action?>">
    <h1>Translator – <?=$moduleName?></h1>
    <input type="submit" class="submit" value="<?=$this->text('label_save')?>">
    <table>
        <tr>
            <th></th>
            <th><?=$this->text('label_translate_from')?> <?=$sourceLabel?></th>
            <th><?=$this->text('label_translate_to')?> <?=$destinationLabel?></th>
        </tr>
<?php foreach ($rows as $row):?>
        <tr>
            <td class="translator_key"><?=$row->displayKey?></td>
            <td class="translator_from">
                <textarea rows="2" cols="40" readonly="readonly"><?=$row->sourceText?></textarea>
            </td>
            <td class="translator_to">
                <textarea name="translator_string_<?=$row->key?>" class="<?=$row->className?>" rows="2" cols="40"><?=$row->destinationText?></textarea>
            </td>
        </tr>
<?php endforeach?>
    </table>
    <input type="submit" class="submit" value="<?=$this->text('label_save')?>">
    <?=$csrfTokenInput?>
</form>
