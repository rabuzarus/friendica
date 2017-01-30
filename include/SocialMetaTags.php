<?php

/**
 * @file include/SocialMetaTags.php
 * @brief This file contains the SocialMetaTags class with its methods for creating
 *     entries for the head element to make the content of the page embeddable.
 * 
 * @todo: combine Open Graph and Scheme.org (maybe also twitter)
 *  remove Dublin Core since we don't support it very well at the moment
 *  add additional methods (setThroughArray($arr), setProfile($profile), setItem($item))
 *  add Doxygen
 *  rename public static function set() to public static function setField();
 */
require_once('include/bbcode.php');
require_once("include/html2plain.php");

class SocialMetaTags {
	private static $tags = array();

	private static $shtml = null;
	private static $og = null;
	private static $so = null;
	private static $tw = null;
	private static $dc = null;

	public static function set($tag, $content) {
		if ($content == "") {
			return;
		}

		if ($tag == 'title' || $tag == 'description') {
			$content = trim(html2plain(bbcode($content, false, false), 0, true));
		}

		if ($tag == 'title' || $tag == 'description' || $tag == 'author') {
			$content = htmlspecialchars($content, ENT_COMPAT, 'UTF-8', true);
		}

		if ($tag == 'description' && strlen($content) > 160) {
			$content = substr($content, 0, 157) . '...';
		}

		self::$tags[$tag] = $content;
	}

	public static function get() {
		// If no tags are available return here
		if (!count(self::$tags)) {
			return;
		}

		$header = "";
		$a = get_app();

		if (!isset(self::$tags['title']) && isset(self::$tags['author'])) {
			self::$tags['title'] = self::$tags['author'];
		}

		// Process the available tags according to the definitions
		// of each supported type
		self::assignTags();

		// Standard HTML
		if (self::$shtml) {
			foreach (self::$shtml as $k => $v) {
				$header .= '<meta name="' . $k . '" content="' . $v . '" />' . "\r\n" ;
			}
		}

		// Open Graph
		if (self::$og) {
			foreach (self::$og as $k => $v) {
				$header .= '<meta property="' . $k . '" content="' . $v . '" />' . "\r\n" ;
			}
		}

		// schema.org
		if (self::$so) {
			$type = self::getSchemaOrgType(self::$tags['type']);
			$header .= '<meta itemscope itemtype="http://schema.org/'. $type . '" />';

			foreach (self::$so as $k => $v) {
				$header .= '<meta property="' . $k . '" content="' . $v . '" />' . "\r\n" ;
			}
		}

		// Twitter
		if (self::$tw && self::checkTwitterRequirements()) {
			foreach (self::$tw as $k => $v) {
				$header .= '<meta name="' . $k . '" content="' . $v . '" />' . "\r\n" ;
			}
		}

		// Dublin Core
		if (self::$dc) {
			foreach (self::$dc as $k => $v) {
				$header .= '<meta name="' . $k . '" content="' . $v . '" />' . "\r\n" ;
			}
		}

		if ($header != "") {
			return $header;
		}
	}

	private static function assignTags() {

		// Get the supported fields for each type
		$ogTags = self::getOpenGraphTags();
		$tTags = self::getTwitterCardTags();
		$soTags = self::getSchemaOrgTags();
		$dcTags = self::getDublinCoreTags();
		$hTags = self::getHtmlTags();

		// Compare the available fields with the supported fields and
		// copy the supported entries into an own array
		$ogCache = array_intersect_key(self::$tags, array_flip($ogTags));
		$tCache = array_intersect_key(self::$tags, array_flip($tTags));
		$soCache = array_intersect_key(self::$tags, array_flip($soTags));
		$dcCache = array_intersect_key(self::$tags, array_flip($dcTags));

		$html = array_intersect_key(self::$tags, array_flip($hTags));

		// Change the key and values according to the definitions
		$opengraph = self::constructOpenGraphArray($ogCache);
		$schemaorg = self::constructSchemaOrgArray($soCache);
		$twitter = self::constructTwitterArray($tCache);
		$dublincore = self::constructDublinCoreArray($dcCache);


		self::$shtml = $html;
		self::$og = $opengraph;
		self::$so = $schemaorg;
		self::$tw = $twitter;
		self::$dc = $dublincore;

	}

	public static function get_field($field) {
		if(isset(self::$tags[$field])) {
			return self::$tags[$field];
		}

		return false;
	}

