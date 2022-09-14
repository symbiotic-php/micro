<?php

declare(strict_types=1);

namespace Symbiotic\View;

use Symbiotic\Apps\ApplicationInterface;
use Symbiotic\Apps\AppsRepositoryInterface;
use Symbiotic\Core\CoreInterface;
use Symbiotic\Core\Support\RenderableInterface;
use Symbiotic\Core\Support\Str;
use Symbiotic\Core\SymbioticException;
use Symbiotic\Packages\ResourceExceptionInterface;
use Symbiotic\Packages\TemplatesRepositoryInterface;
use Symbiotic\Routing\RouteNotFoundException;
use Symbiotic\Routing\UrlGeneratorInterface;


class View implements RenderableInterface
{

    /**
     * All of the captured sections.
     *
     * @var array
     */
    public array $sections = [];

    /**
     * The last section on which injection was started.
     *
     * @var array
     */
    public array $last = [];

    /**
     * Current view Application
     *
     * @var ApplicationInterface|null
     */
    protected ?ApplicationInterface $application = null;

    /**
     * @var UrlGeneratorInterface|null
     */
    protected ?UrlGeneratorInterface $urlGenerator = null;

    /**
     * @param string        $template Compiled php template {@see TemplatesRepositoryInterface::getTemplate()}
     * @param array         $vars
     * @param string        $app_id
     * @param CoreInterface $container
     */
    public function __construct(
        protected string $template,
        protected array $vars,
        protected string $app_id,
        protected CoreInterface $container
    ) {
    }


