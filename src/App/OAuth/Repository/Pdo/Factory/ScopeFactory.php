<?php


namespace App\OAuth\Repository\Pdo\Factory;

use App\OAuth\Repository\Pdo\Scope;
use Psr\Container\ContainerInterface;

final class ScopeFactory
{
    /**
     * @param ContainerInterface $container
     * @return Scope
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        return new Scope();
    }
}
