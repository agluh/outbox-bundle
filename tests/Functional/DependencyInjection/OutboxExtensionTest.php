<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Functional\DependencyInjection;

use AGluh\Bundle\OutboxBundle\Command\PrunePublishedDomainEventsCommand;
use AGluh\Bundle\OutboxBundle\Command\PublishDomainEventsCommand;
use AGluh\Bundle\OutboxBundle\Command\StopWorkersCommand;
use AGluh\Bundle\OutboxBundle\DependencyInjection\OutboxExtension;
use AGluh\Bundle\OutboxBundle\Doctrine\DoctrineOutboxEventRepository;
use AGluh\Bundle\OutboxBundle\Doctrine\EventListener\ClassMetadataListener;
use AGluh\Bundle\OutboxBundle\Doctrine\EventListener\PersistDomainEventsListener;
use AGluh\Bundle\OutboxBundle\EventListener\DispatchPcntlSignalListener;
use AGluh\Bundle\OutboxBundle\EventListener\KernelTerminateEventListener;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnRestartSignalListener;
use AGluh\Bundle\OutboxBundle\EventListener\StopWorkerOnSigtermSignalListener;
use AGluh\Bundle\OutboxBundle\Serialization\PhpSerializer;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use PHPUnit\Framework\ExpectationFailedException;

class OutboxExtensionTest extends AbstractExtensionTestCase
{
    private const TABLE_NAME = 'custom_name';

    protected function getContainerExtensions(): array
    {
        return [
            new OutboxExtension(),
        ];
    }

    public function test_after_loading_doctrine_table_name_parameter_has_been_set(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('agluh_outbox_bundle.outbox_table_name',
            'outbox');
    }

    public function test_after_loading_doctrine_table_name_parameter_has_been_set_with_expected_value(): void
    {
        $this->load([
            'table_name' => self::TABLE_NAME,
        ]);

        $this->assertContainerBuilderHasParameter('agluh_outbox_bundle.outbox_table_name',
            self::TABLE_NAME);
    }

    public function test_after_loading_event_listener_is_tagged_if_auto_publish_parameter_set(): void
    {
        $this->load([
            'auto_publish' => true,
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithTag('agluh_outbox_bundle.event_listener.kernel_terminate_event', 'kernel.event_subscriber');
    }

    public function test_after_loading_event_listener_is_not_tagged_if_auto_publish_parameter_not_set(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->load([
            'auto_publish' => false,
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithTag('agluh_outbox_bundle.event_listener.kernel_terminate_event', 'kernel.event_subscriber');
    }

    public function test_after_loading_all_services_are_registered(): void
    {
        $services = [
            KernelTerminateEventListener::class => 'agluh_outbox_bundle.event_listener.kernel_terminate_event',
            StopWorkerOnSigtermSignalListener::class => 'agluh_outbox_bundle.event_listener.stop_worker_on_sigterm',
            DispatchPcntlSignalListener::class => 'agluh_outbox_bundle.event_listener.dispatch_pcntl_signal',
            StopWorkerOnRestartSignalListener::class => 'agluh_outbox_bundle.event_listener.stop_worker_on_restart',
            PhpSerializer::class => 'agluh_outbox_bundle.serializer.default',
            DoctrineOutboxEventRepository::class => 'agluh_outbox_bundle.repository.doctrine',
            PersistDomainEventsListener::class => 'agluh_outbox_bundle.event_listener.events_collector',
            ClassMetadataListener::class => 'agluh_outbox_bundle.event_listener.table_name_alter',
            PublishDomainEventsCommand::class => 'agluh_outbox_bundle.command.publish',
            StopWorkersCommand::class => 'agluh_outbox_bundle.command.stop_workers',
            PrunePublishedDomainEventsCommand::class => 'agluh_outbox_bundle.command.prune_published',
        ];

        $this->load();

        foreach ($services as $class => $id) {
            $this->assertContainerBuilderHasService($id, $class);
        }
    }

    public function test_after_loading_event_listeners_are_tagged(): void
    {
        $services = [
            //'agluh_outbox_bundle.event_listener.kernel_terminate_event' => 'kernel.event_subscriber',
            'agluh_outbox_bundle.event_listener.stop_worker_on_sigterm' => ['kernel.event_subscriber'],
            'agluh_outbox_bundle.event_listener.dispatch_pcntl_signal' => ['kernel.event_subscriber'],
            'agluh_outbox_bundle.event_listener.stop_worker_on_restart' => ['kernel.event_subscriber'],
            'agluh_outbox_bundle.event_listener.events_collector' => ['doctrine.event_subscriber', ['priority' => 10]],
            'agluh_outbox_bundle.event_listener.table_name_alter' => ['doctrine.event_subscriber'],
        ];

        $this->load();

        foreach ($services as $id => $info) {
            $tag = $info[0];
            $attributes = $info[1] ?? [];
            $this->assertContainerBuilderHasServiceDefinitionWithTag($id, $tag, $attributes);
        }
    }

    public function test_after_loading_console_commands_are_tagged(): void
    {
        $services = [
            'agluh_outbox_bundle.command.publish' => ['command' => 'outbox:publish'],
            'agluh_outbox_bundle.command.stop_workers' => ['command' => 'outbox:stop-workers'],
            'agluh_outbox_bundle.command.prune_published' => ['command' => 'outbox:prune-published'],
        ];

        $this->load();

        foreach ($services as $id => $attributes) {
            $this->assertContainerBuilderHasServiceDefinitionWithTag($id, 'console.command', $attributes);
        }
    }

    public function test_after_loading_cache_service_is_valid(): void
    {
        $this->load();

        $this->assertContainerBuilderHasServiceDefinitionWithParent('agluh_outbox_bundle.cache.restart_workers_signal', 'cache.app');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('agluh_outbox_bundle.cache.restart_workers_signal', 'cache.pool');
    }
}
