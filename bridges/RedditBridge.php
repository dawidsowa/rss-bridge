<?php
class RedditBridge extends BridgeAbstract
{

	const MAINTAINER = 'dawidsowa';
	const NAME = 'Reddit Bridge';
	const URI = 'https://www.reddit.com';
	const DESCRIPTION = 'Reddit RSS Feed fixer';

	const PARAMETERS = array(
		'single' => array(
			'r' => array(
				'name' => 'SubReddit',
				'required' => true,
				'exampleValue' => 'selfhosted',
				'title' => 'SubReddit name'
			)
		),
		'multi' => array(
			'rs' => array(
				'name' => 'SubReddits',
				'required' => true,
				'exampleValue' => 'selfhosted, php',
				'title' => 'SubReddit names, separated by commas'
			)
		)
	);

	private function encodePermalink($link)
	{
		return self::URI
			. implode('/', array_map('urlencode', explode('/', $link)));
	}

	public function getIcon()
	{
		return 'https://www.redditstatic.com/desktop2x/img/favicon/favicon-96x96.png';
	}

	public function collectData()
	{

		switch ($this->queriedContext) {

			case 'single':
				$subreddits[] = $this->getInput('r');
				break;
			case 'multi':
				$subreddits = explode(',', $this->getInput('rs'));
				break;
		}

		foreach ($subreddits as $subreddit) {
			$name = trim($subreddit);

			$values = getContents(self::URI . '/r/' . $name . '.json')
				or returnServerError('Unable to fetch posts!');
			$decodedValues = json_decode($values);

			foreach ($decodedValues->data->children as $post) {
				$data = $post->data;

				$item = array();
				$item['author'] = $data->author;
				$item['title'] = $data->title;
				$item['id'] = $data->id;
				$item['uri'] = $this->encodePermalink($data->permalink);

				$item['timestamp'] = $data->created_utc;

				if ($data->is_self) {
					$item['content'] =
						htmlspecialchars_decode($data->selftext_html);
				} elseif ($data->post_hint == 'link') {
					// Link WITH preview

					// TODO If it's a link to image, use that link
					$item['content'] = '<a href="' . $data->url
						. '"><img src="' . $data->thumbnail . '" /></a>';
				} elseif ($data->post_hint == 'image') {
					$item['content'] = '<a href="'
						. $this->encodePermalink($data->permalink)
						. '"><img src="' . $data->url . '" /></a>';
				} elseif ($data->is_gallery) {
					$images = array();
					foreach ($data->gallery_data->items as $media) {
						$id = $media->media_id;
						$src = $data->media_metadata->$id->s->u;

						$images[] = '<img src="' . $src . '"/>';
					}

					$item['content'] = implode("", $images);
				} elseif ($data->is_video) {
					// JSON has a video url without audio, so it's better to just use image
					$item['content'] =  '<a href="'
						. $this->encodePermalink($data->permalink) . '">'
						. '<figure>'
						. '<figcaption>Video</figcaption>'
						. '<img src="'
						. $data->preview->images[0]->resolutions[3]->url . '"/>'
						. '</figure>'
						. '</a>';
				} else {
					// Link WITHOUT preview
					if (explode('.', $data->domain)[0] == 'self') {
						// Crossposted text post
						// TODO (optionally?) Fetch content of the original post.
						// REVIEW Should it point to the original, or the crosspost?
						$item['content'] = '<a href="'
							. $this->encodePermalink($data->permalink)
							. '">Crossposted from r/'
							. explode('.', $data->domain)[1] .  '</a>';
					} else {
						// Link WITHOUT preview
						$item['content'] = '<a href="' . $data->url . '">'
							. $data->domain . '</a>';
					}
				}

				$this->items[] = $item;
			};
		}
	}
}
