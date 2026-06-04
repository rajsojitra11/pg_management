<?php

use Modules\Purchase\Models\Purchase;

function year_delete_check($id)
{
    if (! class_exists('Modules\Purchase\Models\Purchase')) {
        return true;
    }

    $purchase = Purchase::where('year_id', $id)->count();
    if ($purchase > 0) {
        return false;
    } else {
        return true;
    }
}
function year_delete_check_1($id)
{
    if (! class_exists('Modules\Purchase\Models\Purchase')) {
        return 'came here 1';
    }

    $purchase = Purchase::where('year_id', $id)->count();
    if ($purchase > 0) {
        return 'came here 2';
    } else {
        return 'came here 3';
    }
}

/**
 * Generate all year formats from a year name input
 *
 * @param  string  $yearName  The input year name (e.g., "2023-24" or "2023-2024")
 * @return array Array containing all format variations
 */
function generateYearFormats($yearName)
{
    // Parse the year name to extract start and end years
    if (strpos($yearName, '-') !== false) {
        $parts = explode('-', $yearName);
        $startYear = trim($parts[0]);
        $endPart = trim($parts[1]);

        // Handle different input formats
        if (strlen($endPart) == 2) {
            // Input like "2023-24"
            $startYearFull = $startYear;
            $endYearFull = substr($startYear, 0, 2).$endPart;
        } else {
            // Input like "2023-2024"
            $startYearFull = $startYear;
            $endYearFull = $endPart;
        }

        $startYearShort = substr($startYearFull, -2);
        $endYearShort = substr($endYearFull, -2);

        return [
            'full_short' => $startYearFull.'-'.$endYearShort,
            'short_full' => $startYearShort.'-'.$endYearFull,
            'short_short' => $startYearShort.'-'.$endYearShort,
            'full_full' => $startYearFull.'-'.$endYearFull,
            'short' => $endYearShort,
            'full' => $endYearFull,
        ];
    } else {
        // Single year input like "2024"
        $year = trim($yearName);
        $yearShort = substr($year, -2);

        return [
            'full_short' => $year.'-'.sprintf('%02d', (int) $yearShort + 1),
            'short_full' => $yearShort.'-'.($year + 1),
            'short_short' => $yearShort.'-'.sprintf('%02d', (int) $yearShort + 1),
            'full_full' => $year.'-'.($year + 1),
            'short' => sprintf('%02d', (int) $yearShort + 1),
            'full' => (string) ($year + 1),
        ];
    }
}

/**
 * Get formatted year display based on configuration
 *
 * @param  object  $year  The year model instance
 * @param  string  $format  Optional format override
 * @return string Formatted year string
 */
function getFormattedYear($year, $format = null)
{
    $format = $format ?: getYearDisplayFormat();

    switch ($format) {
        case 'full_short':
            return $year->full_short ?: $year->name;
        case 'short_full':
            return $year->short_full ?: $year->name;
        case 'short_short':
            return $year->short_short ?: $year->name;
        case 'full_full':
            return $year->full_full ?: $year->name;
        case 'short':
            return $year->short ?: $year->name;
        case 'full':
            return $year->full ?: $year->name;
        default:
            return $year->name;
    }
}

/**
 * Get all available year format options for dropdown
 *
 * @return array Array of format options
 */
function getYearFormatOptions()
{
    return [
        'full_short' => 'Full-Short (2023-24)',
        'short_full' => 'Short-Full (23-2024)',
        'short_short' => 'Short-Short (23-24)',
        'full_full' => 'Full-Full (2023-2024)',
        'short' => 'Short (24)',
        'full' => 'Full (2024)',
    ];
}
