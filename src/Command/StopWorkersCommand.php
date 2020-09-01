<?php

namespace AGluh\Bundle\OutboxBundle\Command;

use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnRestartSignalListener;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StopWorkersCommand extends Command
{
    protected static $defaultName = 'outbox:stop-workers';
    private CacheItemPoolInterface $restartSignalCachePool;

    public function __construct(CacheItemPoolInterface $restartSignalCachePool)
    {
        $this->restartSignalCachePool = $restartSignalCachePool;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([])
            ->setDescription('Stops workers after their current event');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        $cacheItem = $this->restartSignalCachePool->getItem(StopWorkerOnRestartSignalListener::RESTART_REQUESTED_TIMESTAMP_KEY);
        $cacheItem->set(microtime(true));
        $this->restartSignalCachePool->save($cacheItem);

        $io->success('Signal successfully sent to stop any running workers.');

        return 0;
    }
}
