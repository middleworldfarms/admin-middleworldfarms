<?php
/**
 * Global PHP functions stub for IDE support
 * This file provides type hints for global PHP functions that may not be recognized by some IDEs
 */

if (!function_exists('rand')) {
    /**
     * Generate a random integer
     * @param int $min
     * @param int $max
     * @return int
     */
    function rand($min = null, $max = null) {}
}

if (!function_exists('date')) {
    /**
     * Format a local time/date
     * @param string $format
     * @param int|null $timestamp
     * @return string
     */
    function date($format, $timestamp = null) {}
}

if (!function_exists('time')) {
    /**
     * Return current Unix timestamp
     * @return int
     */
    function time() {}
}

if (!function_exists('strtotime')) {
    /**
     * Parse about any English textual datetime description into a Unix timestamp
     * @param string $datetime
     * @param int|null $baseTimestamp
     * @return int|false
     */
    function strtotime($datetime, $baseTimestamp = null) {}
}
