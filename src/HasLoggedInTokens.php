<?php

namespace Renepardon\LighthouseGraphQLPassport;

use GuzzleHttp\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

trait HasLoggedInTokens
{
    /**
     * @return mixed
     */
    public function getTokens()
    {
        $data = [
            'grant_type'    => 'logged_in_grant',
            'client_id'     => config('lighthouse-graphql-passport.client_id'),
            'client_secret' => config('lighthouse-graphql-passport.client_secret'),
        ];

        $client = new Client(['base_uri' => config('app.url')]);
        $response = $client->post('/oauth/token', [
                'form_params' => $data,
                'headers'     => ['Accept' => 'application/json',],
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $request
     *
     * @return Authenticatable|null
     */
    public function byLoggedInUser($request): ?Authenticatable
    {
        return Auth::user();
    }
}
