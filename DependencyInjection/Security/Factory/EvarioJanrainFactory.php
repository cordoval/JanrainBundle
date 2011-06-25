<?php

namespace Evario\JanrainBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;

class EvarioJanrainFactory extends AbstractFactory
{
    public function __construct()
    {
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'evario_janrain';
    }

    protected function getListenerId()
    {
        return 'evario_janrain.security.authentication.listener';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        // with user provider
        if (isset($config['provider'])) {
            $authProviderId = 'evario_janrain.auth.'.$id;

            $container
                ->setDefinition($authProviderId, new DefinitionDecorator('evario_janrain.auth'))
                ->addArgument(new Reference($userProviderId))
                ->addArgument(new Reference('security.user_checker'))
            ;

            return $authProviderId;
        }

        // without user provider
        return 'evario_janrain.auth';
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPointId)
    {
        $entryPointId = 'evario_janrain.security.authentication.entry_point.'.$id;
        $container
            ->setDefinition($entryPointId, new DefinitionDecorator('evario_janrain.security.authentication.entry_point'))
            ->replaceArgument(1, $config)
        ;

        // set options to container for use by other classes
        $container->setParameter('evario_janrain.options.'.$id, $config);

        return $entryPointId;
    }
}