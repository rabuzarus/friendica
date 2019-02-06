<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\PConfig;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Register;
use Friendica\Model\User;
use Friendica\Util\Strings;

abstract class Identities extends BaseModule
{
	const CLOSED  = 0;
	const APPROVE = 1;
	const OPEN    = 2;

	/**
	 * @brief Module POST method to process submitted data
	 *
	 * Extend this method if the module is supposed to process POST requests.
	 * Doesn't display any content
	 */
	public static function post()
	{

		if (!local_user()) {
			\notice(L10n::t('Permission denied.') . EOL);
			return;
		}
		BaseModule::checkFormSecurityTokenRedirectOnError('/identities', 'identities');

		$a = self::getApp();

		if (!User::authenticate($a->user, trim($_POST['password']))) {
			\notice(L10n::t('Wrong password. Permission denied.') . EOL);
			return;
		}

		$arr = [];

		$arr['username']     = Strings::escapeTags(trim(defaults($_POST, 'username',   '')));
		$arr['nickname']     = Strings::escapeTags(trim(defaults($_POST, 'nickname',   '')));
		$arr['openid_url']   = Strings::escapeTags(trim(defaults($_POST, 'openid_url', '')));
		$arr['photo']        = Strings::escapeTags(trim(defaults($_POST, 'photo',      '')));
		$arr['password']     = defaults($_POST,                         'password',   '');
		$arr['account-type'] = intval(defaults($_POST, 'account-type', 0));
		$arr['page-flags']   = intval(defaults($_POSTl, 'page-flags',  0));

		if (empty($arr['account-type']) && empty($arr['page-flags'])) {
			\notice(L10n::t('Please select an account type.') . EOL);
			return;
		}

		$netpublish = intval(defaults($_POST, 'profile_publish_reg', 0));

		$max_dailies = intval(Config::get('system', 'max_daily_registrations'));
		if ($max_dailies) {
			$count = DBA::count('user', ['`register_date` > UTC_TIMESTAMP - INTERVAL 1 day']);
			if ($count >= $max_dailies) {
				return;
			}
		}

		switch (Config::get('config', 'register_policy')) {
			case self::OPEN:
				$blocked = 0;
				$verified = 1;
				break;

			case self::APPROVE:
				$blocked = 1;
				$verified = 0;
				break;

			case self::CLOSED:
			default:
				if (empty($_SESSION['authenticated']) && empty($_SESSION['administrator'])) {
					\notice(L10n::t('Permission denied.') . EOL);
					return;
				}
				$blocked = 1;
				$verified = 0;
				break;
		}

		$arr['blocked']    = $blocked;
		$arr['verified']   = $verified;
		$arr['language']   = L10n::detectLanguage();
		$arr['parent-uid'] = local_user();
		$arr['email']      = $a->user['email'];

		try {
			Logger::log("Creating the user");
			$result = User::create($arr);
		} catch (\Exception $e) {
			\notice($e->getMessage());
			return;
		}

		$user = $result['user'];

		if ($netpublish && intval(Config::get('config', 'register_policy')) !== self::APPROVE) {
			$url = $a->getBaseUrl() . '/profile/' . $user['nickname'];
			Worker::add(PRIORITY_LOW, 'Directory', $url);
		}

		if (intval(Config::get('config', 'register_policy')) === self::OPEN) {
				\info(L10n::t('Registration successful.') . EOL);
				$a->internalRedirect();
		} elseif (intval(Config::get('config', 'register_policy')) === self::APPROVE) {
			if (!strlen(Config::get('config', 'admin_email'))) {
				\notice(L10n::t('Your registration can not be processed.') . EOL);
				$a->internalRedirect();
			}

			Register::createForApproval($user['uid'], Config::get('system', 'language'), $_POST['permonlybox']);

//			// invite system
//			if ($using_invites && $invite_id) {
//				Register::deleteByHash($invite_id);
//				PConfig::set($user['uid'], 'system', 'invites_remaining', $num_invites);
//			}

			// send email to admins
			$admins_stmt = DBA::select(
				'user',
				['uid', 'language', 'email'],
				['email' => explode(',', str_replace(' ', '', Config::get('config', 'admin_email')))]
			);

			// send notification to admins
			while ($admin = DBA::fetch($admins_stmt)) {
				\notification([
					'type'         => NOTIFY_SYSTEM,
					'event'        => 'SYSTEM_REGISTER_REQUEST',
					'source_name'  => $user['username'],
					'source_mail'  => $user['email'],
					'source_nick'  => $user['nickname'],
					'source_link'  => $a->getBaseUrl() . '/admin/users/',
					'link'         => $a->getBaseUrl() . '/admin/users/',
					'source_photo' => $a->getBaseUrl() . '/photo/avatar/' . $user['uid'] . '.jpg',
					'to_email'     => $admin['email'],
					'uid'          => $admin['uid'],
					'language'     => defaults($admin, 'language', 'en'),
					'show_in_notification_page' => false
				]);
			}
			DBA::close($admins_stmt);

			// send notification to the user, that the registration is pending
//			User::sendRegisterPendingEmail(
//				$user,
//				Config::get('config', 'sitename'),
//				$a->getBaseURL(),
//				$result['password']
//			);

			\info(L10n::t('Your registration is pending approval by the site owner.') . EOL);
			$a->internalRedirect();
		}

		return;
	}

