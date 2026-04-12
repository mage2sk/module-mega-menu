<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Preview;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\App\CacheInterface;
use Panth\MegaMenu\Controller\Adminhtml\Menu\PreviewToken;

class Index implements HttpGetActionInterface, HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * Session key for used preview keys
     */
    const SESSION_KEY_USED_KEYS = 'megamenu_used_preview_keys';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param PageFactory $resultPageFactory
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param ForwardFactory $resultForwardFactory
     * @param SessionManagerInterface $session
     * @param CacheInterface $cache
     */
    public function __construct(
        PageFactory $resultPageFactory,
        RequestInterface $request,
        LoggerInterface $logger,
        ForwardFactory $resultForwardFactory,
        SessionManagerInterface $session,
        CacheInterface $cache
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->session = $session;
        $this->cache = $cache;
    }

    /**
     * Execute action - Preview page for admin users ONLY
     *
     * Supports two modes:
     * 1. POST with secret key - original approach, renders from POST data
     * 2. GET with menu_id + preview token - save-then-preview approach, renders from DB
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            // Mode 1: POST with items_json — preview unsaved data (from admin iframe)
            $itemsJson = $this->request->getParam('items_json', '');
            if (!empty($itemsJson)) {
                return $this->renderPreviewPage();
            }

            // Mode 2: GET with menu_id + preview token (save-then-preview)
            $menuId = $this->request->getParam('menu_id', '');
            $previewToken = $this->request->getParam('token', '');
            if (!empty($menuId) && !empty($previewToken)) {
                if ($this->validatePreviewToken($previewToken, $menuId)) {
                    return $this->renderPreviewPage();
                }
                return $this->forward404();
            }

            // Mode 3: GET with menu_id only (load saved data from DB)
            if (!empty($menuId)) {
                return $this->renderPreviewPage();
            }

            // Mode 4: POST with secret key (legacy approach)
            $secretKeyFromUrl = $this->request->getParam('key', '');
            $secretKeyFromPost = $this->request->getParam('secret_key', '');
            if (!empty($secretKeyFromUrl) && !empty($secretKeyFromPost)
                && $secretKeyFromUrl === $secretKeyFromPost) {
                return $this->renderPreviewPage();
            }

            return $this->forward404();

        } catch (\Exception $e) {
            // Return error page
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('Preview Error'));
            return $resultPage;
        }
    }

    /**
     * Validate a preview token from cache
     *
     * The token is stored in Magento's shared cache by the admin PreviewToken controller.
     * This allows cross-session validation (admin session -> frontend session).
     *
     * @param string $token
     * @param string $menuId
     * @return bool
     */
    private function validatePreviewToken(string $token, string $menuId): bool
    {
        $cacheKey = PreviewToken::CACHE_PREFIX . $token;
        $cachedMenuId = $this->cache->load($cacheKey);

        if ($cachedMenuId === false) {
            return false;
        }

        // Verify the token was issued for this specific menu_id
        if ((string) $cachedMenuId !== (string) $menuId) {
            return false;
        }

        // Remove the token from cache (single-use)
        $this->cache->remove($cacheKey);

        return true;
    }

    /**
     * Render the preview page
     *
     * @return ResultInterface
     */
    private function renderPreviewPage(): ResultInterface
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Menu Preview'));

        $resultPage->addHandle('panth_menu_preview_index');
        $resultPage->getConfig()->setPageLayout('empty');

        // Prevent ALL caching — headers for browser, Varnish, and Magento FPC
        $resultPage->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        $resultPage->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private', true);
        $resultPage->setHeader('Pragma', 'no-cache', true);
        $resultPage->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT', true);
        // Tell Magento FPC to NOT cache this page
        $resultPage->setHeader('X-Magento-Cache-Control', 'max-age=0', true);
        $resultPage->setHeader('X-Magento-Tags', 'PREVIEW_NOCACHE_' . time(), true);

        return $resultPage;
    }

    /**
     * Forward to 404 page
     *
     * @return ResultInterface
     */
    private function forward404(): ResultInterface
    {
        $resultForward = $this->resultForwardFactory->create();
        $resultForward->setController('index');
        $resultForward->setModule('cms');
        $resultForward->forward('noroute');

        return $resultForward;
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
}
