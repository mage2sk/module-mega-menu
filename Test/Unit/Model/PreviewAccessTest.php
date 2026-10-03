<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Model;

use Magento\Framework\App\DeploymentConfig;
use Panth\MegaMenu\Model\PreviewAccess;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PreviewAccessTest extends TestCase
{
    private function access(?string $key): PreviewAccess
    {
        $config = $this->createStub(DeploymentConfig::class);
        $config->method('get')->willReturn($key);

        return new PreviewAccess($config);
    }

    private function sign(string $payload, string $key): string
    {
        return hash_hmac('sha256', 'panth_megamenu_preview|' . $payload, $key);
    }

    public function testCreatedTokenValidates(): void
    {
        $access = $this->access('secret-key');
        $token = $access->createToken();

        $this->assertMatchesRegularExpression('/^\d+\.[0-9a-f]{16}\.[0-9a-f]{64}$/', $token);
        $this->assertTrue($access->isValidToken($token));
    }

    public function testTokensAreUnique(): void
    {
        $access = $this->access('secret-key');

        $this->assertNotSame($access->createToken(), $access->createToken());
    }

    public function testTokenFromDifferentKeyIsRejected(): void
    {
        $token = $this->access('key-one')->createToken();

        $this->assertFalse($this->access('key-two')->isValidToken($token));
    }

    public function testNoKeyProducesEmptyTokenAndRejects(): void
    {
        $this->assertSame('', $this->access('   ')->createToken());
        $valid = $this->access('k')->createToken();
        $this->assertFalse($this->access(null)->isValidToken($valid));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $payload = (time() - 10) . '.abcdef0123456789';

        $this->assertFalse($this->access('k')->isValidToken($payload . '.' . $this->sign($payload, 'k')));
    }

    public function testTokenTooFarInFutureIsRejected(): void
    {
        $payload = (time() + PreviewAccess::TOKEN_LIFETIME + 100) . '.abcdef0123456789';

        $this->assertFalse($this->access('k')->isValidToken($payload . '.' . $this->sign($payload, 'k')));
    }

    public function testHandBuiltValidTokenAccepted(): void
    {
        $payload = (time() + 60) . '.abcdef0123456789';

        $this->assertTrue($this->access('k')->isValidToken($payload . '.' . $this->sign($payload, 'k')));
    }

    public function testTamperedSignatureIsRejected(): void
    {
        $access = $this->access('k');
        $token = $access->createToken();
        $last = substr($token, -1) === 'a' ? 'b' : 'a';

        $this->assertFalse($access->isValidToken(substr($token, 0, -1) . $last));
    }

    public static function malformedProvider(): array
    {
        return [
            'null' => [null],
            'int' => [123],
            'empty' => [''],
            'too long' => [str_repeat('1', 129)],
            'two parts' => ['123.abc'],
            'non digit expiry' => ['12a.abcdef.ff'],
            'non hex nonce' => ['123.xyz.ff'],
        ];
    }

    #[DataProvider('malformedProvider')]
    public function testMalformedTokensRejected(mixed $token): void
    {
        $this->assertFalse($this->access('k')->isValidToken($token));
    }
}
