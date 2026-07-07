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
    const SESSION_KEY_USED_KEYS = 'megamenu_used_preview_keys';

    protected $resultPageFactory;

    protected $request;

    protected $logger;

    protected $resultForwardFactory;

    protected $session;

    protected $cache;

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

    public function execute(): ResultInterface
    {
        try {
            $itemsJson = $this->request->getParam('items_json', '');
            if (!empty($itemsJson)) {
                return $this->renderPreviewPage();
            }

            $menuId = $this->request->getParam('menu_id', '');
            $previewToken = $this->request->getParam('token', '');
            if (!empty($menuId) && !empty($previewToken)) {
                if ($this->validatePreviewToken($previewToken, $menuId)) {
                    return $this->renderPreviewPage();
                }
                return $this->forward404();
            }

            if (!empty($menuId)) {
                return $this->renderPreviewPage();
            }

            $secretKeyFromUrl = $this->request->getParam('key', '');
            $secretKeyFromPost = $this->request->getParam('secret_key', '');
            if (!empty($secretKeyFromUrl) && !empty($secretKeyFromPost)
                && $secretKeyFromUrl === $secretKeyFromPost) {
                return $this->renderPreviewPage();
            }

            return $this->forward404();
        } catch (\Exception $e) {
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('Preview Error'));
            return $resultPage;
        }
    }

    private function validatePreviewToken(string $token, string $menuId): bool
    {
        $cacheKey = PreviewToken::CACHE_PREFIX . $token;
        $cachedMenuId = $this->cache->load($cacheKey);

        if ($cachedMenuId === false) {
            return false;
        }

        if ((string) $cachedMenuId !== (string) $menuId) {
            return false;
        }

        $this->cache->remove($cacheKey);

        return true;
    }

    private function renderPreviewPage(): ResultInterface
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Menu Preview'));

        $resultPage->addHandle('panth_menu_preview_index');
        $resultPage->getConfig()->setPageLayout('empty');

        $resultPage->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        $resultPage->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private', true);
        $resultPage->setHeader('Pragma', 'no-cache', true);
        $resultPage->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT', true);

        $resultPage->setHeader('X-Magento-Cache-Control', 'max-age=0', true);
        $resultPage->setHeader('X-Magento-Tags', 'PREVIEW_NOCACHE_' . time(), true);

        return $resultPage;
    }

    private function forward404(): ResultInterface
    {
        $resultForward = $this->resultForwardFactory->create();
        $resultForward->setController('index');
        $resultForward->setModule('cms');
        $resultForward->forward('noroute');

        return $resultForward;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
