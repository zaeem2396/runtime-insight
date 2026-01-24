<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Symfony\EventSubscriber;

use ClarityPHP\RuntimeInsight\Contracts\AnalyzerInterface;
use ClarityPHP\RuntimeInsight\DTO\Explanation;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

/**
 * Event subscriber that automatically analyzes exceptions using Runtime Insight.
 */
final class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AnalyzerInterface $analyzer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    /**
     * Handle kernel exception event.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        try {
            $exception = $event->getThrowable();
            $explanation = $this->analyzer->analyze($exception);

            if ($explanation->isEmpty()) {
                return;
            }

            $this->logExplanation($explanation, $exception);
        } catch (Throwable) {
            // Silently fail - don't let Runtime Insight break exception handling
        }
    }

    /**
     * Log the explanation to Symfony's logger.
     */
    private function logExplanation(Explanation $explanation, Throwable $exception): void
    {
        $context = [
            'exception' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'explanation' => [
                'message' => $explanation->getMessage(),
                'cause' => $explanation->getCause(),
                'suggestions' => $explanation->getSuggestions(),
                'confidence' => $explanation->getConfidence(),
                'error_type' => $explanation->getErrorType(),
                'location' => $explanation->getLocation(),
            ],
        ];

        $this->logger->debug('Runtime Insight Explanation', $context);
    }
}
