<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class GetIcons extends Action
{
    protected $jsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        $library = $this->getRequest()->getParam('library', 'fontawesome');

        $icons = $this->getIconsByLibrary($library);

        return $result->setData([
            'success' => true,
            'library' => $library,
            'icons' => $icons
        ]);
    }

    protected function getIconsByLibrary($library)
    {
        switch ($library) {
            case 'fontawesome':
                return $this->getFontAwesomeIcons();
            case 'lineicons':
                return $this->getLineIcons();
            case 'feather':
                return $this->getFeatherIcons();
            case 'material':
                return $this->getMaterialIcons();
            case 'emoji':
                return $this->getEmojis();
            default:
                return [];
        }
    }

    protected function getFontAwesomeIcons()
    {
        return [
            ['class' => 'fa-solid fa-home', 'name' => 'Home'],
            ['class' => 'fa-solid fa-user', 'name' => 'User'],
            ['class' => 'fa-solid fa-heart', 'name' => 'Heart'],
            ['class' => 'fa-solid fa-star', 'name' => 'Star'],
            ['class' => 'fa-solid fa-shopping-cart', 'name' => 'Shopping Cart'],
            ['class' => 'fa-solid fa-search', 'name' => 'Search'],
            ['class' => 'fa-solid fa-envelope', 'name' => 'Envelope'],
            ['class' => 'fa-solid fa-phone', 'name' => 'Phone'],
            ['class' => 'fa-solid fa-bars', 'name' => 'Bars'],
            ['class' => 'fa-solid fa-check', 'name' => 'Check'],
            ['class' => 'fa-solid fa-times', 'name' => 'Times'],
            ['class' => 'fa-solid fa-arrow-right', 'name' => 'Arrow Right'],
            ['class' => 'fa-solid fa-arrow-left', 'name' => 'Arrow Left'],
            ['class' => 'fa-solid fa-chevron-down', 'name' => 'Chevron Down'],
            ['class' => 'fa-solid fa-chevron-right', 'name' => 'Chevron Right'],
            ['class' => 'fa-solid fa-tag', 'name' => 'Tag'],
            ['class' => 'fa-solid fa-tags', 'name' => 'Tags'],
            ['class' => 'fa-solid fa-gift', 'name' => 'Gift'],
            ['class' => 'fa-solid fa-fire', 'name' => 'Fire'],
            ['class' => 'fa-solid fa-bolt', 'name' => 'Bolt'],
        ];
    }

    protected function getLineIcons()
    {
        return [
            ['class' => 'lni lni-home', 'name' => 'Home'],
            ['class' => 'lni lni-user', 'name' => 'User'],
            ['class' => 'lni lni-heart', 'name' => 'Heart'],
            ['class' => 'lni lni-star', 'name' => 'Star'],
            ['class' => 'lni lni-cart', 'name' => 'Cart'],
            ['class' => 'lni lni-search', 'name' => 'Search'],
            ['class' => 'lni lni-envelope', 'name' => 'Envelope'],
            ['class' => 'lni lni-phone', 'name' => 'Phone'],
            ['class' => 'lni lni-menu', 'name' => 'Menu'],
            ['class' => 'lni lni-checkmark', 'name' => 'Checkmark'],
        ];
    }

    protected function getFeatherIcons()
    {
        return [
            ['class' => 'feather-home', 'name' => 'Home'],
            ['class' => 'feather-user', 'name' => 'User'],
            ['class' => 'feather-heart', 'name' => 'Heart'],
            ['class' => 'feather-star', 'name' => 'Star'],
            ['class' => 'feather-shopping-cart', 'name' => 'Shopping Cart'],
            ['class' => 'feather-search', 'name' => 'Search'],
            ['class' => 'feather-mail', 'name' => 'Mail'],
            ['class' => 'feather-phone', 'name' => 'Phone'],
            ['class' => 'feather-menu', 'name' => 'Menu'],
            ['class' => 'feather-check', 'name' => 'Check'],
        ];
    }

    protected function getMaterialIcons()
    {
        return [
            ['class' => 'material-icons', 'name' => 'home', 'text' => 'home'],
            ['class' => 'material-icons', 'name' => 'person', 'text' => 'person'],
            ['class' => 'material-icons', 'name' => 'favorite', 'text' => 'favorite'],
            ['class' => 'material-icons', 'name' => 'star', 'text' => 'star'],
            ['class' => 'material-icons', 'name' => 'shopping_cart', 'text' => 'shopping_cart'],
            ['class' => 'material-icons', 'name' => 'search', 'text' => 'search'],
            ['class' => 'material-icons', 'name' => 'email', 'text' => 'email'],
            ['class' => 'material-icons', 'name' => 'phone', 'text' => 'phone'],
            ['class' => 'material-icons', 'name' => 'menu', 'text' => 'menu'],
            ['class' => 'material-icons', 'name' => 'check', 'text' => 'check'],
        ];
    }

    protected function getEmojis()
    {
        return [
            ['emoji' => '🏠', 'name' => 'Home'],
            ['emoji' => '👤', 'name' => 'User'],
            ['emoji' => '❤️', 'name' => 'Heart'],
            ['emoji' => '⭐', 'name' => 'Star'],
            ['emoji' => '🛒', 'name' => 'Shopping Cart'],
            ['emoji' => '🔍', 'name' => 'Search'],
            ['emoji' => '✉️', 'name' => 'Envelope'],
            ['emoji' => '📞', 'name' => 'Phone'],
            ['emoji' => '☰', 'name' => 'Menu'],
            ['emoji' => '✅', 'name' => 'Check'],
            ['emoji' => '🎁', 'name' => 'Gift'],
            ['emoji' => '🔥', 'name' => 'Fire'],
            ['emoji' => '⚡', 'name' => 'Bolt'],
            ['emoji' => '🏷️', 'name' => 'Tag'],
        ];
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Panth_MegaMenu::menu');
    }
}
