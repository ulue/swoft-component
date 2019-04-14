<?php declare(strict_types=1);

namespace Swoft\Http\Server;

use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\BeanEvent;
use Swoft\Bean\Container;
use Swoft\Co;
use Swoft\Dispatcher;
use Swoft\Http\Message\Request;
use Swoft\Http\Message\Response;
use Swoft\Http\Server\Middleware\DefaultMiddleware;
use Swoft\Http\Server\Middleware\RequestMiddleware;
use Swoft\Http\Server\Middleware\UserMiddleware;
use Swoft\Http\Server\Middleware\ValidatorMiddleware;

/**
 * Class HttpDispatcher
 *
 * @Bean("httpDispatcher")
 * @since 2.0
 */
class HttpDispatcher extends Dispatcher
{
    /**
     * Default middleware to handler request
     *
     * @var string
     */
    protected $defaultMiddleware = DefaultMiddleware::class;

    /**
     * Dispatch http request
     *
     * @param array ...$params
     */
    public function dispatch(...$params): void
    {
        /**
         * @var Request  $request
         * @var Response $response
         */
        [$request, $response] = $params;
        // return; // QPS: 3.14w
        // $response->send(); // QPS: 2.43w
        // return;

        /* @var RequestHandler $requestHandler */
        $requestHandler = Container::$instance->getPrototype(RequestHandler::class);
        $requestHandler->initialize($this->requestMiddleware(), $this->defaultMiddleware);

        try {
            // Trigger before handle event
            \Swoft::trigger(HttpServerEvent::BEFORE_REQUEST, null, $request, $response);
            // $response->send();
            // return;
            // Begin handle request, return response
            $response = $requestHandler->handle($request);
        } catch (\Throwable $e) {
            echo json_encode($e);
            \printf(
                "HTTP Dispatch Error: %s\nAt %s %d\n",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }

        // Trigger after request
        \Swoft::trigger(HttpServerEvent::AFTER_REQUEST, null, $response);
    }

    /**
     * @return array
     */
    public function preMiddleware(): array
    {
        return [
            // RequestMiddleware::class
        ];
    }

    /**
     * @return array
     */
    public function afterMiddleware(): array
    {
        return [
            UserMiddleware::class,
            ValidatorMiddleware::class
        ];
    }
}