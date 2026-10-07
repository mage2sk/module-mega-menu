<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class PreviewToken extends Action implements CsrfAwareActionInterface
{
    const CACHE_PREFIX = 'megamenu_preview_token_';
    const TOKEN_LIFETIME = 300;

    private $jsonFactory;

    private $cache;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CacheInterface $cache
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->cache = $cache;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        $menuId = $this->getRequest()->getParam('menu_id');
        if (!$menuId) {
            return $result->setData(['success' => false, 'message' => 'Missing menu_id']);
        }

        $token = bin2hex(random_bytes(32));

        $this->cache->save(
            (string) $menuId,
            self::CACHE_PREFIX . $token,
            ['megamenu_preview'],
            self::TOKEN_LIFETIME
        );

        return $result->setData([
            'success' => true,
            'token' => $token
        ]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Panth_MegaMenu::menu');
    }
}
