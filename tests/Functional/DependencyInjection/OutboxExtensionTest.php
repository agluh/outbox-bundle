<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Functional\DependencyInjection;

use AGluh\Bundle\OutboxBundle\DependencyInjection\OutboxExtension;
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
}
