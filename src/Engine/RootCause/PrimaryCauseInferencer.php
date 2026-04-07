<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\RootCause;

use function str_contains;
use function strtolower;

/**
 * Maps exception class and message to a primary cause narrative and a remediation category.
 */
final class PrimaryCauseInferencer
{
    public const CATEGORY_TYPE_NULL = 'type_null';

    public const CATEGORY_TYPE_MISMATCH = 'type_mismatch';

    public const CATEGORY_NULL_ACCESS = 'null_access';

    public const CATEGORY_UNDEFINED_INDEX = 'undefined_index';

    public const CATEGORY_NOT_FOUND = 'not_found';

    public const CATEGORY_ARGUMENT_COUNT = 'argument_count';

    public const CATEGORY_DIVISION_BY_ZERO = 'division_by_zero';

    public const CATEGORY_PARSE = 'parse';

    public const CATEGORY_SQL = 'sql';

    public const CATEGORY_VALIDATION = 'validation';

    public const CATEGORY_CONFIGURATION = 'configuration';

    public const CATEGORY_GENERIC = 'generic';

    /**
     * @return array{primary: string, category: string}
     */
    public function infer(string $exceptionClass, string $message): array
    {
        $lowerClass = strtolower($exceptionClass);
        $lowerMsg = strtolower($message);

        if ($lowerClass === 'divisionbyzeroerror' || str_contains($lowerMsg, 'division by zero')) {
            return [
                'primary' => 'Division by zero: a numeric operation used zero as the divisor.',
                'category' => self::CATEGORY_DIVISION_BY_ZERO,
            ];
        }

        if ($lowerClass === 'argumentcounterror' || str_contains($lowerMsg, 'too few arguments') || str_contains($lowerMsg, 'too many arguments')) {
            return [
                'primary' => 'The number or types of arguments passed to a function or method do not match its signature.',
                'category' => self::CATEGORY_ARGUMENT_COUNT,
            ];
        }

        if ($lowerClass === 'parseerror' || str_contains($lowerClass, 'parseerror')) {
            return [
                'primary' => 'PHP could not parse the source file (syntax error).',
                'category' => self::CATEGORY_PARSE,
            ];
        }

        if (
            str_contains($lowerClass, 'pdoexception')
            || str_contains($lowerMsg, 'sqlstate')
            || str_contains($lowerMsg, 'integrity constraint')
            || str_contains($lowerMsg, 'duplicate entry')
            || str_contains($lowerMsg, 'foreign key constraint')
        ) {
            return [
                'primary' => 'A database operation failed (connection, SQL, or constraint).',
                'category' => self::CATEGORY_SQL,
            ];
        }

        if ($this->looksLikeValidationFailure($exceptionClass, $message, $lowerMsg)) {
            return [
                'primary' => 'Input validation rejected the request or failed a rule.',
                'category' => self::CATEGORY_VALIDATION,
            ];
        }

        if ($this->looksLikeConfigurationOrEnv($message, $lowerMsg)) {
            return [
                'primary' => 'Configuration or environment appears missing or invalid for this code path.',
                'category' => self::CATEGORY_CONFIGURATION,
            ];
        }

        if (str_contains($lowerClass, 'typeerror')) {
            if (str_contains($message, 'null given') || str_contains($message, 'on null')) {
                return [
                    'primary' => 'Null value passed where a non-null type was expected (type error).',
                    'category' => self::CATEGORY_TYPE_NULL,
                ];
            }

            return [
                'primary' => 'Type mismatch: ' . $message,
                'category' => self::CATEGORY_TYPE_MISMATCH,
            ];
        }

        if (str_contains($lowerMsg, 'on null') || str_contains($lowerMsg, 'null pointer')) {
            return [
                'primary' => 'A null reference was accessed (method or property on null).',
                'category' => self::CATEGORY_NULL_ACCESS,
            ];
        }

        if (str_contains($message, 'Undefined array key') || str_contains($message, 'Undefined index')) {
            return [
                'primary' => 'An array key was missing or undefined.',
                'category' => self::CATEGORY_UNDEFINED_INDEX,
            ];
        }

        if (str_contains($lowerMsg, 'not found')) {
            return [
                'primary' => 'A required class, interface, or resource was not found.',
                'category' => self::CATEGORY_NOT_FOUND,
            ];
        }

        return [
            'primary' => 'Runtime failure: ' . $message,
            'category' => self::CATEGORY_GENERIC,
        ];
    }

    private function looksLikeValidationFailure(string $exceptionClass, string $message, string $lowerMsg): bool
    {
        if (str_contains($lowerMsg, 'validation failed')) {
            return true;
        }

        if (str_contains($lowerMsg, 'the given data was invalid')) {
            return true;
        }

        if (str_contains($lowerMsg, 'field is required') || str_contains($lowerMsg, 'is required')) {
            return true;
        }

        if (str_contains(strtolower($exceptionClass), 'invalidargumentexception')) {
            return str_contains($lowerMsg, 'required')
                || str_contains($lowerMsg, 'must be')
                || str_contains($lowerMsg, 'invalid');
        }

        return false;
    }

    private function looksLikeConfigurationOrEnv(string $message, string $lowerMsg): bool
    {
        if (str_contains($lowerMsg, 'environment variable') && str_contains($lowerMsg, 'not set')) {
            return true;
        }

        if (str_contains($lowerMsg, 'env(') && str_contains($lowerMsg, 'undefined')) {
            return true;
        }

        if (str_contains($lowerMsg, 'configuration') && (str_contains($lowerMsg, 'missing') || str_contains($lowerMsg, 'not defined'))) {
            return true;
        }

        if (str_contains($lowerMsg, 'no such file') && str_contains($lowerMsg, '.env')) {
            return true;
        }

        return str_contains($lowerMsg, 'undefined array key') && str_contains($lowerMsg, 'config');
    }
}
