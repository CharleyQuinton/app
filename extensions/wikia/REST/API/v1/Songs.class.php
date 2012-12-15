<?php
namespace REST\API\v1;

class Songs extends \REST\base\Resource implements \REST\base\Readable {
	public function read(){
		return null;
	}

	/**
	 * Taken from extensions/3rdparty/LyricWiki/server.php
	 */
	public static function formatTitle( $title, $artist = null ) {
		wfProfileIn( __METHOD__ );

		if ( $artist !== null ) {
			$title = "{$artist}:{$title}";
		}

		$songTitle = Artists::formatTitle( $title );

		wfProfileOut( __METHOD__ );
		return $songTitle;
	}

	public static function parseTitle( $title ) {
		wfProfileIn( __METHOD__ );

		$count = preg_match( "/^([^:]+):(.*)$/", $title, $matches );

		if ( $count > 0 ) {
			$info = array(
				'artist' => $matches[1],
				'title' => $matches[2]
			);

			wfProfileOut( __METHOD__ );
			return $info;
		} else {
			wfProfileOut( __METHOD__ );
			throw new \Exception( 'Incorrectly formatted title' );
		}
	}
}