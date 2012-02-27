<?php

/**
 * Back-end functionality of Translator_XH.
 * Copyright (c) 2011 Christoph M. Becker (see license.txt)
 */
 

// utf-8-marker: äöüß


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


define('TRANSLATOR_VERSION', '1beta4');


/**
 * Returns plugin version information.
 *
 * @return string
 */
function translator_version() {
    return '<h1>Translator_XH</h1>'."\n"
	    .'<p>Version: '.TRANSLATOR_VERSION.'</p>'."\n"
	    .'<p>Copyright &copy; 2011 Christoph M. Becker</p>'."\n"
	    .'<p style="text-align: justify">This program is free software: you can redistribute it and/or modify'
	    .' it under the terms of the GNU General Public License as published by'
	    .' the Free Software Foundation, either version 3 of the License, or'
	    .' (at your option) any later version.</p>'."\n"
	    .'<p style="text-align: justify">This program is distributed in the hope that it will be useful,'
	    .' but WITHOUT ANY WARRANTY; without even the implied warranty of'
	    .' MERCHAN&shy;TABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the'
	    .' GNU General Public License for more details.</p>'."\n"
	    .'<p style="text-align: justify">You should have received a copy of the GNU General Public License'
	    .' along with this program.  If not, see'
	    .' <a href="http://www.gnu.org/licenses/">http://www.gnu.org/licenses/</a>.</p>'."\n";
}


/**
 * Returns requirements information.
 *
 * @return string
 */
function translator_system_check() { // RELEASE-TODO
    global $pth, $tx, $plugin_tx;

    define('TRANSLATOR_PHP_VERSION', '4.3.0');
    $ptx =& $plugin_tx['translator'];
    $imgdir = $pth['folder']['plugins'].'translator/images/';
    $ok = tag('img src="'.$imgdir.'ok.png" alt="ok"');
    $warn = tag('img src="'.$imgdir.'warn.png" alt="warning"');
    $fail = tag('img src="'.$imgdir.'fail.png" alt="failure"');
    $htm = tag('hr').'<h4>'.$ptx['syscheck_title'].'</h4>'
	    .(version_compare(PHP_VERSION, TRANSLATOR_PHP_VERSION) >= 0 ? $ok : $fail)
	    .'&nbsp;&nbsp;'.sprintf($ptx['syscheck_phpversion'], TRANSLATOR_PHP_VERSION)
	    .tag('br').tag('br')."\n";
    foreach (array('date') as $ext) {
	$htm .= (extension_loaded($ext) ? $ok : $fail)
		.'&nbsp;&nbsp;'.sprintf($ptx['syscheck_extension'], $ext).tag('br')."\n";
    }
    $htm .= (!get_magic_quotes_runtime() ? $ok : $fail)
	    .'&nbsp;&nbsp;'.$ptx['syscheck_magic_quotes'].tag('br')."\n";
    $htm .= (strtoupper($tx['meta']['codepage']) == 'UTF-8' ? $ok : $warn)
	    .'&nbsp;&nbsp;'.$ptx['syscheck_encoding'].tag('br').tag('br')."\n";
    $folders = array_map(create_function('$plugin',
	    'return \''.$pth['folder']['plugins'].'\'.$plugin.\'/languages/\';'),
	    translator_plugins());
    array_unshift($folders, $pth['folder']['language']);
    array_push($folders, translator_download_folder());
    foreach ($folders as $folder) {
	$htm .= (is_writable($folder) ? $ok : $warn)
		.'&nbsp;&nbsp;'.sprintf($ptx['syscheck_writable'], $folder).tag('br')."\n";
    }
    return $htm;
}


/**
 * Returns the name of the download folder.
 *
 * @return string
 */
function translator_download_folder() {
    global $pth, $plugin_cf;
    
    $dn = trim($plugin_cf['translator']['folder_download'], '/');
    $dn = $pth['folder']['base'].(empty($dn) ? '' : $dn.'/');
    if (!is_dir($dn)) {mkdir($dn, 0777);}
    return $dn;
}


