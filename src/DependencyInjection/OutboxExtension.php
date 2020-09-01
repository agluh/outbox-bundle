<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\DependencyInjection;

use AGluh\Bundle\OutboxBundle\Doctrine\DBAL\Types\DateTimeImmutableMicrosecondsType;
use Exception;
use Ramsey\Uuid\Doctrine\UuidBinaryType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class OutboxExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    /**
     * @param array<mixed> $config
     *
     * @throws Exception
     */
    protected function loadInternal(array $config, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('agluh_outbox_bundle.outbox_table_name', $config['table_name']);

        $definition = $container->getDefinition('agluh_outbox_bundle.event_listener.kernel_terminate_event');
        if ($config['auto_publish']) {
            $definition->addTag('kernel.event_subscriber');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->addCustomDBALType($container);
    }

    private function addCustomDBALType(ContainerBuilder $container): void
    {
        $config = [
            'dbal' => [
                'types' => [
                    DateTimeImmutableMicrosecondsType::NAME => DateTimeImmutableMicrosecondsType::class,
                    UuidBinaryType::NAME => UuidBinaryType::class,
                ],
                'mapping_types' => [
                    'uuid_binary' => 'binary',
                ],
            ],
        ];

        $container->prependExtensionConfig('doctrine', $config);
    }

    public function getAlias(): string
    {
        return 'agluh_outbox';
    }
}
