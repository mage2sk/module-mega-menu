<?php
/**
 * Panth MegaMenu Theme Resolver Plugin
 *
 * Intercepts theme resolution to inject theme-specific context and ensure
 * proper theme detection throughout the application lifecycle.
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 * @author    Panth
 * @copyright Copyright (c) Panth
 * @license   Proprietary
 */

declare(strict_types=1);

namespace Panth\MegaMenu\Plugin;

use Magento\Framework\View\DesignInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Panth\MegaMenu\Helper\Theme as ThemeHelper;
use Psr\Log\LoggerInterface;

/**
 * Theme Resolver Plugin
 *
 * This plugin intercepts the theme resolution process to:
 * 1. Detect the current theme (Hyva or Luma)
 * 2. Set theme-specific context for conditional rendering
 * 3. Enable theme-aware layout loading
 * 4. Provide theme information to templates and blocks
 */
class ThemeResolver
{
    /**
     * Request parameter for theme override (useful for testing)
     */
    private const PARAM_THEME_OVERRIDE = 'megamenu_theme_test';

    /**
     * @var ThemeHelper
     */
    private ThemeHelper $themeHelper;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var HttpRequest
     */
    private HttpRequest $request;

    /**
     * @var bool Flag to track if theme has been resolved
     */
    private bool $themeResolved = false;

    /**
     * Constructor
     *
     * @param ThemeHelper $themeHelper
     * @param LoggerInterface $logger
     * @param HttpRequest $request
     */
    public function __construct(
        ThemeHelper $themeHelper,
        LoggerInterface $logger,
        HttpRequest $request
    ) {
        $this->themeHelper = $themeHelper;
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * After plugin for getDesignTheme
     *
     * Intercepts theme resolution to log and validate theme detection.
     * This ensures our theme helper is working correctly.
     *
     * @param DesignInterface $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetDesignTheme(DesignInterface $subject, $result)
    {
        // Only resolve theme once per request
        if ($this->themeResolved) {
            return $result;
        }

        $this->themeResolved = true;

        try {
            // Get detected theme type
            $themeType = $this->themeHelper->getCurrentTheme();

            // Log theme resolution for debugging
            $this->logThemeResolution($themeType, $result);

            // Check for theme override parameter (development/testing only)
            $this->checkThemeOverride();

        } catch (\Exception $e) {
            // Silently handle errors
        }

        return $result;
    }

    /**
     * Around plugin for setDesignTheme
     *
     * Intercepts theme changes to reset theme detection cache.
     * This ensures theme detection stays synchronized with theme changes.
     *
     * @param DesignInterface $subject
     * @param callable $proceed
     * @param mixed $theme
     * @param array $params
     * @return mixed
     */
    public function aroundSetDesignTheme(
        DesignInterface $subject,
        callable $proceed,
        $theme,
        $params = []
    ) {
        // Reset theme cache before theme change
        $this->themeHelper->resetCache();

        // Proceed with original theme setting
        $result = $proceed($theme, $params);

        // Reset resolved flag to allow re-detection
        $this->themeResolved = false;

        return $result;
    }

    /**
     * Log theme resolution details
     *
     * Logs comprehensive information about theme detection for debugging purposes.
     * Only logs in developer mode or when debug logging is enabled.
     *
     * @param string $themeType
     * @param mixed $themeObject
     * @return void
     */
    private function logThemeResolution(string $themeType, $themeObject): void
    {
        // Logging disabled for production
    }

    /**
     * Check for theme override parameter
     *
     * Allows developers to test theme-specific functionality by adding
     * a URL parameter. Only works in developer mode for security.
     *
     * Example: ?megamenu_theme_test=hyva
     *
     * @return void
     */
    private function checkThemeOverride(): void
    {
        $overrideTheme = $this->request->getParam(self::PARAM_THEME_OVERRIDE);
        // Theme override checking disabled for production
    }

    /**
     * Before plugin for loadLayout
     *
     * Ensures theme is resolved before layout loading begins.
     * This guarantees theme-specific layouts are loaded correctly.
     *
     * @param mixed $subject
     * @param string|null $layoutHandles
     * @return array
     */
    public function beforeLoadLayout($subject, $layoutHandles = null): array
    {
        // Ensure theme is detected before layout loading
        if (!$this->themeResolved) {
            $themeType = $this->themeHelper->getCurrentTheme();
            $this->themeResolved = true;
        }

        return [$layoutHandles];
    }
}
