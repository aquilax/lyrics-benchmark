<?php

require 'vendor/autoload.php';

$config = [
	'adapteroptions' => [
		'host' => '10.10.10.242',
		'port' => 8983,
		'path' => '/solr/',
		'core' => 'lyrics',
	]
];

$client = new Solarium_Client( $config );