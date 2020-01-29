<?php

namespace Renepardon\LighthouseGraphQLPassport\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Hash;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Register extends BaseAuthResolver
{
    /**
     * @param                                                          $rootValue
     * @param array                                                    $args
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext|null $context
     * @param \GraphQL\Type\Definition\ResolveInfo                     $resolveInfo
     *
     * @throws \Exception
     *
     * @return array
     */
    public function resolve($rootValue, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo = null)
    {
        $model = app(config('auth.providers.users.model'));
        $input = collect($args)->except('password_confirmation')->toArray();
        $input['password'] = Hash::make($input['password']);
        $model->fill($input);
        $model->save();

        if ($model instanceof MustVerifyEmail) {
            $model->sendEmailVerificationNotification();

            return [
                'tokens' => [],
                'status' => 'MUST_VERIFY_EMAIL',
            ];
        }

        $credentials = $this->buildCredentials([
            'username' => $args[config('lighthouse-graphql-passport.username')],
            'password' => $args['password'],
        ]);
        $user = $model->where(config('lighthouse-graphql-passport.username'), $args[config('lighthouse-graphql-passport.username')])->first();
        $response = $this->makeRequest($credentials);
        $response['user'] = $user;

        event(new Registered($user));

        return [
            'tokens' => $response,
            'status' => 'SUCCESS',
        ];
    }
}