/**
 * Returns the absolute URL.
 *
 * @param string $url  The relative URL.
 * @return string
 */
function translator_absolute_url($url) {
    global $sn;
    
    $parts = explode('/', $sn.$url);
    $i = 0;
    while ($i < count($parts)) {
	switch ($parts[$i]) {
	    case '.':
		array_splice($parts, $i, 1);
		break;
	    case '..':
		array_splice($parts, $i-1, 2);
		$i--;
		break;
	    default:
		$i++;
	}
    }
    return $_SERVER['SERVER_NAME'].implode('/', $parts);
}


/**
 * Returns all internationalized plugins.
 *
 * @return array
 */
function translator_plugins() {
    global $pth;
    
    $plugins = array();
    $dn = $pth['folder']['plugins'];
    if (($dh = opendir($dn)) === FALSE) {
	e('cntopen', 'folder', $dn);
	return array();
    }
    while (($fn = readdir($dh)) !== FALSE) {
	if ($fn != '.' && $fn != '..' && is_dir($dn.$fn) && is_dir($dn.$fn.'/languages/')) {
	    $plugins[] = $fn;
	}
    }
    closedir($dh);
    sort($plugins);
    return $plugins;
}


/**
 * Reads a language file.
 *
 * @param string $plugin
 * @param string $lang
 * @param array $texts  Output param.
 * @return void
 */
function translator_read_language($plugin, $lang, &$texts) {
    global $pth;
    if ($plugin == 'pluginloader') {global $tx;}
        
    $texts = array();
    $fn = $plugin == 'CORE' || $plugin == 'CORE-LANGCONFIG'
	    ? $pth['folder']['language']
	    : $pth['folder']['plugins'].$plugin.'/languages/';
    $fn .= $plugin == 'CORE-LANGCONFIG' ? $lang.'config.php' : $lang.'.php';
    if (file_exists($fn)) {
	include($fn);
	if (in_array($plugin, array('CORE', 'CORE-LANGCONFIG', 'pluginloader'))) {
	    $tx_name = $plugin == 'CORE' ? 'tx'
		    : ($plugin == 'CORE-LANGCONFIG' ? 'txc' : 'pluginloader_tx');
	    foreach ($$tx_name as $k1 => $v1) {
		foreach ($v1 as $k2 => $v2) {
		    if ($plugin != 'pluginloader' || $k1 != 'error' || $k2 == 'plugin_error') {
			$texts[$k1.'_'.$k2] = $v2;
		    }
		}
	    }
	} else {
	    foreach ($plugin_tx[$plugin] as $key => $val) {
		$texts[$key] = $val;
	    }
	}
    }
}


/**
 * Writes a language file.
 *
 * @param string $plugin
 * @param string $lang
 * @param array $texts  Input param.
 * @return void
 */
function translator_write_language($plugin, $lang, &$texts) {
    global $pth, $plugin_cf;
    
    $pcf =& $plugin_cf['translator'];
    
    $cnt = '<?php'."\n\n";
    if (!empty($pcf['translation_author']) && !empty($pcf['translation_license'])) {
	$cnt .= '/*'."\n".' * Copyright (c) '.date('Y').' '.$pcf['translation_author']."\n"
		.' *'."\n".' * '.wordwrap($pcf['translation_license'], 75, "\n * ")."\n".' */'."\n\n";
    }
    if (in_array($plugin, array('CORE', 'CORE-LANGCONFIG', 'pluginloader'))) {
	$tx_name = $plugin == 'CORE' ? 'tx'
		: ($plugin == 'CORE-LANGCONFIG' ? 'txc' : 'pluginloader_tx');
	foreach ($texts as $key => $val) {
	    $keys = explode('_', $key, 2);
	    $cnt .= '$'.$tx_name.'[\''.$keys[0].'\'][\''.$keys[1].'\']="'
		    .addcslashes($val, "\r\n\t\v\f\\\$\"").'";'."\n";
	}
	if ($plugin == 'pluginloader') {
	    foreach (array('cntopen', 'cntwriteto', 'notreadable') as $k2) {
		$cnt .= '$pluginloader_tx[\'error\'][\''.$k2.'\']='
			.'$tx[\'error\'][\''.$k2.'\'].\' \';'."\n";
	    }
	}
    } else {
	foreach ($texts as $key => $val) {
	    $cnt .= '$plugin_tx[\''.$plugin.'\'][\''.$key.'\']="'.
		    addcslashes($val, "\r\n\t\v\f\\\$\"").'";'."\n";
	}
    }
    $cnt .= "\n".'?>'."\n";
    
    $fn = $plugin == 'CORE'|| $plugin == 'CORE-LANGCONFIG'
	    ? $pth['folder']['language']
	    : $pth['folder']['plugins'].$plugin.'/languages/';
    $fn .= $plugin == 'CORE-LANGCONFIG' ? $lang.'config.php' : $lang.'.php';
    if (($fh = fopen($fn, 'w')) === FALSE || fwrite($fh, $cnt) === FALSE) {
	e('cntsave', 'language', $fn);
    }
    if ($fh !== FALSE) {fclose($fh);}
}


