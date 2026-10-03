<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class PreviewAccess implements ArgumentInterface
{
    public const TOKEN_PARAM = 'preview_token';

    public const TOKEN_LIFETIME = 3600;

    private const SIGNATURE_CONTEXT = 'panth_megamenu_preview';

    private DeploymentConfig $deploymentConfig;

    public function __construct(DeploymentConfig $deploymentConfig)
    {
        $this->deploymentConfig = $deploymentConfig;
    }

    public function createToken(): string
    {
        $key = $this->getSigningKey();
        if ($key === '') {
            return '';
        }

        $payload = (time() + self::TOKEN_LIFETIME) . '.' . bin2hex(random_bytes(8));

        return $payload . '.' . $this->sign($payload, $key);
    }

    public function isValidToken($token): bool
    {
        if (!is_string($token) || $token === '' || strlen($token) > 128) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3 || !ctype_digit($parts[0]) || !ctype_xdigit($parts[1])) {
            return false;
        }

        $expires = (int) $parts[0];
        $now = time();
        if ($expires < $now || $expires > $now + self::TOKEN_LIFETIME) {
            return false;
        }

        $key = $this->getSigningKey();
        if ($key === '') {
            return false;
        }

        return hash_equals($this->sign($parts[0] . '.' . $parts[1], $key), $parts[2]);
    }

    private function sign(string $payload, string $key): string
    {
        return hash_hmac('sha256', self::SIGNATURE_CONTEXT . '|' . $payload, $key);
    }

    private function getSigningKey(): string
    {
        return trim((string) $this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY));
    }
}
