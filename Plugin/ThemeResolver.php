<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Plugin;

use Magento\Framework\View\DesignInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Panth\MegaMenu\Helper\Theme as ThemeHelper;
use Psr\Log\LoggerInterface;

class ThemeResolver
{
    private const PARAM_THEME_OVERRIDE = 'megamenu_theme_test';

    private ThemeHelper $themeHelper;

    private LoggerInterface $logger;

    private HttpRequest $request;

    private bool $themeResolved = false;

    public function __construct(
        ThemeHelper $themeHelper,
        LoggerInterface $logger,
        HttpRequest $request
    ) {
        $this->themeHelper = $themeHelper;
        $this->logger = $logger;
        $this->request = $request;
    }

    public function afterGetDesignTheme(DesignInterface $subject, $result)
    {
        if ($this->themeResolved) {
            return $result;
        }

        $this->themeResolved = true;

        try {
            $themeType = $this->themeHelper->getCurrentTheme();

            $this->logThemeResolution($themeType, $result);

            $this->checkThemeOverride();
        } catch (\Exception $e) {
        }

        return $result;
    }

    public function aroundSetDesignTheme(
        DesignInterface $subject,
        callable $proceed,
        $theme,
        $params = []
    ) {
        $this->themeHelper->resetCache();

        $result = $proceed($theme, $params);

        $this->themeResolved = false;

        return $result;
    }

    private function logThemeResolution(string $themeType, $themeObject): void
    {
    }

    private function checkThemeOverride(): void
    {
        $overrideTheme = $this->request->getParam(self::PARAM_THEME_OVERRIDE);
    }

    public function beforeLoadLayout($subject, $layoutHandles = null): array
    {
        if (!$this->themeResolved) {
            $themeType = $this->themeHelper->getCurrentTheme();
            $this->themeResolved = true;
        }

        return [$layoutHandles];
    }
}