///**
// * Returns a selectbox with all languages available for the plugin.
// *
// * @param string $plugin
// * @param string $selected
// */
//function translator_language_select($plugin, $selected) {
//    global $pth;
//    
//    $dn = $plugin == 'CORE' || $plugin == 'CORE-LANGCONFIG'
//	    ? $pth['folder']['language']
//	    : $pth['folder']['plugins'].$plugin.'/languages/';
//    $ext = $plugin == 'CORE-LANGCONFIG' ? 'config\.php' : '\.php';
//    $htm = '<select>'."\n";
//    $dh = opendir($dn);
//    while (($fn = readdir($dh)) !== FALSE) {
//	if (preg_match('/^([A-z]{2})'.$ext.'$/', $fn, $matches)) {
//	    $sel = $matches[1] == $selected ? ' selected="selected"' : '';
//	    $htm .= '<option'.$sel.'>'.$matches[1].'</option>'."\n";
//	}
//    }
//    closedir($dh);
//    $htm .= '</select>'."\n";
//    return $htm;
//}


/**
 * Returns the display of the list of available plugins.
 *
 * @return string
 */
function translator_admin_main() {
    global $pth, $sn, $sl, $tx, $plugin_cf, $plugin_tx;
    
    $lang = empty($plugin_cf['translator']['translate_to']) ? $sl : $plugin_cf['translator']['translate_to'];
    $url = $sn.'?translator&amp;admin=plugin_main&amp;action=zip&amp;lang='.$lang;
    $htm = '<form id="translator-list" action="'.$url.'" method="POST">'."\n"
	    .'<h1>'.$plugin_tx['translator']['label_plugins'].'</h1>'."\n".'<ul>'."\n";
    $url = $sn.'?translator&amp;admin=plugin_main&amp;action=edit&amp;from='
	    .$plugin_cf['translator']['translate_from'].'&amp;to='.$lang.'&amp;plugin=';
    $checked = isset($_POST['translator-plugins']) && in_array('CORE', $_POST['translator-plugins'])
	    ? ' checked="checked"' : '';
    $htm .= '<li>'.tag('input type="checkbox" name="translator-plugins[]" value="CORE"'.$checked)
	    .'<a href="'.$url.'CORE">CORE</a></li>'."\n";
    $checked = isset($_POST['translator-plugins']) && in_array('CORE-LANGCONFIG', $_POST['translator-plugins'])
	    ? ' checked="checked"' : '';
    $htm .= '<li>'.tag('input type="checkbox" name="translator-plugins[]" value="CORE-LANGCONFIG"'.$checked)
	    .'<a href="'.$url.'CORE-LANGCONFIG">CORE-LANGCONFIG</a></li>'."\n";
    foreach (translator_plugins() as $plugin) {
	$checked = isset($_POST['translator-plugins']) && in_array($plugin, $_POST['translator-plugins'])
		? ' checked="checked"' : '';
	$htm .= '<li>'.tag('input type="checkbox" name="translator-plugins[]" value="'.$plugin.'"'.$checked)
		.'<a href="'.$url.$plugin.'">'.ucfirst($plugin).'</a></li>'."\n";
    }
    $fn = isset($_POST['translator-filename']) ? $_POST['translator-filename'] : '';
    $htm .= '</ul>'."\n"
	    .tag('input type="submit" class="submit" value="'.ucfirst($tx['action']['save']).'"')
	    .' '.$plugin_tx['translator']['label_filename'].'&nbsp;'
	    .tag('input type="text" name="translator-filename" value="'.$fn.'"').'.zip'
	    .'</form>'."\n";
    return $htm;
}


