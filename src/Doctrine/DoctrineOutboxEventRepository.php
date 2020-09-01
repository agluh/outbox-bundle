<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Doctrine;

use AGluh\Bundle\OutboxBundle\Doctrine\DBAL\Types\DateTimeImmutableMicrosecondsType;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEvent;
use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEventRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineOutboxEventRepository implements OutboxEventRepository
{
    private EntityManagerInterface $entityManager;
    private string $tableName;

    public function __construct(EntityManagerInterface $entityManager, string $tableName)
    {
        $this->entityManager = $entityManager;
        $this->tableName = $tableName;
    }

    public function append(OutboxEvent $event): void
    {
        $this->entityManager->persist($event);
        $classMetadata = $this->entityManager->getClassMetadata(OutboxEvent::class);
        $this->entityManager->getUnitOfWork()->computeChangeSet($classMetadata, $event);
    }

    public function getBy(string $id): ?OutboxEvent
    {
        return $this->entityManager->find(OutboxEvent::class, $id);
    }

    /**
     * @return array<OutboxEvent>
     */
    public function getNextUnpublishedEvents(DateTimeImmutable $now, int $limit): array
    {
        if (false === $this->entityManager->getConnection()->getSchemaManager()->tablesExist([$this->tableName])) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('oe')
            ->from(OutboxEvent::class, 'oe')
            ->where('oe.publishedAt IS NULL')
            ->andWhere('oe.toBePublishedAt <= :now')
            ->setParameter('now', $now, DateTimeImmutableMicrosecondsType::NAME)
            ->orderBy('oe.toBePublishedAt', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function save(OutboxEvent $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    public function prunePublishedEvents(): void
    {
        if (false === $this->entityManager->getConnection()->getSchemaManager()->tablesExist([$this->tableName])) {
            return;
        }

        $qb = $this->entityManager->createQueryBuilder();

        $qb->delete(OutboxEvent::class, 'oe')
            ->where('oe.publishedAt IS NOT NULL');

        $qb->getQuery()->getResult();
    }
}
