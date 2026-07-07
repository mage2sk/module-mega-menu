<?php
namespace Panth\MegaMenu\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Header\Logo;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Helper\Data as ConfigHelper;

class StickyMenu extends Template
{
    private $logo;

    private $storeManager;

    private $configHelper;

    public function __construct(
        Context $context,
        Logo $logo,
        StoreManagerInterface $storeManager,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        $this->logo = $logo;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    public function getLogoSrc(): string
    {
        return $this->logo->getLogoSrc();
    }

    public function getLogoAlt(): string
    {
        return $this->logo->getLogoAlt();
    }

    public function getStoreName(): string
    {
        return $this->_scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCacheKeyInfo()
    {
        return [
            'STICKY_MENU',
            $this->storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId()
        ];
    }
}
