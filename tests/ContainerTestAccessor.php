<?php

declare(strict_types=1);

namespace Yokai\ManyToManyMatrixBundle\Tests;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContainerTestAccessor extends KernelTestCase
{
    public static function container(): ContainerInterface
    {
        return self::bootKernel()->getContainer();
    }
}
