<?php

class BaseClass {

	const TYPE_NORMALIZED = 1;
	const TYPE_DENORMALIZED = 2;

	const ARTIST_TYPE = 'artist';
	const ALBUM_TYPE = 'album';
	const SONG_TYPE = 'song';

	const MIN_ALBUM_COUNT = 0;
	const MAX_ALBUM_COUNT = 15;

	const MIN_SONG_COUNT = 3;
	const MAX_SONG_COUNT = 30;

	var $client = null;
	var $indexType = self::TYPE_NORMALIZED;

	function __construct( Solarium_Client $client, $indexType ) {
		$this->client = $client;
		$this->indexType = $indexType;
		$this->words = explode(' ', 'One and a-two and a-three Ya-tya-da tya tya da tya tya da tya Ta dee-da-dum On a desert island, a magic yours and my land Everyday is a holiday with you Under a blue sky dear we could get an idea Of what our two lips were meant to do Strolling beside you hand in hand well go Through love promised land dear All our lives I know believe me  Happiness would be ours if for only three hours On a desert  island in my dreams Ya-tya-da tya tya da tya tya da tya Ta dee-da-dum Ya-tya-da tya tya da tya tya da tya Ta dee-da-dum Strolling beside you hand in hand well go Through love promise land dear All our lives I know sincerely Every gal and guy can have a desert island If they are in love as much as we Happiness will be ours if for only three hours On a desert island in my dreams On a desert island in my dreams');
		$this->wordsSize = count( $this->words );
	}

	function generateName() {
		return implode( ' ', func_get_args() );
	}

	function rand( $min, $max ) {
		return mt_rand( $min, $max );
	}

}