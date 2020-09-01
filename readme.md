# Outbox bundle
Implements [Outbox pattern](https://microservices.io/patterns/data/transactional-outbox.html) for DDD-based Symfony applications.

## How it works:
1. We collect domain events from aggregate being persisted and save them in a separate table within a single database transaction.
2. After a successful commit we perform publication for stored domain events.
    1. If bundle configured with `auto_publish=true` option, then domain events from outbox table are published using a Symfony event listener in the kernel.TERMINATE event.
    2. If bundle configured with `auto_publish=false` option, then you should use CLI interface described below to periodically run worker for publishing of stored events.

**Important note:** events are published on-by-one in order they are stored in outbox table during aggregate persistence. 
If for some reason during DomainEventEnqueuedForPublishingEvent handler domain event is not marked as published (i.e. publication date not set)
then next time outbox will try to publish *the same domain event* until it succeeded. This ensures time consistency of published domain events.

### Acknowledgments
Inspired by [Domain Event Bundle](https://github.com/headsnet/domain-events-bundle).
Worker class manly based on Worker from symfony/messenger component.

### Installation

_Requires Symfony 4.4, or Symfony 5.x_

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require agluh/outbox-bundle
```

#### Applications that don't use Symfony Flex

Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require agluh/outbox-bundle
```

Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    AGluh\Bundle\OutboxBundle\OutboxBundle::class => ['all' => true],
];
```

### Usage
Outbox bundle integrates into your Symfony application mostly by application events.
You should implement listeners for them as shown below.

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use AGluh\Bundle\OutboxBundle\Event\AggregateRootPreparedForPersistenceEvent;
use AGluh\Bundle\OutboxBundle\Event\DomainEventPreparedForPersistenceEvent;
use AGluh\Bundle\OutboxBundle\Event\DomainEventEnqueuedForPublishingEvent;
use AGluh\Bundle\OutboxBundle\Event\DomainEventPublishedEvent;

class OutboxIntegrationEventsListener implements EventSubscriberInterface
{
    /**
     * In this method you should collect domain events from your aggregate root
     * and than call $event->addDomainEvent() to persist them to outbox.
     */
    public function onAggregateRootPreparedForPersistence (AggregateRootPreparedForPersistenceEvent $event): void
    {
        $aggregateRoot = $event->aggregateRoot();
         
        /**
         * Example: DomainEventPublisher is a base class or an interface
         * of your aggregate.
         */
        if($aggregateRoot instanceof DomainEventPublisher) {
    
            /**
             * Here DomainEventPublisher::popEvents() is the method
             * that returns array of domain events for aggregate.
             */
            foreach ($aggregateRoot->popEvents() as $domainEvent) {
                $event->addDomainEvent($domainEvent);
            }
        }
    }

    /**
     * In this method you can alter date of expected publication for domain event.
     * By default it will be the date of registration of the event in outbox.
     */
    public function onDomainEventPreparedForPersistence(DomainEventPreparedForPersistenceEvent $event): void
    {
        $domainEvent = $event->domainEvent();
        
        /**
         * Here DomainEvent is an interface or base class for your domain event.
         */
        if($domainEvent instanceof DomainEvent) {
            
            /**
             * In this example we use event occurrence date as date of expected publication.
             */
            $event->changeExpectedPublicationDate($domainEvent->occurrenceDate());
        }
    }
    
    /**
     * This function will be called by Outbox bundle for each domain event should be published.
     */
    public function onOutboxEventEnqueuedForPublishing(DomainEventEnqueuedForPublishingEvent $event): void
    {
        // It makes sense to stop propagation for event
        $event->stopPropagation();

        $domainEvent = $event->domainEvent();

        // Do whatever you mention under 'publish event' here. For example, send to RabbitMQ.
        
        // You must set publication date here to mark event as published in outbox.
        $event->setPublicationDate(new \DateTimeImmutable());
    }
    
    /**
     * This function will be called after outbox persists domain event as published.
     */
    public function onDomainEventPublished(DomainEventPublishedEvent $event): void
    {
        // Do something if you want
    }


    public static function getSubscribedEvents(): array
    {
        return [
            AggregateRootPreparedForPersistenceEvent::class => 'onAggregateRootPreparedForPersistence',
            DomainEventPreparedForPersistenceEvent::class => 'onDomainEventPreparedForPersistence',
            DomainEventEnqueuedForPublishingEvent::class => 'onOutboxEventEnqueuedForPublishing',
            DomainEventPublishedEvent::class => 'onDomainEventPublished'
        ];
    }
}
```

### Console commands

#### Publish domain events
```bash
outbox:publish [options]

Options:
  -l, --limit=LIMIT                Limit the number of published events
  -m, --memory-limit=MEMORY-LIMIT  The memory limit the worker can consume
  -t, --time-limit=TIME-LIMIT      The time limit in seconds the worker can run
  -s, --sleep=SLEEP                Seconds to sleep before asking for new unpublished events after no unpublished events were found. Applicable only for demonized worker [default: 1]
  -d, --daemonize                  Daemonize worker
  -b, --batch-size=BATCH-SIZE      Limit the number of events worker can query at every iteration [default: 20]
  -h, --help                       Display this help message
  -q, --quiet                      Do not output any message
  -V, --version                    Display this application version
      --ansi                       Force ANSI output
      --no-ansi                    Disable ANSI output
  -n, --no-interaction             Do not ask any interactive question
  -e, --env=ENV                    The Environment name. [default: "dev"]
      --no-debug                   Switches off debug mode.
  -v|vv|vvv, --verbose             Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

The php process is not designed to work for a long time. So it has to quit periodically. Or, the command may exit because of error or exception. Something has to bring it back and continue events publication (if not used auto publication). You can use Supervisord for that. It starts processes and keep an eye on them while they are working.

Here an example of supervisord configuration. It runs four instances of outbox:publish command.

```ini
[program:outbox_worker]
command=/path/to/bin/console --env=prod --no-debug --time-limit=3600 outbox:publish
process_name=%(program_name)s_%(process_num)02d
numprocs=1 # There is no reason to use multiple workers here because of the locking
autostart=true
autorestart=true
startsecs=0
redirect_stderr=true
```

_Note: Pay attention to --time-limit it tells the command to exit after 60 minutes._

#### Prune published domain events
```bash
outbox:prune-published
```

#### Stop workers
```bash
outbox:stop-workers
```

### Default configuration
```yaml
agluh_outbox:
    table_name: outbox      # Name of outbox table for Doctrine mapping
    auto_publish: false     # Publish domain events on kernel.TERMINATE or console.TERMINATE
```