<?php
/**
 * Panth MegaMenu Theme Detection Helper
 *
 * Provides comprehensive theme detection functionality to support both Hyva and Luma themes.
 * This helper enables the module to intelligently adapt its behavior based on the active theme.
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 * @license   Proprietary
 */

declare(strict_types=1);

namespace Panth\MegaMenu\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Model\Theme\Registration as ThemeRegistration;
use Magento\Framework\Module\Manager as ModuleManager;
use Psr\Log\LoggerInterface;

/**
 * Theme Detection Helper
 *
 * Detects and identifies the current theme to enable dual-theme support
 * for both Hyva (Alpine.js based) and Luma (Knockout.js based) themes.
 */
class Theme extends AbstractHelper
{
    /**
     * Theme identifier constants
     */
    public const THEME_HYVA = 'hyva';
    public const THEME_LUMA = 'luma';
    public const THEME_UNKNOWN = 'unknown';

    /**
     * Cache key for theme detection
     */
    private const CACHE_KEY_THEME_TYPE = 'panth_megamenu_theme_type';

    /**
     * @var DesignInterface
     */
    private DesignInterface $design;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ModuleManager
     */
    private ModuleManager $moduleManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var string|null Cached theme type
     */
    private ?string $cachedThemeType = null;

    /**
     * Constructor
     *
     * @param Context $context
     * @param DesignInterface $design
     * @param StoreManagerInterface $storeManager
     * @param ModuleManager $moduleManager
     */
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

    /**
     * Check if current theme is Hyva
     *
     * Detects Hyva theme by checking:
     * 1. If Hyva_Theme module is enabled
     * 2. If theme path contains 'Hyva'
     * 3. If theme parent is Hyva theme
     *
     * @return bool True if Hyva theme is active
     */
    public function isHyva(): bool
    {
        return $this->getCurrentTheme() === self::THEME_HYVA;
    }

    /**
     * Check if current theme is Luma
     *
     * Detects Luma (or Luma-based) theme by checking:
     * 1. If theme is not Hyva
     * 2. If theme path contains 'Luma' or 'Magento/blank'
     * 3. Default fallback for standard Magento themes
     *
     * @return bool True if Luma theme is active
     */
    public function isLuma(): bool
    {
        return $this->getCurrentTheme() === self::THEME_LUMA;
    }

    /**
     * Get current theme identifier
     *
     * Returns a string identifier for the current theme.
     * Uses caching to avoid repeated detection calls.
     *
     * @return string One of: THEME_HYVA, THEME_LUMA, or THEME_UNKNOWN
     */
    public function getCurrentTheme(): string
    {
        // Return cached result if available
        if ($this->cachedThemeType !== null) {
            return $this->cachedThemeType;
        }

        try {
            // Primary check: if Hyva_Theme module is enabled, it's likely a Hyva-based theme
            // This works for child themes that inherit from Panth/Infotech → Hyva/default
            if ($this->moduleManager->isEnabled('Hyva_Theme')) {
                $themePath = $this->getThemePath();
                // Only override if theme is explicitly a Luma theme
                if ($this->isLumaThemePath($themePath)) {
                    $this->cachedThemeType = self::THEME_LUMA;
                    $this->logThemeDetection(self::THEME_LUMA, "Hyva module enabled but Luma theme active: {$themePath}");
                    return $this->cachedThemeType;
                }
                $this->cachedThemeType = self::THEME_HYVA;
                $this->logThemeDetection(self::THEME_HYVA, 'Hyva_Theme module detected');
                return $this->cachedThemeType;
            }

            // Secondary: check theme path for Hyva indicators
            $themePath = $this->getThemePath();
            if ($this->isHyvaThemePath($themePath)) {
                $this->cachedThemeType = self::THEME_HYVA;
                $this->logThemeDetection(self::THEME_HYVA, "Theme path contains Hyva: {$themePath}");
                return $this->cachedThemeType;
            }

            // Default to Luma for unknown themes (most Magento themes are Luma-based)
            $this->cachedThemeType = self::THEME_LUMA;
            $this->logThemeDetection(self::THEME_LUMA, "Defaulting to Luma for theme: {$themePath}");
            return $this->cachedThemeType;

        } catch (\Exception $e) {
            $this->cachedThemeType = self::THEME_UNKNOWN;
            return $this->cachedThemeType;
        }
    }

    /**
     * Get template path based on current theme
     *
     * Returns the appropriate template file path based on the active theme.
     * This allows for theme-specific template rendering.
     *
     * @param string $hyvaTemplate Template path for Hyva theme
     * @param string $lumaTemplate Template path for Luma theme
     * @return string The appropriate template path for current theme
     */
    public function getTemplateForTheme(string $hyvaTemplate, string $lumaTemplate): string
    {
        return $this->isHyva() ? $hyvaTemplate : $lumaTemplate;
    }

    /**
     * Get CSS class suffix for current theme
     *
     * Returns a CSS class suffix that can be used to apply theme-specific styles.
     * Useful for adding conditional classes like 'megamenu-hyva' or 'megamenu-luma'.
     *
     * @return string CSS class suffix (e.g., 'hyva', 'luma')
     */
    public function getThemeClassSuffix(): string
    {
        return $this->getCurrentTheme();
    }

    /**
     * Check if Alpine.js should be used
     *
     * Alpine.js is the primary JavaScript framework in Hyva themes.
     * This method determines if Alpine.js-based components should be loaded.
     *
     * @return bool True if Alpine.js should be used
     */
    public function useAlpineJs(): bool
    {
        return $this->isHyva();
    }

    /**
     * Check if KnockoutJS should be used
     *
     * KnockoutJS is the primary JavaScript framework in Luma themes.
     * This method determines if KnockoutJS-based components should be loaded.
     *
     * @return bool True if KnockoutJS should be used
     */
    public function useKnockoutJs(): bool
    {
        return $this->isLuma();
    }

    /**
     * Get current theme path
     *
     * Retrieves the full path of the currently active theme.
     *
     * @return string Theme path (e.g., 'Hyva/default', 'Magento/luma')
     */
    private function getThemePath(): string
    {
        try {
            $themeId = $this->design->getConfigurationDesignTheme();

            // If theme ID is numeric, it's a theme from database
            if (is_numeric($themeId)) {
                $themePath = $this->design->getDesignTheme()->getThemePath();
                return $themePath ?? '';
            }

            // Otherwise, use the theme ID as path
            return (string) $themeId;

        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if theme path indicates Hyva theme
     *
     * @param string $themePath
     * @return bool
     */
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

    /**
     * Check if theme path indicates Luma theme
     *
     * @param string $themePath
     * @return bool
     */
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

    /**
     * Log theme detection for debugging
     *
     * @param string $themeType
     * @param string $reason
     * @return void
     */
    private function logThemeDetection(string $themeType, string $reason): void
    {
        // Logging disabled for production
    }

    /**
     * Reset cached theme type
     *
     * Useful for testing or when theme changes during runtime.
     * Not typically needed in production.
     *
     * @return void
     */
    public function resetCache(): void
    {
        $this->cachedThemeType = null;
    }

    /**
     * Get theme-specific configuration
     *
     * Returns configuration array with theme-specific settings.
     * Can be extended to include more theme-specific configurations.
     *
     * @return array<string, mixed>
     */
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
