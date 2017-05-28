<?php
/* @file cover_photo.php
   @brief Module-file with functions for handling of profile-photos
*/
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

		$r = q("SELECT * FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = %d LIMIT 1",
			dbesc($image_id),
			dbesc(local_user()),
			intval(0));

		if (dbm::is_result($r)) {
			$base_image = $r[0];

			$im = new Photo($base_image['data'], $base_image['type']);
			if ($im->is_valid()) {
				$g = q("SELECT `width`, `height` FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` = 2",
					dbesc($image_id),
					intval(local_user())
				);
				// Scale these numbers to the original photo instead of the scaled photo we operated on
				$scaled_width = $g[0]['width'];
				$scaled_height = $g[0]['height'];
				if ((! $scaled_width) || (! $scaled_height)) {
					logger('potential divide by zero scaling cover photo');
					return;
				}
				$orig_srcx = ($r[0]['width'] / $scaled_width) * $srcX;
				$orig_srcy = ($r[0]['height'] / $scaled_height) * $srcY;
 				$orig_srcw = ($srcW / $scaled_width) * $r[0]['width'];
 				$orig_srch = ($srcH / $scaled_height) * $r[0]['height'];

				$im->cropImageRect(1200, 400, $orig_srcx, $orig_srcy, $orig_srcw, $orig_srch);
				$r1 = $im->store(local_user(), 0, $base_image['resource-id'], $base_image['filename'], t('Cover Photos'), 7, PHOTO_COVER);

				$im->doScaleImage(600, 200);
				$r2 = $im->store(local_user(), 0, $base_image['resource-id'], $base_image['filename'], t('Cover Photos'), 8, PHOTO_COVER);

				if ($r1 === false || $r2 === false) {
					// If one failed, delete them all so we can start over.
					notice(t('Image resize failed.') . EOL );
					$x = q("DELETE FROM `photo` WHERE `resource-id` = '%s' AND `uid` = %d AND `scale` >= 7 ",
						dbesc($base_image['resource_id']),
						local_user()
					);
					return;
				}
				///@todo Implement sending an activity
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
			intval(local_user()),
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
		$r = q("SELECT `data`, `type`, `resource-id`, FROM `photo` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($r[0]['id']),
			intval(local_user())
		);
		if (! dbm::is_result($r)) {
			notice(t('Photo not available.') . EOL);
			return;
		}
		$ph = new Photo($r[0]['data'], $r[0]['type']);
 
		cover_photo_crop_ui_head($a, $ph);
	}

	$profiles = q("SELECT `id`,`profile-name` AS `name`,`is-default` AS `default` FROM profile WHERE uid = %d",
		intval(local_user())
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
