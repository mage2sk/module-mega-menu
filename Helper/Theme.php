<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Theme\Registration as ThemeRegistration;
use Magento\Framework\Module\Manager as ModuleManager;
use Psr\Log\LoggerInterface;

class Theme extends AbstractHelper
{
    public const THEME_HYVA = 'hyva';
    public const THEME_LUMA = 'luma';
    public const THEME_UNKNOWN = 'unknown';

    private const CACHE_KEY_THEME_TYPE = 'panth_megamenu_theme_type';

    private DesignInterface $design;

    private StoreManagerInterface $storeManager;

    private ModuleManager $moduleManager;

    private LoggerInterface $logger;

    private ?string $cachedThemeType = null;

    public function __construct(
        Context $context,
        DesignInterface $design,
        StoreManagerInterface $storeManager,
        ModuleManager $moduleManager
    ) {
        parent::__construct($context);
        $this->design = $design;
        $this->storeManager = $storeManager;
        $this->moduleManager = $moduleManager;
        $this->logger = $context->getLogger();
    }

    public function isHyva(): bool
    {
        return $this->getCurrentTheme() === self::THEME_HYVA;
    }

    public function isLuma(): bool
    {
        return $this->getCurrentTheme() === self::THEME_LUMA;
    }

    public function getCurrentTheme(): string
    {
        if ($this->cachedThemeType !== null) {
            return $this->cachedThemeType;
        }

        try {
            if ($this->moduleManager->isEnabled('Hyva_Theme')) {
                $themePath = $this->getThemePath();

                if ($this->isLumaThemePath($themePath)) {
                    $this->cachedThemeType = self::THEME_LUMA;
                    $this->logThemeDetection(self::THEME_LUMA, "Hyva module enabled but Luma theme active: {$themePath}");
                    return $this->cachedThemeType;
                }
                $this->cachedThemeType = self::THEME_HYVA;
                $this->logThemeDetection(self::THEME_HYVA, 'Hyva_Theme module detected');
                return $this->cachedThemeType;
            }

            $themePath = $this->getThemePath();
            if ($this->isHyvaThemePath($themePath)) {
                $this->cachedThemeType = self::THEME_HYVA;
                $this->logThemeDetection(self::THEME_HYVA, "Theme path contains Hyva: {$themePath}");
                return $this->cachedThemeType;
            }

            $this->cachedThemeType = self::THEME_LUMA;
            $this->logThemeDetection(self::THEME_LUMA, "Defaulting to Luma for theme: {$themePath}");
            return $this->cachedThemeType;
        } catch (\Exception $e) {
            $this->cachedThemeType = self::THEME_UNKNOWN;
            return $this->cachedThemeType;
        }
    }

    public function getTemplateForTheme(string $hyvaTemplate, string $lumaTemplate): string
    {
        return $this->isHyva() ? $hyvaTemplate : $lumaTemplate;
    }

    public function getThemeClassSuffix(): string
    {
        return $this->getCurrentTheme();
    }

    public function useAlpineJs(): bool
    {
        return $this->isHyva();
    }

    public function useKnockoutJs(): bool
    {
        return $this->isLuma();
    }

    private function getThemePath(): string
    {
        try {
            $themeId = $this->design->getConfigurationDesignTheme();

            if (is_numeric($themeId)) {
                $themePath = $this->design->getDesignTheme()->getThemePath();
                return $themePath ?? '';
            }

            return (string) $themeId;
        } catch (\Exception $e) {
            return '';
        }
    }

    private function isHyvaThemePath(string $themePath): bool
    {
        $hyvaIndicators = [
            'Hyva',
            'hyva',
            'HYVA'
        ];

        foreach ($hyvaIndicators as $indicator) {
            if (stripos($themePath, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isLumaThemePath(string $themePath): bool
    {
        $lumaIndicators = [
            'Magento/luma',
            'Magento/blank',
            'Luma',
            'luma'
        ];

        foreach ($lumaIndicators as $indicator) {
            if (stripos($themePath, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    private function logThemeDetection(string $themeType, string $reason): void
    {
    }

    public function resetCache(): void
    {
        $this->cachedThemeType = null;
    }

    public function getThemeConfig(): array
    {
        $themeType = $this->getCurrentTheme();

        return [
            'theme_type' => $themeType,
            'is_hyva' => $this->isHyva(),
            'is_luma' => $this->isLuma(),
            'use_alpine' => $this->useAlpineJs(),
            'use_knockout' => $this->useKnockoutJs(),
            'css_class_suffix' => $this->getThemeClassSuffix(),
            'theme_path' => $this->getThemePath()
        ];
    }
}
