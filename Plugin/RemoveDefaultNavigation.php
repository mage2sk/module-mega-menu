<?php
namespace Panth\MegaMenu\Plugin;

use Magento\Framework\View\Layout;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class RemoveDefaultNavigation
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Remove default navigation if MegaMenu is enabled and configured with identifier
     *
     * @param Layout $subject
     * @return void
     */
    public function afterGenerateElements(Layout $subject)
    {
        // Check if MegaMenu is enabled
        $isEnabled = $this->scopeConfig->isSetFlag(
            'panth_megamenu/general/enabled',
            ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            return;
        }

        // Check if menu identifier is configured
        $menuIdentifier = $this->scopeConfig->getValue(
            'panth_megamenu/general/menu_identifier',
            ScopeInterface::SCOPE_STORE
        );

        // Only remove default navigation if identifier is set
        if ($menuIdentifier) {
            // Remove Magento default category navigation
            if ($subject->hasElement('catalog.topnav')) {
                $subject->unsetElement('catalog.topnav');
            }

            // Remove Hyvä default menu blocks when MegaMenu is enabled
            if ($subject->hasElement('topmenu_desktop')) {
                $subject->unsetElement('topmenu_desktop');
            }
            if ($subject->hasElement('topmenu_mobile')) {
                $subject->unsetElement('topmenu_mobile');
            }
        }
    }
}
