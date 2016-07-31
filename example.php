<?php

use SpotifyWebAPI\SpotifyWebAPI;

use SpotifyWebApiExtensions\GuzzleClientFactory;
use SpotifyWebApiExtensions\GuzzleRequestAdapter;

use Doctrine\Common\Cache\FilesystemCache;

require_once('./vendor/autoload.php');

$guzzleAdapter = new GuzzleRequestAdapter(
	GuzzleClientFactory::create(
		new FilesystemCache(__DIR__ . '/cache')
	)
);

$api = new SpotifyWebAPI($guzzleAdapter);
print_r($api->search('Nothing else matters', ['track'], ['market' => 'DE']));
