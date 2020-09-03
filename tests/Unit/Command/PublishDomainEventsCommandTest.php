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

    public function test_run_with_options(): void
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
        $tester->execute([
            '--limit' => 5,
            '--time-limit' => 60,
            '--memory-limit' => '20M',
            '--sleep' => 5,
        ]);
    }

    public function test_run_as_daemon(): void
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(true);

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);

        $repository = $this->createMock(OutboxEventRepository::class);
        $repository->method('getNextUnpublishedEvents')->willReturn([]);

        $serializer = $this->createMock(SerializerInterface::class);

        $command = new PublishDomainEventsCommand(new EventDispatcher(), $lockFactory, $repository, $serializer);

        $tester = new CommandTester($command);
        $tester->execute([
            '--time-limit' => 5,
            '--daemonize' => '1',
        ]);

        self::assertStringContainsString('Ran as daemon', $tester->getDisplay());
    }

    /**
     * @dataProvider getBytesConversionTestData
     */
    public function test_bytes_conversion(string $limit, int $bytes): void
    {
        $command = (new \ReflectionClass(PublishDomainEventsCommand::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(PublishDomainEventsCommand::class, 'convertToBytes');
        $method->setAccessible(true);
        self::assertEquals($bytes, $method->invoke($command, $limit));
    }

    /**
     * @return array<mixed>
     */
    public function getBytesConversionTestData(): array
    {
        return [
            ['2k', 2048],
            ['2 k', 2048],
            ['8m', 8 * 1024 * 1024],
            ['+2 k', 2048],
            ['+2???k', 2048],
            ['0x10', 16],
            ['0xf', 15],
            ['010', 8],
            ['+0x10 k', 16 * 1024],
            ['1g', 1024 * 1024 * 1024],
            ['1G', 1024 * 1024 * 1024],
            ['-1', -1],
            ['0', 0],
            ['2mk', 2048], // the unit must be the last char, so in this case 'k', not 'm'
        ];
    }
}
