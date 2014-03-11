<?php
error_reporting(E_ALL);

require 'vendor/autoload.php';
require 'BaseClass.php';

$config = [
	'adapteroptions' => [
		'host' => 'localhost',
		'port' => 8983,
		'path' => '/solr/',
		'core' => 'lyrics',
	]
];

$client = new Solarium_Client( $config );