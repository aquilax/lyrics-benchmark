<?php
require 'init.php';

class Filler extends BaseClass{

	const MIN_ALBUM_COUNT = 0;
	const MAX_ALBUM_COUNT = 15;

	const MIN_SONG_COUNT = 0;
	const MAX_SONG_COUNT = 30;

	const MIN_LYRICS_WORDS = 60;
	const MAX_LYRICS_WORDS = 360;

	const MAX_QUEUE_COUNT = 200;

	var $queue = [];
	var $update = null;
	var $article_id = 1;


	function __construct( Solarium_Client $client, $indexType ) {
		parent::__construct( $client, $indexType );
		$this->update = $this->client->createUpdate();
	}

	function fill( $artistsCount ){
		// init rand with artistsCount for data consistency
		mt_srand( $artistsCount );
		for ( $i = 1; $i <= $artistsCount; $i++ ) {
			$this->generateArtist($i);
		}
		$this->commit(); // commit the last queue
	}

	function generateArtist ( $index ) {
		$doc = $this->newDocument();
		$artistId = $this->newId();
		$artistName = $this->generateName( 'Artist', $index );
		$doc->id = $artistId;
		$doc->artist_name = $artistName;
		$doc->type = self::ARTIST_TYPE;
		$doc->itunes = $this->generateItunes();
		$doc->image = $this->generateName( 'Artist', $index, '.jpg');
		$doc->albums = $this->generateAlbums( $doc );
		$doc->songs = $this->generateSongs( $doc, null );
		$this->addDoc( $doc );
	}

	function generateAlbums( $artist ) {
		$albums = [];
		$albumsCount = $this->rand( self::MIN_ALBUM_COUNT, self::MAX_ALBUM_COUNT );
		for ( $i = 1; $i <= $albumsCount; $i++ ) {
			$doc = $this->newDocument();
			$albumId = $this->newId();
			$albumName = $this->generateName( 'Album', $i);
			$doc->id = $albumId;
			$doc->artist_name = $artist->artist_name;
			$doc->album_name = $albumName;
			$doc->type = self::ALBUM_TYPE;
			$doc->release_date = $this->rand( 1930, 2014 );
			$doc->itunes = $this->generateItunes();
			$doc->image = $this->generateName( 'Album', $i, '.jpg' );
			$doc->songs = $this->generateSongs( $artist, $doc );
			$this->addDoc( $doc );
			$albums[] = $this->getAlbumData( $doc );
		}
		return json_encode( $albums );
	}

	function getAlbumData( $albumDoc ) {
		switch ( $this->indexType ) {
			case self::TYPE_NORMALIZED:
				return $albumDoc->id;
				break;
			case self::TYPE_DENORMALIZED:
				return [
					'name' => $albumDoc->album_name,
					'image' => $albumDoc->image,
					'year' => $albumDoc->release_date,
				];
				break;
		}
	}

	function generateSongs( $artist, $album ) {
		$songs = [];
		$albumName = is_null( $album ) ? '' : $album->album_name;
		$songCount = $this->rand( self::MIN_SONG_COUNT, self::MAX_SONG_COUNT );
		for ( $i = 1; $i <= $songCount; $i++ ) {
			$doc = $this->newDocument();
			$songId = $this->newId();
			$songName = $this->generateName( 'Song', $i );
			$doc->id = $songId;
			$doc->artist_name = $artist->artist_name;
			$doc->album_name = $albumName;
			$doc->song_name = $songName;
			$doc->type = self::SONG_TYPE;
			$doc->itunes = $this->generateItunes();
			$doc->lyrics = $this->generateLyrics();
			$this->addDoc( $doc );
			$songs[] = $this->getSongData( $doc );
		}
		return json_encode( $songs );
	}

	function getSongData( $songDoc ) {
		switch ( $this->indexType ) {
			case self::TYPE_NORMALIZED:
				return $songDoc->id;
				break;
			case self::TYPE_DENORMALIZED:
				return [
					'name' => $songDoc->song_name,
				];
				break;
		}
	}

	function newDocument() {
		return $this->update->createDocument();
	}

	function generateItunes() {
		return $this->rand( 100000, 999999 );
	}

	function newId() {
		return $this->article_id++;
	}

	function addDoc( $doc ){
		$this->queue[] = $doc;
		$this->autoCommit();
		echo '.';
	}

	function autoCommit() {
		if (self::MAX_QUEUE_COUNT == count( $this->queue )) {
			$this->commit();
		}
	}

	function commit() {
		$result = null;
		if ( $this->queue ) {
			$this->update->addDocuments( $this->queue );
			$this->update->addCommit();
			$result = $this->client->update( $this->update );
			$this->update = $this->client->createUpdate();
			$this->queue = [];
			echo 'C';
		}
		return $result;
	}

 	function generateLyrics() {
		$words = [];
		$wordsCount = rand( self::MIN_LYRICS_WORDS, self::MAX_LYRICS_WORDS );
		for ( $i = 0; $i < $wordsCount; $i++ ) {
			$words[] = $this->words[rand( 0, $this->wordsSize-1 )];
		}
		return implode( ' ', $words );
	}

}

$typeSet = [
	'normalized' => 1,
	'denormalized' => 2,
];

if ( isset( $argv[1] ) && is_numeric( $argv[1] ) ) {
	$artistsCount = (int)$argv[1];
} else {
	die( 'Please provide number of artists to create as first parameter' . PHP_EOL );
}

if ( isset( $argv[2] ) && in_array($argv[2], array_keys( $typeSet ) ) ) {
	$indexType = $typeSet[$argv[2]] ;
} else {
	die( 'Please provide type of index to create: ' . implode( ', ', array_keys( $typeSet ) ) . PHP_EOL );
}

$filler = new Filler( $client, $indexType );
$filler->fill( $artistsCount );