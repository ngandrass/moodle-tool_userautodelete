<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the variable_resolver class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();


/**
 * Unit tests for the variable_resolver class.
 */
final class variable_resolver_test extends \advanced_testcase {
    /**
     * Tests that resolve() replaces known variable references with their context values.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_resolve_replaces_known_variables(): void {
        $context = [
            'user' => ['firstname' => 'Alice', 'lastname' => 'Smith', 'email' => 'alice@example.com'],
            'site' => ['name' => 'My Moodle'],
        ];

        $this->assertSame(
            'Hello Alice Smith, welcome to My Moodle!',
            variable_resolver::resolve('Hello {{user.firstname}} {{user.lastname}}, welcome to {{site.name}}!', $context),
            'Known variables must be replaced with context values.'
        );
    }

    /**
     * Tests that resolve() replaces all occurrences of the same variable token.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_resolve_replaces_all_occurrences_of_same_token(): void {
        $context = ['user' => ['firstname' => 'Alice']];

        $this->assertSame(
            'Hi Alice, again Alice!',
            variable_resolver::resolve('Hi {{user.firstname}}, again {{user.firstname}}!', $context),
            'All occurrences of the same variable token must be replaced.'
        );
    }

    /**
     * Tests that resolve() leaves unknown variable references unreplaced in the output.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_resolve_leaves_unknown_variables_unreplaced(): void {
        $context = ['user' => ['firstname' => 'Bob']];

        $this->assertSame(
            'Hello Bob, your token is {{user.invalidvariable}}.',
            variable_resolver::resolve('Hello {{user.firstname}}, your token is {{user.invalidvariable}}.', $context),
            'Unknown variable references must remain unreplaced in the output.'
        );
    }

    /**
     * Tests that resolve() handles a template with no variable references correctly.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_resolve_handles_template_without_variables(): void {
        $this->assertSame(
            'Plain text without any variables.',
            variable_resolver::resolve('Plain text without any variables.', []),
            'A template with no variable references must be returned unchanged.'
        );
    }

    /**
     * Tests that resolve() returns an empty string unchanged.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_resolve_handles_empty_template(): void {
        $this->assertSame(
            '',
            variable_resolver::resolve('', ['user' => ['firstname' => 'Test']]),
            'An empty template must return an empty string.'
        );
    }

    /**
     * Tests that resolve() casts non-string context values to strings.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_resolve_casts_non_string_context_values(): void {
        $context = ['user' => ['id' => 42]];

        $this->assertSame(
            'User ID: 42',
            variable_resolver::resolve('User ID: {{user.id}}', $context),
            'Numeric context values must be cast to string during resolution.'
        );
    }

    /**
     * Tests that has_unresolved_variables() reports false when all variables were resolved.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_has_unresolved_variables_returns_false_for_resolved_template(): void {
        $this->assertFalse(
            variable_resolver::has_unresolved_variables('Hello Alice Smith.'),
            'A fully resolved template must not report unresolved variables.'
        );
    }

    /**
     * Tests that has_unresolved_variables() reports true when unresolved variables remain.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_has_unresolved_variables_returns_true_for_unresolved_template(): void {
        $this->assertTrue(
            variable_resolver::has_unresolved_variables('Hello {{user.firstname}}.'),
            'A template containing unresolved variables must report unresolved variables.'
        );
    }

    /**
     * Tests that get_unresolved_variables() returns an empty array for templates
     * without unresolved variable tokens.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_get_unresolved_variables_returns_empty_for_plain_text(): void {
        $this->assertSame(
            [],
            variable_resolver::get_unresolved_variables('No variables here.'),
            'Templates without variable tokens must not return unresolved variables.'
        );
    }

    /**
     * Tests that get_unresolved_variables() returns unresolved variables and deduplicates repeats.
     *
     * @covers \tool_userautodelete\local\variable_resolver
     *
     * @return void
     */
    public function test_get_unresolved_variables_returns_unique_tokens(): void {
        $unresolved = variable_resolver::get_unresolved_variables(
            '{{user.bad}} and {{user.bad}} plus {{site.unknown}}'
        );

        $this->assertCount(2, $unresolved, 'Repeated unresolved variables must be returned only once.');
        $this->assertContains('{{user.bad}}', $unresolved);
        $this->assertContains('{{site.unknown}}', $unresolved);
    }
}
