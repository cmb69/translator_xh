<form id="translator" method="post" action="<?=$this->action()?>">
    <h1>Translator – <?=$this->moduleName()?></h1>
    <input type="submit" class="submit" value="<?=$this->text('label_save')?>">
    <table>
        <tr>
            <th></th>
            <th><?=$this->text('label_translate_from')?> <?=$this->sourceLabel()?></th>
            <th><?=$this->text('label_translate_to')?> <?=$this->destinationLabel()?></th>
        </tr>
<?php foreach ($this->rows as $row):?>
        <tr>
            <td class="translator_key"><?=$this->escape($row->displayKey)?></td>
            <td class="translator_from">
                <textarea rows="2" cols="40" readonly="readonly"><?=$this->escape($row->sourceText)?></textarea>
            </td>
            <td class="translator_to">
                <textarea name="translator_string_<?=$this->escape($row->key)?>" class="<?=$this->escape($row->className)?>" rows="2" cols="40"><?=$this->escape($row->destinationText)?></textarea>
            </td>
        </tr>
<?php endforeach?>
    </table>
    <input type="submit" class="submit" value="<?=$this->text('label_save')?>">
    <?=$this->csrfTokenInput()?>
</form>
