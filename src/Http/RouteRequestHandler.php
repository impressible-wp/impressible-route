<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A PSR-15 compatible kernel for applying middleware.
 */
class RouteRequestHandler implements RequestHandlerInterface
{
    /**
     * The route to handle request with.
     *
     * @var Route|null
     */
    private $route = null;

    /**
     * Class constructor
     *
     * @param Route $route
     */
    public function __construct(?Route $route = null)
    {
        $this->route = $route;
    }

    /**
     * Set the route of the kernel.
     *
     * @param Route $route
     *
     * @return $this
     */
    public function setRoute(Route $route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * Get the route of the kernel
     *
     * @return Route
     *
     * @throws \Exception if route is not set.
     */
    public function getRoute(): Route
    {
        if (empty($this->route)) {
            throw new \Exception('Route is not set to the Kernel');
        }
        return $this->route;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->getRoute()->getCallable()($request);
    }
}
