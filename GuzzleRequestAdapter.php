<?php

namespace SpotifyWebApiExtensions;

use SpotifyWebAPI\Request;
use SpotifyWebAPI\SpotifyWebAPIException;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GuzzleRequestAdapter extends Request
{
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Make a request to Spotify.
     * You'll probably want to use one of the convenience methods instead.
     *
     * @param string $method The HTTP method to use.
     * @param string $url The URL to request.
     * @param array $parameters Optional. Query parameters.
     * @param array $headers Optional. HTTP headers.
     *
     * @throws SpotifyWebAPIException
     *
     * @return array Response data.
     * - array|object body The response body. Type is controlled by Request::setReturnAssoc().
     * - array headers Response headers.
     * - int status HTTP status code.
     * - string url The requested URL.
     */
    public function send($method, $url, $parameters = array(), $headers = array())
    {
        $url = rtrim($url, '/');
        $method = strtoupper($method);

        $query = null;
        $formParams = null;
        $body = null;

        switch ($method) {
            case 'DELETE': // No break
            case 'PUT':
            case 'POST':
                if (is_array($parameters) || is_object($parameters)) {
                    $formParams = $parameters;
                }
                else {
                    $body = $parameters;
                }
                break;

            default:
                $query = $parameters;
                break;
        }

        try {
            $options = [
                'query' => $query,
                'form_params' => $formParams,
                'body' => $body,
                'headers' => $headers
            ];

            $response = $this->client->request($method, $url, $options);
        }
        catch (GuzzleException $e) {
            throw new SpotifyWebAPIException('Guzzle transport error: ' . $e->getMessage());
        }

        $headers = $response->getHeaders();
        $rawBody = (string) $response->getBody();

        $status = $response->getStatusCode();
        $body = json_decode($rawBody, $this->getReturnAssoc());

        if ($status < 200 || $status > 299) {
            $errorBody = json_decode($rawBody);
            $error = (isset($errorBody->error)) ? $errorBody->error : null;

            if (isset($error->message) && isset($error->status)) {
                // API call error
                throw new SpotifyWebAPIException($error->message, $error->status);
            } elseif (isset($errorBody->error_description)) {
                // Auth call error
                throw new SpotifyWebAPIException($errorBody->error_description, $status);
            } else {
                // Something went really wrong
                throw new SpotifyWebAPIException('An unknown error occurred.', $status);
            }
        }

        return array(
            'body' => $body,
            'headers' => $headers,
            'status' => $status,
            'url' => $url,
        );
    }
}
