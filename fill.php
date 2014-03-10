<?php
require 'init.php';

class Filler {

	const TYPE_NORMALIZED = 1;
	const TYPE_DENORMALIZED = 2;

	const ARTIST_TYPE = 'artist';
	const ALBUM_TYPE = 'album';
	const SONG_TYPE = 'song';

	const MIN_ALBUM_COUNT = 0;
	const MAX_ALBUM_COUNT = 30;

	const MIN_SONG_COUNT = 0;
	const MAX_SONG_COUNT = 30;

	var $queue = [];
	var $client = null;
	var $update = null;
	var $article_id = 1;
	var $indexType = self::TYPE_NORMALIZED;

	function __construct( $client, $indexType ) {
		$this->client = $client;
		$this->update = $this->client->createUpdate();
	}

	function fill( $artistsCount ){
		for ( $i = 1; $i <= $artistsCount; $i++ ) {
			$this->generateArtist($i);
		}
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
		$doc->albums = $this->generateAlbums( $artistId, $artistName );
		$doc->songs = $this->generateSongs( $artistId, $artistName, 0, '');
		$this->addDoc( $doc );
	}

	function generateAlbums( $artistId, $artistName ) {
		$albums = [];
		$albumsCount = rand( self::MIN_ALBUM_COUNT, self::MAX_ALBUM_COUNT );
		for ( $i = 1; $i <= $albumsCount; $i++ ) {
			$doc = $this->newDocument();
			$albumId = $this->newId();
			$albumName = $this->generateName( $artistName, 'Album', $i, $albumId );
			$doc->id = $albumId;
			$doc->artist_name = $artistName;
			$doc->album_name = $albumName;
			$doc->type = self::ALBUM_TYPE;
			$doc->itunes = $this->generateItunes();
			$doc->image = $this->generateName( 'Album', $i, '.jpg' );
			$doc->songs = $this->generateSongs( $artistId, $artistName, $albumId, $albumName );
			$this->addDoc( $doc );
		}
		return $albums;
	}

	function generateSongs( $artistId, $artistName, $albumId, $albumName ) {
		$songs = [];
		$songCount = rand( self::MIN_SONG_COUNT, self::MAX_SONG_COUNT );
		for ( $i = 1; $i <= $songCount; $i++ ) {
			$doc = $this->newDocument();
			$songId = $this->newId();
			$songName = $this->generateName( $artistName, 'song', $i, $albumId );
			$doc->id = $albumId;
			$doc->artist_name = $artistName;
			$doc->album_name = $albumName;
			$doc->song_name = $songName;
			$doc->type = self::SONG_TYPE;
			$doc->itunes = $this->generateItunes();
			$doc->lyrics = $this->generateLyrics();
			$this->addDoc( $doc );
		}
		return $songs;
	}


	function newDocument() {
		return $this->update->createDocument();
	}

	function generateName() {
		return implode( ' ', func_get_args() );
	}

	function generateItunes() {
		return rand( 100000, 999999 );
	}

	function newId() {
		return $this->article_id++;
	}

	function addDoc( $doc ){
		$this->queue[] = $doc;
	}

}

$typeSet = [
	'normalized' => 1,
	'denormalized' => 2,
];

if ( !isset( $argv[1] ) || !is_numeric( $argv[1] ) ) {
	$artistsCount = (int)$argv[1];
} else {
	die( 'Please provide number of artists to create as first parameter' );
}

if ( !isset( $argv[2] ) || !in_array($argv[2], array_keys( $typeSet ) ) ) {
	$indexType = $typeSet[$argv[2]] ;
} else {
	die( 'Please provide type of index to create: ' . implode( ', ', $typeSet ) );
}

$filler = new Filler( $client, $indexType );
$filler->fill( $artistsCount );