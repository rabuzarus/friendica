<?php

require_once('include/text.php');


function manage_post(&$a) {

	if(! local_user())
		return;

	$uid = local_user();
	$orig_record = $a->user;
	$mid = ((x($_POST['identity'])) ? intval($_POST['identity']) : 0);

	identity_switch($uid, $mid, $orig_record);

	$ret = array();
	call_hooks('home_init',$ret);

	goaway(z_root() . "/profile/" . $a->user['nickname'] );
	// NOTREACHED
}



function manage_content(&$a) {

	if(! local_user()) {
		notice( t('Permission denied.') . EOL);
		return;
	}

	if ($_GET['identity']) {
		$_POST['identity'] = $_GET['identity'];
		manage_post($a);
		return;
	}

	$identities = $a->identities;

	//getting additinal information for each identity
	foreach ($identities as $key=>$id) {
		$thumb = q("SELECT `thumb` FROM `contact` WHERE `uid` = '%s' AND `self` = 1",
			dbesc($id['uid'])
		);

		$identities[$key][thumb] = $thumb[0][thumb];

		$identities[$key]['selected'] = (($id['nickname'] === $a->user['nickname']) ? true : false);
	}

	$o = replace_macros(get_markup_template('manage.tpl'), array(
		'$title' => t('Manage Identities and/or Pages'),
		'$new_account' => t('Create a new identity'),
		'$desc' => t('Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'),
		'$choose' => t('Select an identity to manage: '),
		'$identities' => $identities,
		'$submit' => t('Submit'),
	));

	return $o;

}
