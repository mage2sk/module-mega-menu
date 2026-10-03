<?php
namespace Panth\MegaMenu\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Layout;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;

class RemoveDefaultNavigation
{
    protected $scopeConfig;

    private $storeManager;

    private $menuResource;

    private $pageConfig;

    private $resolved = [];

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        MenuResource $menuResource,
        PageConfig $pageConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->menuResource = $menuResource;
        $this->pageConfig = $pageConfig;
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

        $menuIdentifier = trim((string) $this->scopeConfig->getValue(
            'panth_megamenu/general/menu_identifier',
            ScopeInterface::SCOPE_STORE
        ));

        if ($menuIdentifier === '' || !$this->menuResolves($menuIdentifier)) {
            return;
        }

        $this->pageConfig->addBodyClass('panth-megamenu-active');

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

    private function menuResolves(string $identifier): bool
    {
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (\Exception $e) {
            return false;
        }

        $key = $storeId . '|' . $identifier;
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        $result = false;
        try {
            $row = $this->menuResource->loadByIdentifier($identifier, $storeId);
            if (!empty($row)) {
                $items = json_decode((string) ($row['items_json'] ?? ''), true);
                if (is_array($items)) {
                    foreach ($items as $item) {
                        if (is_array($item)
                            && (!isset($item['show_on_frontend']) || !empty($item['show_on_frontend']))
                        ) {
                            $result = true;
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $result = false;
        }

        $this->resolved[$key] = $result;
        return $result;
    }
}
