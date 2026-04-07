<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\RuntimeContext;

/**
 * Fix suggestions and prevention advice from remediation category and message.
 */
final class RemediationBuilder
{
    /**
     * @return array{fixes: array<int, string>, prevention: array<int, string>}
     */
    public function build(string $category, RuntimeContext $context): array
    {
        $fixes = [];
        $prevention = [];

        switch ($category) {
            case PrimaryCauseInferencer::CATEGORY_TYPE_NULL:
            case PrimaryCauseInferencer::CATEGORY_NULL_ACCESS:
                $this->appendNullFixes($fixes, $prevention);
                break;
            case PrimaryCauseInferencer::CATEGORY_UNDEFINED_INDEX:
                $this->appendIndexFixes($fixes, $prevention);
                break;
            case PrimaryCauseInferencer::CATEGORY_TYPE_MISMATCH:
                $fixes[] = 'Ensure the argument or property has the expected type at the call site.';
                break;
            case PrimaryCauseInferencer::CATEGORY_ARGUMENT_COUNT:
                $this->appendArgCountFixes($fixes, $prevention);
                break;
            case PrimaryCauseInferencer::CATEGORY_DIVISION_BY_ZERO:
                $this->appendDivZeroFixes($fixes, $prevention);
                break;
            case PrimaryCauseInferencer::CATEGORY_PARSE:
                $this->appendParseFixes($fixes, $prevention);
                break;
            case PrimaryCauseInferencer::CATEGORY_SQL:
                $this->appendSqlFixes($fixes, $prevention);
                break;
            case PrimaryCauseInferencer::CATEGORY_VALIDATION:
                $this->appendValidationFixes($fixes, $prevention);
                break;
            case PrimaryCauseInferencer::CATEGORY_CONFIGURATION:
                $this->appendConfigurationFixes($fixes, $prevention);
                break;
            case PrimaryCauseInferencer::CATEGORY_NOT_FOUND:
                $fixes[] = 'Verify autoloading, namespaces, and that the class or file exists where the runtime expects it.';
                break;
            default:
                break;
        }

        if ($context->requestContext !== null) {
            $fixes[] = 'Validate request input and ensure required parameters are present.';
            $prevention[] = 'Consider middleware or validation to enforce preconditions for this route.';
        }

        if ($fixes === []) {
            $fixes[] = 'Review the stack trace and error location for the exact failing operation.';
        }

        if ($prevention === []) {
            $prevention[] = 'Add defensive checks and tests around the failing code path.';
        }

        return ['fixes' => $fixes, 'prevention' => $prevention];
    }

    /**
     * @param array<int, string> $fixes
     * @param array<int, string> $prevention
     */
    private function appendNullFixes(array &$fixes, array &$prevention): void
    {
        $fixes[] = 'Add a null check before accessing the value (e.g. if ($x !== null)).';
        $fixes[] = 'Use the nullsafe operator (?->) or provide a default with ??';
        $prevention[] = 'Add guards or validation so null is handled before the failing call.';
    }

    /**
     * @param array<int, string> $fixes
     * @param array<int, string> $prevention
     */
    private function appendIndexFixes(array &$fixes, array &$prevention): void
    {
        $fixes[] = 'Check that the key exists with isset() or array_key_exists() before access.';
        $fixes[] = 'Use the null coalescing operator ?? to provide a default value.';
        $prevention[] = 'Define defaults for array keys or validate structure before use.';
    }

    /**
     * @param array<int, string> $fixes
     * @param array<int, string> $prevention
     */
    private function appendArgCountFixes(array &$fixes, array &$prevention): void
    {
        $fixes[] = 'Match the callee signature: count and order of arguments, including optional parameters.';
        $fixes[] = 'If using variadics or unpacking, ensure the array shape matches what the function expects.';
        $prevention[] = 'Use static analysis or tests to catch signature mismatches before deploy.';
    }

    /**
     * @param array<int, string> $fixes
     * @param array<int, string> $prevention
     */
    private function appendDivZeroFixes(array &$fixes, array &$prevention): void
    {
        $fixes[] = 'Guard the divisor: check for zero before dividing.';
        $fixes[] = 'Use max() or a small epsilon only when appropriate for your domain.';
        $prevention[] = 'Validate numeric inputs and document assumptions for denominators.';
    }

    /**
     * @param array<int, string> $fixes
     * @param array<int, string> $prevention
     */
    private function appendParseFixes(array &$fixes, array &$prevention): void
    {
        $fixes[] = 'Open the reported file at the line/column and fix the syntax error.';
        $fixes[] = 'Run `php -l` on the file locally to confirm it parses.';
        $prevention[] = 'Enable syntax checks in CI and editor diagnostics before merge.';
    }

    /**
     * @param array<int, string> $fixes
     * @param array<int, string> $prevention
     */
    private function appendSqlFixes(array &$fixes, array &$prevention): void
    {
        $fixes[] = 'Inspect the failing query, bindings, and schema (migrations vs database).';
        $fixes[] = 'Check connection config, credentials, and that required tables or indexes exist.';
        $prevention[] = 'Use migrations, constraints, and integration tests for critical queries.';
    }

    /**
     * @param array<int, string> $fixes
     * @param array<int, string> $prevention
     */
    private function appendValidationFixes(array &$fixes, array &$prevention): void
    {
        $fixes[] = 'Align request rules with what the controller or action expects (required fields, types).';
        $fixes[] = 'Return or log validation errors clearly so clients can correct input.';
        $prevention[] = 'Centralize validation (Form Request, DTO, or Symfony constraints) and add API tests.';
    }

    /**
     * @param array<int, string> $fixes
     * @param array<int, string> $prevention
     */
    private function appendConfigurationFixes(array &$fixes, array &$prevention): void
    {
        $fixes[] = 'Verify required environment variables and config keys are set for this environment.';
        $fixes[] = 'Compare local vs staging/production `.env` or config files for missing entries.';
        $prevention[] = 'Document required env vars in README/USAGE and fail fast with clear messages when unset.';
    }
}
