<?php
/* @file cover_photo.php
   @brief Module-file with functions for handling of profile-photos
*/

use Friendica\App;
use Friendica\Core\Config;

require_once "include/Photo.php";

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
		// Phase 2 - we have finished cropping
		if ($a->argc != 2) {
			notice(t('Image uploaded but image cropping failed.') . EOL);
			return;
		}

		$image_id = $a->argv[1];
		if (substr($image_id, -2, 1) == '-') {
			$scale = substr($image_id, -1, 1);
			$image_id = substr($image_id, 0, -2);
		}

		$srcX = $_POST['xstart'];
		$srcY = $_POST['ystart'];
		$srcW = $_POST['xfinal'] - $srcX;
		$srcH = $_POST['yfinal'] - $srcY;

		$r = dba::select('photo',
			array(),
			array(
				'resource-id' => $image_id,
				'uid' => local_user(),
				'scale' => 0
			),
			array('limit' => 1)
		);

		if (dbm::is_result($r)) {
			$base_image = $r;

			$im = new Photo($base_image['data'], $base_image['type']);
			if ($im->is_valid()) {
				$g = q("SELECT `width`, `height` FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = 2",
					dbesc($image_id),
					intval(local_user())
				);
				// The new dba::select funtion doesn't seem to work without params
//				$g = dba::select('photo',
//					array('width', 'height'),
//					array(
//						'resource-id' => $image_id,
//						'uid' => local_user(),
//						'scale' => 2
//					)
//				);

				// Scale these numbers to the original photo instead of the scaled photo we operated on
				$scaled_width = $g[0]['width'];
				$scaled_height = $g[0]['height'];

				if ((! $scaled_width) || (! $scaled_height)) {
					logger('potential divide by zero scaling cover photo');
					return;
				}

				// Unset all other cover photos for the specific user
				q("UPDATE `photo` SET `photo_usage` = %d WHERE `photo_usage` = %d AND `uid` = %d",
					intval(PHOTO_NORMAL),
					intval(PHOTO_COVER),
					intval(local_user())
				);
				// The new dba::update funtion doesn't seem to work without params
//				dba::update('photo',
//					array('photo_usage' => PHOTO_NORMAL),
//					array(
//						'photo-usage' => PHOTO_COVER,
//						'uid' => local_user()
//					)
//				);

				$orig_srcx = ($r['width'] / $scaled_width) * $srcX;
				$orig_srcy = ($r['height'] / $scaled_height) * $srcY;
 				$orig_srcw = ($srcW / $scaled_width) * $r['width'];
 				$orig_srch = ($srcH / $scaled_height) * $r['height'];

				// Crop the image and scale it to a dimension of 1200px x 400px and store it
				$im->cropImageRect(1200, 400, $orig_srcx, $orig_srcy, $orig_srcw, $orig_srch);
				$r1 = $im->store(local_user(), 0, $base_image['resource-id'], $base_image['filename'], t('Cover Photos'), 7, PHOTO_COVER);

				// We also store a smaler version with a dimension of 600px x 200px
				$im->doScaleImage(600, 200);
				$r2 = $im->store(local_user(), 0, $base_image['resource-id'], $base_image['filename'], t('Cover Photos'), 8, PHOTO_COVER);

				if ($r1 === false || $r2 === false) {
					// If one failed, delete them all so we can start over.
					notice(t('Image resize failed.') . EOL);
					$x = q("DELETE FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` >= 7 ",
						dbesc($base_image['resource_id']),
						local_user()
					);
					return;
				}

				send_cover_photo_activity($base_image);
			} else {
				notice(t('Unable to process image') . EOL);
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

	$maximagesize = Config::get('system', 'maximagesize');

	if (($maximagesize) && ($filesize > $maximagesize)) {
		notice( sprintf(t('Image exceeds size limit of %s'), formatBytes($maximagesize)) . EOL);
		@unlink($src);
		return;
	}

	$imagedata = @file_get_contents($src);
	$ph = new Photo($imagedata, $filetype);

	if (! $ph->is_valid()) {
		notice(t('Unable to process image.') . EOL);
		@unlink($src);
		return;
	}

	$ph->orient($src);
	@unlink($src);
	return cover_photo_crop_ui_head($a, $ph);
}

/* @brief Generate content of profile-photo view
 *
 * @param $a Current application
 * @return void
 *
 */
function cover_photo_content(&$a) {
	if (! local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	$newuser = false;
	if ($a->argc == 2 && $a->argv[1] === 'new') {
		$newuser = true;
	}

	if ($a->argv[1] === 'use') {
		if ($a->arg < 3) {
			notice(t('Permission denied.') . EOL);
			return;
		};

		$resource_id = $a->argv[2];
		$r = q("SELECT `id`, `album`, `scale` FROM `photo` WHERE `uid` = %d AND `resource-id` = '%s' ORDER BY `scale` ASC",
			local_user(),
			dbesc($resource_id)
		);

		if (! dbm::is_result($r)) {
			notice(t('Photo not available.') . EOL);
			return;
		}

		$havescale = false;
		foreach ($r as $rr) {
			if ($rr['scale'] == 7) {
				$havescale = true;
			}
		}

		$r = dba::select('photo',
			array('data', 'type', 'resource-id'),
			array(
				'id' => $r[0]['id'],
				'uid' => local_user()
			),
			array('limit' => 1)
		);
		if (! dbm::is_result($r)) {
			notice(t('Photo not available.') . EOL);
			return;
		}
		$ph = new Photo($r['data'], $r['type']);
 
		cover_photo_crop_ui_head($a, $ph);
	}

	$profiles = q("SELECT `id`,`profile-name` AS `name`,`is-default` AS `default` FROM profile WHERE uid = %d",
		local_user()
	);

	if (! x($a->data,'imagecrop')) {
		$tpl = get_markup_template('cover_photo.tpl');
		$o .= replace_macros($tpl, array(
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
		$filename = $a->data['imagecrop'] . '-2';
		$resolution = 2;
		$tpl = get_markup_template("cropcover.tpl");
		$o .= replace_macros($tpl, array(
			'$filename' => $filename,
			'$profile' => intval($_REQUEST['profile']),
			'$resource' => $a->data['imagecrop'] . '-2',
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

/**
 * @brief Send an activity message for the cover photo
 * @param array $photo Photo entry from the database<br>
 *     string 'ressource-id' => The ressource ID of the photo<br>
 *     string 'type' => The mimetype of the photo<br>
 */
function send_cover_photo_activity($photo) {
	$a = get_app();

	if ($a->user['hidewall'] || get_config('system', 'block_public')) {
		return;
	}

	$self = dba::select('contact',
		array(),
		array('self' => 1, 'uid' => local_user()),
		array('limit' => 1)
	);

	if (! dbm::is_result($self)) {
		return;
	}

	$profile = dba::select('profile',
		array('gender'),
		array('uid' => local_user(), 'is-default' => 1),
		array('limit' => 1)
	);

	if (! dbm::is_result($profile)) {
		return;
	}


	// Create the status text depending on gender
	if ($profile && stripos($profile['gender'], t('Female')) !== false) {
		$t = t('%1$s updated her %2$s');
	} elseif ($profile && stripos($profile['gender'], t('Male')) !== false) {
		$t = t('%1$s updated his %2$s');
	} else {
		$t = t('%1$s updated their %2$s');
	}

	$atext = '[url=' . $self['url'] . ']' . $self['name'] . '[/url]';
	$ptext = '[url=' . App::get_baseurl() . '/photos/' . $self['nickname'] . '/image/' . $photo['resource-id'] . ']' . t('cover photo') . '[/url]';
	$ltext = '[url=' . App::get_baseurl() . '/profile/' . $self['nickname'] . ']' . '[img]' . App::get_baseurl() . '/photo/' . $photo['resource-id'] . '-8[/img][/url]'; 
	$btext = sprintf($t, $atext, $ptext) . "\n\n" . $ltext;

	$arr = array();

	$arr['guid']       = get_guid(32);
	$arr['uri']        = $arr['parent-uri'] = item_new_uri($a->get_hostname(), $self['uid']);
	$arr['uid']        = $self['uid'];
	$arr['contact-id'] = $self['id'];
	$arr['wall']       = 1;
	$arr['type']       = 'wall';
	$arr['gravity']    = 0;
	$arr['origin']     = 1;
	$arr['last-child'] = 1;

	$arr['author-name']   = $arr['owner-name'] = $self['name'];
	$arr['author-link']   = $arr['owner-link'] = $self['url'];
	$arr['author-avatar'] = $arr['owner-avatar'] = $self['thumb'];

	$arr['body'] = $btext;

	$arr['verb']        = ACTIVITY_UPDATE;
	$arr['object-type'] = ACTIVITY_OBJ_PHOTO;
	$arr['object']      = '<object><type>' . ACTIVITY_OBJ_PHOTO . '</type><title>' . $self['name'] . '</title>'
	. '<id>' . App::get_baseurl() . '/photo/' . $photo['resource-id'] . '-7</id>';
	$arr['object']     .= '<link>' . xmlify('<link rel="photo" type="' . $photo['type'] . '" href="' . App::get_baseurl() . '/photo/' . $photo['resource-id'] . '-7" />' . "\n");
	$arr['object']     .= '</link></object>' . "\n";

	$arr['allow_cid'] = $self['allow_cid'];
	$arr['allow_gid'] = $self['allow_gid'];
	$arr['deny_cid']  = $self['deny_cid'];
	$arr['deny_gid']  = $self['deny_gid'];

	require_once 'include/items.php';

	$i = item_store($arr);
	if ($i) {
		proc_run(PRIORITY_HIGH, "include/notifier.php", "activity", $i);
	}

}

/* @brief Generate the UI for photo-cropping
 *
 * @param $a Current application
 * @param $ph Photo-Factory
 * @return void
 *
 */
function cover_photo_crop_ui_head(&$a, $ph) {
	$max_length = Config::get('system', 'max_image_length');
	if (! $max_length) {
		$max_length = MAX_IMAGE_LENGTH;
	}
	if ($max_length > 0) {
		$ph->scaleImage($max_length);
	}

	$width  = $ph->getWidth();
	$height = $ph->getHeight();

	if ($width < 300 || $height < 300) {
		$ph->scaleImageUp(240);
		$width  = $ph->getWidth();
		$height = $ph->getHeight();
	}

	$hash = photo_new_resource();

	$smallest = 0;

	$r = $ph->store(local_user(), 0 , $hash, $filename, t('Cover Photos'), 0, PHOTO_COVER);

	if ($r) {
		info(t('Image uploaded successfully.') . EOL);
	} else {
		notice(t('Image upload failed.') . EOL);
	}

	if ($width > 320 || $height > 320) {
		$ph->scaleImage(320);
		$r = $ph->store(local_user(), 0 , $hash, $filename, t('Cover Photos'), 2, PHOTO_COVER);

		if ($r === false) {
			notice(sprintf(t('Image size reduction [%s] failed.'),"320") . EOL);
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
