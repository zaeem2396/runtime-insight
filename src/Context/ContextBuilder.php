<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Context;

use ClarityPHP\RuntimeInsight\Config;
use ClarityPHP\RuntimeInsight\Contracts\ContextBuilderInterface;
use ClarityPHP\RuntimeInsight\DTO\ApplicationContext;
use ClarityPHP\RuntimeInsight\DTO\DatabaseContext;
use ClarityPHP\RuntimeInsight\DTO\ExceptionInfo;
use ClarityPHP\RuntimeInsight\DTO\RequestContext;
use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;
use ClarityPHP\RuntimeInsight\DTO\SourceContext;
use ClarityPHP\RuntimeInsight\DTO\StackFrame;
use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;
use Throwable;

use function count;
use function file;
use function file_exists;
use function implode;
use function is_readable;
use function is_string;
use function max;
use function min;
use function sprintf;
use function str_contains;

/**
 * Builds structured RuntimeContext from a Throwable.
 */
final class ContextBuilder implements ContextBuilderInterface
{
    public function __construct(
        private readonly Config $config,
    ) {}

    /**
     * Build a runtime context from a throwable.
     */
    public function build(Throwable $throwable): RuntimeContext
    {
        return new RuntimeContext(
            exception: ExceptionInfo::fromThrowable($throwable),
            stackTrace: $this->buildStackTrace($throwable),
            sourceContext: $this->buildSourceContext(
                $throwable->getFile(),
                $throwable->getLine(),
            ),
            requestContext: $this->config->shouldIncludeRequest()
                ? $this->buildRequestContext()
                : null,
            applicationContext: $this->buildApplicationContext(),
            databaseContext: $this->buildDatabaseContext(),
        );
    }

    /**
     * Build stack trace info from throwable.
     */
    private function buildStackTrace(Throwable $throwable): StackTraceInfo
    {
        $trace = $throwable->getTrace();
        $frames = [];

        foreach ($trace as $frame) {
            /** @var array<string, mixed> $frame */
            $file = isset($frame['file']) && is_string($frame['file']) ? $frame['file'] : null;
            $isVendor = $file !== null && $this->isVendorPath($file);

            $frames[] = StackFrame::fromArray($frame, $isVendor);
        }

        return new StackTraceInfo(
            frames: $frames,
            rawTrace: $throwable->getTraceAsString(),
        );
    }

    /**
     * Build source context around the error location.
     */
    private function buildSourceContext(string $file, int $line): SourceContext
    {
        if (! file_exists($file) || ! is_readable($file)) {
            return SourceContext::empty();
        }

        $fileLines = file($file);
        if ($fileLines === false) {
            return SourceContext::empty();
        }

        $contextLines = $this->config->getSourceLines();
        $startLine = max(1, $line - $contextLines);
        $endLine = min(count($fileLines), $line + $contextLines);

        $lines = [];
        for ($i = $startLine; $i <= $endLine; $i++) {
            $lines[$i] = $fileLines[$i - 1] ?? '';
        }

        // Build code snippet string
        $snippetLines = [];
        foreach ($lines as $lineNum => $content) {
            $marker = $lineNum === $line ? ' → ' : '   ';
            $snippetLines[] = sprintf('%s%4d | %s', $marker, $lineNum, rtrim($content));
        }

        return new SourceContext(
            file: $file,
            errorLine: $line,
            lines: $lines,
            codeSnippet: implode("\n", $snippetLines),
            methodSignature: $this->extractMethodSignature($fileLines, $line),
            className: $this->extractClassName($fileLines),
        );
    }

    /**
     * Build request context (placeholder - framework adapters will override).
     *
     * @phpstan-ignore-next-line
     */
    private function buildRequestContext(): ?RequestContext
    {
        // Base implementation returns null
        // Framework-specific adapters (Laravel/Symfony) will provide actual request context
        return null;
    }

    /**
     * Build application context (placeholder - framework adapters will override).
     *
     * @phpstan-ignore-next-line
     */
    private function buildApplicationContext(): ?ApplicationContext
    {
        // Base implementation returns null
        // Framework-specific adapters will provide actual application context
        return null;
    }

    /**
     * Build database/query context (placeholder - framework adapters build their own).
     *
     * @phpstan-ignore-next-line return.unusedType (framework builders pass DatabaseContext in RuntimeContext)
     */
    private function buildDatabaseContext(): ?DatabaseContext
    {
        return null;
    }

    /**
     * Check if a file path is in the vendor directory.
     */
    private function isVendorPath(string $path): bool
    {
        return str_contains($path, '/vendor/') || str_contains($path, '\\vendor\\');
    }

    /**
     * Extract method signature from source lines.
     *
     * @param array<string> $fileLines
     */
    private function extractMethodSignature(array $fileLines, int $errorLine): ?string
    {
        // Search backwards from error line to find function/method signature
        for ($i = $errorLine - 1; $i >= 0 && $i >= $errorLine - 50; $i--) {
            $line = $fileLines[$i] ?? '';
            if (preg_match('/^\s*(public|protected|private|static)?\s*(function)\s+(\w+)\s*\(/', $line, $matches)) {
                return trim($line);
            }
        }

        return null;
    }

    /**
     * Extract class name from source file.
     *
     * @param array<string> $fileLines
     */
    private function extractClassName(array $fileLines): ?string
    {
        foreach ($fileLines as $line) {
            if (preg_match('/^\s*(class|interface|trait|enum)\s+(\w+)/', $line, $matches)) {
                return $matches[2];
            }
        }

        return null;
    }
}
