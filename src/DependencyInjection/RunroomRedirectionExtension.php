<?php

declare(strict_types=1);

/*
 * This file is part of the Runroom package.
 *
 * (c) Runroom <runroom@runroom.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Runroom\RedirectionBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * @TODO: Remove this if when Symfony 6 support is dropped
 */
if (!class_exists(Extension::class)) {
    class_alias(\Symfony\Component\HttpKernel\DependencyInjection\Extension::class, Extension::class);
}

final class RunroomRedirectionExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        if (isset($bundles['SonataAdminBundle'])) {
            $loader->load('admin.php');
        }

        if ($config['enable_automatic_redirections']) {
            $definition = $container->getDefinition('runroom.redirection.event_listener.automatic_redirect');

            $definition->replaceArgument('$configuration', $config['automatic_redirections']);
        }
    }
}
