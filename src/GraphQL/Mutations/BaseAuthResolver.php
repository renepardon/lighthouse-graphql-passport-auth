<?php

namespace Renepardon\LighthouseGraphQLPassport\GraphQL\Mutations;

use GuzzleHttp\Client;
use Nuwave\Lighthouse\Exceptions\AuthenticationException;

/**
 * Class BaseAuthResolver.
 */
class BaseAuthResolver
{
    /**
     * @param array  $args
     * @param string $grantType
     *
     * @return mixed
     */
    public function buildCredentials(array $args = [], $grantType = 'password')
    {
        $args = collect($args);
        $credentials = $args->except('directive')->toArray();
        $credentials['client_id'] = $args->get('client_id', config('lighthouse-graphql-passport.client_id'));
        $credentials['client_secret'] = $args->get('client_secret', config('lighthouse-graphql-passport.client_secret'));
        $credentials['grant_type'] = $grantType;

        return $credentials;
    }

    /**
     * @param array $credentials
     *
     * @throws AuthenticationException
     *
     * @return mixed
     */
    public function makeRequest(array $credentials)
    {
        $client = new Client(['base_uri' => config('app.url')]);
        $response = $client->post('/oauth/token', [
                'form_params' => $credentials,
                'headers'     => ['Accept' => 'application/json',],
            ]
        );

        $decodedResponse = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() != 200) {
            throw new AuthenticationException($decodedResponse['message']);
        }

        return $decodedResponse;
    }
}
