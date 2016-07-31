# spotify-web-api-extensions
Guzzle Adapter for use with SpotifyWebApi (https://github.com/jwilsson/spotify-web-api-php)

## Installation

To install run:

    composer require andig/spotify-web-api-extensions:dev-master

## Usage

See `example.php` for how to use Guzzle as HTTP client for SpotifyWebApi:

	$guzzleAdapter = new GuzzleRequestAdapter(
		GuzzleClientFactory::create(
			new FilesystemCache(__DIR__ . '/cache')
		)
	);

	$api = new SpotifyWebAPI($guzzleAdapter);
	print_r($api->search('Nothing else matters', ['track'], ['market' => 'DE']));
