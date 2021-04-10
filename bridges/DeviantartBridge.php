<?php

class DeviantartBridge extends FeedExpander {

	const MAINTAINER = 'dawidsowa';
	const NAME = 'DeviantartBridge';
	const URI = 'https://www.deviantart.com';
	const DESCRIPTION = 'DeviantartBridge feed cleaner';
	const PARAMETERS = array(array(
		'user' => array(
			'name' => 'Username',
			'required' => true
		)));
	const CACHE_TIMEOUT = 1;

	public function detectParameters($url) {
		$parsed_url = parse_url($url);

		if ($parsed_url['host'] != 'www.deviantart.com') return null;

		$user = explode('/', $parsed_url['path'])[1];

		if (in_array($user, array('watch', 'daily-deviations', 'topic', 'popular'))) {
			return null;
		}

		return array(
			'user' => $user
		);
	}

	public function collectData() {
		$this->collectExpandableDatas('https://backend.deviantart.com/rss.xml?q=gallery%3A' . $this->getInput('user'));
	}

	protected function parseItem($feedItem) {
		$item = $this->parseRSS_2_0_Item($feedItem);

		$html = str_get_html($item['content']);

		if (!is_null($html->find('img[alt="thumbnail"]', 0))) {
			$html->find('img[alt="thumbnail"]', 0)->src = $item['enclosures'][0];
		}

		$item['content'] = $html;

		return $item;
	}
}