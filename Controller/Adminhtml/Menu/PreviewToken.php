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

/**
 * Admin controller to generate a single-use preview token.
 *
 * The token is stored in the shared Magento cache so the frontend
 * Preview controller can validate it without needing admin session access.
 */
class PreviewToken extends Action implements CsrfAwareActionInterface
{
    const CACHE_PREFIX = 'megamenu_preview_token_';
    const TOKEN_LIFETIME = 300; // 5 minutes

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var CacheInterface
     */
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

    /**
     * Generate a preview token for the given menu_id
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $menuId = $this->getRequest()->getParam('menu_id');
        if (!$menuId) {
            return $result->setData(['success' => false, 'message' => 'Missing menu_id']);
        }

        // Generate a unique token
        $token = bin2hex(random_bytes(32));

        // Store in cache: token -> menu_id mapping (expires in 5 minutes)
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

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Panth_MegaMenu::menu');
    }
}
