<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Functional\Doctrine;

use AGluh\Bundle\OutboxBundle\Doctrine\DoctrineOutboxEventRepository;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEvent;
use AGluh\Bundle\OutboxBundle\OutboxBundle;
use AGluh\Bundle\OutboxBundle\Tests\Fixtures\OnlyPublishedEventsFixture;
use AGluh\Bundle\OutboxBundle\Tests\Fixtures\OnlyUnpublishedEventsFixture;
use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Nyholm\BundleTest\BaseBundleTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

class DoctrineOutboxEventRepositoryTest extends BaseBundleTestCase
{
    private const EVENT_ID = '2ffd5b4b-3f0f-48da-8a09-dd68b903b5f8';
    private const NOW = '2020-09-03 10:00:00.000001';
    private const NOW_PLUS_MS = '2020-09-03 10:00:00.000002';
    private const NOW_MINUS_MS = '2020-09-03 10:00:00.000000';

    private DoctrineOutboxEventRepository $repository;
    private ORMExecutor $executor;
    private Connection $connection;
    private string $tableName;

    public function setUp(): void
    {
        $kernel = $this->createKernel();

        $kernel->addConfigFile(dirname(__DIR__, 2).'/Fixtures/config.yml');

        $kernel->addBundle(FrameworkBundle::class);
        $kernel->addBundle(DoctrineBundle::class);
        $kernel->addBundle(DAMADoctrineTestBundle::class);
        $kernel->addBundle(DoctrineFixturesBundle::class);

        $this->bootKernel();

        $container = $this->getContainer();

        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.default_entity_manager');

        self::assertNotNull($em);

        $this->connection = $em->getConnection();

        $this->tableName = $container->getParameter('agluh_outbox_bundle.outbox_table_name');

        $this->repository = new DoctrineOutboxEventRepository($em, $this->tableName);

        $this->executor = new ORMExecutor($em, new ORMPurger());
    }

    public function test_saving_new_entity(): void
    {
        $now = new DateTimeImmutable(self::NOW);
        $expectedToBePublishedAt = new DateTimeImmutable(self::NOW_PLUS_MS);

        $event = $this->buildOutboxEvent($now, $expectedToBePublishedAt);
        $this->repository->save($event);

        $fetched = $this->repository->getBy($event->id());

        self::assertNotNull($fetched);
        self::assertInstanceOf(OutboxEvent::class, $fetched);
        self::assertSame($event, $fetched);

        /* @phpstan-ignore-next-line */
        self::assertNull($fetched->publicationDate());
    }

    public function test_getter_by_not_existed_id(): void
    {
        $fetched = $this->repository->getBy(self::EVENT_ID);

        self::assertNull($fetched);
    }

    public function test_loading_unpublished_events_with_publication_date_not_reached(): void
    {
        $now = new DateTimeImmutable(self::NOW);
        $expectedToBePublishedAt = new DateTimeImmutable(self::NOW_PLUS_MS);

        $event = $this->buildOutboxEvent($now, $expectedToBePublishedAt);
        $this->repository->save($event);

        self::assertCount(0, $this->repository->getNextUnpublishedEvents($now, 1));
    }

    public function test_loading_unpublished_events_with_publication_date_reached(): void
    {
        $now = new DateTimeImmutable(self::NOW);
        $expectedToBePublishedAt = new DateTimeImmutable(self::NOW_MINUS_MS);

        $event = $this->buildOutboxEvent($now, $expectedToBePublishedAt);
        $this->repository->save($event);

        self::assertCount(1, $this->repository->getNextUnpublishedEvents($now, 1));
    }

    public function test_should_not_load_published_events_with_publication_date_reached(): void
    {
        $now = new DateTimeImmutable(self::NOW);
        $expectedToBePublishedAt = new DateTimeImmutable(self::NOW_MINUS_MS);

        $event = $this->buildOutboxEvent($now, $expectedToBePublishedAt);
        $event->markAsPublishedAt($expectedToBePublishedAt);
        $this->repository->save($event);

        self::assertCount(0, $this->repository->getNextUnpublishedEvents($now, 1));
    }

    public function test_prune_operation_deletes_published_events(): void
    {
        $loader = new Loader();
        $loader->addFixture(new OnlyPublishedEventsFixture());
        $this->executor->execute($loader->getFixtures());

        self::assertEquals(5, $this->connection->fetchColumn('SELECT COUNT(*) FROM '.$this->tableName));

        $this->repository->prunePublishedEvents();

        self::assertEquals(0, $this->connection->fetchColumn('SELECT COUNT(*) FROM '.$this->tableName));
    }

    public function test_prune_operation_preserves_unpublished_events(): void
    {
        $loader = new Loader();
        $loader->addFixture(new OnlyUnpublishedEventsFixture());
        $this->executor->execute($loader->getFixtures());

        self::assertEquals(5, $this->connection->fetchColumn('SELECT COUNT(*) FROM '.$this->tableName));

        $this->repository->prunePublishedEvents();

        self::assertEquals(5, $this->connection->fetchColumn('SELECT COUNT(*) FROM '.$this->tableName));
    }

    public function test_fetching_unpublished_events_ordered_by_expected_publication_date(): void
    {
        $toPePublishedAtDates = [
            '2020-09-03 10:00:00.000001',
            '2020-09-03 10:00:00.000005',
            '2020-09-03 10:00:00.000003',
            '2020-09-03 10:00:00.000002',
            '2020-09-03 10:00:00.000004',
        ];

        $now = new DateTimeImmutable('2020-09-03 10:00:00.000006');

        foreach ($toPePublishedAtDates as $date) {
            $expectedToBePublishedAt = new DateTimeImmutable($date);

            $event = $this->buildOutboxEvent($now, $expectedToBePublishedAt);
            $this->repository->save($event);
        }

        $events = $this->repository->getNextUnpublishedEvents($now, 5);

        self::assertCount(5, $events);

        $format = 'Y-m-d H:i:s.u';

        self::assertEquals($toPePublishedAtDates[0], $events[0]->expectedPublicationDate()->format($format));
        self::assertEquals($toPePublishedAtDates[3], $events[1]->expectedPublicationDate()->format($format));
        self::assertEquals($toPePublishedAtDates[2], $events[2]->expectedPublicationDate()->format($format));
        self::assertEquals($toPePublishedAtDates[4], $events[3]->expectedPublicationDate()->format($format));
        self::assertEquals($toPePublishedAtDates[1], $events[4]->expectedPublicationDate()->format($format));
    }

    private function buildOutboxEvent(DateTimeImmutable $registeredAt, DateTimeImmutable $toBePublishedAt): OutboxEvent
    {
        return new OutboxEvent(
            Uuid::uuid4(),
            'some data',
            $registeredAt,
            $toBePublishedAt
        );
    }

    protected function getBundleClass(): string
    {
        return OutboxBundle::class;
    }
}
