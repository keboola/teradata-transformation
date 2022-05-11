<?php

declare(strict_types=1);

namespace Keboola\TeradataTransformation\Config;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getRootDefinition(TreeBuilder $treeBuilder): ArrayNodeDefinition
    {
        $rootNode = parent::getRootDefinition($treeBuilder);
        $rootNode->children()->append($this->getAuthorizationDefinition());
        return $rootNode;
    }

    protected function getAuthorizationDefinition(): ArrayNodeDefinition
    {
        $builder = new TreeBuilder('authorization');
        /** @var ArrayNodeDefinition $authorizationNode */
        $authorizationNode = $builder->getRootNode();

        // @formatter:off
        $authorizationNode
            ->isRequired()
            ->children()->arrayNode('workspace')
                ->ignoreExtraKeys()
                ->isRequired()
                ->children()
                    ->scalarNode('host')->isRequired()->end()
                    ->integerNode('port')->defaultValue(1025)->end()
                    ->scalarNode('user')->isRequired()->end()
                    ->scalarNode('password')->isRequired()->end()
                    ->scalarNode('database')->isRequired()->end()
                ->end()
            ->end()
        ;
        // @formatter:on

        return $authorizationNode;
    }

    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->integerNode('query_timeout')->defaultValue(7200)->end()
                ->arrayNode('blocks')
                    ->isRequired()
                    ->arrayPrototype()
                    ->children()
                        ->scalarNode('name')->isRequired()->end()
                        ->arrayNode('codes')
                            ->isRequired()
                            ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')->isRequired()->end()
                                ->arrayNode('script')
                                    ->isRequired()
                                    ->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
