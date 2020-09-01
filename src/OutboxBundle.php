<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle;

use AGluh\Bundle\OutboxBundle\DependencyInjection\OutboxExtension;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OutboxBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->addDoctrineMapping($container);
    }

    private function addDoctrineMapping(ContainerBuilder $container): void
    {
        if (class_exists(DoctrineOrmMappingsPass::class)) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createXmlMappingDriver(
                    [realpath(__DIR__.'/Doctrine/Mapping') => 'AGluh\Bundle\OutboxBundle\Domain\Model'],
                    []
                )
            );
        }
    }

    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new OutboxExtension();
        }

        return $this->extension;
    }
}
