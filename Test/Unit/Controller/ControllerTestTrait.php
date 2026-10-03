<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\App\ActionFlag;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\RedirectInterface as AppRedirectInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;

trait ControllerTestTrait
{
    private array $params = [];
    private array $postValue = [];
    private bool $ajax = false;
    private string $content = '';
    private array $setParams = [];
    private array $messages = ['error' => [], 'success' => [], 'exception' => [], 'notice' => []];
    private ?array $redirectedTo = null;
    private mixed $jsonPayload = null;
    private array $titles = [];
    private ?string $activeMenu = null;
    private array $headers = [];
    private ?AuthorizationInterface $authorization = null;
    private bool $formKeyValid = true;
    private ?Json $json = null;
    private string $body = '';
    private ?HttpResponse $response = null;

    private function request(): Http
    {
        $request = $this->createStub(Http::class);
        $request->method('getParam')->willReturnCallback(
            fn (string $name, $default = null) => $this->setParams[$name] ?? $this->params[$name] ?? $default
        );
        $request->method('getParams')->willReturnCallback(fn () => $this->params);
        $request->method('getPostValue')->willReturnCallback(
            fn ($key = null, $default = null) => $key === null ? $this->postValue : ($this->postValue[$key] ?? $default)
        );
        $request->method('getPost')->willReturnCallback(
            fn ($key = null, $default = null) => $key === null ? $this->postValue : ($this->postValue[$key] ?? $default)
        );
        $request->method('isAjax')->willReturnCallback(fn () => $this->ajax);
        $request->method('getContent')->willReturnCallback(fn () => $this->content);
        $request->method('setParam')->willReturnCallback(function (string $name, $value) use ($request) {
            $this->setParams[$name] = $value;
            return $request;
        });

        return $request;
    }

    private function context(): Context
    {
        $messages = $this->createStub(ManagerInterface::class);
        foreach (['error' => 'addErrorMessage', 'success' => 'addSuccessMessage', 'notice' => 'addNoticeMessage'] as $type => $method) {
            $messages->method($method)->willReturnCallback(function ($message) use ($type, $messages) {
                $this->messages[$type][] = (string) $message;
                return $messages;
            });
        }
        $messages->method('addExceptionMessage')->willReturnCallback(function ($e, $message = null) use ($messages) {
            $this->messages['exception'][] = (string) $message;
            return $messages;
        });

        $redirect = $this->createStub(Redirect::class);
        $redirect->method('setPath')->willReturnCallback(function (string $path, array $params = []) use ($redirect) {
            $this->redirectedTo = [$path, $params];
            return $redirect;
        });
        $redirect->method('setRefererUrl')->willReturnCallback(function () use ($redirect) {
            $this->redirectedTo = ['referer', []];
            return $redirect;
        });
        $redirectFactory = $this->createStub(RedirectFactory::class);
        $redirectFactory->method('create')->willReturn($redirect);

        $resultFactory = $this->createStub(ResultFactory::class);
        $resultFactory->method('create')->willReturnCallback(
            fn (string $type) => $type === ResultFactory::TYPE_JSON ? $this->jsonResult() : $redirect
        );

        $response = $this->createStub(HttpResponse::class);
        $response->method('setHeader')->willReturnCallback(function ($name, $value) use ($response) {
            $this->headers[$name] = $value;
            return $response;
        });
        $response->method('setHttpResponseCode')->willReturnSelf();
        $response->method('setRedirect')->willReturnCallback(function ($url) use ($response) {
            $this->redirectedTo = [(string) $url, []];
            return $response;
        });
        $response->method('setBody')->willReturnCallback(function ($body) use ($response) {
            $this->body = (string) $body;
            return $response;
        });
        $this->response = $response;

        $appRedirect = $this->createStub(AppRedirectInterface::class);
        $appRedirect->method('redirect')->willReturnCallback(function ($resp, string $path, array $args = []): void {
            $this->redirectedTo = [$path, $args];
        });

        $formKey = $this->createStub(FormKeyValidator::class);
        $formKey->method('validate')->willReturnCallback(fn () => $this->formKeyValid);

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($this->request());
        $context->method('getResponse')->willReturn($response);
        $context->method('getMessageManager')->willReturn($messages);
        $context->method('getResultRedirectFactory')->willReturn($redirectFactory);
        $context->method('getResultFactory')->willReturn($resultFactory);
        $context->method('getAuthorization')->willReturnCallback(
            fn () => $this->authorization ?? $this->createStub(AuthorizationInterface::class)
        );
        $context->method('getFormKeyValidator')->willReturn($formKey);
        $context->method('getRedirect')->willReturn($appRedirect);
        $context->method('getSession')->willReturn($this->createStub(BackendSession::class));
        $context->method('getActionFlag')->willReturn($this->createStub(ActionFlag::class));
        $helper = $this->createStub(BackendHelper::class);
        $helper->method('getUrl')->willReturnCallback(fn ($route = '', $params = []) => (string) $route);
        $context->method('getHelper')->willReturn($helper);

        return $context;
    }

    private function jsonResult(): Json
    {
        if ($this->json === null) {
            $json = $this->createStub(Json::class);
            $json->method('setData')->willReturnCallback(function ($data) use ($json) {
                $this->jsonPayload = $data;
                return $json;
            });
            $json->method('setHttpResponseCode')->willReturnSelf();
            $json->method('setHeader')->willReturnSelf();
            $this->json = $json;
        }

        return $this->json;
    }

    private function jsonFactory(): JsonFactory
    {
        $factory = $this->createStub(JsonFactory::class);
        $factory->method('create')->willReturnCallback(fn () => $this->jsonResult());

        return $factory;
    }

    private function pageFactory(): PageFactory
    {
        $title = $this->createStub(Title::class);
        $title->method('prepend')->willReturnCallback(function ($value): void {
            $this->titles[] = (string) $value;
        });
        $config = $this->createStub(PageConfig::class);
        $config->method('getTitle')->willReturn($title);
        $page = $this->createStub(Page::class);
        $page->method('getConfig')->willReturn($config);
        $page->method('setActiveMenu')->willReturnCallback(function (string $menu) use ($page) {
            $this->activeMenu = $menu;
            return $page;
        });
        $factory = $this->createStub(PageFactory::class);
        $factory->method('create')->willReturn($page);

        return $factory;
    }

    private function assertAclResource(object $controller, string $resource): void
    {
        $checked = [];
        $auth = $this->createStub(AuthorizationInterface::class);
        $auth->method('isAllowed')->willReturnCallback(function (string $res) use (&$checked) {
            $checked[] = $res;
            return true;
        });
        $property = new \ReflectionProperty($controller, '_authorization');
        $property->setValue($controller, $auth);
        $method = new \ReflectionMethod($controller, '_isAllowed');

        $this->assertTrue($method->invoke($controller));
        $this->assertSame([$resource], $checked);
    }
}
