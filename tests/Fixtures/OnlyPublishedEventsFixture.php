<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Fixtures;

use AGluh\Bundle\OutboxBundle\Domain\Model\OutboxEvent;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class OnlyPublishedEventsFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 5; ++$i) {
            $event = new OutboxEvent(
                Uuid::uuid4(),
                'some data',
                new DateTimeImmutable(),
                new DateTimeImmutable()
            );

            $event->markAsPublishedAt(new DateTimeImmutable());

            $manager->persist($event);
        }

        $manager->flush();
    }
}
