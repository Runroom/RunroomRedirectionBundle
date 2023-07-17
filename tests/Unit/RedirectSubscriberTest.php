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

namespace Runroom\RedirectionBundle\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runroom\RedirectionBundle\EventSubscriber\RedirectSubscriber;
use Runroom\RedirectionBundle\Repository\RedirectRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

final class RedirectSubscriberTest extends TestCase
{
    private MockObject&RedirectRepositoryInterface $repository;
    private RedirectSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RedirectRepositoryInterface::class);

        $this->subscriber = new RedirectSubscriber($this->repository);
    }

    public function testItSubscribesToKernelRequestEvent(): void
    {
        static::assertArrayHasKey(KernelEvents::REQUEST, RedirectSubscriber::getSubscribedEvents());
    }

    public function testItDoesNotDoAnythingIfTheRequestIsNotTheMasterOne(): void
    {
        $event = $this->getResponseEvent(HttpKernelInterface::SUB_REQUEST);

        $this->subscriber->onKernelRequest($event);

        static::assertNull($event->getResponse());
    }

    public function testItDoesNotDOAnythingIfTheRouteIsNotFoundOnTheRepository(): void
    {
        $this->repository->expects(static::once())->method('findRedirect')->with('/');

        $event = $this->getResponseEvent();

        $this->subscriber->onKernelRequest($event);

        static::assertNull($event->getResponse());
    }

    public function testItDoesARedirectToDestinationUrl(): void
    {
        $this->repository->expects(static::once())->method('findRedirect')->with('/')->willReturn([
            'destination' => '/redirect',
            'httpCode' => 301,
        ]);

        $event = $this->getResponseEvent();

        $this->subscriber->onKernelRequest($event);

        /**
         * @var RedirectResponse
         */
        $response = $event->getResponse();

        static::assertSame('/redirect', $response->getTargetUrl());
        static::assertSame(301, $response->getStatusCode());
    }

    private function getResponseEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createStub(Kernel::class), new Request(), $requestType);
    }
}
