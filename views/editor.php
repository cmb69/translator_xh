<form id="translator" method="post" action="<?=$this->action()?>">
    <h1>Translator – <?=$this->moduleName()?></h1>
    <input type="submit" class="submit" value="<?=$this->text('label_save')?>">
    <table>
        <tr>
            <th></th>
            <th><?=$this->text('label_translate_from')?> <?=$this->sourceLabel()?></th>
            <th><?=$this->text('label_translate_to')?> <?=$this->destinationLabel()?></th>
        </tr>
        <?=$this->rows()?>
    </table>
    <input type="submit" class="submit" value="<?=$this->text('label_save')?>">
    <?=$this->csrfTokenInput()?>
</form>
