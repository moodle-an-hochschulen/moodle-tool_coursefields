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
 * Admin tool "Set course fields" - Helper functions
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursefields;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/admin/tool/coursefields/lib.php');

/**
 * Helper functions for tool_coursefields.
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_fields {
    /**
     * Check if a field type supports "Only if empty" mode.
     *
     * @param string $fieldtype The field type to check
     * @return bool True if the field type supports "Only if empty" mode
     */
    public static function supports_empty_mode($fieldtype) {
        return in_array($fieldtype, ['text', 'textarea', 'date', 'select', 'number']);
    }

    /**
     * Check if a field value is considered empty based on the field type.
     *
     * @param string $fieldtype The field type (text, textarea, date, select, number, etc.).
     * @param mixed $fieldvalue The field value to check.
     * @return bool True if the field value is considered empty.
     */
    public static function is_field_value_empty($fieldtype, $fieldvalue) {
        // For text fields: null or empty string after trimming means empty.
        if ($fieldtype == 'text') {
            if ($fieldvalue === null) {
                return true;
            }
            if (is_string($fieldvalue)) {
                return (trim(strip_tags($fieldvalue)) === '');
            }
            return false;
        }

        // For textarea fields: null or empty string after trimming means empty.
        // Textarea can be either a string or an array with 'text' key.
        if ($fieldtype == 'textarea') {
            if ($fieldvalue === null) {
                return true;
            }
            if (is_array($fieldvalue) && isset($fieldvalue['text'])) {
                return (trim(strip_tags($fieldvalue['text'])) === '');
            }
            if (is_string($fieldvalue)) {
                return (trim(strip_tags($fieldvalue)) === '');
            }
            return false;
        }

        // For date fields: 0 (int) or null means empty.
        if ($fieldtype == 'date') {
            return ($fieldvalue === null || $fieldvalue === 0);
        }

        // For select fields: 0 (int) or null means empty.
        if ($fieldtype == 'select') {
            return ($fieldvalue === null || $fieldvalue === 0);
        }

        // For number fields: null or empty string means empty. 0 is a valid value.
        if ($fieldtype == 'number') {
            return ($fieldvalue === null || $fieldvalue === '');
        }

        // Any other fieldtype.
        return false;
    }

    /**
     * Output a trace message, but only if not running in PHPUnit (as PHPUnit would assess the output as problem).
     *
     * @param string $message The message to output
     */
    private static function trace($message) {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            mtrace($message);
        }
    }

    /**
     * Alter course fields information for a single course.
     *
     * @param \core_course_list_element $course
     * @param \stdClass $fields
     */
    public static function maybe_alter_course_fields($course, $fields) {
        // Do not do anything if the user can't edit the course.
        if (!$course->can_edit()) {
            return;
        }

        // Get the original course record.
        $record = get_course($course->id);

        // Get the custom fields data for this course.
        $handler = \core_course\customfield\course_handler::create();
        $customfields = $handler->get_instance_data($course->id);

        // Trace.
        self::trace("Now processing: Course with ID {$course->id}.");

        // Iterate over all submitted fields.
        foreach ($fields as $key => $value) {
            // Do only if we are really dealing with a custom field now.
            if (substr($key, 0, 12) == 'customfield_') {
                // At least customfield_textarea values are not strings but associative arrays.
                // When customfield_textarea field ist set by /course/edit.php, this works fine.
                // However, as the field value is json_encoded and json_decoded and as this,
                // due to the nature of json_encoding transforms the associative array into an
                // object, we have to handle this case here explicitely.
                if (is_object($value)) {
                    $value = (array) $value;
                }

                // Get the field name.
                $fieldname = substr($key, 12);

                // Trace.
                self::trace("... Now processing: Form field '{$fieldname}'.");

                // If this field does not have an update mode set, skip it.
                if (!isset($fields->{'customfieldupdate_' . $fieldname})) {
                    // Trace.
                    self::trace("... ... No update mode set for field '{$fieldname}' at all. This should not happen - skipping.");

                    continue;
                }

                // Get the update mode.
                $updatemode = $fields->{'customfieldupdate_' . $fieldname};

                // Skip if update mode is "none" - don't change this field at all.
                if ($updatemode == TOOL_COURSEFIELDS_NONE) {
                    // Trace.
                    self::trace("... ... Update mode: None - skipping.");

                    continue;
                }

                // If update mode is "empty" and the field already has a value, skip it as well.
                if ($updatemode == TOOL_COURSEFIELDS_EMPTY) {
                    // Trace.
                    self::trace("... ... Update mode: Only if empty.");

                    // Initlialize a flag which indicates that the field already has a value.
                    $fieldhasvalue = false;

                    // Iterate over all custom fields data.
                    foreach ($customfields as $data) {
                        // Get field type.
                        $fieldtype = $data->get_field()->get('type');

                        // If we are looking at the correct field now (i.e. the one which we want to update).
                        // Note that the textarea field needs a special check here.
                        if (
                            $data->get_field()->get('shortname') === $fieldname ||
                                $fieldtype == 'textarea' && $data->get_field()->get('shortname') . '_editor' === $fieldname
                        ) {
                            // Get field value.
                            $fieldvalue = $data->get_value();

                            // Debug: Log the field value and its type.
                            self::trace("... ... Current field value: " . var_export($fieldvalue, true) .
                                    " (type: " . gettype($fieldvalue) . ")");

                            // For fields of unsupported types: Always skip it as this mode should have been never set in the GUI.
                            if (\tool_coursefields\set_fields::supports_empty_mode($fieldtype) == false) {
                                // Trace.
                                self::trace("... ... Field type '{$fieldtype}' does not support 'Only if empty' mode - skipping.");
                                $fieldhasvalue = true;
                            } else {
                                // Use centralized logic to check if field is empty.
                                $isempty = self::is_field_value_empty($fieldtype, $fieldvalue);

                                if (!$isempty) {
                                    // Trace.
                                    self::trace("... ... Field '{$fieldname}' already has a value - skipping.");
                                    $fieldhasvalue = true;
                                } else {
                                    // Trace.
                                    self::trace("... ... Field '{$fieldname}' is empty - will update.");
                                }
                            }

                            break;
                        }
                    }

                    // Skip this field if it already has a value.
                    if ($fieldhasvalue) {
                        continue;
                    }
                }

                // If update mode is "all".
                if ($updatemode == TOOL_COURSEFIELDS_ALL) {
                    // Trace.
                    self::trace("... ... Update mode: Always.");
                }

                // Trace.
                if (is_array($value)) {
                    if (isset($value['text'])) {
                        $valueformtrace = mb_strimwidth($value['text'], 0, 50, "...");
                    } else {
                        $valueformtrace = json_encode($value);
                    }
                } else {
                    $valueformtrace = $value;
                }
                self::trace("... ... Setting field to the given new value: {$valueformtrace}");

                // Set the field value in the course record to be stored later.
                $record->{$key} = $value;
            }
        }

        // Trace.
        self::trace("... Now updating the course in the database.");

        // Update the course.
        try {
            update_course($record);

            // Trace.
            self::trace("... Done.");
        } catch (\moodle_exception $e) {
            // Trace.
            self::trace("... Error.");

            debugging($e->getMessage());
        }
    }
}
