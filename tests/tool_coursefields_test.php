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
 * Admin tool "Set course fields" - PHPUnit tests
 *
 * @package    tool_coursefields
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursefields;

use core_course\customfield\course_handler;

require_once(__DIR__ . '/../lib.php');

/**
 * PHPUnit tests for set_fields class.
 *
 * @package    tool_coursefields
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_coursefields_test extends \advanced_testcase {

    /**
     * Setup testcase.
     */
    protected function setUp(): void {
        $this->resetAfterTest();
    }

    /**
     * Data provider for test_set_fields_comprehensive.
     *
     * Generates test cases covering all combinations of:
     * - Field types: text, textarea, date, select, number, checkbox
     * - Action modes: TOOL_COURSEFIELDS_NONE, TOOL_COURSEFIELDS_EMPTY, TOOL_COURSEFIELDS_ALL
     * - Field empty vs field has value
     *
     * @return array Test cases
     */
    public static function set_fields_comprehensive_provider(): array {
        // Initialize test cases array.
        $testcases = [];

        // Field types with their empty and filled values.
        $fieldtypes = [
            'text' => [
                'empty' => null,
                'initial' => 'initialvalue',
                'new' => 'newvalue',
                'configdata' => null,
            ],
            'textarea' => [
                'empty' => null,
                'initial' => ['text' => 'initialtext', 'format' => FORMAT_HTML],
                'new' => ['text' => 'newtext', 'format' => FORMAT_HTML],
                'configdata' => null,
            ],
            'date' => [
                'empty' => 0,
                'initial' => 1234567890,
                'new' => 1609459200,
                'configdata' => null,
            ],
            'select' => [
                'empty' => 0,
                'initial' => 1,
                'new' => 2,
                'configdata' => ['options' => "a\nb\nc"],
            ],
            'number' => [
                'empty' => null,
                'initial' => 10,
                'new' => 42,
                'configdata' => null,
            ],
            'checkbox' => [
                'empty' => null,
                'initial' => 0,
                'new' => 1,
                'configdata' => ['checkbydefault' => 0],
            ],
        ];

        // Action modes.
        $modes = [TOOL_COURSEFIELDS_NONE, TOOL_COURSEFIELDS_EMPTY, TOOL_COURSEFIELDS_ALL];

        // Field states.
        $states = ['empty', 'filled'];

        // Generate all combinations.
        foreach ($fieldtypes as $fieldtype => $fielddata) {
            foreach ($modes as $mode) {
                foreach ($states as $state) {
                    // Determine if field should be updated.
                    $shouldupdate = false;

                    // Never update if mode is TOOL_COURSEFIELDS_NONE.
                    if ($mode === TOOL_COURSEFIELDS_NONE) {
                        $shouldupdate = false;
                    }
                    // Always update if mode is TOOL_COURSEFIELDS_ALL.
                    else if ($mode === TOOL_COURSEFIELDS_ALL) {
                        $shouldupdate = true;
                    }
                    // For TOOL_COURSEFIELDS_EMPTY mode: only update if field is empty.
                    else if ($mode === TOOL_COURSEFIELDS_EMPTY && $state === 'empty') {
                        // TOOL_COURSEFIELDS_EMPTY mode is not supported for checkbox.
                        if (\tool_coursefields\set_fields::supports_empty_mode($fieldtype)) {
                            $shouldupdate = true;
                        } else {
                            $shouldupdate = false;
                        }
                    }

                    // Build test case name.
                   $name = "{$fieldtype}_{$mode}_{$state}";

                    // Add test case.
                    $testcases[$name] = [
                        'fieldtype' => $fieldtype,
                        'fielddata' => $fielddata,
                        'mode' => $mode,
                        'state' => $state,
                        'shouldupdate' => $shouldupdate,
                    ];
                }
            }
        }

        return $testcases;
    }

    /**
     * Test set_fields with comprehensive coverage of all scenarios.
     *
     * @dataProvider set_fields_comprehensive_provider
     * @param string $fieldtype Field type.
     * @param array $fielddata Field data (empty, initial, new values).
     * @param string $mode Action mode (TOOL_COURSEFIELDS_NONE, TOOL_COURSEFIELDS_EMPTY, TOOL_COURSEFIELDS_ALL).
     * @param string $state Field state (empty, filled).
     * @param bool $shouldupdate Whether field should be updated.
     */
    public function test_set_fields_comprehensive(
        string $fieldtype,
        array $fielddata,
        string $mode,
        string $state,
        bool $shouldupdate
    ): void {
        global $DB;

        // Create and login as admin to have edit permissions.
        $this->setAdminUser();

        // Create category.
        $category = $this->getDataGenerator()->create_category(['name' => 'Test Category']);

        // Create course.
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);

        // Create custom field category.
        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'core_course',
            'area' => 'course',
            'name' => 'Test Fields',
        ]);

        // Create custom field.
        $configdata = $fielddata['configdata'];
        if ($configdata !== null) {
            $configdata = json_encode($configdata);
        }
        $field = $this->getDataGenerator()->create_custom_field([
            'categoryid' => $fieldcategory->get('id'),
            'type' => $fieldtype,
            'shortname' => 'testfield',
            'name' => 'Test Field',
            'configdata' => $configdata,
        ]);

        // Set initial field value if state is 'filled'.
        if ($state === 'filled') {
            // Set the custom field value via the course record.
            $courserecord = $DB->get_record('course', ['id' => $course->id], '*', MUST_EXIST);
            $fieldname = ($fieldtype === 'textarea') ? 'customfield_testfield_editor' : 'customfield_testfield';
            $courserecord->{$fieldname} = $fielddata['initial'];
            update_course($courserecord);
        }

        // Get course as object with can_edit method.
        $courseobj = new \core_course_list_element($course);

        // Prepare fields object with update mode and new value.
        $fields = new \stdClass();

        // Determine suffix for textarea fields.
        $suffix = ($fieldtype === 'textarea') ? '_editor' : '';

        // Set field value.
        $fields->{"customfield_testfield{$suffix}"} = $fielddata['new'];

        // Set update mode.
        $fields->{"customfieldupdate_testfield{$suffix}"} = $mode;

        // Call the method under test.
        set_fields::maybe_alter_course_fields($courseobj, $fields);

        // Get the updated field value.
        $handler = course_handler::create();
        $customfields = $handler->get_instance_data($course->id, true);

        $actualvalue = null;
        foreach ($customfields as $data) {
            if ($data->get_field()->get('shortname') === 'testfield') {
                $actualvalue = $data->get_value();
                break;
            }
        }

        // Determine expected value.
        if ($shouldupdate) {
            $expectedvalue = $fielddata['new'];
            if ($fieldtype === 'textarea') {
                // For textarea, the value is stored as text only.
                $expectedvalue = $fielddata['new']['text'];
            }
        } else {
            // Field should not be updated.
            if ($state === 'empty') {
                $expectedvalue = $fielddata['empty'];
            } else {
                $expectedvalue = $fielddata['initial'];
                if ($fieldtype === 'textarea') {
                    // For textarea, the value is stored as text only.
                    $expectedvalue = $fielddata['initial']['text'];
                }
            }
        }

        // Assert the value matches expectation.
        $this->assertEquals(
            $expectedvalue,
            $actualvalue,
            "Field type: {$fieldtype}, Mode: {$mode}, State: {$state}. " .
            "Expected field to " . ($shouldupdate ? "be updated" : "remain unchanged") . "."
        );
    }

    /**
     * Test that supports_empty_mode returns correct values for each field type.
     */
    public function test_supports_empty_mode(): void {
        // Supported types.
        $this->assertTrue(set_fields::supports_empty_mode('text'));
        $this->assertTrue(set_fields::supports_empty_mode('textarea'));
        $this->assertTrue(set_fields::supports_empty_mode('date'));
        $this->assertTrue(set_fields::supports_empty_mode('select'));
        $this->assertTrue(set_fields::supports_empty_mode('number'));

        // Unsupported types.
        $this->assertFalse(set_fields::supports_empty_mode('checkbox'));
        $this->assertFalse(set_fields::supports_empty_mode('unsupportedtype'));
    }
}
