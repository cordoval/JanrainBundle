<?php

namespace Evario\JanrainBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class JanrainExtension extends Extension
{
    protected $resources = array(
        'security' => 'security.xml',
        'security' => 'security_factories.xml',
    );

    public function load(array $configs, ContainerBuilder $container)
    {
        $this->loadDefaults($container);
        
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        foreach (array('app_id') as $attribute) {
            $container->setParameter('evario_janrain.'.$attribute, $config[$attribute]);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getNamespace()
    {
        return 'http://symfony.com/schema/dic/evario_janrain';
    }

    /**
     * @codeCoverageIgnore
     */
    protected function loadDefaults($container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        
        foreach ($this->resources as $resource) {
            $loader->load($resource);
        }
    }
}