<?php

require 'init.php';

class Benchmark extends BaseClass {

	function start( $count ) {
		// init rand with count for data consistency
		mt_srand( $count );

		$this->test( 'dummy', $count );
		$this->test( 'getArtist', $count );
		$this->test( 'getAlbum', $count );
		$this->test( 'getSong', $count );
		$this->test( 'searchLyrics', $count );
		$this->test( 'searchArtist', $count );
		$this->test( 'searchSong', $count );
	}

	function dummy( $count ) {
		sleep( 1 );
	}

	function newQueryFromSearch( $fields ) {
		$query = $this->client->createSelect();
		$q = implode(' AND ', array_keys( $fields ) );
		$query->setQuery( $q, array_values($fields) );
		return $query;
	}

	function getArtist ( $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$artistName = $this->generateName( 'Artist', $this->rand( 1, 1000 ) );
			switch ($this->indexType) {
				case self::TYPE_NORMALIZED:
					$this->getArtistNormalized( $artistName );
					break;
				case self::TYPE_DENORMALIZED:
					$this->getArtistDeNormalized( $artistName );
					break;
			}
		}
	}

	function getArtistNormalized ( $artistName ) {
		$resultSet = $this->getArtistDeNormalized( $artistName );
		if ( $resultSet->getNumFound() ) {
			$artist = $resultSet->getDocuments()[0];
			$albums = json_decode( $artist->albums, true );
			$songs = json_decode( $artist->songs, true );
			$ids = array_merge( $albums, $songs );
			$types = sprintf( "'%s', '%s'", self::ALBUM_TYPE, self::SONG_TYPE );
			$query = $this->newQueryFromSearch([
				//'type: (%1%)' => $types,
				'id: (%1%)' =>  implode( ' ', $ids ),
			]);
			return $this->client->select( $query );
		}
	}

	function getArtistDeNormalized( $artistName ) {
		$query = $this->newQueryFromSearch([
			'type: %1%' => self::ARTIST_TYPE,
			'artist_name: %P2%' => $artistName,

		]);
		$query->setStart(0)->setRows(1);
		return $this->client->select( $query );
	}

	function getAlbum ( $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$artistName = $this->generateName( 'Artist', $this->rand( 1, 1000 ) );
			$albumName = $this->generateName( 'Album', $this->rand( self::MIN_ALBUM_COUNT, self::MAX_ALBUM_COUNT ) );
			switch ($this->indexType) {
				case self::TYPE_NORMALIZED:
					$this->getAlbumNormalized( $artistName, $albumName );
					break;
				case self::TYPE_DENORMALIZED:
					$this->getAlbumDeNormalized( $artistName, $albumName );
					break;
			}
		}
	}

	function getAlbumNormalized ( $artistName, $albumName ) {
		$resultSet = $this->getAlbumDeNormalized( $artistName, $albumName );
		if ( $resultSet->getNumFound() ) {
			$album = $resultSet->getDocuments()[0];
			$songs = json_decode( $album->songs, true );
			if ( $songs ) {
				$query = $this->newQueryFromSearch( [
					//'type: %P1%' => self::SONG_TYPE,
					'id: (%1%)' =>  implode( ' ', $songs ),
				] );
				$query->addSort( 'number', Solarium_Query_Select::SORT_ASC );
				return $this->client->select( $query );
			}
		}
		return null;
	}

	function getAlbumDeNormalized( $artistName, $albumName ) {
		$query = $this->newQueryFromSearch([
			'type: %1%' => self::ALBUM_TYPE,
			'artist_name: %P2%' => $artistName,
			'album_name: %P3%' => $albumName,
		]);
		$query->setStart(0)->setRows(1);
		return $this->client->select( $query );
	}

	function getSong ( $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$artistName = $this->generateName( 'Artist', $this->rand( 1, 1000 ) );
			$albumName = $this->generateName( 'Album', $this->rand( self::MIN_ALBUM_COUNT, self::MAX_ALBUM_COUNT ) );
			$songName = $this->generateName( 'Song', $this->rand( self::MIN_SONG_COUNT, self::MAX_SONG_COUNT ) );
			switch ($this->indexType) {
				case self::TYPE_NORMALIZED:
					$this->getSongNormalized( $artistName, $albumName, $songName );
					break;
				case self::TYPE_DENORMALIZED:
					$this->getSongDeNormalized( $artistName, $albumName, $songName );
					break;
			}
		}
	}

	function getSongDeNormalized( $artistName, $albumName, $songName ) {
		$query = $this->newQueryFromSearch([
			'type: %1%' => self::SONG_TYPE,
			'artist_name: %P2%' => $artistName,
			'album_name: %P2%' => $albumName,
			'song_name: %P2%' => $songName,

		]);
		$query->setStart(0)->setRows(1);
		return $this->client->select( $query );
	}

	function getSongNormalized( $artistName, $albumName, $songName ) {
		$resultSet = $this->getSongDeNormalized( $artistName, $albumName, $songName );
		if ( $resultSet->getNumFound() ) {
			$song = $resultSet->getDocuments()[0];
			// JUST select tow random ids here as we don't have the actual data
			$ids = json_decode( rand(1, 1000), rand(1, 1000) );
			$query = $this->newQueryFromSearch( [
				'id: (%1%)' =>  implode( ' ', $ids ),
			] );
			return $this->client->select( $query );
		}
		return null;
	}

	function searchLyrics( $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$word = $this->words[rand( 0, $this->wordsSize-1 )];
			$query = $this->newQueryFromSearch( [
				'type: %P1%' => self::SONG_TYPE,
				'lyrics: %1%' =>  $word,
			] );
			$this->client->select( $query );
		}
	}

	function searchArtist( $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$artistName = $this->generateName( 'Artist', $this->rand( 1, 1000 ) );
			$query = $this->newQueryFromSearch( [
				'type: %P1%' => self::ARTIST_TYPE,
				'search_artist_name: %1%' =>  $artistName,
			] );
			$this->client->select( $query );
		}
	}

	function searchSong( $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$artistName = $this->generateName( 'song', $this->rand( self::MIN_SONG_COUNT, self::MAX_SONG_COUNT ) );
			$query = $this->newQueryFromSearch( [
				'type: %P1%' => self::SONG_TYPE,
				'search_song_name: %1%' =>  $artistName,
			] );
			$this->client->select( $query );
		}
	}


	function test( $methodName, $count ) {
		$time_start = microtime(true);

		$this->{$methodName}( $count );

		$time_end = microtime(true);
		$time = $time_end - $time_start;
		self::log( sprintf("Benchmarking %s(%d)\ttype: %d\tresult: %f seconds", $methodName, $this->indexType,  $count, $time ) );
	}

	static function log( $text ) {
		echo $text . PHP_EOL;
	}
}


$typeSet = [
	'normalized' => 1,
	'denormalized' => 2,
];

if ( isset( $argv[1] ) && is_numeric( $argv[1] ) ) {
	$artistsCount = (int)$argv[1];
} else {
	die( 'Please provide number of queries to perform' . PHP_EOL );
}

if ( isset( $argv[2] ) && in_array($argv[2], array_keys( $typeSet ) ) ) {
	$indexType = $typeSet[$argv[2]] ;
} else {
	die( 'Please provide type of index to create: ' . implode( ', ', array_keys( $typeSet ) ) . PHP_EOL );
}

$bench = new Benchmark( $client, $indexType );
$bench->start( $artistsCount );