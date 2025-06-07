<?php

use Plib\View;

/**
 * @var View $this
 * @var string $url
 */
?>

<p>
    <?=$this->text('label_download_url')?><br>
    <input id="translator_download_link" type="text" readonly="readonly" value="<?=$this->esc($url)?>">
</p>
