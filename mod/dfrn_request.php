<?php

/**
 * @file mod/dfrn_request.php
 * @brief Module: dfrn_request
 *
 * Purpose: Handles communication associated with the issuance of
 * friend requests.
 *
 * @see PDF with dfrn specs: https://github.com/friendica/friendica/blob/master/spec/dfrn2.pdf
 *    You also find a graphic which describes the confirmation process at
 *    https://github.com/friendica/friendica/blob/master/spec/dfrn2_contact_request.png
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\Login;
use Friendica\Network\Probe;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;
use Friendica\Util\Strings;

require_once 'include/enotify.php';

function dfrn_request_init(App $a)
{
	if ($a->argc > 1) {
		$which = $a->argv[1];
		Profile::load($a, $which);
	}

	return;
}

/**
 * Function: dfrn_request_post
 *
 * Purpose:
 * Handles multiple scenarios.
 *
 * Scenario 1:
 * Clicking 'submit' on a friend request page.
 *
 * Scenario 2:
 * Following Scenario 1, we are brought back to our home site
 * in order to link our friend request with our own server cell.
 * After logging in, we click 'submit' to approve the linkage.
 *
 * @param App $a
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function dfrn_request_post(App $a)
{
	if (($a->argc != 2) || (!count($a->profile))) {
		Logger::log('Wrong count of argc or profiles: argc=' . $a->argc . ',profile()=' . count($a->profile));
		return;
	}

	if (!empty($_POST['cancel'])) {
		$a->internalRedirect();
	}

	/*
	 * Scenario 2: We've introduced ourself to another cell, then have been returned to our own cell
	 * to confirm the request, and then we've clicked submit (perhaps after logging in).
	 * That brings us here:
	 */
	if (!empty($_POST['localconfirm']) && ($_POST['localconfirm'] == 1)) {
		// Ensure this is a valid request
		if (local_user() && ($a->user['nickname'] == $a->argv[1]) && !empty($_POST['dfrn_url'])) {
			$dfrn_url    = Strings::escapeTags(trim($_POST['dfrn_url']));
			$aes_allow   = !empty($_POST['aes_allow']);
			$confirm_key = defaults($_POST, 'confirm_key', "");
			$hidden      = (!empty($_POST['hidden-contact']) ? intval($_POST['hidden-contact']) : 0);
			$contact_record = null;
			$blocked     = 1;
			$pending     = 1;

			if (!empty($dfrn_url)) {
				// Lookup the contact based on their URL (which is the only unique thing we have at the moment)
				$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `nurl` = '%s' AND NOT `self` LIMIT 1",
					intval(local_user()),
					DBA::escape(Strings::normaliseLink($dfrn_url))
				);

				if (DBA::isResult($r)) {
					if (strlen($r[0]['dfrn-id'])) {
						// We don't need to be here. It has already happened.
						notice(L10n::t("This introduction has already been accepted.") . EOL);
						return;
					} else {
						$contact_record = $r[0];
					}
				}

				if (is_array($contact_record)) {
					$r = q("UPDATE `contact` SET `ret-aes` = %d, hidden = %d WHERE `id` = %d",
						intval($aes_allow),
						intval($hidden),
						intval($contact_record['id'])
					);
				} else {
					// Scrape the other site's profile page to pick up the dfrn links, key, fn, and photo
					$parms = Probe::profile($dfrn_url);

					if (!count($parms)) {
						notice(L10n::t('Profile location is not valid or does not contain profile information.') . EOL);
						return;
					} else {
						if (empty($parms['fn'])) {
							notice(L10n::t('Warning: profile location has no identifiable owner name.') . EOL);
						}
						if (empty($parms['photo'])) {
							notice(L10n::t('Warning: profile location has no profile photo.') . EOL);
						}
						$invalid = Probe::validDfrn($parms);
						if ($invalid) {
							notice(L10n::tt("%d required parameter was not found at the given location", "%d required parameters were not found at the given location", $invalid) . EOL);
							return;
						}
					}

					$dfrn_request = $parms['dfrn-request'];

					$photo = $parms["photo"];

					// Escape the entire array
					DBA::escapeArray($parms);

					// Create a contact record on our site for the other person
					$r = q("INSERT INTO `contact` ( `uid`, `created`,`url`, `nurl`, `addr`, `name`, `nick`, `photo`, `site-pubkey`,
						`request`, `confirm`, `notify`, `poll`, `network`, `aes_allow`, `hidden`, `blocked`, `pending`)
						VALUES ( %d, '%s', '%s', '%s', '%s', '%s' , '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, %d, %d)",
						intval(local_user()),
						DateTimeFormat::utcNow(),
						DBA::escape($dfrn_url),
						DBA::escape(Strings::normaliseLink($dfrn_url)),
						$parms['addr'],
						$parms['fn'],
						$parms['nick'],
						$parms['photo'],
						$parms['key'],
						$parms['dfrn-request'],
						$parms['dfrn-confirm'],
						$parms['dfrn-notify'],
						$parms['dfrn-poll'],
						DBA::escape(Protocol::DFRN),
						intval($aes_allow),
						intval($hidden),
						intval($blocked),
						intval($pending)
					);
				}

				if ($r) {
					info(L10n::t("Introduction complete.") . EOL);
				}

				$r = q("SELECT `id`, `network` FROM `contact` WHERE `uid` = %d AND `url` = '%s' AND `site-pubkey` = '%s' LIMIT 1",
					intval(local_user()),
					DBA::escape($dfrn_url),
					defaults($parms, 'key', '') // Potentially missing
				);
				if (DBA::isResult($r)) {
					Group::addMember(User::getDefaultGroup(local_user(), $r[0]["network"]), $r[0]['id']);

					if (isset($photo)) {
						Contact::updateAvatar($photo, local_user(), $r[0]["id"], true);
					}

					$forward_path = "contact/" . $r[0]['id'];
				} else {
					$forward_path = "contact";
				}

				// Allow the blocked remote notification to complete
				if (is_array($contact_record)) {
					$dfrn_request = $contact_record['request'];
				}

				if (!empty($dfrn_request) && strlen($confirm_key)) {
					Network::fetchUrl($dfrn_request . '?confirm_key=' . $confirm_key);
				}

				// (ignore reply, nothing we can do it failed)
				$a->internalRedirect($forward_path);
				return; // NOTREACHED
			}
		}

		// invalid/bogus request
		notice(L10n::t('Unrecoverable protocol error.') . EOL);
		$a->internalRedirect();
		return; // NOTREACHED
	}

	/*
	 * Otherwise:
	 *
	 * Scenario 1:
	 * We are the requestee. A person from a remote cell has made an introduction
	 * on our profile web page and clicked submit. We will use their DFRN-URL to
	 * figure out how to contact their cell.
	 *
	 * Scrape the originating DFRN-URL for everything we need. Create a contact record
	 * and an introduction to show our user next time he/she logs in.
	 * Finally redirect back to the requestor so that their site can record the request.
	 * If our user (the requestee) later confirms this request, a record of it will need
	 * to exist on the requestor's cell in order for the confirmation process to complete..
	 *
	 * It's possible that neither the requestor or the requestee are logged in at the moment,
	 * and the requestor does not yet have any credentials to the requestee profile.
	 *
	 * Who is the requestee? We've already loaded their profile which means their nickname should be
	 * in $a->argv[1] and we should have their complete info in $a->profile.
	 *
	 */
	if (!(is_array($a->profile) && count($a->profile))) {
		notice(L10n::t('Profile unavailable.') . EOL);
		return;
	}

	$nickname       = $a->profile['nickname'];
	$uid            = $a->profile['uid'];
	$maxreq         = intval($a->profile['maxreq']);
	$contact_record = null;
	$failed         = false;
	$parms          = null;
	$blocked = 1;
	$pending = 1;

	if (!empty($_POST['dfrn_url'])) {
		// Block friend request spam
		if ($maxreq) {
			$r = q("SELECT * FROM `intro` WHERE `datetime` > '%s' AND `uid` = %d",
				DBA::escape(DateTimeFormat::utc('now - 24 hours')),
				intval($uid)
			);
			if (DBA::isResult($r) && count($r) > $maxreq) {
				notice(L10n::t('%s has received too many connection requests today.', $a->profile['name']) . EOL);
				notice(L10n::t('Spam protection measures have been invoked.') . EOL);
				notice(L10n::t('Friends are advised to please try again in 24 hours.') . EOL);
				return;
			}
		}

		/* Cleanup old introductions that remain blocked.
		 * Also remove the contact record, but only if there is no existing relationship
		 */
		$r = q("SELECT `intro`.*, `intro`.`id` AS `iid`, `contact`.`id` AS `cid`, `contact`.`rel`
			FROM `intro` LEFT JOIN `contact` on `intro`.`contact-id` = `contact`.`id`
			WHERE `intro`.`blocked` = 1 AND `contact`.`self` = 0
			AND `intro`.`datetime` < UTC_TIMESTAMP() - INTERVAL 30 MINUTE "
		);
		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				if (!$rr['rel']) {
					DBA::delete('contact', ['id' => $rr['cid'], 'self' => false]);
				}
				DBA::delete('intro', ['id' => $rr['iid']]);
			}
		}

		$url = trim($_POST['dfrn_url']);
		if (!strlen($url)) {
			notice(L10n::t("Invalid locator") . EOL);
			return;
		}

		$hcard = '';

		// Detect the network
		$data = Probe::uri($url);
		$network = $data["network"];

		// Canonicalise email-style profile locator
		$url = Probe::webfingerDfrn($url, $hcard);

		if (substr($url, 0, 5) === 'stat:') {
			// Every time we detect the remote subscription we define this as OStatus.
			// We do this even if it is not OStatus.
			// we only need to pass this through another section of the code.
			if ($network != Protocol::DIASPORA) {
				$network = Protocol::OSTATUS;
			}

			$url = substr($url, 5);
		} else {
			$network = Protocol::DFRN;
		}

		Logger::log('dfrn_request: url: ' . $url . ',network=' . $network, Logger::DEBUG);

		if ($network === Protocol::DFRN) {
			$ret = q("SELECT * FROM `contact` WHERE `uid` = %d AND `url` = '%s' AND `self` = 0 LIMIT 1",
				intval($uid),
				DBA::escape($url)
			);

			if (DBA::isResult($ret)) {
				if (strlen($ret[0]['issued-id'])) {
					notice(L10n::t('You have already introduced yourself here.') . EOL);
					return;
				} elseif ($ret[0]['rel'] == Contact::FRIEND) {
					notice(L10n::t('Apparently you are already friends with %s.', $a->profile['name']) . EOL);
					return;
				} else {
					$contact_record = $ret[0];
					$parms = ['dfrn-request' => $ret[0]['request']];
				}
			}

			$issued_id = Strings::getRandomHex();

			if (is_array($contact_record)) {
				// There is a contact record but no issued-id, so this
				// is a reciprocal introduction from a known contact
				$r = q("UPDATE `contact` SET `issued-id` = '%s' WHERE `id` = %d",
					DBA::escape($issued_id),
					intval($contact_record['id'])
				);
			} else {
				$url = Network::isUrlValid($url);
				if (!$url) {
					notice(L10n::t('Invalid profile URL.') . EOL);
					$a->internalRedirect($a->cmd);
					return; // NOTREACHED
				}

				if (!Network::isUrlAllowed($url)) {
					notice(L10n::t('Disallowed profile URL.') . EOL);
					$a->internalRedirect($a->cmd);
					return; // NOTREACHED
				}

				if (Network::isUrlBlocked($url)) {
					notice(L10n::t('Blocked domain') . EOL);
					$a->internalRedirect($a->cmd);
					return; // NOTREACHED
				}

				$parms = Probe::profile(($hcard) ? $hcard : $url);

				if (!count($parms)) {
					notice(L10n::t('Profile location is not valid or does not contain profile information.') . EOL);
					$a->internalRedirect($a->cmd);
				} else {
					if (empty($parms['fn'])) {
						notice(L10n::t('Warning: profile location has no identifiable owner name.') . EOL);
					}
					if (empty($parms['photo'])) {
						notice(L10n::t('Warning: profile location has no profile photo.') . EOL);
					}
					$invalid = Probe::validDfrn($parms);
					if ($invalid) {
						notice(L10n::tt("%d required parameter was not found at the given location", "%d required parameters were not found at the given location", $invalid) . EOL);

						return;
					}
				}

				$parms['url'] = $url;
				$parms['issued-id'] = $issued_id;
				$photo = $parms["photo"];

				DBA::escapeArray($parms);
				$r = q("INSERT INTO `contact` ( `uid`, `created`, `url`, `nurl`, `addr`, `name`, `nick`, `issued-id`, `photo`, `site-pubkey`,
					`request`, `confirm`, `notify`, `poll`, `network`, `blocked`, `pending` )
					VALUES ( %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d )",
					intval($uid),
					DBA::escape(DateTimeFormat::utcNow()),
					$parms['url'],
					DBA::escape(Strings::normaliseLink($url)),
					$parms['addr'],
					$parms['fn'],
					$parms['nick'],
					$parms['issued-id'],
					$parms['photo'],
					$parms['key'],
					$parms['dfrn-request'],
					$parms['dfrn-confirm'],
					$parms['dfrn-notify'],
					$parms['dfrn-poll'],
					DBA::escape(Protocol::DFRN),
					intval($blocked),
					intval($pending)
				);

				// find the contact record we just created
				if ($r) {
					$r = q("SELECT `id` FROM `contact`
						WHERE `uid` = %d AND `url` = '%s' AND `issued-id` = '%s' LIMIT 1",
						intval($uid),
						$parms['url'],
						$parms['issued-id']
					);
					if (DBA::isResult($r)) {
						$contact_record = $r[0];
						Contact::updateAvatar($photo, $uid, $contact_record["id"], true);
					}
				}
			}
			if ($r === false) {
				notice(L10n::t('Failed to update contact record.') . EOL);
				return;
			}

			$hash = Strings::getRandomHex() . (string) time();   // Generate a confirm_key

			if (is_array($contact_record)) {
				q("INSERT INTO `intro` ( `uid`, `contact-id`, `blocked`, `knowyou`, `note`, `hash`, `datetime`)
					VALUES ( %d, %d, 1, %d, '%s', '%s', '%s' )",
					intval($uid),
					intval($contact_record['id']),
					intval(!empty($_POST['knowyou'])),
					DBA::escape(Strings::escapeTags(trim(defaults($_POST, 'dfrn-request-message', '')))),
					DBA::escape($hash),
					DBA::escape(DateTimeFormat::utcNow())
				);
			}

			// This notice will only be seen by the requestor if the requestor and requestee are on the same server.
			if (!$failed) {
				info(L10n::t('Your introduction has been sent.') . EOL);
			}

			// "Homecoming" - send the requestor back to their site to record the introduction.
			$dfrn_url = bin2hex($a->getBaseURL() . '/profile/' . $nickname);
			$aes_allow = ((function_exists('openssl_encrypt')) ? 1 : 0);

			System::externalRedirect($parms['dfrn-request'] . "?dfrn_url=$dfrn_url"
				. '&dfrn_version=' . DFRN_PROTOCOL_VERSION
				. '&confirm_key=' . $hash
				. (($aes_allow) ? "&aes_allow=1" : "")
			);
			// NOTREACHED
			// END $network === Protocol::DFRN
		} elseif (($network != Protocol::PHANTOM) && ($url != "")) {

			/* Substitute our user's feed URL into $url template
			 * Send the subscriber home to subscribe
			 */
			// Diaspora needs the uri in the format user@domain.tld
			// Diaspora will support the remote subscription in a future version
			if ($network == Protocol::DIASPORA) {
				$uri = $nickname . '@' . $a->getHostName();

				if ($a->getURLPath()) {
					$uri .= '/' . $a->getURLPath();
				}

				$uri = urlencode($uri);
			} else {
				$uri = 'profile/' . $nickname;
			}

			$url = str_replace('{uri}', $uri, $url);
			System::externalRedirect($url);
			// NOTREACHED
			// END $network != Protocol::PHANTOM
		} else {
			notice(L10n::t("Remote subscription can't be done for your network. Please subscribe directly on your system.") . EOL);
			return;
		}
	} return;
}

