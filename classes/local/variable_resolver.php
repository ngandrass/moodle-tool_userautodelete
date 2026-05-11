<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Template variable resolver for resolving {{namespace.key}} variable references.
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Resolves {{namespace.key}} variable references in template strings.
 *
 * This class provides a shared variable resolution mechanism that can be used
 * by action sub-plugins to replace variables in configurable text fields before
 * they are sent or acted upon. Unknown variables are always left unreplaced.
 */
final class variable_resolver {
    /** @var string Regular expression pattern to match {{namespace.key}} variable references. */
    const VARIABLE_PATTERN = '/\{\{([a-zA-Z_]+)\.([a-zA-Z_]+)}}/';

    /**
     * Resolves all {{namespace.key}} variable references in the given template string.
     *
     * Variable references whose namespace or key is not found in the given context
     * are left unreplaced in the output.
     *
     * @param string $template The template string containing variable references
     * @param array $context Nested associative array of resolved values in the
     * form ['namespace' => ['key' => 'value']]
     * @return string The template with all resolvable variables replaced by their values
     */
    public static function resolve(string $template, array $context): string {
        return preg_replace_callback(
            self::VARIABLE_PATTERN,
            function (array $matches) use ($context): string {
                $namespace = $matches[1];
                $key = $matches[2];

                if (isset($context[$namespace][$key])) {
                    return (string) $context[$namespace][$key];
                }

                // Variable not found in context - leave unreplaced.
                return $matches[0];
            },
            $template
        );
    }


    /**
     * Detects whether a template still contains unresolved variable references.
     *
     * @param string $template The template string to inspect
     * @return bool True if unresolved {{namespace.key}} tokens are present
     */
    public static function has_unresolved_variables(string $template): bool {
        return preg_match(self::VARIABLE_PATTERN, $template) === 1;
    }

    /**
     * Extracts unresolved variable references from a template string.
     *
     * @param string $template The template string to inspect
     * @return string[] Array of unique unresolved variable references found in the template
     */
    public static function get_unresolved_variables(string $template): array {
        preg_match_all(self::VARIABLE_PATTERN, $template, $matches, PREG_SET_ORDER);
        return array_values(array_unique(array_map(fn($match): string => $match[0], $matches)));
    }
}
