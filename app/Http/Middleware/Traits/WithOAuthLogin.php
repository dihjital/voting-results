<?php

namespace App\Http\Middleware\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

use Carbon\Carbon;

trait WithOAuthLogin
{
    protected $api_user;
    protected $api_secret;

    protected $client_id;
    protected $client_secret;

    public function initializeWithOAuthLogin()
    {
        $this->api_user = env('API_USER');
        $this->api_secret = env('API_SECRET');

        $this->client_id = env('PASSPORT_CLIENT_ID');
        $this->client_secret = env('PASSPORT_CLIENT_SECRET');

        // TODO: For some reason the session variables are not set when a new token is requested from the back-end
        // This is only a work-around until we find the right solution
        $this->deleteTokensFromSession();
    }

    protected function checkHalfTime($issued_at, $expires_in): bool
    {
        if (empty($issued_at) || empty($expires_in))
            return true;

        $expires_at = $issued_at->copy()->addSeconds($expires_in);
        $half_time = $issued_at->copy()->average($expires_at);

        return Carbon::now() > $half_time;
    }

    protected function getTokensFromSession(): array
    {
        return [
            'access_token' => Session::get('access_token'),
            'refresh_token' => Session::get('refresh_token'),
        ];
    }

    protected function storeTokensInSession($access_token, $refresh_token): void
    {
        $this->deleteTokensFromSession();

        Session::put('access_token', $access_token);
        Session::put('refresh_token', $refresh_token);
    }

    protected function deleteTokensFromSession(): void
    {
        Session::forget([
            'access_token',
            'refresh_token'
        ]);
    }

    protected function getTokensFromCache(): array
    {
        if (Cache::has('access_token') && 
            Cache::has('refresh_token') &&
            Cache::has('issued_at') &&
            Cache::has('expires_in')) {
                return [
                    'access_token' => Cache::get('access_token'),
                    'refresh_token' => Cache::get('refresh_token'),
                    'issued_at' => Cache::get('issued_at'),
                    'expires_in' => Cache::get('expires_in'),
                ];
        }

        return [];
    }

    protected function storeTokensInCache($access_token, $refresh_token, $expires_in): void
    {
        $this->deleteTokensFromSession();
        $this->deleteTokensFromCache();

        Cache::put('access_token', $access_token);
        Cache::put('refresh_token', $refresh_token);
        Cache::put('issued_at', Carbon::now());
        Cache::put('expires_in', $expires_in);
    }

    protected function deleteTokensFromCache(): void
    {
        Cache::forget('access_token');
        Cache::forget('refresh_token');
        Cache::forget('issued_at');
        Cache::forget('expires_in');
    }

    protected function refreshToken($refresh_token): array
    {
        $response = Http::asForm()->post(env('PASSPORT_LOGIN_ENDPOINT'), [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => 'list-quizzes list-questions list-votes',
        ]);

        if ($response->ok()) {
            return [
                'access_token' => $response['access_token'], 
                'refresh_token' => $response['refresh_token'], 
                'expires_in' => $response['expires_in'],
            ];
        }

        $this->deleteTokensFromCache();

        // TODO: Log here that refresh-token has failed. Use Log::error
        return [];
    }

    protected static function numberOfNonEmptyElements($a): int
    {
        return count(array_filter($a, fn($item) => !empty($item)));
    }

    public function login(): array 
    {
        $tokens = $this->getTokensFromSession();

        if (self::numberOfNonEmptyElements($tokens) !== 2) {
            $tokens = $this->getTokensFromCache();
            if (self::numberOfNonEmptyElements($tokens) === 4) {
                list('access_token' => $access_token, 
                    'refresh_token' => $refresh_token, 
                    'issued_at' => $issued_at, 
                    'expires_in' => $expires_in) = $tokens;
                // We are over half time so refresh tokens ...
                if ($this->checkHalfTime($issued_at, $expires_in)) {
                    $tokens = $this->refreshToken($refresh_token);
                    if (self::numberOfNonEmptyElements($tokens) === 3) {
                        list('access_token' => $access_token, 
                            'refresh_token' => $refresh_token, 
                            'expires_in' => $expires_in) = $tokens;
                        $this->storeTokensInSession($access_token, $refresh_token);
                        $this->storeTokensInCache($access_token, $refresh_token, $expires_in);
                        Log::debug('Returning tokens after re-freshing them from the back-end');
                        return [$access_token, $refresh_token];
                    }
                } else {
                    Log::debug('Returning tokens from cache for further validation');
                }
            }
        }

        return $this->areTokensValid($tokens)
            ? $tokens
            : $this->getNewTokensFromApi();
    }

    protected function areTokensValid(array $tokens): bool
    {
        if (!array_key_exists('access_token', $tokens)) return false;

        $response = Http::withToken($tokens['access_token'])
            ->get(self::getURL().'/validate');

        if (!$response->ok()) {
            Log::error($response->body());
            throw new \Exception($response->body(), $response->status());
        }

        if ($response['valid'] === true) {
            $this->storeTokensInSession(...$tokens);
            $this->storeTokensInCache(...$tokens);
            return true;
        }

        Log::debug('Access token is no longer valid.');

        $this->deleteTokensFromCache();
        $this->deleteTokensFromSession();

        return false;
    }

    protected function getNewTokensFromApi(): array
    {
        // Tokens are not stored in session neither in cache so we have to log in ...
        $response = Http::asForm()->post(self::getURL().'/login', [
            'email'     => $this->api_user,
            'password'  => $this->api_secret,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => 'list-quizzes list-questions list-votes',
        ]);

        if (!$response->ok()) {
            throw new \Exception($response->status().': '.$response->body());
        }

        $access_token = $response['access_token'];
        $refresh_token = $response['refresh_token'];
        $expires_in = $response['expires_in'];

        $this->storeTokensInSession($access_token, $refresh_token);
        $this->storeTokensInCache($access_token, $refresh_token, $expires_in);

        Log::debug('Returning tokens from the back-end');
        return [$access_token, $refresh_token];
    }
}