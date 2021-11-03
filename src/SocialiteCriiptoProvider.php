<?php

namespace UptimeDevelopment\SocialiteCriipto;

use Laravel\Socialite\Two\User;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use CoderCat\JWKToPEM\JWKConverter;
use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;

class SocialiteCriiptoProvider extends AbstractProvider
{
     /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'criipto';

    /**
     * @var string[]
     */
    protected $scopes = [
        'openid'        
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            $this->getOpenIdConfiguration()->authorization_endpoint,
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->getOpenIdConfiguration()->token_endpoint;
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $response = $this->getAccessTokenResponse($this->getCode());
        $this->credentialsResponseBody = $response;

        $user = $this->mapUserToObject($this->getUserByToken(
            $token = $this->parseIdToken($response)
        ));      

        session(['socialite_' . self::IDENTIFIER . '_idtoken' => $token]);

        return $user->setToken($token);
    }

    /**
     * Get the id token from the token response body.
     *
     * @param string $body
     *
     * @return string
     */
    protected function parseIdToken($body)
    {
        return Arr::get($body, 'id_token');
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => [
                'Accept' => 'application/x-www-form-urlencoded', 
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
            ],
            'form_params' => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the raw user for the given id token.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        // Reading public keys from criipto for validating the JWT token
        $keys = $this->getJWTKeys();

        return (array) JWT::decode($token, $keys, $this->getOpenIdConfiguration()->id_token_signing_alg_values_supported);
    }

    /**
     * Get the current JWT signing keys in an openssl supported format
     *
     * @return array
     */
    private function getJWTKeys() {
        return Cache::remember('socialite_' . self::IDENTIFIER . '_jwtpublickeys', config('services.criipto.cache_time'), function () {           
            $response = $this->getHttpClient()->get($this->getOpenIdConfiguration()->jwks_uri);
            $jwks = json_decode($response->getBody(), true);
            $public_keys = array();
            foreach ($jwks['keys'] as $jwk) {       
                $jwkConverter = new JWKConverter();         
                $public_keys[$jwk['kid']] = $jwkConverter->toPEM($jwk);
            }
            return $public_keys;
        });
    }

    /**
     * Get the OpenID configuration from criipto     
     */
    private function getOpenIdConfiguration() 
    {
        return Cache::remember('socialite_' . self::IDENTIFIER . '_openidconfiguration', config('services.criipto.cache_time'), function () {         
            try {
                $response = $this->getHttpClient()->get(config('services.criipto.base_uri') . '/.well-known/openid-configuration', ['http_errors' => true]);
            } catch(ClientException $e) {
                throw new Exception("Unable to read the OpenID configuration. Make sure the base_uri is set correctly");
            }


            return json_decode($response->getBody());
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        //We only return the common fields. All other fields can be found in 'user'
        return (new User())->setRaw($user)->map([
            'id' => $user['sub']  
        ]);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string  $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        $fields = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,            
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
        ];        

        return $fields;
    }    

    /**
     * Add additional required config items
     *
     * @return array
     */
    public static function additionalConfigKeys()
    {
        return ['base_uri'];
    }

    /**
     * Tell Criipto the user has singed out
     *
     * @param [type] $guard
     * @param [type] $user
     * @return void
     */
    public function logOut($guard, $user) {
        $idToken = session('socialite_' . self::IDENTIFIER . '_idtoken');
        if (!empty($idToken)) {
            abort(redirect($this->getOpenIdConfiguration()->end_session_endpoint . "?id_token_hint=" . $idToken . "&post_logout_redirect_uri=" . urlencode($this->config['redirect_logout'] ?: request()->fullUrl())));
        }
    }
}