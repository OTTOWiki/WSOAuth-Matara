<?php
namespace WSOAuth\AuthenticationProvider;

use League\OAuth2\Client\Provider\GenericProvider;
use MediaWiki\User\UserIdentity;

class CustomAuth extends AuthProvider {
    /**
     *  @var GenericProvider
     */
    private $provider;

    public function __construct(
        string $clientId,
        string $clientSecret,
        ?string $authUri,
        ?string $redirectUri,
        array $extensionData = []
    ) {
        $this->provider = new GenericProvider([
            'clientId'                => $clientId,
            'clientSecret'            => $clientSecret,
            'redirectUri'             => $redirectUri,
            'urlAuthorize'            => $extensionData['urlAuthorize'] ?? '',
            'urlAccessToken'          => $extensionData['urlAccessToken'] ?? '',
            'urlResourceOwnerDetails' => $extensionData['urlResourceOwnerDetails'] ?? '',
        ]);
    }


    public function login( ?string &$key, ?string &$secret, ?string &$authUrl ): bool {
        $authUrl = $this->provider->getAuthorizationUrl([
            'scope' => [ 'email profile' ],
        ]);
        $secret = $this->provider->getState();
        return true;
    }

    public function logout( UserIdentity &$user ): void {
        //可调用提供商的登出端点或清理本地 session
    }

    public function getUser( string $key, string $secret, &$errorMessage ) {
        if ( !isset($_GET['code']) ) {
            return false;
        }
        if ( !isset($_GET['state']) || $_GET['state'] !== $secret ) {
            $errorMessage = 'Invalid state';
            return false;
        }
        try {
            $token = $this->provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
            $user = $this->provider->getResourceOwner($token);
            $data = $user->toArray();
            //下面这行日志用于查看提供商返回的用户数据结构，如果需要的话可以启用它，请一定记得在生产环境中禁用
            //error_log( 'CustomAuth user data: ' . var_export( $data, true ) );
            //根据提供商返回的数据结构调整字段映射
            return [
                'name' => $data['name']  ?? null,
                'realname' => $data['given_name'] ?? null,
                'email' => $data['email'] ?? null
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function saveExtraAttributes( int $id ): void {
    }
}