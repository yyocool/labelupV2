<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserLoginLogRepository;
use App\Repositories\UserOauthAccountRepository;
use App\Repositories\UserProfileRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class OAuthService
{
    public const PROVIDERS = ['naver', 'kakao', 'google'];

    private UserRepository $users;
    private UserProfileRepository $profiles;
    private UserOauthAccountRepository $oauth;
    private UserLoginLogRepository $loginLogs;

    public function __construct()
    {
        $this->users = new UserRepository();
        $this->profiles = new UserProfileRepository();
        $this->oauth = new UserOauthAccountRepository();
        $this->loginLogs = new UserLoginLogRepository();
    }

    public function isSupported(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true);
    }

    public function isConfigured(string $provider): bool
    {
        if (!$this->isSupported($provider)) {
            return false;
        }
        $cfg = $this->config($provider);
        return $cfg['client_id'] !== '' && ($provider === 'kakao' || $cfg['client_secret'] !== '');
    }

    /** @return array<string, bool> */
    public function configuredMap(): array
    {
        $map = [];
        foreach (self::PROVIDERS as $p) {
            $map[$p] = $this->isConfigured($p);
        }
        return $map;
    }

    public function authorizationUrl(string $provider, string $state): string
    {
        $cfg = $this->requireConfig($provider);
        $redirectUri = $this->callbackUrl($provider);

        return match ($provider) {
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $cfg['client_id'],
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'state' => $state,
                'access_type' => 'online',
                'prompt' => 'select_account',
            ], '', '&', PHP_QUERY_RFC3986),
            'naver' => 'https://nid.naver.com/oauth2.0/authorize?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $cfg['client_id'],
                'redirect_uri' => $redirectUri,
                'state' => $state,
            ], '', '&', PHP_QUERY_RFC3986),
            'kakao' => 'https://kauth.kakao.com/oauth/authorize?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $cfg['client_id'],
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scope' => 'profile_nickname,account_email',
            ], '', '&', PHP_QUERY_RFC3986),
            default => throw new RuntimeException('지원하지 않는 소셜 로그인입니다.'),
        };
    }

    /**
     * @return array{id:int,email:string,name:?string,role:string,status:string}
     */
    public function handleCallback(string $provider, string $code): array
    {
        $token = $this->exchangeCode($provider, $code);
        $profile = $this->fetchProfile($provider, (string) ($token['access_token'] ?? ''));

        $providerUserId = (string) ($profile['id'] ?? '');
        if ($providerUserId === '') {
            throw new RuntimeException('소셜 계정 정보를 확인하지 못했습니다.');
        }

        $email = $this->normalizeEmail((string) ($profile['email'] ?? ''), $provider, $providerUserId);
        $name = trim((string) ($profile['name'] ?? '')) ?: $this->providerLabel($provider) . ' 회원';
        $accessToken = (string) ($token['access_token'] ?? '');
        $refreshToken = isset($token['refresh_token']) ? (string) $token['refresh_token'] : null;
        $expiresAt = null;
        if (isset($token['expires_in']) && is_numeric($token['expires_in'])) {
            $expiresAt = date('Y-m-d H:i:s', time() + (int) $token['expires_in']);
        }

        $link = $this->oauth->findByProvider($provider, $providerUserId);
        if ($link) {
            $user = $this->users->findById((int) $link['user_id']);
            if (!$user || ($user['status'] ?? '') !== 'active') {
                throw new RuntimeException('연결된 계정을 사용할 수 없습니다. 고객센터로 문의해 주세요.');
            }
            $this->oauth->upsert(
                (int) $user['id'],
                $provider,
                $providerUserId,
                $email,
                $accessToken !== '' ? $accessToken : null,
                $refreshToken,
                $expiresAt
            );
            $this->users->updateLastLogin((int) $user['id']);
            $this->loginLogs->log((int) $user['id'], (string) $user['email'], true, 'oauth_' . $provider);
            return $user;
        }

        $existingByEmail = $this->users->findByEmail($email);
        if ($existingByEmail) {
            if (($existingByEmail['status'] ?? '') !== 'active') {
                throw new RuntimeException('해당 이메일 계정을 사용할 수 없습니다.');
            }
            $userId = (int) $existingByEmail['id'];
            $this->oauth->upsert($userId, $provider, $providerUserId, $email, $accessToken ?: null, $refreshToken, $expiresAt);
            $this->users->updateLastLogin($userId);
            $this->loginLogs->log($userId, $email, true, 'oauth_link_' . $provider);
            return $this->users->findById($userId) ?? $existingByEmail;
        }

        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $userId = $this->users->create($email, $hash);
        $this->profiles->create($userId, mb_substr($name, 0, 100));
        (new MemberGradeService())->assignDefault($userId);
        $this->oauth->upsert($userId, $provider, $providerUserId, $email, $accessToken ?: null, $refreshToken, $expiresAt);
        $this->users->updateLastLogin($userId);
        $this->loginLogs->log($userId, $email, true, 'oauth_register_' . $provider);

        $user = $this->users->findById($userId);
        if (!$user) {
            throw new RuntimeException('회원 생성에 실패했습니다.');
        }
        return $user;
    }

    public function callbackUrl(string $provider): string
    {
        return $this->absoluteUrl('auth/' . $provider . '/callback');
    }

    /** @return array{client_id:string,client_secret:string} */
    private function config(string $provider): array
    {
        return match ($provider) {
            'google' => [
                'client_id' => trim((string) env('GOOGLE_CLIENT_ID', '')),
                'client_secret' => trim((string) env('GOOGLE_CLIENT_SECRET', '')),
            ],
            'naver' => [
                'client_id' => trim((string) env('NAVER_CLIENT_ID', '')),
                'client_secret' => trim((string) env('NAVER_CLIENT_SECRET', '')),
            ],
            'kakao' => [
                'client_id' => trim((string) env('KAKAO_REST_API_KEY', env('KAKAO_CLIENT_ID', ''))),
                'client_secret' => trim((string) env('KAKAO_CLIENT_SECRET', '')),
            ],
            default => ['client_id' => '', 'client_secret' => ''],
        };
    }

    /** @return array{client_id:string,client_secret:string} */
    private function requireConfig(string $provider): array
    {
        if (!$this->isConfigured($provider)) {
            throw new RuntimeException($this->providerLabel($provider) . ' 로그인 키가 아직 설정되지 않았습니다.');
        }
        return $this->config($provider);
    }

    /** @return array<string, mixed> */
    private function exchangeCode(string $provider, string $code): array
    {
        $cfg = $this->requireConfig($provider);
        $redirectUri = $this->callbackUrl($provider);

        return match ($provider) {
            'google' => $this->httpJson('POST', 'https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $cfg['client_id'],
                'client_secret' => $cfg['client_secret'],
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]),
            'naver' => $this->httpJson('POST', 'https://nid.naver.com/oauth2.0/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $cfg['client_id'],
                'client_secret' => $cfg['client_secret'],
                'code' => $code,
                'state' => (string) ($_SESSION['oauth_state'] ?? ''),
            ]),
            'kakao' => $this->httpJson('POST', 'https://kauth.kakao.com/oauth/token', array_filter([
                'grant_type' => 'authorization_code',
                'client_id' => $cfg['client_id'],
                'redirect_uri' => $redirectUri,
                'code' => $code,
                'client_secret' => $cfg['client_secret'] !== '' ? $cfg['client_secret'] : null,
            ], static fn ($v) => $v !== null && $v !== '')),
            default => throw new RuntimeException('지원하지 않는 소셜 로그인입니다.'),
        };
    }

    /**
     * @return array{id:string,email:?string,name:?string}
     */
    private function fetchProfile(string $provider, string $accessToken): array
    {
        if ($accessToken === '') {
            throw new RuntimeException('액세스 토큰을 받지 못했습니다.');
        }

        if ($provider === 'google') {
            $data = $this->httpJson('GET', 'https://openidconnect.googleapis.com/v1/userinfo', [], [
                'Authorization: Bearer ' . $accessToken,
            ]);
            return [
                'id' => (string) ($data['sub'] ?? ''),
                'email' => isset($data['email']) ? (string) $data['email'] : null,
                'name' => (string) ($data['name'] ?? $data['given_name'] ?? ''),
            ];
        }

        if ($provider === 'naver') {
            $data = $this->httpJson('GET', 'https://openapi.naver.com/v1/nid/me', [], [
                'Authorization: Bearer ' . $accessToken,
            ]);
            $resp = is_array($data['response'] ?? null) ? $data['response'] : [];
            return [
                'id' => (string) ($resp['id'] ?? ''),
                'email' => isset($resp['email']) ? (string) $resp['email'] : null,
                'name' => (string) ($resp['name'] ?? $resp['nickname'] ?? ''),
            ];
        }

        // kakao
        $data = $this->httpJson('GET', 'https://kapi.kakao.com/v2/user/me', [], [
            'Authorization: Bearer ' . $accessToken,
        ]);
        $kakaoAccount = is_array($data['kakao_account'] ?? null) ? $data['kakao_account'] : [];
        $profile = is_array($kakaoAccount['profile'] ?? null) ? $kakaoAccount['profile'] : [];
        return [
            'id' => (string) ($data['id'] ?? ''),
            'email' => isset($kakaoAccount['email']) ? (string) $kakaoAccount['email'] : null,
            'name' => (string) ($profile['nickname'] ?? ''),
        ];
    }

    private function normalizeEmail(?string $email, string $provider, string $providerUserId): string
    {
        $email = strtolower(trim((string) $email));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
        return sprintf('%s_%s@oauth.labelup.local', $provider, preg_replace('/[^a-zA-Z0-9_-]/', '', $providerUserId));
    }

    public function providerLabel(string $provider): string
    {
        return match ($provider) {
            'naver' => '네이버',
            'kakao' => '카카오',
            'google' => '구글',
            default => $provider,
        };
    }

    private function absoluteUrl(string $path): string
    {
        $base = rtrim((string) env('APP_URL', ''), '/');
        if ($base === '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            $scheme = $https ? 'https' : 'http';
            $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $base = $scheme . '://' . $host;
        }
        return $base . '/' . ltrim($path, '/');
    }

    /**
     * @param array<string, scalar|null> $form
     * @param array<int, string> $headers
     * @return array<string, mixed>
     */
    private function httpJson(string $method, string $url, array $form = [], array $headers = []): array
    {
        $body = $form !== [] ? http_build_query($form) : null;
        $method = strtoupper($method);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $reqHeaders = array_merge(['Accept: application/json'], $headers);
            if ($body !== null) {
                $reqHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
            }
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => $reqHeaders,
            ]);
            if ($method !== 'GET' && $body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($raw === false) {
                throw new RuntimeException('소셜 로그인 서버 연결 실패: ' . ($error ?: 'unknown'));
            }
        } else {
            $hdr = "Accept: application/json\r\n";
            foreach ($headers as $h) {
                $hdr .= $h . "\r\n";
            }
            $opts = [
                'http' => [
                    'method' => $method,
                    'header' => $hdr,
                    'timeout' => 30,
                    'ignore_errors' => true,
                ],
            ];
            if ($body !== null && $method !== 'GET') {
                $opts['http']['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
                $opts['http']['content'] = $body;
            }
            $raw = file_get_contents($url, false, stream_context_create($opts));
            $status = 0;
            if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
                $status = (int) $m[1];
            }
            if ($raw === false) {
                throw new RuntimeException('소셜 로그인 서버 연결에 실패했습니다.');
            }
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('소셜 로그인 응답을 해석할 수 없습니다.');
        }
        if ($status >= 400 || isset($decoded['error']) || (($decoded['errorCode'] ?? '') !== '' && ($decoded['errorCode'] ?? '00') !== '00')) {
            $msg = (string) ($decoded['error_description'] ?? $decoded['error_msg'] ?? $decoded['msg'] ?? $decoded['error'] ?? '소셜 로그인에 실패했습니다.');
            throw new RuntimeException($msg);
        }

        return $decoded;
    }
}
