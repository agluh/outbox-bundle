<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Command;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnEventLimitListener;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnMemoryLimitListener;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnTimeLimitListener;
use AGluh\Bundle\OutboxBundle\Relay\Worker;
use AGluh\Bundle\OutboxBundle\Serialization\SerializerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;

class PublishDomainEventsCommand extends Command
{
    protected static $defaultName = 'outbox:publish';
    private EventDispatcherInterface $eventDispatcher;
    private LockFactory $lockFactory;
    private OutboxEventRepository $repository;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LockFactory $lockFactory,
        OutboxEventRepository $repository,
        SerializerInterface $serializer,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct();

        $this->eventDispatcher = $eventDispatcher;
        $this->lockFactory = $lockFactory;
        $this->repository = $repository;
        $this->serializer = $serializer;
        $this->logger = $logger ?? new NullLogger();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limit the number of published events'),
                new InputOption('memory-limit', 'm', InputOption::VALUE_REQUIRED, 'The memory limit the worker can consume'),
                new InputOption('time-limit', 't', InputOption::VALUE_REQUIRED, 'The time limit in seconds the worker can run'),
                new InputOption('sleep', 's', InputOption::VALUE_REQUIRED, 'Seconds to sleep before asking for new unpublished events after no unpublished events were found. Applicable only for demonized worker', 1),
                new InputOption('daemonize', 'd', InputOption::VALUE_NONE, 'Daemonize worker', null),
                new InputOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Limit the number of events worker can query at every iteration', 20),
            ])
            ->setDescription('Publish domain events');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopsWhen = [];

        $limit = $input->getOption('limit');
        if (null !== $limit && is_numeric($limit)) {
            $stopsWhen[] = "published {$limit} events";
            $this->eventDispatcher->addSubscriber(new StopWorkerOnEventLimitListener((int) $limit, $this->logger));
        }

        $memoryLimit = $input->getOption('memory-limit');
        if (null !== $memoryLimit && is_string($memoryLimit)) {
            $stopsWhen[] = "exceeded {$memoryLimit} of memory";
            $this->eventDispatcher->addSubscriber(new StopWorkerOnMemoryLimitListener($this->convertToBytes($memoryLimit), $this->logger));
        }

        $timeLimit = $input->getOption('time-limit');
        if (null !== $timeLimit && is_numeric($timeLimit)) {
            $stopsWhen[] = "been running for {$timeLimit}s";
            $this->eventDispatcher->addSubscriber(new StopWorkerOnTimeLimitListener((int) $timeLimit, $this->logger));
        }

        $stopsWhen[] = 'received a stop signal via the outbox:stop-workers command';

        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);
        $io->success('Publishing domain events.');

        $last = array_pop($stopsWhen);
        $stopsWhen = ($stopsWhen ? implode(', ', $stopsWhen).' or ' : '').$last;
        $io->comment("The worker will automatically exit once it has {$stopsWhen}.");

        $io->comment('Quit the worker with CONTROL-C.');

        if (OutputInterface::VERBOSITY_VERBOSE > $output->getVerbosity()) {
            $io->comment('Re-run the command with a -vv option to see logs about published events.');
        }

        if ($input->getOption('daemonize')) {
            $io->comment('Ran as daemon');
        }

        $options = [
            'daemonize' => $input->getOption('daemonize'),
            'batchSize' => $input->getOption('batch-size'),
        ];

        $sleep = $input->getOption('sleep');
        if (null !== $sleep && is_numeric($sleep)) {
            $options['sleep'] = ((int) $sleep) * 1000000;
        }

        $worker = new Worker($this->eventDispatcher, $this->lockFactory, $this->repository, $this->serializer, $this->logger);
        $worker->run($options);

        return 0;
    }

    private function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = strtolower($memoryLimit);
        $max = strtolower(ltrim($memoryLimit, '+'));
        if (0 === strpos($max, '0x')) {
            $max = \intval($max, 16);
        } elseif (0 === strpos($max, '0')) {
            $max = \intval($max, 8);
        } else {
            $max = (int) $max;
        }

        switch (substr(rtrim($memoryLimit, 'b'), -1)) {
            case 't': $max *= 1024;
            // no break
            case 'g': $max *= 1024;
            // no break
            case 'm': $max *= 1024;
            // no break
            case 'k': $max *= 1024;
        }

        return $max;
    }
}
