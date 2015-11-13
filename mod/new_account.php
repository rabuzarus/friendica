<?php

require_once('include/user.php');


function new_account_post(&$a) {
	$arr = $_POST;

	//use for email and password the values of the present user
	$arr['email'] = $a->user['email'];
	$arr['password1'] = $a->user['password'];
	$arr['confirm'] = $arr['password1'];
	$arr['verified'] = true;
	$arr['multireg'] = true;

	$result = create_user($arr);

	if(! $result['success']) {
	notice($result['message']);
	return;
	}

	$user = $result['user'];

	if($netpublish) {
		$url = $a->get_baseurl() . '/profile/' . $user['nickname'];
		proc_run('php',"include/directory.php","$url");
	}

	goaway(z_root() . '/' . manage);
}


function new_account_content(&$a) {
	$block = get_config('system','block_extended_register');

	if(local_user() && ($block)) {
		notice("Permission denied." . EOL);
		return;
	}

	if(local_user() && ($block)) {
		notice("Permission denied." . EOL);
		return;
	}

	$username     = ((x($_POST,'username'))     ? $_POST['username']     : ((x($_GET,'username'))     ? $_GET['username']              : ''));
	$email        = ((x($_POST,'email'))        ? $_POST['email']        : ((x($_GET,'email'))        ? $_GET['email']                 : ''));
	$nickname     = ((x($_POST,'nickname'))     ? $_POST['nickname']     : ((x($_GET,'nickname'))     ? $_GET['nickname']              : ''));
	$photo        = ((x($_POST,'photo'))        ? $_POST['photo']        : ((x($_GET,'photo'))        ? hex2bin($_GET['photo'])        : ''));
	$pageflags    = ((x($_POST,'page-flags'))   ? $_POST['page-flags']   : ((x($_GET,'page-flags'))   ? $_GET['page-flags']            : ''));

	if(get_config('system','publish_all')) {
		$profile_publish_reg = '<input type="hidden" name="profile_publish_reg" value="1" />';
	}
	else {
		$publish_tpl = get_markup_template("profile_publish.tpl");
		$profile_publish = replace_macros($publish_tpl,array(
			'$instance'     => 'reg',
			'$pubdesc'      => t('Include your profile in member directory?'),
			'$yes_selected' => ' checked="checked" ',
			'$no_selected'  => '',
			'$str_yes'      => t('Yes'),
			'$str_no'       => t('No'),
		));
	}

	$r = q("SELECT count(*) AS `contacts` FROM `contact`");
	$passwords = !$r[0]["contacts"];

	$tpl = get_markup_template("account.tpl");

	$o = replace_macros(get_markup_template('new_account.tpl'), array(
		'$title'        => t('Add an Account'),
		'$registertext' =>((x($a->config,'register_text'))
			? bbcode($a->config['register_text'])
			: "" ),
		'$fillwith'  => $fillwith,
		'$fillext'   => $fillext,
		'$oidlabel'  => $oidlabel,
		'$namelabel' => t('Your Full Name ' . "\x28" . 'e.g. Joe Smith, real or real-looking' . "\x29" . ': '),
		'$nickdesc'  => str_replace('$sitename',$a->get_hostname(),t('Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be \'<strong>nickname@$sitename</strong>\'.')),
		'$nicklabel' => t('Choose a nickname: '),
		'$photo'     => $photo,
		'$publish'   => $profile_publish,
		'$username'  => $username,
		'$nickname'  => $nickname,
		'$sitename'  => $a->get_hostname(),
		'$importh'   => t('Import'),
		'$importt'   => t('Import your profile to this friendica instance'),
		'$submit'       => t('Create')

	));
	return $o;
}
