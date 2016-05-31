<?php

namespace Yokai\ManyToManyMatrixBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Yann Eugoné <yeugone@prestaconcept.net>
 */
class RegisterTemplateCompilerPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('twig.form.resources')) {
            return;
        }

        $template = 'YokaiManyToManyMatrixBundle::bootstrap_3_layout.html.twig';

        $resources = $container->getParameter('twig.form.resources');
        if (!in_array($template, $resources)) {
            $resources[] = $template;
        }

        $container->setParameter('twig.form.resources', $resources);
    }
}
