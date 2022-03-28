<?php declare(strict_types=1);

namespace Impressible\ImpressibleRoute;

use Psr\Container\ContainerInterface;

/**
 * A way to lazy load object instance for callables.
 *
 * Placeholder for an object instance up until a method is called.
 * Then it will create the object with the given recipe, and proxy
 * the method call to the created object.
 *
 * A useful helper to for defining Routes without pre-create
 * everything with the routes.
 */
class LazyLoadObject
{
    /**
     * The callable to produce the specified object.
     *
     * @var callable
     */
    private $recipe;

    /**
     * Class constructor.
     *
     * @param callable $recipe The callable to produce the specified object.
     */
    public function __construct(callable $recipe)
    {
        $this->recipe = $recipe;
    }

    /**
     * Create an ObjectPromise from a container and the
     * id to get object with.
     *
     * @return void
     */
    public static function fromContainer(ContainerInterface $container, string $id): LazyLoadObject
    {
        return new LazyLoadObject(fn() => $container->get($id));
    }

    /**
     * Magic method. Will be called when a method is called.
     * Will create the inner object with the given recipe,
     * then proxy the method call to it.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed|void
     */
    public function __call($name, $arguments)
    {
        $object = ($this->recipe)();
        return call_user_func_array([$object, $name], $arguments);
    }
}