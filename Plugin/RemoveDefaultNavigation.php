<?php
namespace Panth\MegaMenu\Plugin;

use Magento\Framework\View\Layout;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class RemoveDefaultNavigation
{
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function afterGenerateElements(Layout $subject)
    {
        $isEnabled = $this->scopeConfig->isSetFlag(
            'panth_megamenu/general/enabled',
            ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            return;
        }

        $menuIdentifier = $this->scopeConfig->getValue(
            'panth_megamenu/general/menu_identifier',
            ScopeInterface::SCOPE_STORE
        );

        if ($menuIdentifier) {
            if ($subject->hasElement('catalog.topnav')) {
                $subject->unsetElement('catalog.topnav');
            }

            if ($subject->hasElement('topmenu_desktop')) {
                $subject->unsetElement('topmenu_desktop');
            }
            if ($subject->hasElement('topmenu_mobile')) {
                $subject->unsetElement('topmenu_mobile');
            }
        }
    }
}
