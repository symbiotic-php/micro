<?php

declare(strict_types=1);

namespace Symbiotic\Core;

use Symbiotic\Container\{ArrayAccessTrait,
    Container,
    DIContainerInterface,
    ServiceContainerTrait,
    SingletonTrait
};
use Symbiotic\Core\Bootstrap\{BootBootstrap, CoreBootstrap, ProvidersBootstrap};


class Core extends Container implements CoreInterface
{

    use ServiceContainerTrait,
        ArrayAccessTrait,
        SingletonTrait;

    /**
     * Class names Runners {@see \Symbiotic\Core\Runner}
     * @var string[]
     */
    protected array $runners = [];

    /**
     * @var string|null
     */
    protected ?string $base_path;

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected array $bootstraps = [];

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected array $last_bootstraps = [
        ProvidersBootstrap::class,
        BootBootstrap::class
    ];

    /**
     * It is used to load other scripts after the framework fails
     * @var \Closure[]|array
     * @used-by Core::runNext()
     */
    protected array $then = [];

    /**
     * Used after the successful operation of the framework
     * @var \Closure[]|array
     * @used-by Core::runComplete()
     * @see     Core::onComplete()
     */
    protected array $complete = [];

    /**
     * @var \CLosure[]|array
     */
    protected array $before_handle = [];


    public function __construct(array $config = [])
    {
        $this->dependencyInjectionContainer = static::$instance = $this;
        $this->instance(DIContainerInterface::class, $this);
        $this->instance(CoreInterface::class, $this);

        $this->instance('bootstrap_config', $config);
        $this->base_path = rtrim($config['base_path'] ?? __DIR__, '\\/');
        $this->runBootstrap(CoreBootstrap::class);
    }

    /**
     * @param string $class
     *
     * @return void
     */
    public function runBootstrap(string $class): void
    {
        (new $class())->bootstrap($this);
    }

    /**
     * @param string| string[] $bootstraps
     */
    public function addBootstraps(string|array $bootstraps): void
    {
        foreach ((array)$bootstraps as $v) {
            $this->bootstraps[] = $v;
        }
    }

    /**
     * @return void
     */
    public function bootstrap(): void
    {
        if (!$this->isBooted()) {
            foreach (($this->bootstraps + $this->last_bootstraps) as $class) {
                $this->runBootstrap($class);
            }
        }

        $this->booted = true;
    }

    /**
     * Determine if the application has booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * @param RunnerInterface $runner
     * @param int             $priority
     * @param string|null     $name
     *
     * @return void
     */
    public function addRunner(RunnerInterface $runner, int $priority = 1, string $name = null): void
    {
        if (is_null($name)) {
            $name = \get_class($runner);
        }
        /// todo: we need to make an addition with priority

        $this->runners[$name] = [$runner, $priority];
    }

    public function run(): void
    {
        uasort($this->runners, function ($a, $b) {
            return $a[1] <=> $b[1];
        });
        foreach (array_column($this->runners, 0) as $runner) {
            /**
             * @var RunnerInterface $runner
             */
            $runner = new $runner($this);
            if ($runner->isHandle()) {
                $result = $runner->run();
                if ($result) {
                    $this->runComplete();
                    exit;
                } else {
                    $this->runNext();
                }
                break;
            }
        }
    }

    /**
     * Shutdown event
     */
    public function runComplete(): void
    {
        foreach ($this->complete as $v) {
            $this->call($v);
        }
    }

    /**
     * Starts the scripts after the framework
     * @used-by run()
     */
    public function runNext(): void
    {
        foreach ($this->then as $v) {
            if ($this->call($v)) {
                return;
            }
        }
    }

    /**
     * @param \Closure $loader
     */
    public function beforeHandle(\Closure $loader): void
    {
        $this->before_handle[] = $loader;
    }

    /**
     * Event before the request is processed by the framework
     *
     * It can be used to connect files necessary for testing a controller or command
     * @used-by  \Symbiotic\Http\Kernel\HttpRunner::run()
     */
    public function runBefore(): void
    {
        foreach ($this->before_handle as $v) {
            $this->call($v);
        }
    }

    public function onComplete(\Closure $complete): void
    {
        $this->complete[] = $complete;
    }

    /**
     *  It is used to load other scripts after the framework fails
     *
     * the function must return true to break the chain after itself
     *
     * @param \Closure $loader
     */
    public function then(\Closure $loader): void
    {
        $this->then[] = $loader;
    }

    /**
     * Get the base path of the Laravel installation.
     *
     * @param string $path Optionally, a path to append to the base path
     *
     * @return string
     *
     * @todo:  The method is used once, is it needed?
     */
    public function getBasePath(string $path = ''): string
    {
        return $this->base_path . ($path ? \_S\DS . $path : $path);
    }

    public function __clone()
    {
        parent::__clone();
        $this->dependencyInjectionContainer = $this;
    }


}


