<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Functional\DependencyInjection;

use AGluh\Bundle\OutboxBundle\DependencyInjection\Configuration;
use AGluh\Bundle\OutboxBundle\DependencyInjection\OutboxExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension(): ExtensionInterface
    {
        return new OutboxExtension();
    }

    protected function getConfiguration(): ConfigurationInterface
    {
        return new Configuration();
    }

    public function test_configuration(): void
    {
        $expectedConfiguration = [
            'table_name' => 'test',
            'auto_publish' => true,
        ];

        $sources = [
            dirname(__DIR__, 2).'/Fixtures/bundle_config.yml',
        ];

        $this->assertProcessedConfigurationEquals($expectedConfiguration, $sources);
    }
}
