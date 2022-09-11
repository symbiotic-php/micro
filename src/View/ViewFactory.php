<?php

declare(strict_types=1);

namespace Symbiotic\View;

use Psr\Container\ContainerInterface;
use Symbiotic\Container\CloningContainer;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Core\Support\Str;
use Symbiotic\Packages\TemplatesRepositoryInterface;
use Symbiotic\Routing\RouteInterface;


class ViewFactory implements CloningContainer
{

    public function __construct(
        protected CoreInterface $container,
        protected TemplatesRepositoryInterface $templates
    ) {
    }

    /**
     * @param string      $path
     * @param array       $vars
     * @param string|null $app_id
     *
     * @return View
     * @throws \Symbiotic\Packages\ResourceExceptionInterface
     */
    public function make(string $path, array $vars = [], string $app_id = null): View
    {
        $container = $this->container;

        if (is_array(($sc = Str::sc($path)))) {
            $id = $sc[0];
            $path = $sc[1];
        } elseif (is_string($app_id)) {
            $id = $app_id;
        } else {
            /**
             * @var RouteInterface | null $route
             */
            $route = $container->get(RouteInterface::class, null);
            if (!$route || ($id = $route->getApp()) === null) {
                throw new ViewException('Route instance not found in Core container!');
            }
        }
        try {
            $template = $this->templates->getTemplate($id, $path);
        } catch (\Throwable $e) {
            throw new ViewException($e->getMessage(), $e->getCode(), $e);
        }
        return new View($template, $vars, $id, $this->container);
    }

    /**
     * @param ContainerInterface|null $container
     *
     * @return $this|null
     */
    public function cloneInstance(?ContainerInterface $container): ?static
    {
        $new = clone $this;
        $new->container = $container;
        return $new;
    }

}