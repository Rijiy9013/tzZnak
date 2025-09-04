<?php
declare(strict_types=1);

namespace App\Console;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

final class ConsoleKernel
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array              $commands
    )
    {
    }

    public function create(): Application
    {
        $app = new Application('Catalog CLI', '1.0.0');
        foreach ($this->commands as $class) {
            $cmd = $this->container->get($class);
            $app->add($cmd);
        }
        return $app;
    }
}
