<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\EventListener;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use AGluh\Bundle\OutboxBundle\Event\WorkerRunningEvent;
use AGluh\Bundle\OutboxBundle\Event\WorkerStartedEvent;
use AGluh\Bundle\OutboxBundle\Event\WorkerStoppedEvent;
use AGluh\Bundle\OutboxBundle\EventListener\KernelTerminateEventListener;
use AGluh\Bundle\OutboxBundle\Serialization\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class KernelTerminateEventListenerTest extends TestCase
{
    private KernelTerminateEventListener $eventListener;

    protected function setUp(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(true);

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $repository = $this->createMock(OutboxEventRepository::class);
        $repository->method('getNextUnpublishedEvents')->willReturn([]);

        $serializer = $this->createMock(SerializerInterface::class);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(WorkerStartedEvent::class)],
                [self::isInstanceOf(WorkerStoppedEvent::class)]
            )->willReturnCallback(static function ($event) {
                if ($event instanceof WorkerRunningEvent) {
                    $event->worker()->stop();
                }

                return $event;
            });

        $this->eventListener = new KernelTerminateEventListener($eventDispatcher, $lockFactory, $repository, $serializer);
    }

    public function test_console_terminate_event_handler(): void
    {
        $command = $this->createMock(Command::class);
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $event = new ConsoleTerminateEvent($command, $input, $output, 0);

        $this->eventListener->onConsoleTerminate($event);
    }

    public function test_kernel_terminate_event_handler(): void
    {
        $kernel = $this->createMock(Kernel::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $event = new TerminateEvent($kernel, $request, $response);

        $this->eventListener->onTerminate($event);
    }
}
