<?php

namespace App\Tools;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Nette\Application\ApplicationException;
use Nette\DI;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Tracy\Debugger;

/**
 * Class Script
 *
 * @package Tools
 */
abstract class Script
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * @var array<string>
     */
    private $arguments = [];

    /**
     * @var string
     */
    private $name;

    /**
     * All autorun methods should begins with do, doImportProduct ect.
     */
    private const METHOD_PREFIX = 'do';

    /**
     * @var string
     */
    private $originLogDirectory;

    /**
     * @var string
     */
    private $baseDir;

    /**
     * @var \Nette\DI\Container
     */
    protected $container;

    /**
     * @var bool
     */
    private $isTerminated = false;

    /**
     * Setup everything before dos
     *
     * @return void
     */
    public function setUp(): void
    {
        // before dos
        return;
    }

    /**
     * Tear down everything after dos
     *
     * @return void
     */
    public function tearDown(): void
    {
        // after dos
        return;
    }

    /**
     * Write message to current output and format by output
     *
     * @param string $message Message to print out
     * @throws \Nette\Application\ApplicationException
     * @return void
     */
    public function write(string $message): void
    {
        if ($this->io) {
            $this->getIO()->write($message);
        } else {
            echo $message . ($this->isCli() ? "\n" : '<br>');
            \ob_flush();
            \flush();
        }

        return;
    }

    /**
     * Write message to current output and format by output
     *
     * @param string $message Message to print out
     * @throws \Nette\Application\ApplicationException
     * @return void
     */
    public function writeError(string $message): void
    {
        if ($this->io) {
            $this->getIO()->writeError("Chyba: $message");
        } else {
            echo "Chyba: $message" . ($this->isCli() ? "\n" : '<br>');
            \ob_flush();
            \flush();
        }

        return;
    }

    /**
     * Return IOInterface from composer
     *
     * @throws \Nette\Application\ApplicationException
     * @return \Composer\IO\IOInterface
     */
    public function getIO(): IOInterface
    {
        if (!$this->io) {
            throw new ApplicationException('There is no IO for Asking input');
        }

        return $this->io;
    }

    /**
     * Setting IO interface
     *
     * @param \Composer\IO\IOInterface $io
     */
    public function setIO(IOInterface $io): void
    {
        $this->io = $io;
    }

    /**
     * Setting arguments
     *
     * @param string[] $args
     */
    public function setArguments(array $args): void
    {
        $this->arguments = $args;
    }

    /**
     * Get arguments
     *
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get project name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set project name
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Return project base dir
     *
     * @return string
     */
    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * Setting base directory of project
     *
     * @param string $baseDir
     * @return void
     */
    public function setBaseDir(string $baseDir): void
    {
         $this->baseDir = $baseDir;
    }

    /**
     * Returns if cli mode
     *
     * @return bool
     */
    protected function isCli(): bool
    {
        return \PHP_SAPI === 'cli';
    }

    /**
     * Tells if runs from composer cli
     *
     * @return bool
     */
    protected function isComposerCli(): bool
    {
        return $this->io !== null;
    }

    /**
     * Set log directory
     *
     * @param $directory
     */
    public function setLogDirectory(string $directory): void
    {
        $this->originLogDirectory = Debugger::$logDirectory;
        FileSystem::createDir($directory);
        Debugger::$logDirectory = $directory;
    }

    /**
     * Restore log directory to default
     */
    public function restoreLogDirectory(): void
    {
        Debugger::$logDirectory = $this->originLogDirectory;
    }

    /**
     * Setting container
     *
     * @param \Nette\DI\Container $container
     */
    public function setContainer(DI\Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Get contianer
     *
     * @return \Nette\DI\Container
     */
    public function getContainer(): DI\Container
    {
        return $this->container;
    }

    /**
     * Trigger as event from composer
     *
     * @param \Composer\Script\Event $event Composer event
     * @return void
     */
    public static function fire(Event $event): void
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $container = include $vendorDir . '/../app/bootstrap.php';
        $args = $event->getArguments();
        $name = $event->getName();
        $event->getIO()->write("Fire: $name");

        static::run($container, $args, $event->getIO());
    }

    /**
     * Create and return scripts if exists
     *
     * @param string $class
     * @param string[] $arguments
     * @throws \Nette\Application\ApplicationException
     * @return \App\Tools\Script
     */
    public function getScript(string $class, array $arguments): Script
    {
        $script = static::createScript($this->container, $class, $arguments);

        if ($this->isComposerCli()) {
            $script->setIO($this->getIO());
        }

        return $script;
    }

    /**
     * Terminate scripts
     */
    public function terminate(): void
    {
        $this->isTerminated = true;
    }

    /**
     * Is terminated?
     *
     * @return bool
     */
    public function isTerminated(): bool
    {
        return $this->isTerminated;
    }

    /**
     * Create script
     *
     * @param \Nette\DI\Container $container
     * @param string $class
     * @param string[] $arguments
     * @return \App\Tools\Script
     */
    protected static function createScript(DI\Container $container, string $class, array $arguments): Script
    {
        $script = $container->createInstance($class);
        $name = \str_replace('\\', '_', Strings::lower(\preg_replace('/(?<!^)[A-Z]/', '_$0', $class)));

        $script->setName($name);
        $script->setContainer($container);
        $script->setArguments($arguments);
        $script->setBaseDir($container->parameters['baseDir']);

        return $script;
    }

    /**
     * Run script run
     *
     * @param \Nette\DI\Container $container Main container
     * @param string[] $arguments Arguments to run
     * @param \Composer\IO\IOInterface|null $io IO from composer
     * @return void
     */
    public static function run(DI\Container $container, array $arguments, ?IOInterface $io = null): void
    {
        $script = static::createScript($container, static::class, $arguments);
        $name = $script->getName();

        if ($io) {
            $script->setIO($io);
        }

        $script->setLogDirectory(Debugger::$logDirectory . "/$name");
        $start = \microtime(true);
        $script->setUp();

        foreach (\get_class_methods(static::class) as $method) {
            if (\substr($method, 0, 2) !== self::METHOD_PREFIX) {
                continue;
            }

            \call_user_func_array([$script, $method], []);

            if ($script->isTerminated()) {
                break;
            }
        }

        $script->tearDown();
        $totalTime = \microtime(true) - $start;

        Debugger::log('finished in time: ' . \round($totalTime, 4) . 's');
        $script->restoreLogDirectory();
    }
}
