<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Command;

use AGluh\Bundle\OutboxBundle\Command\PublishDomainEventsCommand;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use AGluh\Bundle\OutboxBundle\Serialization\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class PublishDomainEventsCommandTest extends TestCase
{
    public function test_basic_run(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->expects(self::once())->method('acquire')->willReturn(true);

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->expects(self::once())->method('createLock')->willReturn($lock);

        $repository = $this->createMock(OutboxEventRepository::class);
        $repository->expects(self::once())->method('getNextUnpublishedEvents')->willReturn([]);

        $serializer = $this->createMock(SerializerInterface::class);

        $command = new PublishDomainEventsCommand(new EventDispatcher(), $lockFactory, $repository, $serializer);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }
}
