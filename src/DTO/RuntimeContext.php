<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\DTO;

/**
 * Complete runtime context for error analysis.
 */
final readonly class RuntimeContext
{
    public function __construct(
        public ExceptionInfo $exception,
        public StackTraceInfo $stackTrace,
        public SourceContext $sourceContext,
        public ?RequestContext $requestContext = null,
        public ?ApplicationContext $applicationContext = null,
        public ?DatabaseContext $databaseContext = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'exception' => $this->exception->toArray(),
            'stack_trace' => $this->stackTrace->toArray(),
            'source_context' => $this->sourceContext->toArray(),
            'request_context' => $this->requestContext?->toArray(),
            'application_context' => $this->applicationContext?->toArray(),
            'database_context' => $this->databaseContext?->toArray(),
        ];
    }

    /**
     * Create a summary string for AI analysis.
     */
    public function toSummary(): string
    {
        $summary = "Exception: {$this->exception->class}\n";
        $summary .= "Message: {$this->exception->message}\n";
        $summary .= "File: {$this->exception->file}:{$this->exception->line}\n\n";

        $callChain = $this->stackTrace->getCallChainSummary(10);
        if ($callChain !== '') {
            $summary .= "Call chain:\n{$callChain}\n\n";
        }

        if ($this->sourceContext->codeSnippet !== '') {
            $summary .= "Code Context:\n{$this->sourceContext->codeSnippet}\n\n";
        }

        if ($this->requestContext !== null) {
            $summary .= "Request: {$this->requestContext->method} {$this->requestContext->uri}\n";
        }

        return $summary;
    }
}