	private static function constructOpenGraphArray($arr) {
		// If we don't have all required tags we can return here
		if (!self::checkOpenGraphRequirements()) {
			return;
		}

		$og = array();

		foreach ($arr as $field => $v) {
			switch ($field) {
				case 'author':
				case 'publisher':
				case 'tag':
					$og['og:article:' . $field] = $v;
					break;

				case 'created':
					$og['og:article:published_time'] = $v;
					break;

				case 'modified':
					$og['article:modified_time'] = $v;
					break;

				case 'username':
					$og['og:profile:' . $field] = $v;
					break;

				case 'type':
					$og['og:type'] = self::getOpenGraphType($v);
				default:
					$og['og:' . $field] = $v;
					break;
			}
		}

		if (count($og)) {
			return $og;
		}
	}

	private static function constructSchemaOrgArray($arr) {
		$so = array();

		foreach ($arr as $field => $v) {
			switch ($field) {
				case 'title':
					$so['headline'] = $v;
					break;

				case 'tag':
					$so['keywords'] = $v;
					$break;

				case 'created':
					$so['dateCreated'] = $v;
					$break;

				case 'modified':
					$so['dateModified'] = $v;
					break;

				case 'username':
					$so['name'] = $v;
					break;

				default:
					$so[$field] = $v;
					break;
			}
		}

		if (count($so)) {
			return $so;
		}
	}

	private static function constructTwitterArray($arr) {
		// If we don't have all required tags we can return here
		if (!self::checkTwitterRequirements()) {
			return;
		}

		$twitter = array();
		foreach ($arr as $field => $v) {
			$twitter['twitter:' . $field] = $v;
		}

		if (count($twitter)) {
			// Every twitter card does need the field "card" with
			// value "summaray" or "summary_large_image"
			$card = ((self::$tags['type'] == 'photo') ? 'summery_large_image' : 'summery');
			$twitter = array_merge(array('twitter:card' => $card), $twitter);

			return $twitter;
		}
	}

	private static function constructDublinCoreArray($arr) {
		$dc = array();
		foreach ($arr as $field => $v) {
			$dc['DC.' . $field] = $v;
		}

		if (count($dc)) {
			return $dc;
		}
	}

	private static function getHtmlTags() {
		$tags = array('title', 'fulltitle', 'description', 'author');
		return $tags;
	}

	private static function getOpenGraphTags() {
		$standardTags = array('title', 'type', 'description', 'url', 'image');
		switch (self::$tags['type']) {
			case 'article':
				$additionalTags = array('author', 'publisher','tag', 'created', 'modified');
				break;

			case 'profile':
				$additionalTags = array('username');
				break;

			default:
				break;
		}

		$tags = array_merge_recursive($standardTags, $additionalTags);
		return $tags;
	}

	private static function getTwitterCardTags() {
		$tags= array('title', 'description', 'url', 'image');
		return $tags;
	}

	private static function getSchemaOrgTags() {
		$standardTags = array('type', 'description', 'url', 'image');
		switch (self::$tags['type']) {
			case 'article':
				$additionalTags = array('title', 'author', 'publisher','tag', 'created', 'modified');
				break;

			case 'profile':
				$additionalTags = array('username');
				break;

			default:
				break;
		}

		$tags = array_merge_recursive($standardTags, $additionalTags);
		return $tags;
	}

	private static function getDublinCoreTags() {
		$tags = array('title', 'description');
		return $tags;
	}

	private static function checkTwitterRequirements() {
		if (isset(self::$tags['title']) && isset(self::$tags['description'])) {
			return true;
		}

		return false;
	}

	private static function checkOpenGraphRequirements() {
		if (
			isset(self::$tags['title'])
			&& isset(self::$tags['type'])
			&& isset(self::$tags['image'])
			&& isset(self::$tags['url'])
		) {
			return true;
		}

		return false;
	}

	private static function getOpenGraphType($type) {
		switch ($type) {
			case 'profile':
				$itemType = 'profile';
				break;

			case 'post':
				$itemType = 'article';
			default:
				$itemType = 'website';
				break;
			}

		return $itemType;
	}

	private static function getSchemaOrgType($type) {
		switch ($type) {
			case 'profile':
				$itemType = 'Person';
				break;

			default:
				$itemType = 'BlogPosting';
				break;
			}

		return $itemType;
	}

}
