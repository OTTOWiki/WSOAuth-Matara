# Matara-Login-v2
摩多罗登录器是基于PluggableAuth和WSOAuth的扩展，支持自定义OAuth2提供商登录

## 安装
1. 下载并安装 [PluggableAuth](https://www.mediawiki.org/wiki/Extension:PluggableAuth) 扩展。
2. 下载并安装本扩展。
3. 在 `LocalSettings.php` 中添加以下配置：
```php
wfLoadExtension( 'PluggableAuth' );
wfLoadExtension( 'WSOAuth-Matara' );
```
4. 运行 `php maintenance/run.php update` 来更新数据库。
5. 运行 `composer install` 来安装依赖。
6. 根据您的需要进行配置。

## 配置
本扩展未删除原版WSOAuth相关内容，您可以参考[原版WSOAuth的配置文档](https://www.mediawiki.org/wiki/Extension:WSOAuth)来配置MediaWiki的OAuth登录和Facebook登录。

### 自定义提供商
在您的 `LocalSettings.php` 中以如下格式添加自定义提供商的配置：

```php
$wgPluggableAuth_Config['使用XXX登录'] = [
    'plugin' => 'WSOAuth',
    'data' => [
        'type' => 'custom',
        //上面的type必须为custom
        'clientId' => '',
        'clientSecret' => '',
        'redirectUri' => 'https://your.wiki/index.php?title=Special:PluggableAuthLogin',
        'extensionData' => [
            'urlAuthorize' => '您的OAuth2提供商的授权URL',
            'urlAccessToken' => '您的OAuth2提供商的访问令牌URL',
            'urlResourceOwnerDetails' => '您的OAuth2提供商的用户信息URL',
        ],
    ],
];
```
### 配置scope
由于神秘问题，我们无法从LocalSettings中获取scope，因此您需要在CustomAuth.php中手动添加scope。
在约53行，找到如下内容：

```php
		$authUrl = $this->provider->getAuthorizationUrl( [
			'scope' => [ 'email' ]
		] );

```

将其修改为您需要的scope，例如谷歌的scope为：

```php
        $authUrl = $this->provider->getAuthorizationUrl( [
            'scope' => [ 'email profile' ]
        ] );

```

### 修改字段映射
由于WSOAuth的限制，您可能需要在CustomAuth.php中手动修改以下内容：

1. 在约第58行找到如下内容

```php
return [
                'name' => $data['用户id']  ?? null,
                'realname' => $data['用户名字（可以与id相同）'] ?? null,
                'email' => $data['用户邮箱'] ?? null
            ];
```

2. 根据您的OAuth2提供商的返回数据结构，调整字段映射。
如Google格式：

```php
                'name' => $data['name']  ?? null,
                'realname' => $data['given_name'] ?? null,
                'email' => $data['email'] ?? null
```

## 注意

在某些站点上，您可能会遇到以下错误：

```php
PHP Fatal error: Declaration of MWCallbackStream::write($string) must be compatible with Psr\Http\Message\StreamInterface::write(string $string): int in /xxxwiki/includes/http/MWCallbackStream.php on line 50
```

这是由于MediaWiki实现的 write 方法签名与 PSR-7 的 StreamInterface 不一致。接口定义为 write(string $string): int，而当前实现缺少类型提示/返回类型，导致 PHP 抛出致命错误。修复方法是把方法签名改成与接口完全一致，并返回 int。

修复示例：
把include/http/MWCallbackStream.php中的write方法改成：

```php
    public function write(string $string): int {
        $result = ($this->callback)($this, $string);
        return (int)$result;
    }
```

## WSOAuth原版简介

The **WSOAuth** extension enables you to delegate authentication to an OAuth provider. It provides a layer on top of PluggableAuth to allow authentication via a number of OAuth providers.

This extension requires PluggableAuth to be installed first. It also requires some PHP libraries, which may be installed using Composer.

Additional information about the extension and how to use it can be found on it's [MediaWiki page](https://www.mediawiki.org/wiki/Extension:WSOAuth).
