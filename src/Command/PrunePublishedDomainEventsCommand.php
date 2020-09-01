<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Command;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PrunePublishedDomainEventsCommand extends Command
{
    protected static $defaultName = 'outbox:prune-published';
    private OutboxEventRepository $repository;

    public function __construct(OutboxEventRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([])
            ->setDescription('Prune published domain events');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        $this->repository->prunePublishedEvents();

        $io->success('Published domain events successfully pruned.');

        return 0;
    }
}
