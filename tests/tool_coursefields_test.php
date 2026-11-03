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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

/**
 * PHPUnit tests for set_fields class.
 *
 * @package    tool_coursefields
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \tool_coursefields\set_fields
 */
final class tool_coursefields_test extends \advanced_testcase {
    /**
     * Setup testcase.
     */
    protected function setUp(): void {
        parent::setUp();
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
        $modes = [TOOL_COURSEFIELDS_NONE, TOOL_COURSEFIELDS_EMPTY, TOOL_COURSEFIELDS_CLEAR, TOOL_COURSEFIELDS_ALL];

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

                        // Always update if mode is TOOL_COURSEFIELDS_ALL.
                    } else if ($mode === TOOL_COURSEFIELDS_ALL) {
                        $shouldupdate = true;

                        // Always update if mode is TOOL_COURSEFIELDS_CLEAR.
                    } else if ($mode === TOOL_COURSEFIELDS_CLEAR) {
                        $shouldupdate = true;

                        // For TOOL_COURSEFIELDS_EMPTY mode: only update if field is empty.
                    } else if ($mode === TOOL_COURSEFIELDS_EMPTY && $state === 'empty') {
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
            // For CLEAR mode, expected value is always empty.
            if ($mode === TOOL_COURSEFIELDS_CLEAR) {
                $expectedvalue = $fielddata['empty'];
            } else {
                // For other modes (ALL, EMPTY), expected value is the new value.
                $expectedvalue = $fielddata['new'];
                if ($fieldtype === 'textarea') {
                    // For textarea, the value is stored as text only.
                    $expectedvalue = $fielddata['new']['text'];
                }
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

    /**
     * Test that supports_clear_mode returns correct values for each field type.
     */
    public function test_supports_clear_mode(): void {
        // Supported types.
        $this->assertTrue(set_fields::supports_clear_mode('text'));
        $this->assertTrue(set_fields::supports_clear_mode('textarea'));
        $this->assertTrue(set_fields::supports_clear_mode('date'));
        $this->assertTrue(set_fields::supports_clear_mode('select'));
        $this->assertTrue(set_fields::supports_clear_mode('number'));
        $this->assertTrue(set_fields::supports_clear_mode('checkbox'));

        // Unsupported types.
        $this->assertFalse(set_fields::supports_clear_mode('unsupportedtype'));
    }

    /**
     * Test is_field_value_empty function for all field types.
     *
     * Tests the centralized logic that determines if a field value is empty.
     */
    public function test_is_field_value_empty(): void {
        // Text fields.
        $this->assertTrue(set_fields::is_field_value_empty('text', null), 'Text: null should be empty');
        $this->assertTrue(set_fields::is_field_value_empty('text', ''), 'Text: empty string should be empty');
        $this->assertTrue(set_fields::is_field_value_empty('text', '   '), 'Text: whitespace should be empty');
        $this->assertTrue(set_fields::is_field_value_empty('text', '<p></p>'), 'Text: empty HTML should be empty');
        $this->assertFalse(set_fields::is_field_value_empty('text', 'value'), 'Text: "value" should not be empty');
        $this->assertFalse(set_fields::is_field_value_empty('text', '0'), 'Text: "0" should not be empty');

        // Textarea fields (array format).
        $this->assertTrue(set_fields::is_field_value_empty('textarea', null), 'Textarea: null should be empty');
        $this->assertTrue(
            set_fields::is_field_value_empty('textarea', ['text' => '']),
            'Textarea: empty text should be empty'
        );
        $this->assertTrue(
            set_fields::is_field_value_empty('textarea', ['text' => '   ']),
            'Textarea: whitespace should be empty'
        );
        $this->assertTrue(
            set_fields::is_field_value_empty('textarea', ['text' => '<p></p>']),
            'Textarea: empty HTML should be empty'
        );
        $this->assertFalse(
            set_fields::is_field_value_empty('textarea', ['text' => 'content']),
            'Textarea: "content" should not be empty'
        );

        // Date fields.
        $this->assertTrue(set_fields::is_field_value_empty('date', null), 'Date: null should be empty');
        $this->assertTrue(set_fields::is_field_value_empty('date', 0), 'Date: 0 should be empty');
        $this->assertTrue(set_fields::is_field_value_empty('date', '0'), 'Date: "0" should be empty');
        $this->assertFalse(set_fields::is_field_value_empty('date', 1234567890), 'Date: timestamp should not be empty');
        $this->assertFalse(
            set_fields::is_field_value_empty('date', '1234567890'),
            'Date: timestamp string should not be empty'
        );

        // Select fields.
        $this->assertTrue(set_fields::is_field_value_empty('select', null), 'Select: null should be empty');
        $this->assertTrue(set_fields::is_field_value_empty('select', 0), 'Select: 0 should be empty');
        $this->assertTrue(set_fields::is_field_value_empty('select', '0'), 'Select: "0" should be empty');
        $this->assertFalse(set_fields::is_field_value_empty('select', 1), 'Select: 1 should not be empty');
        $this->assertFalse(set_fields::is_field_value_empty('select', '1'), 'Select: "1" should not be empty');
        $this->assertFalse(set_fields::is_field_value_empty('select', 2), 'Select: 2 should not be empty');

        // Number fields.
        $this->assertTrue(set_fields::is_field_value_empty('number', null), 'Number: null should be empty');
        $this->assertTrue(set_fields::is_field_value_empty('number', ''), 'Number: empty string should be empty');
        $this->assertFalse(set_fields::is_field_value_empty('number', 0), 'Number: 0 should not be empty');
        $this->assertFalse(set_fields::is_field_value_empty('number', '0'), 'Number: "0" should not be empty');
        $this->assertFalse(set_fields::is_field_value_empty('number', 42), 'Number: 42 should not be empty');
        $this->assertFalse(set_fields::is_field_value_empty('number', -5), 'Number: -5 should not be empty');
    }

    /**
     * Test form validation for required fields.
     *
     * Tests that the form prevents setting required fields to empty values.
     */
    public function test_form_validation_required_fields(): void {
        global $DB;

        // Create and login as admin.
        $this->setAdminUser();

        // Create category.
        $category = $this->getDataGenerator()->create_category(['name' => 'Test Category']);

        // Create custom field category.
        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'core_course',
            'area' => 'course',
            'name' => 'Test Fields',
        ]);

        // Create required text field.
        $requiredfield = $this->getDataGenerator()->create_custom_field([
            'categoryid' => $fieldcategory->get('id'),
            'type' => 'text',
            'shortname' => 'requiredfield',
            'name' => 'Required Field',
            'configdata' => json_encode(['required' => '1']),
        ]);

        // Create required textarea field.
        $requiredtextarea = $this->getDataGenerator()->create_custom_field([
            'categoryid' => $fieldcategory->get('id'),
            'type' => 'textarea',
            'shortname' => 'requiredtextarea',
            'name' => 'Required Textarea',
            'configdata' => json_encode(['required' => '1']),
        ]);

        // Create non-required text field for comparison.
        $normalfield = $this->getDataGenerator()->create_custom_field([
            'categoryid' => $fieldcategory->get('id'),
            'type' => 'text',
            'shortname' => 'normalfield',
            'name' => 'Normal Field',
            'configdata' => null,
        ]);

        // Create form instance.
        $form = new set_fields_form(null, ['category' => $category->id]);

        // Test 1: Required text field with empty value and mode "Overwrite" should fail validation.
        $data = [
            'category' => $category->id,
            'customfield_requiredfield' => '',
            'customfieldupdate_requiredfield' => TOOL_COURSEFIELDS_ALL,
            'customfield_normalfield' => 'somevalue',
            'customfieldupdate_normalfield' => TOOL_COURSEFIELDS_NONE,
        ];
        $errors = $form->validation($data, []);
        $this->assertArrayHasKey('customfield_requiredfield', $errors, 'Validation should fail for empty required text field');
        $this->assertStringContainsString('required', strtolower($errors['customfield_requiredfield']));

        // Test 2: Required text field with non-empty value should pass validation.
        $data['customfield_requiredfield'] = 'nonemptyvalue';
        $errors = $form->validation($data, []);
        $this->assertArrayNotHasKey(
            'customfield_requiredfield',
            $errors,
            'Validation should pass for non-empty required text field'
        );

        // Test 3: Required text field with mode "Do not change" should pass validation (even if empty).
        $data['customfield_requiredfield'] = '';
        $data['customfieldupdate_requiredfield'] = TOOL_COURSEFIELDS_NONE;
        $errors = $form->validation($data, []);
        $this->assertArrayNotHasKey(
            'customfield_requiredfield',
            $errors,
            'Validation should pass when mode is "Do not change"'
        );

        // Test 4: Non-required field with empty value should pass validation.
        $data['customfield_normalfield'] = '';
        $data['customfieldupdate_normalfield'] = TOOL_COURSEFIELDS_ALL;
        $data['customfield_requiredfield'] = 'nonemptyvalue';
        $data['customfieldupdate_requiredfield'] = TOOL_COURSEFIELDS_ALL;
        $errors = $form->validation($data, []);
        $this->assertArrayNotHasKey('customfield_normalfield', $errors, 'Validation should pass for empty non-required field');

        // Test 5: Required textarea with empty text should fail validation.
        $data = [
            'category' => $category->id,
            'customfield_requiredtextarea_editor' => [
                'text' => '',
                'format' => FORMAT_HTML,
            ],
            'customfieldupdate_requiredtextarea_editor' => TOOL_COURSEFIELDS_ALL,
        ];
        $errors = $form->validation($data, []);
        $this->assertArrayHasKey(
            'customfield_requiredtextarea_editor',
            $errors,
            'Validation should fail for empty required textarea'
        );

        // Test 6: Required textarea with non-empty text should pass validation.
        $data['customfield_requiredtextarea_editor']['text'] = 'Some content';
        $errors = $form->validation($data, []);
        $this->assertArrayNotHasKey(
            'customfield_requiredtextarea_editor',
            $errors,
            'Validation should pass for non-empty required textarea'
        );

        // Test 7: Required textarea with only whitespace should fail validation.
        $data['customfield_requiredtextarea_editor']['text'] = '   ';
        $errors = $form->validation($data, []);
        $this->assertArrayHasKey(
            'customfield_requiredtextarea_editor',
            $errors,
            'Validation should fail for whitespace-only required textarea'
        );
    }

