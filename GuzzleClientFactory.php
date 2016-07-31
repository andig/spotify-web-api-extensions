<?php

namespace SpotifyWebApiExtensions;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;

use Doctrine\Common\Cache\Cache;


class GuzzleClientFactory
{
	public static function create(Cache $cacheProvider = null, $clientOptions = []) {
		$handlerStack = HandlerStack::create();

		// add cache if provided
		if ($cacheProvider) {
			$cacheHandler = new CacheMiddleware(
				new PrivateCacheStrategy(
					new DoctrineCacheStorage($cacheProvider)
				)
			);

			$handlerStack->push($cacheHandler);
		}

		// add retry for connection errors as well as HTTP 429
		$handlerStack->push(Middleware::retry(__CLASS__.'::retryDecider', __CLASS__.'::retryDelay'));

		$options = array_merge([
			'handler' => $handlerStack,
			'timeout'  => 10
		], $clientOptions);

		$client = new Client($options);

		return $client;
	}

	static function retryDecider(
		  $retries,
		  Request $request,
		  Response $response = null,
		  RequestException $exception = null
	) {
		// Limit the number of retries to 5
		if ($retries >= 5) {
			return false;
		}

		// Retry connection exceptions
		if ($exception instanceof ConnectException) {
			return true;
		}

		if ($response) {
			// Retry on server errors
			if ($response->getStatusCode() >= 500) {
				return true;
			}

			// Retry on rate limits
			if ($response->getStatusCode() == 429) {
				$retryDelay = $response->getHeaderLine('Retry-After');

				if (strlen($retryDelay)) {
					printf(" retry delay: %d secs\n", (int)$retryDelay);
					sleep((int)$retryDelay);
					return true;
				}
			}
		}

		return false;
	}

	static function retryDelay($numberOfRetries) {
		return 1000 * $numberOfRetries;
	}
}