/**
 * Returns the display of the translation editor.
 *
 * @param string $plugin
 * @param string $from
 * @param string $to
 * @return string
 */
function translator_edit($plugin, $from, $to) {
    global $pth, $sn, $tx, $plugin_cf, $plugin_tx;
    
    $pcf =& $plugin_cf['translator'];
    $ptx =& $plugin_tx['translator'];
    $url = $sn.'?translator&amp;admin=plugin_main&amp;action=save&amp;from='.$from
	    .'&amp;to='.$to.'&amp;plugin='.$plugin;
    $htm = '<form id="translator" method="post" action="'.$url.'">'."\n"
	    .'<h1>'.ucfirst($plugin).'</h1>'."\n"
	    //.'Translator&nbsp;'.tag('input type="text" name="translator"')."\n"
	    .tag('input type="submit" class="submit" value="'.ucfirst($tx['action']['save']).'"')."\n"
	    .'<table>'."\n";
    foreach (array('from', 'to') as $lang) {
	$fn = $pth['folder']['flags'].$$lang.'.gif';
	$lang_h = $lang.'_h';
	$$lang_h = file_exists($fn)
		? tag('img src="'.$fn.'" alt="'.$$lang.'" title="'.$$lang.'"')
		: $$lang;	
    }
    $htm .= '<tr><th></th><th>'.$ptx['label_translate_from'].'&nbsp;'.$from_h.'</th>'
	    .'<th>'.$ptx['label_translate_to'].'&nbsp;'.$to_h.'</th></tr>'."\n";
    translator_read_language($plugin, $from, $from_tx);
    translator_read_language($plugin, $to, $to_tx);
    translator_read_language($plugin, 'translation-hints', $hints);
    //if ($plugin != 'CORE') {ksort($from_tx);}
    if ($pcf['sort_load']) {ksort($from_tx);}
    foreach ($from_tx as $key => $from_val) {
	$to_val = isset($to_tx[$key]) ? $to_tx[$key]
		: (empty($pcf['default_translation']) ? $from_tx[$key] : $pcf['default_translation']);
	$class = isset($to_tx[$key]) ? '' : ' class="new"';
	$help = isset($hints[$key])
		? '<a class="pl_tooltip" href="javascript:return false">'
		.tag('img src="'.$pth['folder']['plugins'].'translator/images/help.png" alt="Help"')
		.'<span>'.$hints[$key].'</span></a>'
		: '';
	$htm .= '<tr>'
		.'<td class="key">'.$help.$key.'</td>'
		.'<td class="from"><textarea rows="2" cols="40" readonly="readonly">'
		.htmlspecialchars($from_val).'</textarea></td>'
		.'<td class="to"><textarea name="translator-'.$key.'"'.$class.' rows="2" cols="40">'
		.htmlspecialchars($to_val).'</textarea></td>'
		.'</tr>'."\n";
    }
    $htm .= '</table>'."\n"
	    .tag('input type="submit" class="submit" value="'.ucfirst($tx['action']['save']).'"')."\n"
	    .'</form>'."\n";   
    
    return $htm;
}


/**
 * Saves the translated language file.
 * Return the display of plugin_main.
 *
 * @param string $plugin
 * @param string $from
 * @param string $to
 * @return string
 */
