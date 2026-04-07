<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight\Engine\RootCause;

use ClarityPHP\RuntimeInsight\DTO\StackTraceInfo;

use function count;

/**
 * Vendor vs application frame statistics for root cause narrative.
 */
final class StackTraceAnalyzer
{
    /**
     * @return array{
     *     vendor_frames: int,
     *     app_frames: int,
     *     first_app_location: string|null,
     *     narrative: string
     * }
     */
    public function summarize(StackTraceInfo $stackTrace): array
    {
        $frames = $stackTrace->frames;
        if ($frames === []) {
            return [
                'vendor_frames' => 0,
                'app_frames' => 0,
                'first_app_location' => null,
                'narrative' => '',
            ];
        }

        $vendor = 0;
        $app = 0;
        $firstAppLocation = null;
        foreach ($frames as $frame) {
            if ($frame->isVendor) {
                ++$vendor;
            } else {
                ++$app;
                if ($firstAppLocation === null) {
                    $loc = $frame->getLocation();
                    $firstAppLocation = $loc !== '' ? $loc : null;
                }
            }
        }

        $total = count($frames);
        $narrative = "Stack depth {$total}: {$vendor} vendor frame(s), {$app} application frame(s).";
        if ($firstAppLocation !== null) {
            $narrative .= ' First application frame at ' . $firstAppLocation . '.';
        } elseif ($vendor > 0 && $app === 0) {
            $narrative .= ' Failure originates in vendor or extension code only; inspect the top frame.';
        }

        return [
            'vendor_frames' => $vendor,
            'app_frames' => $app,
            'first_app_location' => $firstAppLocation,
            'narrative' => $narrative,
        ];
    }
}
