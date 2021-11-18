<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Command;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * @internal
 */
trait StopwatchHelperTrait
{
    private static function formatEvent(StopwatchEvent $event): string
    {
        return sprintf(
            'Start time: %s — End time: %s — Duration: %s — Memory used: %s',
            date('H:i:s', (int) (($event->getOrigin() + $event->getStartTime()) / 1000)),
            date('H:i:s', (int) (($event->getOrigin() + $event->getEndTime()) / 1000)),
            static::formatTimePrecision((int) $event->getDuration() / 1000),
            Helper::formatMemory($event->getMemory())
        );
    }

    private static function formatTimePrecision($secs): string
    {
        static $timeFormats = [
            [0, 'sec', 1, 2],
            [2, 'secs', 1, 2],
            [60, '1 min'],
            [120, 'mins', 60],
            [3600, '1 hr'],
            [7200, 'hrs', 3600],
            [86400, '1 day'],
            [172800, 'days', 86400],
        ];

        foreach ($timeFormats as $index => $format) {
            if ($secs >= $format[0]) {
                if ((isset($timeFormats[$index + 1]) && $secs < $timeFormats[$index + 1][0])
                    || $index === \count($timeFormats) - 1
                ) {
                    switch (\count($format)) {
                        case 2:
                            return $format[1];

                        case 4:
                            return round($secs / $format[2], $format[3]) . ' ' . $format[1];

                        default:
                            return floor($secs / $format[2]) . ' ' . $format[1];
                    }
                }
            }
        }

        return '0 sec';
    }
}