function translator_save($plugin, $from, $to) {
    global $plugin_cf;
    
    $pcf =& $plugin_cf['translator'];
    $texts = array();
    if ($pcf['sort_save']) {
	foreach ($_POST as $key => $val) {
	    $newval = stsl($val);
	    if (strpos($key, 'translator-') === 0
		    && (empty($pcf['default_translation']) || $newval != $pcf['default_translation'])) {
		$texts[substr($key, 11)] = $newval;
	    }
	}
    } else {
	translator_read_language($plugin, $from, $from_tx);
	foreach ($from_tx as $key => $_) {
	    $newval = stsl($_POST['translator-'.$key]);
	    if (empty($pcf['default_translation']) || $newval != $pcf['default_translation']) {
		$texts[$key] = $newval;
	    }
	}
    }
    translator_write_language($plugin, $to, $texts);
    return translator_admin_main();
}


/**
 * Saves a zip with the language files of the selected plugins.
 * Returns the main administration view.
 *
 * @param string $lang
 * @return string
 */
function translator_zip($lang) {
    global $pth, $e, $tx, $plugin_cf, $plugin_tx;

    if (empty($_POST['translator-plugins'])) {
	$e = '<li>'.$plugin_tx['translator']['error_no_plugin'].'</li>'."\n";
	return translator_admin_main();
    }
    include($pth['folder']['plugins'].'translator/zip.lib.php');
    $zip = new zipfile();
    foreach ($_POST['translator-plugins'] as $plugin) {
	$src = $plugin == 'CORE' || $plugin == 'CORE-LANGCONFIG'
		? $pth['folder']['language'] : $pth['folder']['plugins'];
	$dst = $plugin == 'CORE' || $plugin == 'CORE-LANGCONFIG'
		? 'cmsimple/languages/' : 'plugins/';
	$fn = $plugin == 'CORE' ? $lang.'.php'
		: ($plugin == 'CORE-LANGCONFIG' ? $lang.'config.php'
		: $plugin.'/languages/'.$lang.'.php');
	if (file_exists($src.$fn)) {
	    $cnt = file_get_contents($src.$fn);
	} else {
	    e('missing', 'language', $src.$fn);
	    return translator_admin_main();
	}
	$zip->addFile($cnt, $dst.$fn);
    }
    $cnt = $zip->file();
    //header('Content-Type: application/save-as');
    //header('Content-Disposition: attachment; filename="'.$lang.'.zip"');
    //header('Content-Length:'.strlen($cnt));
    //header('Content-Transfer-Encoding: binary');
    //echo $cnt;
    //exit;
    $ok = TRUE;
    $fn = translator_download_folder().$_POST['translator-filename'].'.zip';
//    if (file_exists($fn)) {
//	e('alreadyexists', 'file', $fn);
//	$ok = FALSE;
//    }
    if (($fh = fopen($fn, 'w')) === FALSE || fwrite($fh, $cnt) === FALSE) {
	e('cntsave', 'file', $fn);
	$ok = FALSE;
    }
    if ($fh !== FALSE) {fclose($fh);}
    $htm = translator_admin_main();
    if ($ok) {
	$htm .= '<p>'.$plugin_tx['translator']['label_download_url'].tag('br')
		.tag('input id="translator-download-link" type="text" disabled="disabled" value="http://'
		.translator_absolute_url($fn).'"').'</p>'."\n";
    }
    return $htm;
}


/**
 * Plugin administration
 */
initvar('translator');
if ($translator) {
    initvar('admin');
    initvar('action');
    
    $o .= print_plugin_admin('on');

    switch ($admin) {
	case '':
	    $o .= translator_version().translator_system_check();
	    break;
	case 'plugin_main':
	    switch ($action) {
		case 'plugin_text':
		    $o .= translator_admin_main();
		    break;
		case 'edit':
		    $o .= translator_edit($_GET['plugin'], $_GET['from'], $_GET['to']);
		    break;
		case 'save':
		    $o .= translator_save($_GET['plugin'], $_GET['from'], $_GET['to']);
		    break;
		case 'zip':
		    $o .= translator_zip($_GET['lang']);
		    break;
	    }
	    break;
	default:
	    $o .= plugin_admin_common($action, $admin, $plugin);
    }

}

?>
