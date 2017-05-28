<?php
/* @file cover_photo.php
   @brief Module-file with functions for handling of profile-photos
*/


/*
 * Fertig: Post, init, content, cover_photo_crop_ui
 */
require_once "include/Photo.php";
require_once 'include/identity.php';

/* @brief Initalize the cover-photo edit view
 *
 * @param $a Current application
 * @return void
 *
 */
function cover_photo_init(&$a) {
	if (! local_user()) {
		return;
	}

	profile_load($a,$a->user['nickname']);
}
/* @brief Evaluate posted values
 *
 * @param $a Current application
 * @return void
 *
 */
function cover_photo_post(&$a) {
	if (! local_user()) {
		notice (t('Permission denied.') . EOL);
	}

	check_form_security_token_redirectOnErr('/cover_photo', 'cover_photo');

	if ((x($_POST,'cropfinal')) && ($_POST['cropfinal'] == 1)) {
		// phase 2 - we have finished cropping
		if ($a->argc != 2) {
			notice(t('Image uploaded but image cropping failed.') . EOL);
			return;
		}

		$image_id = $a->argv[1];
		if(substr($image_id,-2,1) == '-') {
			$scale = substr($image_id,-1,1);
			$image_id = substr($image_id,0,-2);
		}

		$srcX = $_POST['xstart'];
		$srcY = $_POST['ystart'];
		$srcW = $_POST['xfinal'] - $srcX;
		$srcH = $_POST['yfinal'] - $srcY;

		$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = %d LIMIT 1",
			dbesc($image_id),
			dbesc(local_user()),
			intval(0)); // Note: scale muss vielleicht raus - siehe hubzilla

		if (dbm::is_result($r)) {
			$base_image = $r[0];

			$im = new Photo($base_image['data'], $base_image['type']);
			if($im->is_valid()) {
				$g = q("SELECT `width`, `height` FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = 2",
					dbesc($image_id),
					intval(local_user())
				);
				// scale these numbers to the original photo instead of the scaled photo we operated on
				$scaled_width = $g[0]['width'];
				$scaled_height = $g[0]['height'];
				if((! $scaled_width) || (! $scaled_height)) {
					logger('potential divide by zero scaling cover photo');
					return;
				}
				$orig_srcx = ($r[0]['width'] / $scaled_width) * $srcX;
				$orig_srcy = ($r[0]['height'] / $scaled_height) * $srcY;
 				$orig_srcw = ($srcW / $scaled_width) * $r[0]['width'];
 				$orig_srch = ($srcH / $scaled_height) * $r[0]['height'];

				$im->cropImageRect(1200,400,$orig_srcx, $orig_srcy, $orig_srcw, $orig_srch);

//				$aid = get_account_id();
//				$p = array('aid' => $aid, 'uid' => local_user(), 'resource-id' => $base_image['resource_id'],
//					'filename' => $base_image['filename'], 'album' => t('Profile Photos'));
//				$p['scale'] = 7;
//				$p['photo_usage'] = PHOTO_COVER;
				
				//$r1 = $im->save($p);  //ist noch nicht implementiert in Photo Klasse
				$r1 = $im->store(local_user(), 0, $base_image['resource-id'],$base_image['filename'], t('Cover Photos'), 7, PHOTO_COVER);

				$im->doScaleImage(600,200);
				$p['scale'] = 8; // braucht cleanup -> entweder save Methode oder $p array löschen
				$r2 = $im->store(local_user(), 0, $base_image['resource-id'],$base_image['filename'], t('Cover Photos'), 8, PHOTO_COVER);
			
				if($r1 === false || $r2 === false) {
					// if one failed, delete them all so we can start over.
					notice(t('Image resize failed.') . EOL );
					$x = q("DELETE FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` >= 7 ",
						dbesc($base_image['resource_id']),
						local_user()
					);
					return;
				}
				//$channel = $a->get_channel(); // was ist das? Wir brauchen das Profil
				//hier wird noch eine Activity gesendet -> mal schauen, ob wir das implementieren können
			} else {
				notice( t('Unable to process image') . EOL);
			}
		}
		goaway($a->get_baseurl() . '/profiles');
		return; // NOTREACHED
	}

	$src      = $_FILES['userfile']['tmp_name'];
	$filename = basename($_FILES['userfile']['name']);
	$filesize = intval($_FILES['userfile']['size']);
	$filetype = $_FILES['userfile']['type'];
	if ($filetype == "") {
		$filetype = guess_image_type($filename);
	}

	$maximagesize = get_config('system','maximagesize');

	if (($maximagesize) && ($filesize > $maximagesize)) {
		notice( sprintf(t('Image exceeds size limit of %s'), formatBytes($maximagesize)) . EOL);
		@unlink($src);
		return;
	}

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata, $filetype);

	if (! $ph->is_valid()) {
		notice( t('Unable to process image.') . EOL );
		@unlink($src);
		return;
	}

	$ph->orient($src);
	@unlink($src);
	return cover_photo_crop_ui_head($a, $ph);
}
function send_cover_photo_activity($channel,$photo,$profile) {
	// for now only create activities for the default profile
//	if(! intval($profile['is_default']))
//		return;
//	$arr = array();
//	$arr['item_thread_top'] = 1;
//	$arr['item_origin'] = 1;
//	$arr['item_wall'] = 1;
//	$arr['obj_type'] = ACTIVITY_OBJ_PHOTO;
//	$arr['verb'] = ACTIVITY_UPDATE;
//	$arr['object'] = json_encode(array(
//		'type' => $arr['obj_type'],
//		'id' => z_root() . '/photo/profile/l/' . $channel['channel_id'],
//		'link' => array('rel' => 'photo', 'type' => $photo['type'], 'href' => z_root() . '/photo/profile/l/' . $channel['channel_id'])
//	));
//	if(stripos($profile['gender'],t('female')) !== false)
//		$t = t('%1$s updated her %2$s');
//	elseif(stripos($profile['gender'],t('male')) !== false)
//		$t = t('%1$s updated his %2$s');
//	else
//		$t = t('%1$s updated their %2$s');
//	$ptext = '[zrl=' . z_root() . '/photos/' . $channel['channel_address'] . '/image/' . $photo['resource_id'] . ']' . t('profile photo') . '[/zrl]';
//	$ltext = '[zrl=' . z_root() . '/profile/' . $channel['channel_address'] . ']' . '[zmg=150x150]' . z_root() . '/photo/' . $photo['resource_id'] . '-4[/zmg][/zrl]'; 
//	$arr['body'] = sprintf($t,$channel['channel_name'],$ptext) . "\n\n" . $ltext;
//	$acl = new AccessList($channel);
//	$x = $acl->get();
//	$arr['allow_cid'] = $x['allow_cid'];
//	$arr['allow_gid'] = $x['allow_gid'];
//	$arr['deny_cid'] = $x['deny_cid'];
//	$arr['deny_gid'] = $x['deny_gid'];
//	$arr['uid'] = $channel['channel_id'];
//	$arr['aid'] = $channel['channel_account_id'];
//	$arr['owner_xchan'] = $channel['channel_hash'];
//	$arr['author_xchan'] = $channel['channel_hash'];
//	post_activity_item($arr);
}
/* @brief Generate content of profile-photo view
 *
 * @param $a Current application
 * @return void
 *
 */
