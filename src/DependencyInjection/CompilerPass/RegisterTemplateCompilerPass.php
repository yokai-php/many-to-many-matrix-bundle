<?php

declare(strict_types=1);

namespace Yokai\ManyToManyMatrixBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Yann Eugoné <yeugone@prestaconcept.net>
 */
class RegisterTemplateCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('twig.form.resources')) {
            return;
        }

        $template = '@YokaiManyToManyMatrix/bootstrap_3_layout.html.twig';

        /** @var string[] $resources */
        $resources = $container->getParameter('twig.form.resources');
        if (!in_array($template, $resources, true)) {
            $resources[] = $template;
        }

        $container->setParameter('twig.form.resources', $resources);
    }
}
