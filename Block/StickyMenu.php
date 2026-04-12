<?php
/**
 * MegaMenu Sticky Menu Block
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
namespace Panth\MegaMenu\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Theme\Block\Html\Header\Logo;
use Magento\Store\Model\StoreManagerInterface;
use Panth\MegaMenu\Helper\Data as ConfigHelper;

class StickyMenu extends Template
{
    /**
     * @var Logo
     */
    private $logo;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @param Context $context
     * @param Logo $logo
     * @param StoreManagerInterface $storeManager
     * @param ConfigHelper $configHelper
     * @param array $data
     */
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

    /**
     * Get logo source URL
     *
     * @return string
     */
    public function getLogoSrc(): string
    {
        return $this->logo->getLogoSrc();
    }

    /**
     * Get logo alt text
     *
     * @return string
     */
    public function getLogoAlt(): string
    {
        return $this->logo->getLogoAlt();
    }

    /**
     * Get store name
     *
     * @return string
     */
    public function getStoreName(): string
    {
        return $this->_scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get cache key info
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return [
            'STICKY_MENU',
            $this->storeManager->getStore()->getId(),
            $this->_design->getDesignTheme()->getId()
        ];
    }
}
