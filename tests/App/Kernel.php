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

namespace Runroom\RedirectionBundle\Tests\App;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Runroom\RedirectionBundle\RunroomRedirectionBundle;
use Runroom\RedirectionBundle\Tests\App\Entity\Entity;
use Runroom\RedirectionBundle\Tests\App\Entity\WrongEntity;
use Sonata\AdminBundle\SonataAdminBundle;
use Sonata\AdminBundle\Twig\Extension\DeprecatedTextExtension;
use Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManager;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new DoctrineBundle(),
            new FrameworkBundle(),
            new KnpMenuBundle(),
            new SecurityBundle(),
            new SonataAdminBundle(),
            new SonataDoctrineORMAdminBundle(),
            new TwigBundle(),
            new ZenstruckFoundryBundle(),

            new RunroomRedirectionBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return $this->getBaseDir() . '/cache';
    }

    public function getLogDir(): string
    {
        return $this->getBaseDir() . '/log';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    /**
     * @todo: Simplify security configuration when dropping support for Symfony 4
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', [
            'test' => true,
            'router' => ['utf8' => true],
            'secret' => 'secret',
            'http_method_override' => false,
        ]);

        $securityConfig = [
            'firewalls' => ['main' => []],
        ];

        if (class_exists(AuthenticatorManager::class)) {
            $securityConfig['enable_authenticator_manager'] = true;
        } else {
            $securityConfig['firewalls']['main']['anonymous'] = true;
        }

        $container->loadFromExtension('security', $securityConfig);

        $container->loadFromExtension('doctrine', [
            'dbal' => ['url' => 'sqlite:///%kernel.cache_dir%/app.db', 'logging' => false],
            'orm' => [
                'auto_mapping' => true,
                'mappings' => [
                    'redirection' => [
                        'type' => 'annotation',
                        'dir' => '%kernel.project_dir%/Entity',
                        'prefix' => 'Runroom\RedirectionBundle\Tests\App\Entity',
                        'is_bundle' => false,
                    ],
                ],
            ],
        ]);

        $container->loadFromExtension('twig', [
            'exception_controller' => null,
            'strict_variables' => '%kernel.debug%',
        ]);

        $container->loadFromExtension('zenstruck_foundry', [
            'auto_refresh_proxies' => false,
        ]);

        if (class_exists(DeprecatedTextExtension::class)) {
            $container->loadFromExtension('sonata_admin', [
                'options' => [
                    'legacy_twig_text_extension' => false,
                ],
            ]);
        }

        $container->loadFromExtension('runroom_redirection', [
            'enable_automatic_redirections' => true,
            'automatic_redirections' => [
                Entity::class => [
                    'route' => 'route.entity',
                    'routeParameters' => ['slug' => 'slug'],
                ],
                WrongEntity::class => [
                    'route' => 'route.missing',
                    'routeParameters' => ['slug' => 'slug'],
                ],
            ],
        ]);
    }

    /**
     * @todo: Add typehint when dropping support for Symfony 4
     *
     * @psalm-suppress TooManyArguments
     *
     * @param RoutingConfigurator $routes
     */
    protected function configureRoutes($routes): void
    {
        if ($routes instanceof RoutingConfigurator) {
            $routes->add('route.entity', '/entity/{slug}')
                ->controller('controller');

            return;
        }

        $routes->add('/entity/{slug}', 'controller', 'route.entity');
    }

    private function getBaseDir(): string
    {
        return sys_get_temp_dir() . '/runroom-redirection-bundle/var';
    }
}