function cover_photo_content(&$a) {
	if(! local_user()) {
		notice( t('Permission denied.') . EOL );
		return;
	}

	// $channel = $a->get_channel(); // gibts bei
	$newuser = false;
	if($a->argc == 2 && $a->argv[1] === 'new') {
		$newuser = true;
	}

	if($a->argv[1] === 'use') {
		if ($a->arg < 3) {
			notice(t('Permission denied.') . EOL);
			return;
		};
		
//		check_form_security_token_redirectOnErr('/cover_photo', 'cover_photo');

		$resource_id = $a->argv[2];
		$r = q("SELECT `id`, `album`, `scale` FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s' ORDER BY `scale` ASC",
			intval(local_user()),
			dbesc($resource_id)
		);
		if(! dbm::is_result($r)) {
			notice(t('Photo not available.') . EOL);
			return;
		}

		$havescale = false;
		foreach($r as $rr) {
			if($rr['scale'] == 7) {
				$havescale = true;
			}
		}
		$r = q("SELECT `data`, `type`, `resource-id`, FROM `photo` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($r[0]['id']),
			intval(local_user())
		);
		if(! dbm::is_result($r)) {
			notice(t('Photo not available.') . EOL);
			return;
		}
		$ph = new Photo($r[0]['data'], $r[0]['type']);

// deaktiviert, scheinen wir nicht zu brauchen
//		$smallest = 0;
//		if($ph->is_valid()) {
//			// go ahead as if we have just uploaded a new photo to crop
//			$i = q("select resource_id, scale from photo where resource_id = '%s' and uid = %d and scale = 0",
//				dbesc($r[0]['resource_id']),
//				intval(local_channel())
//			);
//			if($i) {
//				$hash = $i[0]['resource_id'];
//				foreach($i as $ii) {
//					$smallest = intval($ii['scale']);
//				}
//			}
//		}
 
		cover_photo_crop_ui_head($a, $ph);
	}

	$profiles = q("SELECT `id`,`profile-name` AS `name`,`is-default` AS `default` FROM profile WHERE uid = %d",
		intval(local_user())
	);
	if(! x($a->data,'imagecrop')) {
		$tpl = get_markup_template('cover_photo.tpl');
		$o .= replace_macros($tpl,array(
			'$user' => $a->user['nickname'],
			'$lbl_upfile' => t('Upload File:'),
			'$lbl_profiles' => t('Select a profile:'),
			'$title' => t('Upload Cover Photo'),
			'$submit' => t('Upload'),
			'$profiles' => $profiles,
			'$form_security_token' => get_form_security_token("cover_photo"),
// FIXME - yuk  
			'$select' => sprintf('%s %s', t('or'), ($newuser) ? '<a href="' . $a->get_baseurl() . '">' . t('skip this step') . '</a>' : '<a href="'. $a->get_baseurl() . '/photos/' . $a->user['nickname'] . '">' . t('select a photo from your photo albums') . '</a>')
		));

		call_hooks('cover_photo_content_end', $o);

		return $o;
	} else {
		$filename = $a->data['imagecrop'] . '-2'; // mal schauen, ob das richtig ist
		$resolution = 2;
		$tpl = get_markup_template("cropcover.tpl");
		$o .= replace_macros($tpl,array(
			'$filename' => $filename,
			'$profile' => intval($_REQUEST['profile']),
			'$resource' => $a->data['imagecrop'] . '-2', // mal schauen, ob das richtig ist
			'$image_url' => $a->get_baseurl() . '/photo/' . $filename,
			'$title' => t('Crop Image'),
			'$desc' => t('Please adjust the image cropping for optimum viewing.'),
			'$form_security_token' => get_form_security_token("cover_photo"),
			'$done' => t('Done Editing')
		));
		return $o;
	}

	return; // NOTREACHED
}
/* @brief Generate the UI for photo-cropping
 *
 * @param $a Current application
 * @param $ph Photo-Factory
 * @return void
 *
 */
