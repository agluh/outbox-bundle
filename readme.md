# Outbox bundle
[![Build Status](https://travis-ci.com/agluh/outbox-bundle.svg?branch=master)](https://travis-ci.com/agluh/outbox-bundle)
[![Latest Stable Version](https://poser.pugx.org/agluh/outbox-bundle/v)](//packagist.org/packages/agluh/outbox-bundle)
[![Total Downloads](https://poser.pugx.org/agluh/outbox-bundle/downloads)](//packagist.org/packages/agluh/outbox-bundle)
[![License](https://poser.pugx.org/agluh/outbox-bundle/license)](//packagist.org/packages/agluh/outbox-bundle)


Implements [Outbox pattern](https://microservices.io/patterns/data/transactional-outbox.html) for DDD-based Symfony applications.

### How it works:
1. Bundle collects domain events from aggregate being persisted and save them in a separate table within a single database transaction.
2. After a successful commit those domain events are enqueued for publication.
    1. If bundle configured with `auto_publish=true` option, then domain events from outbox table will be processed using a Symfony event listener in the kernel.TERMINATE or console.TERMINATE events.
    2. If bundle configured with `auto_publish=false` option, then you should use CLI interface described below to periodically run worker for processing of stored events.

**Important note:** events are enqueued for publishing on-by-one in ascending order sorted by expected publication date (witch by default is date of registration domain event in outbox). 
If for some reason on _DomainEventEnqueuedForPublishingEvent_ you did not marked domain event as published (i.e. publication date not set)
then next time outbox will try to enqueue for publishing *the same domain event* until it succeeded. This ensures time consistency of published domain events.

**Note:** you can combine auto publishing with CLI-based publication at the same time. Locking mechanism ensure all events will be published in the right order.

### Acknowledgments
Inspired by [Domain Event Bundle](https://github.com/headsnet/domain-events-bundle).
Worker class mainly based on Worker from symfony/messenger component.

### Installation

_Requires Symfony 4.4, or Symfony 5.x and PHP 7.4 and above_

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
                /**
                 * Basically domain event can be any PHP type that supports serialization.
                 * See Serialization section below in docs.
                 */
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
     * This function will be called by outbox bundle for each domain event should be published.
     */
    public function onDomainEventEnqueuedForPublishing(DomainEventEnqueuedForPublishingEvent $event): void
    {
        // It makes sense to stop propagation for event
        $event->stopPropagation();

        $domainEvent = $event->domainEvent();

        // Do whatever you mention under 'publish event' here. For example, send message to RabbitMQ.
        
        // You MUST set publication date here to mark event as published in outbox table.
        $event->setPublicationDate(new \DateTimeImmutable());
    }
    
    /**
     * This function will be called after outbox bundle persists domain event as published.
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
            DomainEventEnqueuedForPublishingEvent::class => 'onDomainEventEnqueuedForPublishing',
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

Here an example of supervisord configuration.

```ini
[program:outbox_worker]
command=/path/to/bin/console outbox:publish --env=prod --no-debug --time-limit=3600 --daemonize
process_name=%(program_name)s_%(process_num)02d
numprocs=1 # There is no reason to use multiple workers here because of locking nature of events publication process
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

### Custom serializer

By default, outbox bundle uses serialize/unserialize functions from PHP to convert domain event to a string during persistence.
You can use a custom serializer for that purpose. For example, how to use symfony/serializer is shown below.

```php
namespace App\Service;

use AGluh\Bundle\OutboxBundle\Serialization\SerializerInterface;
use AGluh\Bundle\OutboxBundle\Exception\DomainEventDecodingFailedException;

class CustomSerializer implements SerializerInterface
{
    private \Symfony\Component\Serializer\SerializerInterface $serializer;
    
    // Constructor skipped

    public function encode($domainEvent): string
    {
        return $this->serializer->serialize($domainEvent, 'json');
    }
    
    /**
     * @throws DomainEventDecodingFailedException
     */
    public function decode(string $data)
    {
        // In this example we don't convert json back to an object and simply use it further
        return $data;
    }
}
```

Then register new serializer as a service.

```yaml
# config\services.yaml
services:
    agluh_outbox_bundle.serializer:
        class: App\Service\CustomSerializer

```

### Default configuration
```yaml
agluh_outbox:
    table_name: outbox      # Name of outbox table for Doctrine mapping
    auto_publish: false     # Publish domain events on kernel.TERMINATE or console.TERMINATE
```

### Contributing

Contributions are welcome. Composer scripts are configured for your convenience:

```
> composer test       # Run test suite (you should set accessible MySQL server with DATABASE_URL env variable)
> composer cs         # Run coding standards checks
> composer cs-fix     # Fix coding standards violations
> composer static     # Run static analysis with Phpstan
```