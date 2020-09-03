<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Doctrine\EventListener;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEvent;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;

class ClassMetadataListener implements EventSubscriber
{
    private string $tableName;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return array<mixed>
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::loadClassMetadata,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (OutboxEvent::class === $classMetadata->getName()) {
            $classMetadata->setPrimaryTable([
                'name' => $this->tableName,
            ]);
        }
    }
}