    protected function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->urlGenerator ?: $this->urlGenerator = $this->container->get(UrlGeneratorInterface::class);
    }

    /**
     * @param string $path
     * @param bool   $absolute
     *
     * @return string
     *
     * @uses \Symbiotic\Routing\UrlGeneratorInterface::to()
     */
    public function url(string $path = '', bool $absolute = true): string
    {
        return $this->getUrlGenerator()->to($this->appendApp($path), $absolute);
    }

    /**
     * @param string $path
     * @param bool   $absolute
     *
     * @return string
     *
     * @uses \Symbiotic\Routing\UrlGeneratorInterface::asset()
     */
    public function asset(string $path = '', bool $absolute = true): string
    {
        return $this->getUrlGenerator()->asset($this->appendApp($path), $absolute);
    }

    /**
     * @param string $path
     * @param bool   $absolute
     *
     * @return string
     */
    public function css(string $path = '', bool $absolute = true): string
    {
        return '<link rel="stylesheet" href="' . $this->asset($path, $absolute) . '">';
    }

    /**
     * @param string $path
     * @param bool   $absolute
     * @param string $type
     *
     * @return string
     */
    public function js(string $path = '', bool $absolute = true, string $type = 'text/javascript'): string
    {
        return '<script type="' . $type . '" src="' . $this->asset($path, $absolute) . '"></script>';
    }

    /**
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     *
     * @return string
     *
     * @throws RouteNotFoundException
     * @uses \Symbiotic\Routing\UrlGeneratorInterface::route()
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        return $this->getUrlGenerator()->route($this->appendApp($name), $parameters, $absolute);
    }

    /**
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     *
     * @return string
     *
     * @throws RouteNotFoundException
     */
    public function adminRoute(string $name, array $parameters = [], bool $absolute = true): string
    {
        return $this->getUrlGenerator()->adminRoute($this->appendApp($name), $parameters, $absolute);
    }

    /**
     * @param string $name
     * @param array  $parameters
     * @param bool   $absolute
     *
     * @return string
     * @throws RouteNotFoundException
     */
    public function apiRoute(string $name, array $parameters = [], bool $absolute = true): string
    {
        return $this->getUrlGenerator()->apiRoute($this->appendApp($name), $parameters, $absolute);
    }


    /**
     * @param string     $message
     * @param array|null $vars
     * @param null       $lang
     *
     * @return string
     * @throws \Exception
     * @uses \_S\lang()
     */
    public function lang(string $message, array $vars = null, $lang = null): string
    {
        /* @deprecated static access */
        return \_S\lang($this->container, $this->appendApp($message), $vars, $lang);
    }


    /**
     * @param string|array $handler \My\CLass@methodName or ['\My\Class', 'methodName'] array for call_user_func
     * @param null         $app_id
     *
     * @return string|array|View|mixed
     */
    public function action(string|array $handler, $app_id = null): mixed
    {
        return ($app_id ? $this->container[AppsRepositoryInterface::class]->getBootedApp(
            $app_id
        ) : $this->container)->call($handler);
    }

    /**
     * @param string $path
     * @param array  $vars
     * @param bool   $inline
     *
     * @return mixed
     */
    public function include(string $path, array $vars = [], bool $inline = false): View
    {
        return $this->container[ViewFactory::class]
            ->make($this->appendApp($path), $vars)
            ->with($inline ? array_merge($this->vars, $vars) : $vars);
    }

    /**
     * @param       $abstract
     * @param array $parameters
     *
     * @return mixed|ApplicationInterface|null
     *
     * @throws
     */
    public function app($abstract = null, array $parameters = []): mixed
    {
        if (!$this->application) {
            $apps = $this->container->get(AppsRepositoryInterface::class);
            /**
             * @var AppsRepositoryInterface $apps
             */
            if (!$apps->has($this->app_id)) {
                throw new ViewException('Not exists App [' . $this->app_id . ']');
            }
            $this->application = $apps->getBootedApp($this->app_id);
        }

        return is_null($abstract) ? $this->application : $this->application->make($abstract, $parameters);
    }

    /**
     * @param string|null $abstract
     * @param array       $parameters
     *
     * @return mixed
     * @throws
     */
    public function core(string $abstract = null, array $parameters = []): mixed
    {
        return is_null($abstract) ? $this->container : $this->container->make($abstract, $parameters);
    }

    /**
     * Run event
     *
     * @param object $event
     *
     * @return object $event
     */
    public function event(object $event): object
    {
        /**
         * @uses \Symbiotic\Event\EventDispatcher::dispatch()
         */
        return $this->container->get('events')->dispatch($event);
    }

    /**
     * Package id
     *
     * @return string
     */
    public function getAppId(): string
    {
        return $this->app_id;
    }

    /**
     * Stop injecting content into a section and return its contents.
     *
     * @return false|string
     *
     * @throws \Exception
     */
    public function yield_section(): false|string
    {
        return $this->fetch($this->stop());
    }

    /**
     * @param string|\Closure|RenderableInterface|null $content
     *
     * @return string
     *
     * @throws ResourceExceptionInterface|SymbioticException
     */
    public function fetch(string|\Closure|RenderableInterface $content = null): string
    {
        if (null === $content) {
            $content = $this;
        }
        ob_start();
        if (is_callable($content)) {
            $content();
        } elseif ($content instanceof RenderableInterface) {
            $content->render();
        } else {
            echo $content;
        }
        return ob_get_clean();
    }

    /**
     * Stop injecting content into a section.
     *
     * @return string
     */
    public function stop(): string
    {
        $this->extend($last = array_pop($this->last), ob_get_clean());

        return $last;
    }

    /**
     * Extend the content in a given section.
     *
     * @param string                     $section
     * @param string|RenderableInterface $content
     *
     * @return void
     */
    protected function extend(string $section, string|RenderableInterface $content): void
    {
        if (isset($this->sections[$section])) {
            $this->sections[$section] =
                ($content instanceof RenderableInterface) ?
                    function () use ($content) {
                        $content->render();// че это такое??????
                    }
                    : str_replace('@parent', $content, $this->sections[$section]);
        } else {
            $this->sections[$section] = $content;
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function render(): void
    {
        if (!empty($this->template)) {
            extract($this->vars);
            try {
               eval($this->getTemplate());
            } catch (\Throwable|\Error $e) {
                throw new ViewException(
                    $e->getMessage() . PHP_EOL . \htmlspecialchars($this->template), $e->getCode(), $e
                );
            }
        }
    }

    /**
     * @return string
     */
    protected function getTemplate(): string
    {
        /*  return 'use function ' . __NAMESPACE__ . '\\{app,asset,route,css,js,adminRoute,apiRoute,action,render,lang};' . PHP_EOL . ' ?>'
              . $this->template;*/
        return ' ?>' . $this->template;
    }

    /**
     * Append content to a given section.
     *
     * @param string $section
     * @param string $content
     *
     * @return void
     */
    public function append(string $section, string $content): void
    {
        if (isset($this->sections[$section])) {
            $this->sections[$section] .= $content;
        } else {
            $this->sections[$section] = $content;
        }
    }

    /**
     * Get the string contents of a section.
     *
     * @param string $section
     *
     * @return void
     *
     */
    public function yield(string $section): void
    {
        if (isset($this->sections[$section])) {
            $section = $this->sections[$section];
            if (is_callable($section)) {
                $section();
            } elseif ($section instanceof View) {
                $section->setSections($this->sections);
                $section->render();
            } elseif ($section instanceof RenderableInterface) {
                $section->render();
            } else {
                echo $section;
            }
        }
    }

    /**
     * @param array $sections
     *
     * @return void
     */
    public function setSections(array $sections): void
    {
        $this->sections = $sections;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    /**
     * Специальный метод для передачи шаблона в слой
     *
     * @param string      $path
     * @param string|null $content_template
     * @param array       $vars
     * @param bool        $before
     *
     * @return static
     * @throws
     */
    public function layout(
        string $path,
        string $content_template = null,
        array $vars = [],
        bool $before = false
    ): static {
        $app_id = $this->app_id;
        if (is_array(($sc = Str::sc($path)))) {
            $app_id = $sc[0];
            $path = $sc[1];
        }
        if (!is_null($content_template)) {
            $this->template = $content_template;
        }

        $view = $this->container->get(ViewFactory::class)->make($path, $vars, $app_id);
        if ($before) {
            $content = $this->fetch($this);
            $sections = $this->sections;
            $sections['content'] = $content;
            $view->setSections($sections);
        } else {
            $view->inject('content', $this);
        }

        return $view;
    }

    /**
     * Inject inline content into a section.
     *
     * This is helpful for injecting simple strings such as page titles.
     *
     * <code>
     *        // Inject inline content into the "header" section
     *        Section::inject('header', '<title>Symbiotic</title>');
     * </code>
     *
     * @param string $section
     * @param string $content
     *
     * @return void
     */
    public function inject(string $section, string $content): void
    {
        $this->start($section, $content);
    }

    /**
     * Start injecting content into a section.
     *
     * <code>
     *        // Start injecting into the "header" section
     *        Section::start('header');
     *
     *        // Inject a raw string into the "header" section without buffering
     *        Section::start('header', '<title>Symbiotic</title>');
     * </code>
     *
     * @param string               $section
     * @param \Closure|string|null $content
     *
     * @return void
     */
    public function start(string $section, \Closure|string $content = null): void
    {
        if ($content === null) {
            ob_start() and $this->last[] = $section;
        } else {
            $this->extend($section, $content);
        }
    }

    /**
     * @param array $vars
     *
     * @return $this
     */
    public function with(array $vars): static
    {
        $this->vars = $vars;

        return $this;
    }

    protected function appendApp(string $path): string
    {
        if (!is_array(Str::sc($path))) {
            $path = $this->app_id . '::' . $path;
        }

        return $path;
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->fetch($this);
    }

}