class Entity_Parser {

	const REGEX_USERNAME = '/(@[a-zA-Z0-9_]+)/';
	const REGEX_HASHTAG = '/(#[a-zA-Z][a-zA-Z0-9]+)/';
	const REGEX_URL = '/((https?:\/\/)?([a-zA-Z0-9][a-zA-Z0-9-\.]+)(\.[a-zA-Z]{2,3})(\/.+)?)/';

	public static function parse($text, $links = true) {
		$overall = array('entities' => self::entities($text), 'text' => $text, 'html' => self::_parseHTML($text, $links));
		return $overall;
	}

	public static function entities($text) {
		$ext = self::_findMentions($text);
		$ext = self::_findTags($text, $ext);
		$ext = self::_findLinks($text, $ext);
		return $ext;
	}

	public static function parseHTML($text, $links = true) {
		return self::_parseHTML($text, $links);
	}

	protected static function _parseHTML($text, $links = true) {
		$text = preg_replace_callback(self::REGEX_USERNAME, function ($matches) use($links) {
			$username = str_replace('@', '', array_shift($matches));
			$user = Users::find($username);
			if (!is_object($user)) return $username . ' -bad';
			$id = $user->id;
			if ($links) {
				return '<a href="' . Url::make($username) . '" itemprop="mention" data-mention-id="' . $id . '" data-mention-name="' . $username . '">' . array_shift($matches) . '</a>';
			} else {
				return '<span itemprop="mention" data-mention-id="' . $id . '" data-mention-name="' . $username . '">' . array_shift($matches) . '</span>';
			}
		}, $text);

		$text = preg_replace_callback(self::REGEX_HASHTAG, function ($matches) use($links) {
			$name = str_replace('#', '', array_shift($matches));
			if ($links) {
				return '<a href="' . Url::make('search?q=%23' . $name) . '" itmeprop="hashtag" data-hashtag-name="' . $name . '">#' . $name . '</a>';
			} else {
				return '<span itmeprop="hashtag" data-hashtag-name="' . $name . '">#' . $name . '</span>';
			}
		}, $text);

		$text = preg_replace_callback(self::REGEX_URL, function ($matches) use($links) {
			$url = array_shift($matches);
			$full = (!preg_match('/^(https?:\/\/)/', $url)) ? 'http://' . $url : $url;
			return '<a href="' . $full . '" rel="nofollow">' . $url . '</a>';
		}, $text);

		return $text;
	}

	protected static function _findMentions($text, $ext = array()) {
		preg_match_all(self::REGEX_USERNAME, $text, $matches);
		$matches = array_shift($matches);
		$ext['mentions'] = array();
		foreach ($matches as $i => $m) {
			$name = str_replace('@', '', $m);
			$user = Users::find($name);
			if (!is_object($user)) continue;
			$ext['mentions'][] = array(
				'name' => $name,
				'id' => $user->id,
				'pos' => strpos($text, $m),
				'len' => strlen($m)
			);
		}
		return $ext;
	}

	protected static function _findTags($text, $ext = array()) {
		preg_match_all(self::REGEX_HASHTAG, $text, $matches);
		$matches = array_shift($matches);
		$ext['hashtags'] = array();
		foreach ($matches as $i => $m) {
			$name = str_replace('#', '', $m);
			$ext['hashtags'][] = array(
				'name' => $name,
				'pos' => strpos($text, $m),
				'len' => strlen($m)
			);
		}
		return $ext;
	}

	protected static function _findLinks($text, $ext = array()) {
		preg_match_all(self::REGEX_URL, $text, $matches);
		$matches = array_shift($matches);
		$ext['links'] = array();
		foreach ($matches as $i => $m) {
			$ext['links'][] = array(
				'url' => $m,
				'pos' => strpos($text, $m),
				'len' => strlen($m)
			);
		}
		return $ext;
	}
}
