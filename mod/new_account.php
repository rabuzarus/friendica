<?php

require_once('include/user.php');


function new_account_post(&$a) {

	$block = get_config('system','block_extended_register');

	if(! local_user() || ($block)) {
		return;
	}

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

	$uid = local_user();
	$orig_record = $a->user;
	$mid = $user['uid'];

	identity_switch($uid, $mid, $orig_record);

	goaway(z_root() . 'profile_photo/new');
}


function new_account_content(&$a) {
	$block = get_config('system','block_extended_register');

	if(! local_user() || ($block)) {
		notice("Permission denied." . EOL);
		return;
	}

	$photo        = ((x($_POST,'photo'))        ? $_POST['photo']        : ((x($_GET,'photo'))        ? hex2bin($_GET['photo'])        : ''));
	$page_flags   = ((x($_POST,'page-flags'))   ? $_POST['page-flags']   : ((x($_GET,'page-flags'))   ? $_GET['page-flags']            : 0));

	if(get_config('system','publish_all')) {
		$profile_publish_reg = '<input type="hidden" name="profile_publish_reg" value="1" />';
	}
	else {
		$publish_tpl = get_markup_template('profile_publish.tpl');
		$profile_publish = replace_macros($publish_tpl,array(
			'$instance'     => 'reg',
			'$pubdesc'      => t('Include your profile in member directory?'),
			'$yes_selected' => ' checked="checked" ',
			'$no_selected'  => '',
			'$str_yes'      => t('Yes'),
			'$str_no'       => t('No'),
		));
	}

	// Get available page types and format the array to make it useable for the template
	$arr = array();
	$pagetypes = get_pagetypes();
	foreach($pagetypes as $pname => $pdata) {
		$arr[$pname] = array();
		$arr[$pname][0] = $pdata[0];
		foreach(array_slice($pdata,1) as $f) {
			$arr[$pname][1][] = $f;
		}
	}

	$new_account_desc = 'Create another identity or a community/group page. 
		This identity or communit/group page would have the same account details (e-mail and password). 
		It is posible to switch beetwen the identities with the "manage" menu.';
	$pagetype_desc = 'Chose of what kind your identity should be.';

	$tpl = get_markup_template('account.tpl');

	$o = replace_macros(get_markup_template('new_account.tpl'), array(
		'$title'            => t('Create New Identity'),
		'$new_account_desc' => t($new_account_desc),
		'$namelabel'        => t('Your Full Name'),
		'$namedesc'         => t('Examples: Joe Smith, real or real-looking'),
		'$nickdesc'         => str_replace('$sitename',$a->get_hostname(),t('Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be \'<strong>nickname@$sitename</strong>\'.')),
		'$nicklabel'        => t('Choose a nickname: '),
		'$photo'            => $photo,
		'$publish'          => $profile_publish,
		'$pagetype'         => t('Page Type'),
		'$pagetype_desc'    => t($pagetype_desc),
		'$pagetypes'        => $arr,
		'$username'         => $username,
		'$nickname'         => $nickname,
		'$sitename'         => $a->get_hostname(),
		'$importt'          => t('Or <a href="uimport">import an existing profile</a> to this friendica instance'),
		'$submit'           => t('Create')

	));
	return $o;
}
