<?php

/*
	This file is part of myTinyTodo.
	(C) Copyright 2009 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/

require_once('./init.php');

require_once('./lang/class.default.php');
require_once('./lang/'.Config::get('lang').'.php');
$lang = new Lang();

if($needAuth && !is_logged())
{
	die("Access denied!<br> Disable password protection or Log in.");
}

if(isset($_POST['save']))
{
	stop_gpc($_POST);
	$t = array();
	$langs = getLangs();
	Config::$params['lang']['options'] = array_keys($langs);
	Config::set('lang', _post('lang'));
	if(isset($_POST['password']) && $_POST['password'] != '') Config::set('password', $_POST['password']);
	elseif(!_post('allowpassword')) Config::set('password', '');
	Config::set('smartsyntax', (int)_post('smartsyntax'));
	Config::set('autotz', (int)_post('autotz'));
	Config::set('autotag', (int)_post('autotag'));
	Config::set('session', _post('session'));
	Config::set('firstdayofweek', (int)_post('firstdayofweek'));
	Config::set('duedateformat', (int)_post('duedateformat'));
	Config::set('clock', (int)_post('clock'));
	Config::set('dateformat', _post('dateformat'));
	Config::set('dateformatshort', _post('dateformatshort'));
	Config::set('title', trim(_post('title')));
	Config::save();
	$t['saved'] = 1;
	echo json_encode($t);
	exit;
}


function _c($key)
{
	return Config::get($key);
}

function _e($s)
{
	global $lang;
	echo $lang->get($s);
}

function __($s)
{
	global $lang;
	return $lang->get($s);
}

function getLangs()
{
    if (!$h = opendir('./lang')) return false;
    $a = array();
    while(false !== ($file = readdir($h)))
	{
		if(preg_match('/(.+)\.php$/', $file, $m) && $file != 'class.default.php') {
			$a[$m[1]] = $m[1];
		}
    }
    closedir($h);
    return $a;
}

function selectOptions($a, $value, $default=null)
{
	if(!$a) return '';
	$s = '';
	if($default !== null && !isset($a[$value])) $value = $default;
	foreach($a as $k=>$v) {
		$s .= '<option value="'.htmlspecialchars($k).'" '.($k===$value?'selected':'').'>'.htmlspecialchars($v).'</option>';
	}
	return $s;
}

?>

<h3><?php _e('set_header');?></h3>

<div id="settings_msg" style="display:none"></div>

<form method="post" action="settings.php" onSubmit="saveSettings(this);return false;">

<table class="mtt-settings-table">

<tr>
<th><?php _e('set_title');?>:<br><span class="descr"><?php _e('set_title_descr');?></span></th>
<td> <input name="title" value="<?php echo htmlspecialchars(_c('title'));?>" class="in350"> </td>
</tr>

<tr>
<th><?php _e('set_language');?>:</th>
<td> <SELECT name="lang"><?php $langs = getLangs(); echo selectOptions($langs, _c('lang')); ?></SELECT> </td>
</tr>

<tr>
<th><?php _e('set_protection');?>:</th>
<td>
 <label><input type="radio" name="allowpassword" value="1" <?php if(_c('password')!='') echo "checked"; ?> onClick='$(this.form).find("input[name=password]").attr("disabled",false)'><?php _e('set_enabled');?></label> <br>
 <label><input type="radio" name="allowpassword" value="0" <?php if(_c('password')=='') echo "checked"; ?> onClick='$(this.form).find("input[name=password]").attr("disabled","disabled")'><?php _e('set_disabled');?></label> <br>
</td></tr>

<tr>
<th><?php _e('set_newpass');?>:<br><span class="descr"><?php _e('set_newpass_descr');?></span></th>
<td> <input type="password" name="password" <?php if(_c('password')=='') echo "disabled"; ?>> </td>
</tr>

<tr>
<th><?php _e('set_smartsyntax');?>:<br><span class="descr"><?php _e('set_smartsyntax_descr');?></span></th>
<td>
 <label><input type="radio" name="smartsyntax" value="1" <?php if(_c('smartsyntax')) echo "checked"; ?>><?php _e('set_enabled');?></label> <br>
 <label><input type="radio" name="smartsyntax" value="0" <?php if(!_c('smartsyntax')) echo "checked"; ?>><?php _e('set_disabled');?></label>
</td></tr>

<tr>
<th><?php _e('set_autotz');?>:<br><span class="descr"><?php _e('set_autotz_descr');?></span></th>
<td>
 <label><input type="radio" name="autotz" value="1" <?php if(_c('autotz')) echo "checked"; ?>><?php _e('set_enabled');?></label> <br>
 <label><input type="radio" name="autotz" value="0" <?php if(!_c('autotz')) echo "checked"; ?>><?php _e('set_disabled');?></label>
</td></tr>

<tr>
<th><?php _e('set_autotag');?>:<br><span class="descr"><?php _e('set_autotag_descr');?></span></th>
<td>
 <label><input type="radio" name="autotag" value="1" <?php if(_c('autotag')) echo "checked"; ?>><?php _e('set_enabled');?></label> <br>
 <label><input type="radio" name="autotag" value="0" <?php if(!_c('autotag')) echo "checked"; ?>><?php _e('set_disabled');?></label>
</td></tr>

<tr>
<th><?php _e('set_sessions');?>:</span></th>
<td>
 <label><input type="radio" name="session" value="default" <?php if(_c('session')=='default') echo "checked"; ?>><?php _e('set_sessions_php');?></label> <br>
 <label><input type="radio" name="session" value="files" <?php if(_c('session')=='files') echo "checked"; ?>><?php _e('set_sessions_files');?></label> <span class="descr">(&lt;mytinytodo_dir&gt;/tmp/sessions)</span>
</td></tr>

<tr>
<th><?php _e('set_firstdayofweek');?>:</span></th>
<td>
 <SELECT name="firstdayofweek"><?php echo selectOptions(__('days_long'), _c('firstdayofweek')); ?></SELECT>
</td></tr>

<tr>
<th><?php _e('set_duedate');?>:</span></th>
<td>
 <SELECT name="duedateformat"><?php echo selectOptions(array(1=>'yyyy-mm-dd ('.date('Y-m-d').')', 2=>'m/d/yyyy ('.date('n/j/Y').')', 3=>'dd.mm.yyyy ('.date('d.m.Y').')', 4=>'dd/mm/yyyy ('.date('d/m/Y').')'), _c('duedateformat')); ?></SELECT>
</td></tr>

<tr>
<th><?php _e('set_date');?>:</span></th>
<td>
 <input name="dateformat" value="<?php echo htmlspecialchars(_c('dateformat'));?>">
 <SELECT onChange="if(this.value!=0) this.form.dateformat.value=this.value;">
 <?php echo selectOptions(array('F j, Y'=>formatTime('F j, Y'), 'M d, Y'=>formatTime('M d, Y'), 'j M Y'=>formatTime('j M Y'), 'd F Y'=>formatTime('d F Y'),
	'n/j/Y'=>formatTime('n/j/Y'), 'd.m.Y'=>formatTime('d.m.Y'), 'j. F Y'=>formatTime('j. F Y'), 0=>'Custom'), _c('dateformat'), 0); ?>
 </SELECT>
</td></tr>

<tr>
<th><?php _e('set_shortdate');?>:</span></th>
<td>
 <input name="dateformatshort" value="<?php echo htmlspecialchars(_c('dateformatshort'));?>">
 <SELECT onChange="if(this.value!=0) this.form.dateformatshort.value=this.value;">
 <?php echo selectOptions(array('M d'=>formatTime('M d'), 'j M'=>formatTime('j M'), 'n/j'=>formatTime('n/j'), 'd.m'=>formatTime('d.m'), 0=>'Custom'), _c('dateformatshort'), 0); ?>
 </SELECT>
</td></tr>

<tr>
<th><?php _e('set_clock');?>:</span></th>
<td>
 <SELECT name="clock"><?php echo selectOptions(array(12=>__('set_12hour').' ('.date('g:i A').')', 24=>__('set_24hour').' ('.date('H:i').')'), _c('clock')); ?></SELECT>
</td></tr>

<tr><td colspan="2" class="form-buttons">

<input type="submit" value="<?php _e('set_submit');?>">
<input type="button" value="<?php _e('set_cancel');?>" onClick="closeSettings()">

</td></tr>
</table>

</form>