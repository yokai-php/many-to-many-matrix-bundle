<?php

namespace Yokai\ManyToManyMatrixBundle;

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Yokai\ManyToManyMatrixBundle\DependencyInjection\CompilerPass\RegisterTemplateCompilerPass;
use Yokai\ManyToManyMatrixBundle\DependencyInjection\YokaiManyToManyMatrixExtension;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class YokaiManyToManyMatrixBundle extends Bundle
{
    /**
     * @inheritDoc
     */
    public function build(ContainerBuilder $container)
    {
        $container
            ->addCompilerPass(new RegisterTemplateCompilerPass())
        ;
    }

    /**
     * @inheritDoc
     */
    public function getContainerExtension()
    {
        return new YokaiManyToManyMatrixExtension();
    }

    /**
     * @inheritDoc
     */
    public function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return __DIR__;
    }

    /**
     * @inheritDoc
     */
    public function registerCommands(Application $application)
    {
        return;
    }
}
