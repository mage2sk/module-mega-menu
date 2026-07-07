<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Module\PackageInfo;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class InstallReporter
{
    public const MAGENTO_MODULE = 'Panth_MegaMenu';

    public const COMPOSER_PACKAGE = 'mage2kishan/module-mega-menu';

    private const ENDPOINT_INSTALL   = 'https://kishansavaliya.com/panth/notifications/install';
    private const ENDPOINT_HEARTBEAT = 'https://kishansavaliya.com/panth/notifications/heartbeat';

    private const AUTH_USER = 'Kishan';
    private const AUTH_PASS = 'kishan123#';

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly ModuleListInterface $moduleList,
        private readonly PackageInfo $packageInfo,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly FlagManager $flagManager,
        private readonly ComponentRegistrarInterface $componentRegistrar,
        private readonly LoggerInterface $logger
    ) {
    }

    public function reportInstall(): void
    {
        try {
            if (!function_exists('curl_init')) {
                return;
            }
            $version = (string)$this->packageInfo->getVersion(self::MAGENTO_MODULE);
            if ($version === '') {
                return;
            }

            $flagCode = 'panth_' . strtolower(self::MAGENTO_MODULE) . '_reported_' . $version;
            if ($this->flagManager->getFlagData($flagCode)) {
                return;
            }

            $previous = $this->flagManager->getFlagData(
                'panth_' . strtolower(self::MAGENTO_MODULE) . '_last_version'
            );
            $payload = $this->basePayload();
            $payload['event_type']       = $previous ? 'upgrade' : 'install';
            $payload['composer_package'] = self::COMPOSER_PACKAGE;
            $payload['magento_module']   = self::MAGENTO_MODULE;
            $payload['module_version']   = $version;
            $payload['previous_version'] = $previous ?: null;

            $this->post(self::ENDPOINT_INSTALL, $payload);

            $this->flagManager->saveFlag($flagCode, 1);
            $this->flagManager->saveFlag(
                'panth_' . strtolower(self::MAGENTO_MODULE) . '_last_version',
                $version
            );
        } catch (\Throwable $e) {
            $this->logger->warning('[Panth InstallReporter] ' . $e->getMessage());
        }
    }

    public function reportHeartbeat(): void
    {
        try {
            if (!function_exists('curl_init')) {
                return;
            }
            $today = gmdate('Ymd');
            $flagCode = 'panth_' . strtolower(self::MAGENTO_MODULE) . '_heartbeat_' . $today;
            if ($this->flagManager->getFlagData($flagCode)) {
                return;
            }

            $payload = $this->basePayload();
            $payload['panth_modules_active'] = $this->collectActivePanthModules();

            $this->post(self::ENDPOINT_HEARTBEAT, $payload);

            $this->flagManager->saveFlag($flagCode, 1);
        } catch (\Throwable $e) {
            $this->logger->warning('[Panth InstallReporter heartbeat] ' . $e->getMessage());
        }
    }

    private function basePayload(): array
    {
        $store = $this->storeManager->getDefaultStoreView();
        $baseUrl = $store ? $store->getBaseUrl(UrlInterface::URL_TYPE_WEB) : '';
        $siteName = $store ? (string)$store->getName() : '';

        $coreModule = $this->moduleList->getOne('Panth_Core');
        $coreVersion = $coreModule ? (string)$this->packageInfo->getVersion('Panth_Core') : '';
        $corePresent = (bool)$coreModule;

        return [
            'site_url'           => $baseUrl,
            'site_name'          => $siteName,
            'magento_version'    => (string)$this->productMetadata->getVersion(),
            'magento_edition'    => (string)$this->productMetadata->getEdition(),
            'php_version'        => PHP_VERSION,
            'panth_core_present' => $corePresent,
            'panth_core_version' => $coreVersion ?: null,
            'reported_at'        => gmdate('c'),
        ];
    }

    private function collectActivePanthModules(): array
    {
        $out = [];
        foreach ($this->moduleList->getNames() as $name) {
            if (!str_starts_with($name, 'Panth_')) {
                continue;
            }
            $version = (string)$this->packageInfo->getVersion($name);
            $package = $this->resolveComposerPackage($name);
            if ($version === '' || $package === '') {
                continue;
            }
            $out[] = [
                'magento_module'   => $name,
                'version'          => $version,
                'composer_package' => $package,
            ];
        }
        sort($out);
        return $out;
    }

    private function resolveComposerPackage(string $magentoModule): string
    {
        try {
            $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $magentoModule);
            if (!$path) {
                return '';
            }
            $composerPath = rtrim($path, '/') . '/composer.json';
            if (!is_readable($composerPath)) {
                return '';
            }
            $raw = (string)@file_get_contents($composerPath);
            if ($raw === '') {
                return '';
            }
            $data = json_decode($raw, true);
            return is_array($data) && isset($data['name']) && is_string($data['name'])
                ? $data['name']
                : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private function post(string $url, array $payload): void
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new \RuntimeException('payload encode failed');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(self::AUTH_USER . ':' . self::AUTH_PASS),
                'User-Agent: Panth-Module-Reporter/1.0',
            ],
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_NOSIGNAL => 1,
        ]);
        $response = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('curl error: ' . $err);
        }
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException('non-2xx response: ' . $code);
        }
    }
}
