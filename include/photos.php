<?php
/**
 * @file include/photos.php
 * @brief Functions related to photo handling.
 */

function getGps($exifCoord, $hemi) {
	$degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
	$minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
	$seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

	$flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

	return floatval($flip * ($degrees + ($minutes / 60) + ($seconds / 3600)));
}

function gps2Num($coordPart) {
	$parts = explode('/', $coordPart);

	if (count($parts) <= 0)
		return 0;

	if (count($parts) == 1)
		return $parts[0];

	return floatval($parts[0]) / floatval($parts[1]);
}

/**
 * @brief Fetch the photo albums that are available for a viewer
 *
 * The query in this function is cost intensive, so it is cached.
 *
 * @param int $uid User id of the photos
 * @param bool $update Update the cache
 *
 * @return array Returns array of the photo albums
 */
function photo_albums($uid, $update = false) {
	$sql_extra = permissions_sql($uid);

	$key = "photo_albums:".$uid.":".local_user().":".remote_user();
	$albums = Cache::get($key);

	// To avoid to much load we only keep the cache entry of the album owner up to date
	// if the data changes. If the user isn't the owner of the album we need to compare
	// the dates of the db entries to check if the cached data is valid
	if (($update === false) && ($uid != local_user())) {
		if (dbm::is_result($albums)) {
			$ownerkey = "photo_albums:".$uid.":".$uid.":";
			$ownerupdate = Cache::getUpdateValue($ownerkey);
			$lastupdate = Cache::getUpdateValue($key);
			$update = (($ownerupdate > $lastupdate) ? true : false);
			//logger('Update Variable: '.print_r($update,true).' Last Update: '.$lastupdate.' Last Owner Update: '.$ownerupdate);
			//logger('Photo Albums Key: '.$key);
		}
	}

	if (is_null($albums) OR $update) {
		/// @todo This query needs to be renewed. It is really slow
		// At this time we just store the data in the cache
		$albums = qu("SELECT COUNT(DISTINCT `resource-id`) AS `total`, `album`
			FROM `photo` USE INDEX (`uid_album_created`)
			WHERE `uid` = %d  AND `album` != '%s' AND `album` != '%s' $sql_extra
			GROUP BY `album` ORDER BY `created` DESC",
			intval($uid),
			dbesc('Contact Photos'),
			dbesc(t('Contact Photos'))
		);
		Cache::set($key, $albums, CACHE_DAY);
	}
	return $albums;
}

function photo_albums_modify_cache($uid, $action, $albumname, $newalbumname = '') {

	if (!$albumname) {
		return;
	}

	$albumkey = false;
	$newalbumkey = false;

	// We only will modify the cached data of the album owner. This should be
	// the one which should always contain updated data.
	$cachekey = "photo_albums:".$uid.":".$uid.":";
	$albums = Cache::get($cachekey);

	// If no cached data is available we can leave at this point because there
	// is nothing we need to change. The user will get valid data through
	// the cache update of function albums().
	if (is_null($albums)) {
		return;
	}

	// Loop through the array to search if the album is available 
	foreach ($albums as $key => $album) {

		if ($album["album"] == $albumname) {
			$albumkey = $key;
		}
		if ($album["album"] == $newalbumname) {
			$newalbumkey = $key;
		}

		if ((($albumkey !== false) && !$newalbumname)
			|| (($albumkey !== false) && ($newalbumkey !== false))) {
			break;
		}
	}
	$total = $albums[$albumkey]["total"];


	switch ($action) {
		// New photo was uploaded
		case "new_photo":
			if ($albumkey !== false) {
				$albums[$albumkey]["total"]++;
			} else {
				array_unshift($albums, array('album' => $albumname, "total" => 1));
			}
			break;

		// Photo was deleted
		case "remove_photo":
			$total = $albums[$albumkey]["total"] -1;
			$albums[$albumkey]["total"] = $total;

			// Remove the album if this photo was the last photo was removed
			if ($total < 1) {
				unset($albums[$albumkey]);
			}
			break;

		// Photo was moved to another Album
		case "move_photo":
			// Break here if the old and the new album name is identically
			// or the new albmuname is empty
			if (!albumname || ($albumname == $newalbumname)) {
				break;
			}

			$total = $albums[$albumkey]["total"] -1;
			$newtotal = 1;

			// Album found and new album does already exist
			if (($albumkey !== false) && ($newalbumkey !== false)) {
				$newtotal = $albums[$newalbumkey]["total"] + 1;
				$albums[$newalbumkey]["total"] = $newtotal;
				$albums[$albumkey]["total"] = $total;

				// Remove the album if this photo was the last photo was removed
				if ($total < 1) {
					unset($albums[$albumkey]);
				}


			// Album found and new album doesn't exist
			} elseif (($albumkey !== false) && ($newalbumkey === false)) {
				$albums[$albumkey]["total"] = $total;
				// Remove the album if this photo was the last photo was removed
				if ($total < 1) {
					unset($albums[$albumkey]);
				}
				array_unshift($albums, array('album' => $newalbumname, "total" => 1));
			}

			break;

		// Album was renamed
		case "rename_album":
			if ($albumname == $newalbumname) {
				break;
			}

			// Album found and new album does already exist
			if (($albumkey !== false) && ($newalbumkey !== false)) {
				$newtotal = $total + $newtotal;
				$albums[$newalbumkey]["total"] = $newtotal;
				unset($albums[$albumkey]);

			// Album found and new album doesn't exist
			} elseif (($albumkey !== false) && ($newalbumkey === false)) {
				$albums[$albumkey]["album"] = $newalbumname;

			}
			break;

		// Album was deleted
		case "delete_album":
			if ($albumkey !== false) {
				unset($albums[$albumkey]);
			}
			break;
	}

	Cache::set($cachekey, $albums, CACHE_DAY);
}