function dfrn_request_content(App $a)
{
	if (($a->argc != 2) || (!count($a->profile))) {
		return "";
	}

	// "Homecoming". Make sure we're logged in to this site as the correct user. Then offer a confirm button
	// to send us to the post section to record the introduction.
	if (!empty($_GET['dfrn_url'])) {
		if (!local_user()) {
			info(L10n::t("Please login to confirm introduction.") . EOL);
			/* setup the return URL to come back to this page if they use openid */
			return Login::form();
		}

		// Edge case, but can easily happen in the wild. This person is authenticated,
		// but not as the person who needs to deal with this request.
		if ($a->user['nickname'] != $a->argv[1]) {
			notice(L10n::t("Incorrect identity currently logged in. Please login to <strong>this</strong> profile.") . EOL);
			return Login::form();
		}

		$dfrn_url = Strings::escapeTags(trim(hex2bin($_GET['dfrn_url'])));
		$aes_allow = !empty($_GET['aes_allow']);
		$confirm_key = defaults($_GET, 'confirm_key', "");

		// Checking fastlane for validity
		if (!empty($_SESSION['fastlane']) && (Strings::normaliseLink($_SESSION["fastlane"]) == Strings::normaliseLink($dfrn_url))) {
			$_POST["dfrn_url"] = $dfrn_url;
			$_POST["confirm_key"] = $confirm_key;
			$_POST["localconfirm"] = 1;
			$_POST["hidden-contact"] = 0;
			$_POST["submit"] = L10n::t('Confirm');

			dfrn_request_post($a);

			killme();
			return; // NOTREACHED
		}

		$tpl = Renderer::getMarkupTemplate("dfrn_req_confirm.tpl");
		$o = Renderer::replaceMacros($tpl, [
			'$dfrn_url' => $dfrn_url,
			'$aes_allow' => (($aes_allow) ? '<input type="hidden" name="aes_allow" value="1" />' : "" ),
			'$hidethem' => L10n::t('Hide this contact'),
			'$hidechecked' => '',
			'$confirm_key' => $confirm_key,
			'$welcome' => L10n::t('Welcome home %s.', $a->user['username']),
			'$please' => L10n::t('Please confirm your introduction/connection request to %s.', $dfrn_url),
			'$submit' => L10n::t('Confirm'),
			'$uid' => $_SESSION['uid'],
			'$nickname' => $a->user['nickname'],
			'dfrn_rawurl' => $_GET['dfrn_url']
		]);
		return $o;
	} elseif (!empty($_GET['confirm_key'])) {
		// we are the requestee and it is now safe to send our user their introduction,
		// We could just unblock it, but first we have to jump through a few hoops to
		// send an email, or even to find out if we need to send an email.
		$intro = q("SELECT * FROM `intro` WHERE `hash` = '%s' LIMIT 1",
			DBA::escape($_GET['confirm_key'])
		);

		if (DBA::isResult($intro)) {
			$r = q("SELECT `contact`.*, `user`.* FROM `contact` LEFT JOIN `user` ON `contact`.`uid` = `user`.`uid`
				WHERE `contact`.`id` = %d LIMIT 1",
				intval($intro[0]['contact-id'])
			);

			$auto_confirm = false;

			if (DBA::isResult($r)) {
				if ($r[0]['page-flags'] != User::PAGE_FLAGS_NORMAL && $r[0]['page-flags'] != User::PAGE_FLAGS_PRVGROUP) {
					$auto_confirm = true;
				}

				if (!$auto_confirm) {
					notification([
						'type'         => NOTIFY_INTRO,
						'notify_flags' => $r[0]['notify-flags'],
						'language'     => $r[0]['language'],
						'to_name'      => $r[0]['username'],
						'to_email'     => $r[0]['email'],
						'uid'          => $r[0]['uid'],
						'link'         => System::baseUrl() . '/notifications/intros',
						'source_name'  => ((strlen(stripslashes($r[0]['name']))) ? stripslashes($r[0]['name']) : L10n::t('[Name Withheld]')),
						'source_link'  => $r[0]['url'],
						'source_photo' => $r[0]['photo'],
						'verb'         => ACTIVITY_REQ_FRIEND,
						'otype'        => 'intro'
					]);
				}

				if ($auto_confirm) {
					require_once 'mod/dfrn_confirm.php';
					$handsfree = [
						'uid'      => $r[0]['uid'],
						'node'     => $r[0]['nickname'],
						'dfrn_id'  => $r[0]['issued-id'],
						'intro_id' => $intro[0]['id'],
						'duplex'   => (($r[0]['page-flags'] == User::PAGE_FLAGS_FREELOVE) ? 1 : 0),
					];
					dfrn_confirm_post($a, $handsfree);
				}
			}

			if (!$auto_confirm) {

				// If we are auto_confirming, this record will have already been nuked
				// in dfrn_confirm_post()

				q("UPDATE `intro` SET `blocked` = 0 WHERE `hash` = '%s'",
					DBA::escape($_GET['confirm_key'])
				);
			}
		}

		exit();
	} else {
		// Normal web request. Display our user's introduction form.
		if ((Config::get('system', 'block_public')) && (!local_user()) && (!remote_user())) {
			if (!Config::get('system', 'local_block')) {
				notice(L10n::t('Public access denied.') . EOL);
				return;
			}
		}

		// Try to auto-fill the profile address
		// At first look if an address was provided
		// Otherwise take the local address
		if (!empty($_GET['addr'])) {
			$myaddr = hex2bin($_GET['addr']);
		} elseif (!empty($_GET['address'])) {
			$myaddr = $_GET['address'];
		} elseif (local_user()) {
			if (strlen($a->getURLPath())) {
				$myaddr = System::baseUrl() . '/profile/' . $a->user['nickname'];
			} else {
				$myaddr = $a->user['nickname'] . '@' . substr(System::baseUrl(), strpos(System::baseUrl(), '://') + 3);
			}
		} else {
			// last, try a zrl
			$myaddr = Profile::getMyURL();
		}

		$target_addr = $a->profile['nickname'] . '@' . substr(System::baseUrl(), strpos(System::baseUrl(), '://') + 3);

		/* The auto_request form only has the profile address
		 * because nobody is going to read the comments and
		 * it doesn't matter if they know you or not.
		 */
		if ($a->profile['page-flags'] == User::PAGE_FLAGS_NORMAL) {
			$tpl = Renderer::getMarkupTemplate('dfrn_request.tpl');
		} else {
			$tpl = Renderer::getMarkupTemplate('auto_request.tpl');
		}

		$page_desc = L10n::t("Please enter your 'Identity Address' from one of the following supported communications networks:");

		$invite_desc = sprintf(
			L10n::t('If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica site and join us today</a>.'),
			get_server() . '/servers'
		);

		$o = Renderer::replaceMacros($tpl, [
			'$header' => L10n::t('Friend/Connection Request'),
			'$desc' => L10n::t('Examples: jojo@demo.friendica.com, http://demo.friendica.com/profile/jojo, testuser@gnusocial.de'),
			'$pls_answer' => L10n::t('Please answer the following:'),
			'$does_know_you' => ['knowyou', L10n::t('Does %s know you?', $a->profile['name']), false, '', [L10n::t('No'), L10n::t('Yes')]],
			'$add_note' => L10n::t('Add a personal note:'),
			'$page_desc' => $page_desc,
			'$friendica' => L10n::t('Friendica'),
			'$statusnet' => L10n::t("GNU Social \x28Pleroma, Mastodon\x29"),
			'$diaspora' => L10n::t("Diaspora \x28Socialhome, Hubzilla\x29"),
			'$diasnote' => L10n::t(' - please do not use this form.  Instead, enter %s into your Diaspora search bar.', $target_addr),
			'$your_address' => L10n::t('Your Identity Address:'),
			'$invite_desc' => $invite_desc,
			'$submit' => L10n::t('Submit Request'),
			'$cancel' => L10n::t('Cancel'),
			'$nickname' => $a->argv[1],
			'$name' => $a->profile['name'],
			'$myaddr' => $myaddr
		]);
		return $o;
	}
}
