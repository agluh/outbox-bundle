<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Command;

use AGluh\Bundle\OutboxBundle\Command\PrunePublishedDomainEventsCommand;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PrunePublishedDomainEventsCommandTest extends TestCase
{
    public function test_execute(): void
    {
        $repository = $this->createMock(OutboxEventRepository::class);
        $repository->expects(self::once())->method('prunePublishedEvents');

        $command = new PrunePublishedDomainEventsCommand($repository);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }
}
