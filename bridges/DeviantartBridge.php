<?php
class DeviantartBridge extends FeedExpander {

	const MAINTAINER = 'dawidsowa';
	const NAME = 'DeviantartBridge';
	const URI = 'https://www.deviantart.com';
	const DESCRIPTION = 'DeviantartBridge feed cleaner';
	const PARAMETERS = array();
	const CACHE_TIMEOUT = 1;

	public function collectData(){
		$this->collectExpandableDatas('https://backend.deviantart.com/rss.xml?q=gallery%3Ajklind');
	}

	protected function parseItem($feedItem){
		$item = $this->parseRSS_2_0_Item($feedItem);

			$html = str_get_html($item['content']);

		if(! is_null($html->find('img', 0))){
			$html->find('img', 0)->src = $item['enclosures'][0];
		}
		error_log($html);
		$item['content'] = $html;

		return $item;
	}
}