function cover_photo_crop_ui_head(&$a, $ph) {
	$max_length = get_config('system','max_image_length');
	if(! $max_length) {
		$max_length = MAX_IMAGE_LENGTH;
	}
	if($max_length > 0) {
		$ph->scaleImage($max_length);
	}

	$width  = $ph->getWidth();
	$height = $ph->getHeight();

	if($width < 300 || $height < 300) {
		$ph->scaleImageUp(240);
		$width  = $ph->getWidth();
		$height = $ph->getHeight();
	}

	$hash = photo_new_resource();

	$smallest = 0;

	$r = $ph->store(local_user(), 0 , $hash, $filename, t('Cover Photos'), 0, PHOTO_COVER);

	if ($r) {
		info( t('Image uploaded successfully.') . EOL );
	} else {
		notice( t('Image upload failed.') . EOL );
	}

	if ($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$r = $ph->store(local_user(), 0 , $hash, $filename, t('Cover Photos'), 2, PHOTO_COVER);

		if ($r === false) {
			notice( sprintf(t('Image size reduction [%s] failed.'),"320") . EOL );
		} else {
			$smallest = 1;
		}
	}

	$a->data['imagecrop'] = $hash;
	$a->data['imagecrop_resolution'] = $smallest;
	$a->page['htmlhead'] .= replace_macros(get_markup_template("crophead.tpl"), array());
	$a->page['end'] .= replace_macros(get_markup_template("cropend.tpl"), array());
	return;
}
