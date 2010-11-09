<?php


function search_content(&$a) {

	$o = '<div id="live-search"></div>' . "\r\n";

	$o .= '<h3>' . t('Search') . '</h3>';

	$search = ((x($_GET,'search')) ? $_GET['search'] : '');

	$o .= search($search);

	if(! $search)
		return $o;

	require_once("include/bbcode.php");
	require_once('include/security.php');

	$sql_extra = "
		AND `item`.`allow_cid` = '' 
		AND `item`.`allow_gid` = '' 
		AND `item`.`deny_cid`  = '' 
		AND `item`.`deny_gid`  = '' 
	";

	$r = q("SELECT COUNT(*) AS `total`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `wall` = 1
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND MATCH (`item`.`body`) AGAINST ( '%s' IN BOOLEAN MODE )
		$sql_extra ",
		dbesc($search)
	);

	if(count($r))
		$a->set_pager_total($r[0]['total']);

	if(! $r[0]['total']) {
		notice('No results.');
		return $o;
	}

	$r = q("SELECT `item`.*, `item`.`id` AS `item_id`, 
		`contact`.`name`, `contact`.`photo`, `contact`.`url`, `contact`.`rel`,
		`contact`.`network`, `contact`.`thumb`, `contact`.`self`, 
		`contact`.`id` AS `cid`, `contact`.`uid` AS `contact-uid`,
		`user`.`nickname`
		FROM `item` LEFT JOIN `contact` ON `contact`.`id` = `item`.`contact-id`
		LEFT JOIN `user` ON `user`.`uid` = `item`.`uid` 
		WHERE `item`.`visible` = 1 AND `item`.`deleted` = 0
		AND `wall` = 1
		AND `contact`.`blocked` = 0 AND `contact`.`pending` = 0
		AND MATCH (`item`.`body`) AGAINST ( '%s' IN BOOLEAN MODE )
		$sql_extra
		ORDER BY `parent` DESC ",
		dbesc($search)
	);

	$tpl = load_view_file('view/search_item.tpl');
	$droptpl = load_view_file('view/wall_fake_drop.tpl');

	$return_url = $_SESSION['return_url'] = $a->cmd;

	if(count($r)) {

		foreach($r as $item) {

			$comment     = '';
			$owner_url   = '';
			$owner_photo = '';
			$owner_name  = '';
			$sparkle     = '';
			
			if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE))) 
				&& ($item['id'] != $item['parent']))
				continue;

			$profile_name   = ((strlen($item['author-name']))   ? $item['author-name']   : $item['name']);
			$profile_avatar = ((strlen($item['author-avatar'])) ? $item['author-avatar'] : $item['thumb']);
			$profile_link   = ((strlen($item['author-link']))   ? $item['author-link']   : $item['url']);


			$location = (($item['location']) ? '<a target="map" href="http://maps.google.com/?q=' . urlencode($item['location']) . '">' . $item['location'] . '</a>' : '');
			$coord = (($item['coord']) ? '<a target="map" href="http://maps.google.com/?q=' . urlencode($item['coord']) . '">' . $item['coord'] . '</a>' : '');
			if($coord) {
				if($location)
					$location .= '<br /><span class="smalltext">(' . $coord . ')</span>';
				else
					$location = '<span class="smalltext">' . $coord . '</span>';
			}

			$drop = replace_macros($droptpl,array('$id' => $item['id']));

			$o .= replace_macros($tpl,array(
				'$id' => $item['item_id'],
				'$profile_url' => $profile_link,
				'$name' => $profile_name,
				'$sparkle' => $sparkle,
				'$thumb' => $profile_avatar,
				'$title' => $item['title'],
				'$body' => bbcode($item['body']),
				'$ago' => relative_date($item['created']),
				'$location' => $location,
				'$indent' => (($item['parent'] != $item['item_id']) ? ' comment' : ''),
				'$owner_url' => $owner_url,
				'$owner_photo' => $owner_photo,
				'$owner_name' => $owner_name,
				'$drop' => $drop,
				'$conv' => '<a href="' . $a->get_baseurl() . '/display/' . $item['nickname'] . '/' . $item['id'] . '">' . t('View in context') . '</a>'
			));

		}
	}
	return $o;
}

