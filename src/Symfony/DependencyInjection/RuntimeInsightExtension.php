<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Symfony\DependencyInjection;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Context\ContextBuilder;
use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\Contracts\ContextBuilderInterface;
use ClarityPHP\RuntimeInsight\Contracts\ExplanationEngineInterface;
use ClarityPHP\RuntimeInsight\RuntimeInsight;
use ClarityPHP\RuntimeInsight\RuntimeInsightFactory;
use ClarityPHP\RuntimeInsight\Symfony\Context\SymfonyContextBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Symfony Dependency Injection Extension for Runtime Insight.
 */
final class RuntimeInsightExtension extends Extension
{
    /**
     * @param array<string, mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Set current environment
        $config['current_environment'] = $container->getParameter('kernel.environment');

        // Register Config
        /** @var array<string, mixed> $configArray */
        $configArray = $config;
        $container->register(Config::class)
            ->setArguments([Config::fromArray($configArray)]);

        // Register base ContextBuilder
        $container->register(ContextBuilder::class)
            ->setArguments([
                new Reference(Config::class),
            ]);

        // Register Symfony-specific ContextBuilder
        $container->register(ContextBuilderInterface::class, SymfonyContextBuilder::class)
            ->setArguments([
                new Reference(ContextBuilder::class),
                new Reference('kernel'),
                new Reference('request_stack'),
                new Reference('router', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference('security.token_storage', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
                new Reference(Config::class),
            ]);

        // Register ExplanationEngine
        $container->register(ExplanationEngineInterface::class)
            ->setFactory([RuntimeInsightFactory::class, 'createExplanationEngine'])
            ->setArguments([
                new Reference(Config::class),
            ]);

        // Register main RuntimeInsight
        $container->register(RuntimeInsight::class)
            ->setArguments([
                new Reference(ContextBuilderInterface::class),
                new Reference(ExplanationEngineInterface::class),
                new Reference(Config::class),
            ]);

        $container->setAlias(AnalyzerInterface::class, RuntimeInsight::class);
    }

    public function getAlias(): string
    {
        return 'runtime_insight';
    }
}

