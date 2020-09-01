<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('agluh_outbox');

        // @see https://github.com/phpstan/phpstan/issues/844
        // @phpstan-ignore-next-line
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('table_name')
                    ->cannotBeEmpty()
                    ->defaultValue('outbox')
                ->end()
                ->booleanNode('auto_publish')
                    ->defaultFalse()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