	/**
	 * @brief Module GET method to display any content
	 *
	 * Extend this method if the module is supposed to return any display
	 * through a GET request. It can be an HTML page through templating or a
	 * XML feed or a JSON output.
	 *
	 * @return string
	 */
	public static function content()
	{
		// logged in users can register others (people/pages/groups)
		// even with closed registrations, unless specifically prohibited by site policy.
		// 'block_extended_register' blocks all registrations, period.
		$block = Config::get('system', 'block_extended_register');

		if (!local_user() || (local_user() && ($block))) {
			notice('Permission denied.' . EOL);
			return '';
		}


		$max_dailies = intval(Config::get('system', 'max_daily_registrations'));
		if ($max_dailies) {
			$count = DBA::count('user', ['`register_date` > UTC_TIMESTAMP - INTERVAL 1 day']);
			if ($count >= $max_dailies) {
				Logger::log('max daily registrations exceeded.');
				notice(L10n::t('This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.') . EOL);
				return '';
			}
		}

		$username   = defaults($_REQUEST, 'username'  , '');
		$email      = defaults($_REQUEST, 'email'     , '');
		$openid_url = defaults($_REQUEST, 'openid_url', '');
		$nickname   = defaults($_REQUEST, 'nickname'  , '');
		$photo      = defaults($_REQUEST, 'photo'     , '');
		$account_type = intval(defaults($_REQUEST, 'account-type', 0));
		$page_flags = intval(defaults($_REQUEST,   'page-flags',   0));

//		$a = self::getApp(); Logger::log("$a->user: ".print_r($a->user, true));
		if (Config::get('system', 'publish_all')) {
			$profile_publish = '<input type="hidden" name="profile_publish_reg" value="1" />';
		} else {
			$publish_tpl = Renderer::getMarkupTemplate('profile_publish.tpl');
			$profile_publish = Renderer::replaceMacros($publish_tpl, [
				'$instance'     => 'reg',
				'$pubdesc'      => L10n::t('Include your profile in member directory?'),
				'$yes_selected' => '',
				'$no_selected'  => ' checked="checked"',
				'$str_yes'      => L10n::t('Yes'),
				'$str_no'       => L10n::t('No'),
			]);
		}

		$pageset_tpl = Renderer::getMarkupTemplate('settings/pagetypes.tpl');

		$pagetype = Renderer::replaceMacros($pageset_tpl, [
			'$account_types'	=> L10n::t("Account Types"),
			'$community'		=> L10n::t("Community Forum Subtypes"),
			'$account_type'		=> $account_type,
			'$type_organisation' 	=> User::ACCOUNT_TYPE_ORGANISATION,
			'$type_news'		=> User::ACCOUNT_TYPE_NEWS,
			'$type_community' 	=> User::ACCOUNT_TYPE_COMMUNITY,

			'$account_organisation'	=> ['account-type', L10n::t('Organisation Page'), User::ACCOUNT_TYPE_ORGANISATION,
										L10n::t('Account for an organisation that automatically approves contact requests as "Followers".'),
										($account_type == User::ACCOUNT_TYPE_ORGANISATION)],

			'$account_news'		=> ['account-type', L10n::t('News Page'), User::ACCOUNT_TYPE_NEWS,
										L10n::t('Account for a news reflector that automatically approves contact requests as "Followers".'),
										($account_type == User::ACCOUNT_TYPE_NEWS)],

			'$account_community' 	=> ['account-type', L10n::t('Community Forum'), User::ACCOUNT_TYPE_COMMUNITY,
										L10n::t('Account for community discussions.'),
										($account_type == User::ACCOUNT_TYPE_COMMUNITY)],

			'$page_community'	=> ['page-flags', L10n::t('Public Forum'), User::PAGE_FLAGS_COMMUNITY,
										L10n::t('Automatically approves all contact requests.'),
										($page_flags == User::PAGE_FLAGS_COMMUNITY)],

			'$page_prvgroup' 	=> ['page-flags', L10n::t('Private Forum [Experimental]'), User::PAGE_FLAGS_PRVGROUP,
										L10n::t('Requires manual approval of contact requests.'),
										($page_flags == User::PAGE_FLAGS_PRVGROUP)],


		]);

		$ask_password = ! DBA::count('contact');

		$tpl = Renderer::getMarkupTemplate('identities.tpl');

		$arr = ['template' => $tpl];

		Hook::callAll('register_form', $arr);

		$tpl = $arr['template'];


		$o = Renderer::replaceMacros($tpl, [
			'$permonly'     => intval(Config::get('config', 'register_policy')) === self::APPROVE,
			'$permonlybox'  => ['permonlybox', L10n::t('Note for the admin'), '', L10n::t('Leave a message for the admin, why you need this identity')],
			'$title'        => L10n::t('Identities'),
			'$registertext' => BBCode::convert(Config::get('config', 'register_text', '')),
			'$namelabel'    => L10n::t('The Full Name of your new identity (e.g. Joe Smith, real or real-looking): '),
			'$ask_password' => $ask_password,
			'$password'    => ['password', L10n::t('Please enter your password for verification:'), '', ''],
			'$nickdesc'     => L10n::t('Choose a profile nickname. This must begin with a text character. Your profile address of your new identity will then be "<strong>nickname@%s</strong>".', self::getApp()->getHostName()),
			'$nicklabel'    => L10n::t('Choose a nickname: '),
			'$photo'        => $photo,
			'$publish'      => $profile_publish,
			'$regbutt'      => L10n::t('Create new Identitiy'),
			'$username'     => $username,
			'$nickname'     => $nickname,
			'$sitename'     => self::getApp()->getHostName(),
			'$showtoslink'  => Config::get('system', 'tosdisplay'),
			'$tostext'      => L10n::t('Terms of Service'),
			'$baseurl'      => System::baseurl(),
			'$h_descadvn'   => L10n::t('Select the account type of your new identity'),
			'$pagetype'     => $pagetype,
			'$account_type' => $account_type,
			'$form_security_token'   => BaseModule::getFormSecurityToken('identities'),
			'$explicit_content'      => Config::get('system', 'explicit_content', false),
			'$explicit_content_note' => L10n::t('Note: This node explicitly contains adult content'),
		]);

		return $o;
	}
}