    /**
     * Test form validation for unique fields.
     *
     * Tests that the form prevents setting unique fields to values that already exist in other courses.
     * Validates that uniqueness checks are system-wide, not just within a category.
     */
    public function test_form_validation_unique_fields(): void {
        global $DB;

        // Create and login as admin.
        $this->setAdminUser();

        // Create two categories.
        $category1 = $this->getDataGenerator()->create_category(['name' => 'Test Category 1']);
        $category2 = $this->getDataGenerator()->create_category(['name' => 'Test Category 2']);

        // Create a course in category 1 with existing value.
        $existingcourse = $this->getDataGenerator()->create_course(['category' => $category1->id]);

        // Create custom field category.
        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'core_course',
            'area' => 'course',
            'name' => 'Test Fields',
        ]);

        // Create unique text field.
        $uniquefield = $this->getDataGenerator()->create_custom_field([
            'categoryid' => $fieldcategory->get('id'),
            'type' => 'text',
            'shortname' => 'uniquefield',
            'name' => 'Unique Field',
            'configdata' => json_encode(['uniquevalues' => '1']),
        ]);

        // Set value for existing course in category 1.
        $courserecord = $DB->get_record('course', ['id' => $existingcourse->id], '*', MUST_EXIST);
        $courserecord->customfield_uniquefield = 'existingvalue';
        update_course($courserecord);

        // Test 1: Trying to set a unique field with an existing value in same category should fail validation.
        $form = new set_fields_form(null, ['category' => $category1->id]);
        $data = [
            'category' => $category1->id,
            'customfield_uniquefield' => 'existingvalue',
            'customfieldupdate_uniquefield' => TOOL_COURSEFIELDS_ALL,
        ];
        $errors = $form->validation($data, []);
        $this->assertArrayHasKey(
            'customfield_uniquefield',
            $errors,
            'Validation should fail when trying to set a unique field with a duplicate value in same category'
        );
        $this->assertStringContainsString('already used', strtolower($errors['customfield_uniquefield']));

        // Test 2: Trying to set a unique field with an existing value in different category should also fail (system-wide check).
        $form2 = new set_fields_form(null, ['category' => $category2->id]);
        $data2 = [
            'category' => $category2->id,
            'customfield_uniquefield' => 'existingvalue',
            'customfieldupdate_uniquefield' => TOOL_COURSEFIELDS_ALL,
        ];
        $errors2 = $form2->validation($data2, []);
        $this->assertArrayHasKey(
            'customfield_uniquefield',
            $errors2,
            'Validation should fail when trying to set a unique field with a duplicate value '
            . 'in different category (system-wide check)'
        );
        $this->assertStringContainsString('already used', strtolower($errors2['customfield_uniquefield']));

        // Test 3: Setting a unique field with a new value should pass validation.
        $data['customfield_uniquefield'] = 'newuniquevalue';
        $errors3 = $form->validation($data, []);
        $this->assertArrayNotHasKey(
            'customfield_uniquefield',
            $errors3,
            'Validation should pass when setting a unique field with a unique value'
        );

        // Test 4: Setting a unique field with mode "Do not change" should pass validation (even if duplicate).
        $data['customfield_uniquefield'] = 'existingvalue';
        $data['customfieldupdate_uniquefield'] = TOOL_COURSEFIELDS_NONE;
        $errors4 = $form->validation($data, []);
        $this->assertArrayNotHasKey(
            'customfield_uniquefield',
            $errors4,
            'Validation should pass when mode is "Do not change" even with duplicate value'
        );

        // Test 5: Setting a unique field with an empty value should pass validation (uniqueness is only for non-empty values).
        $data['customfield_uniquefield'] = '';
        $data['customfieldupdate_uniquefield'] = TOOL_COURSEFIELDS_ALL;
        $errors5 = $form->validation($data, []);
        $this->assertArrayNotHasKey(
            'customfield_uniquefield',
            $errors5,
            'Validation should pass when setting a unique field with an empty value'
        );
    }

    /**
     * Test has_uneditable_courses function.
     *
     * Tests that courses (including courses in subcategories) which the current user is not allowed to edit are detected.
     */
    public function test_has_uneditable_courses(): void {
        global $DB;

        // Create category with subcategory and courses.
        $category = $this->getDataGenerator()->create_category(['name' => 'Test Category']);
        $subcategory = $this->getDataGenerator()->create_category(['name' => 'Test Subcategory', 'parent' => $category->id]);
        $this->getDataGenerator()->create_course(['category' => $category->id]);
        $subcourse = $this->getDataGenerator()->create_course(['category' => $subcategory->id]);

        // Create a user and assign the manager role in the category.
        $user = $this->getDataGenerator()->create_user();
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        $this->getDataGenerator()->role_assign($managerroleid, $user->id, \context_coursecat::instance($category->id));

        // Test 1: The manager is allowed to edit all courses.
        $this->setUser($user);
        $this->assertFalse(set_fields::has_uneditable_courses(\core_course_category::get($category->id)));

        // Test 2: The manager is not allowed to edit the course in the subcategory anymore.
        $this->setAdminUser();
        assign_capability(
            'moodle/course:update',
            CAP_PROHIBIT,
            $managerroleid,
            \context_course::instance($subcourse->id)->id
        );
        $this->setUser($user);
        $this->assertTrue(set_fields::has_uneditable_courses(\core_course_category::get($category->id)));

        // Test 3: Checking the subcategory alone detects the course as well.
        $this->assertTrue(set_fields::has_uneditable_courses(\core_course_category::get($subcategory->id)));
    }

    /**
     * Test has_uneditable_courses function with hidden courses.
     *
     * Tests that hidden courses which the current user cannot see are detected, even if the user would be allowed to edit
     * them, as they are skipped by the adhoc task.
     */
    public function test_has_uneditable_courses_hidden(): void {
        global $DB;

        // Create category with subcategory, a visible course and a hidden course.
        $category = $this->getDataGenerator()->create_category(['name' => 'Test Category']);
        $subcategory = $this->getDataGenerator()->create_category(['name' => 'Test Subcategory', 'parent' => $category->id]);
        $this->getDataGenerator()->create_course(['category' => $category->id]);
        $hiddencourse = $this->getDataGenerator()->create_course(['category' => $subcategory->id, 'visible' => 0]);

        // Create a user and assign the manager role in the category.
        $user = $this->getDataGenerator()->create_user();
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        $this->getDataGenerator()->role_assign($managerroleid, $user->id, \context_coursecat::instance($category->id));

        // Test 1: The manager is allowed to see and edit all courses, including the hidden one.
        $this->setUser($user);
        $this->assertFalse(set_fields::has_uneditable_courses(\core_course_category::get($category->id)));

        // Test 2: The manager is still allowed to edit the hidden course, but not to see it anymore.
        $this->setAdminUser();
        assign_capability(
            'moodle/course:viewhiddencourses',
            CAP_PROHIBIT,
            $managerroleid,
            \context_course::instance($hiddencourse->id)->id
        );
        // The course list of the category is cached in the user's session, including the result of the visibility check.
        \cache::make('core', 'coursecat')->purge();
        $this->setUser($user);
        $this->assertTrue(has_capability('moodle/course:update', \context_course::instance($hiddencourse->id)));
        $this->assertTrue(set_fields::has_uneditable_courses(\core_course_category::get($category->id)));

        // Test 3: Checking the subcategory alone detects the hidden course as well.
        $this->assertTrue(set_fields::has_uneditable_courses(\core_course_category::get($subcategory->id)));
    }

    /**
     * Test that the adhoc task respects the permissions of the user who queued it.
     *
     * Tests that courses which the user is not allowed to edit and locked fields which the user is not allowed to change
     * in a particular course are skipped, while all other courses are updated.
     *
     * @covers \tool_coursefields\task\set_course_fields_task
     */
    public function test_task_respects_user_permissions(): void {
        global $DB;

        // Create and login as admin to set up the environment.
        $this->setAdminUser();

        // Create category and courses.
        $category = $this->getDataGenerator()->create_category(['name' => 'Test Category']);
        $editablecourse = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $uneditablecourse = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $lockedcourse = $this->getDataGenerator()->create_course(['category' => $category->id]);

        // Create custom field category.
        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'core_course',
            'area' => 'course',
            'name' => 'Test Fields',
        ]);

        // Create a normal and a locked text field.
        $this->getDataGenerator()->create_custom_field([
            'categoryid' => $fieldcategory->get('id'),
            'type' => 'text',
            'shortname' => 'normalfield',
            'name' => 'Normal Field',
        ]);
        $this->getDataGenerator()->create_custom_field([
            'categoryid' => $fieldcategory->get('id'),
            'type' => 'text',
            'shortname' => 'lockedfield',
            'name' => 'Locked Field',
            'configdata' => json_encode(['locked' => '1']),
        ]);

        // Create a user and assign the manager role in the category.
        $user = $this->getDataGenerator()->create_user();
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        $this->getDataGenerator()->role_assign($managerroleid, $user->id, \context_coursecat::instance($category->id));

        // Prevent the manager from editing one course and from changing locked fields in another course.
        assign_capability(
            'moodle/course:update',
            CAP_PROHIBIT,
            $managerroleid,
            \context_course::instance($uneditablecourse->id)->id
        );
        assign_capability(
            'moodle/course:changelockedcustomfields',
            CAP_PROHIBIT,
            $managerroleid,
            \context_course::instance($lockedcourse->id)->id
        );

        // Queue the task as the user, like index.php does.
        $this->setUser($user);
        $fields = new \stdClass();
        $fields->customfield_normalfield = 'newvalue';
        $fields->customfieldupdate_normalfield = TOOL_COURSEFIELDS_ALL;
        $fields->customfield_lockedfield = 'newlockedvalue';
        $fields->customfieldupdate_lockedfield = TOOL_COURSEFIELDS_ALL;
        $task = new \tool_coursefields\task\set_course_fields_task();
        $task->set_userid($user->id);
        $task->set_custom_data(
            [
                'category' => $category->id,
                'fields' => $fields,
            ]
        );
        \core\task\manager::queue_adhoc_task($task);

        // Run the task (as the user who queued it).
        $this->setAdminUser();
        $this->runAdhocTasks(\tool_coursefields\task\set_course_fields_task::class);

        // The editable course must have both fields updated.
        $this->assertEquals(
            ['normalfield' => 'newvalue', 'lockedfield' => 'newlockedvalue'],
            $this->get_course_field_values($editablecourse->id)
        );

        // The uneditable course must not have been touched at all.
        $this->assertEquals(
            ['normalfield' => null, 'lockedfield' => null],
            $this->get_course_field_values($uneditablecourse->id)
        );

        // The course with the prohibited locked fields must only have the normal field updated.
        $this->assertEquals(
            ['normalfield' => 'newvalue', 'lockedfield' => null],
            $this->get_course_field_values($lockedcourse->id)
        );
    }

    /**
     * Helper function to get the custom field values of a course, keyed by field shortname.
     *
     * @param int $courseid The course id
     * @return array The custom field values
     */
    private function get_course_field_values(int $courseid): array {
        $handler = course_handler::create();
        $values = [];
        foreach ($handler->get_instance_data($courseid, true) as $data) {
            $values[$data->get_field()->get('shortname')] = $data->get_value();
        }
        return $values;
    }
}
