<?php

/**
 * @file include/CoverPhoto.php
 * @brief This file contains the Cover class with methods used to get or construct
 *     the cover widget
 */
use Friendica\App;
use Friendica\Core\Config;

require_once "include/Photo.php";

/**
 * @brief Class for constructing or getting the cover widget
 */
class CoverPhoto {

	/**
	* @bref Get the cover photo (as bbcode, html or array)
	* 
	* @param int $uid The user ID
	* @param string $format The output format of the cover photo
	*    (supported types: 'bbcode', 'html', 'array'
	* @param int $res The resolution of the cover
	*    (supported resolutions: 7 for large, 8 for small)
	* 
	* @return string|array|bool Output as bbcode, html, or array. False if no
	*    cover photo is available
	*/
	static public function get($uid, $format = 'bbcode', $res = 7) {

		$r = q("SELECT `height`, `width`, `resource-id`, `type` FROM `photo`
				WHERE `uid` = %d AND `scale` = %d AND `photo_usage` = %d",
			intval($uid),
			intval($res),
			intval(PHOTO_COVER)
		);
		if(! dbm::is_result($r)) {
			return false;
		}

		$output = false;
		$url = App::get_baseurl() . '/photo/' . $r[0]['resource-id'] . '-' . $res ;

		switch($format) {
			case 'bbcode':
				$output = '[url=' . $r[0]['width'] . 'x' . $r[0]['height'] . ']' . $url . '[/url]';
				break;
			case 'html':
				$output = '<img class="cover" width="' . $r[0]['width'] . '" height="' . $r[0]['height'] . '" src="' . $url . '" alt="' . t('cover photo') . '" />';
				break;
			case 'array':
			default:
				$output = array(
					'width'  => $r[0]['width'],
					'height' => $r[0]['height'],
					'type'   => $r[0]['type'],
					'url'    => $url
				);
				break;
		}

		return $output;
	}
	/**
	* @brief Construct the cover photo widget
	* 
	* @param array $arr Array with user data<br>
	*    int 'profile_uid' => The serID of the profile owner<br>
	*    string 'style'    => String with style infromation<br>
	*    string 'title'    => The cover title<br>
	*    string 'subtitle' => The cover subtitle<br>
	*    string 'name'     => The name of the profile owner (used as title)<br>
	*    string 'addr'     => The address of the profile owner (used as subtitle)<br>
	* 
	* @return string Formatted html
	*/
	static public function constructWidget($arr) {

		$o = '';
		$a = get_app();

		if($a->module != 'profile') {
			return '';
		}

		$profile_uid = 0;

		if(array_key_exists('profile_uid', $arr) && intval($arr['profile_uid'])) {
			$profile_uid = intval($arr['profile_uid']);
			$profile = $arr;
		}
		if(! profile_uid) {
			$profile_uid = $a->profile_uid;
			$profile = $a->profile;
		}
		if(! profile_uid && $a->argc > 1) {
			$nick = $a->argv[1];
			$user = qu("SELECT * FROM `user` WHERE `nickname` = '%s' AND `blocked` = 0 LIMIT 1",
				dbesc($nick)
			);

			if (! dbm::is_result($user)) {
				return;
			}
			$profile = get_profiledata_by_nick($nick, $user[0]['uid']);
			$profile_uid = $user[0]['uid'];;
		}
		if(! $profile_uid) {
			return '';
		}

		if(array_key_exists('style', $arr) && isset($arr['style'])) {
			$style = $arr['style'];
		} else {
			$style = 'width:100%; height: auto;';
		}

		// ensure they can't sneak in an eval(js) function
		if(strpbrk($style,'(\'"<>') !== false) {
			 $style = '';
		}
		if(array_key_exists('title', $arr) && isset($arr['title'])) {
			$title = $arr['title'];
		} else {
			$title = $profile['name'];
		}
		if(array_key_exists('subtitle', $arr) && isset($arr['subtitle'])) {
			$subtitle = $arr['subtitle'];
		} else {
			$subtitle = str_replace('@', '&#x40;', $profile['addr']);
		}

		$c = self::get($profile_uid, 'html');
		if($c) {
			$photo_html = (($style) ? str_replace('alt=', ' style="' . $style . '" alt=', $c) : $c);
			$o = replace_macros(get_markup_template('cover_photo_widget.tpl'), array(
				'$photo_html' => $photo_html,
				'$title'      => $title,
				'$subtitle'   => $subtitle,
				'$hovertitle' => t('Click to show more'),
			));
		}
		return $o;
	}

		/**
		 * @brief Send an activity message for the cover photo
		 * @param array $photo Photo entry from the database<br>
		 *     string 'ressource-id' => The ressource ID of the photo<br>
		 *     string 'type' => The mimetype of the photo<br>
		 */
		static public function sendActivity($photo) {
			$a = get_app();

			if ($a->user['hidewall'] || Config::get('system', 'block_public')) {
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
		static public function cropUiHead(&$a, $ph) {
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

}