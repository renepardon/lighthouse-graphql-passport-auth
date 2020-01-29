Lighthouse GraphQL Passport Auth (Laravel ^6.2 / Lighthouse ^4.8)
===============================================

GraphQL mutations for Laravel Passport using Lighthouse version ^4.8

## Installation

**Make sure you have [Laravel Passport](https://laravel.com/docs/6.x/passport) installed.**

To install run `composer require renepardon/lighthouse-graphql-passport-auth`.

ServiceProvider will be attached automatically

Run this command to publish the migration, schema and configuration file

```
php artisan vendor:publish --provider="Renepardon\LighthouseGraphQLPassport\Providers\LighthouseGraphQLPassportServiceProvider"
```

Add the following env vars to your .env

```
PASSPORT_CLIENT_ID=
PASSPORT_CLIENT_SECRET=
```

You are done with the installation!


## Configuration

In the configuration file you can now set the schema file to be used for the exported one like this:

```php
    /*
    |--------------------------------------------------------------------------
    | GraphQL schema
    |--------------------------------------------------------------------------
    |
    | File path of the GraphQL schema to be used, defaults to null so it uses
    | the default location
    |
    */
    'schema' => base_path('graphql/auth.graphql')
```

This will allow you to change the schema and resolvers if needed.

## Usage

This will add 8 mutations to your GraphQL API

```graphql
extend type Mutation {
    login(input: LoginInput): AuthPayload!
    refreshToken(input: RefreshTokenInput): RefreshTokenPayload!
    logout: LogoutResponse!
    forgotPassword(input: ForgotPasswordInput!): ForgotPasswordResponse!
    updateForgottenPassword(input: NewPasswordWithCodeInput): ForgotPasswordResponse!
    register(input: RegisterInput @spread): AuthPayload!
    socialLogin(input: SocialLoginInput! @spread): AuthPayload!
    verifyEmail(input: VerifyEmailInput! @spread): AuthPayload!
}
```

- **login:** Will allow your clients to log in by using the password grant client.
- **refreshToken:** Will allow your clients to refresh a passport token by using the password grant client.
- **logout:** Will allow your clients to invalidate a passport token.
- **forgotPassword:** Will allow your clients to request the forgot password email.
- **updateForgottenPassword:** Will allow your clients to update the forgotten password from the email received.
- **register:** Will allow your clients to register a new user using the default Laravel registration fields
- **socialLogin:** Will allow your clients to log in using access token from social providers using socialite
- **verifyEmail:** Will allow your clients to verify the email after they receive a token in the email

### Using the email verification

If you want to use the email verification feature that comes with laravel, please follow the instruction in the laravel documentation to configure the model in [https://laravel.com/docs/6.x/verification](https://laravel.com/docs/6.x/verification), once that is done add the following traits

```php
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Renepardon\LighthouseGraphQLPassport\HasLoggedInTokens;
use Renepardon\LighthouseGraphQLPassport\MustVerifyEmailGraphQL;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;
    use HasApiTokens;
    use HasSocialLogin;
    use MustVerifyEmailGraphQL;
    use HasLoggedInTokens;
}
```
This will add some methods for the email notification to be sent with a token. Use the token in the following mutation.

```graphql
{
  mutation {
      verifyEmail(input: {
          "token": "HERE_THE_TOKEN"
      }) {
          access_token
          refresh_token
          user {
              id
              name
              email
          }
      }
  }
}
```

If the token is valid the tokens will be issued.

### Using socialite for social login

If you want to use the mutation for social login, please add the `Renepardon\LighthouseGraphQLPassport\HasSocialLogin` 
trait to your user model like this

```php
use Renepardon\LighthouseGraphQLPassport\HasSocialLogin;

class User extends Authenticatable
{
    use Notifiable;
    use HasApiTokens;
    use HasSocialLogin;
}
```

This will add a method that is used by the mutation to get the user from the social network and create or get it from 
the DB based on the `provider` and `provider_id`

```php
    /**
     * @param Request $request
     * @return mixed
     */
    public static function byOAuthToken(Request $request)
    {
        $userData = Socialite::driver($request->get('provider'))->userFromToken($request->get('token'));
        try {
            $user = static::where('provider', Str::lower($request->get('provider')))->where('provider_id', $userData->getId())->firstOrFail();
        } catch (ModelNotFoundException $e) {
            $user = static::create([
                'name' => $userData->getName(),
                'email' => $userData->getEmail(),
                'provider' => $request->get('provider'),
                'provider_id' => $userData->getId(),
                'password' => Hash::make(Str::random(16)),
                'avatar' => $userData->getAvatar()
            ]);
        }
        Auth::onceUsingId($user->id);
        return $user;
    }
``` 

You can override the method and add more fields if you need to.

*Make sure Socialite is configured properly to use the social network, please see [Laravel Socialite](https://laravel.com/docs/6.x/socialite)* 

### Why the OAuth client is used in the backend and not from the client application?

When an application that needs to be re compiled and re deploy to stores like an iOS app needs to change the client for 
whatever reason, it becomes a blocker for QA or even brakes the production app if the client is removed. The app will 
not work until the new version with the updated keys is deployed. There are alternatives to store this configuration 
in the client but for this use case we are relying on the backend to be the OAuth client

## Tests

To run the test in this package, navigate to the root folder of the project and run

```bash
    composer install
```
Then

```bash
    ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email rene dot pardon at boonweb dot de instead of using the issue 
tracker.

## Credits

- [Jose Luis Fonseca](https://github.com/joselfonseca)
- [Christoph, René Pardon](https://github.com/renepardon)

And all developers within the commit history.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
