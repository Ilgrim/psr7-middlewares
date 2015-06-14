<?php
namespace Psr7Middlewares\Middleware;

use RuntimeException;
use Aura\Router\RouterContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuraRouter
{
    use RouterTrait;

    protected $router;

    /**
     * Creates an instance of this middleware
     * 
     * @param RouterContainer|callable $router
     * @param null|array  $extraArguments
     */
    public static function create($router, array $extraArguments = array())
    {
        return new static($router, $extraArguments);
    }

    /**
     * Constructor
     * You can specify the RouterContainer instance or a callable to fetch it in lazy mode
     *
     * @param RouterContainer|callable $router
     * @param array $extraArguments
     */
    public function __construct($router, $extraArguments)
    {
        $this->router = $router;
        $this->extraArguments = $extraArguments;
    }

    /**
     * Execute the middleware
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $router = $this->getRouter();
        $matcher = $router->getMatcher();
        $route = $matcher->match($request);

        if (!$route) {
            return $response->withStatus(404);
        }

        foreach ($route->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = self::executeTarget($route->handler, $request, $response);

        return $next($request, $response);
    }

    /**
     * Returns the route
     *
     * @throws RuntimeException If the route cannot be fetched
     * @return RouterContainer
     */
    protected function getRouter()
    {
        if (is_callable($this->router)) {
            $this->router = call_user_func($this->router);
        }

        if ($this->router instanceof RouterContainer) {
            return $this->router;
        }

        throw new RuntimeException('No RouterContainer instance has been provided');
    }
}