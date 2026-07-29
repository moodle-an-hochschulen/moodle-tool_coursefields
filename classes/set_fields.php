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
     * Check if a field type supports "Clear" mode.
     *
     * @param string $fieldtype The field type to check
     * @return bool True if the field type supports "Clear" mode
     */
    public static function supports_clear_mode($fieldtype) {
        return in_array($fieldtype, ['text', 'textarea', 'date', 'select', 'number', 'checkbox']);
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
        // We have to request _all_ fields and not just the visible ones here. Otherwise, fields which are configured
        // with 'Visible to' = 'Nobody' (or with 'Visible to' = 'Teachers' if the user who runs this code does not have
        // the moodle/course:update capability in the course) would be dropped silently and could never be set by this
        // plugin. Requesting all fields is safe as the loop below only touches the fields for which an update mode was
        // submitted in the form, and the form itself only offers the fields which the user is allowed to edit.
        $handler = \core_course\customfield\course_handler::create();
        $customfields = $handler->get_instance_data($course->id, true);

        // Trace.
        self::trace("Now processing: Course with ID {$course->id}.");

        // Iterate over all custom course fields that exist in Moodle.
        // This approach is independent of whether fields are hidden in the form or not.
        foreach ($customfields as $data) {
            // Get field information.
            $fieldtype = $data->get_field()->get('type');
            $fieldshortname = $data->get_field()->get('shortname');
            $isunique = ($data->get_field()->get_configdata_property('uniquevalues') == 1);
            $isrequired = ($data->get_field()->get_configdata_property('required') == 1);

            // For textarea fields, the form uses _editor suffix.
            $formfieldname = ($fieldtype === 'textarea') ? $fieldshortname . '_editor' : $fieldshortname;

            // Trace.
            self::trace("... Now processing: Custom field '{$fieldshortname}' (type: {$fieldtype}).");

            // Check if there's an update mode set for this field in the submitted form data.
            $updatemodekey = 'customfieldupdate_' . $formfieldname;
            if (!isset($fields->{$updatemodekey})) {
                // Trace.
                self::trace("... ... No update mode set for this field - skipping.");
                continue;
            }

            // Get the update mode.
            $updatemode = $fields->{$updatemodekey};

            // Get the field value key.
            $fieldvaluekey = 'customfield_' . $formfieldname;

            // Get the submitted value (will be null if field was hidden via hideIf).
            $value = isset($fields->{$fieldvaluekey}) ? $fields->{$fieldvaluekey} : null;

            // At least customfield_textarea values are not strings but associative arrays.
            // When customfield_textarea field ist set by /course/edit.php, this works fine.
            // However, as the field value is json_encoded and json_decoded and as this,
            // due to the nature of json_encoding transforms the associative array into an
            // object, we have to handle this case here explicitely.
            if (is_object($value)) {
                $value = (array) $value;
            }

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

                // Safety net: For unique fields, EMPTY mode should never have been set in the GUI.
                if ($isunique) {
                    // Trace.
                    self::trace("... ... Field is unique - 'Only if empty' mode not allowed - skipping.");
                    continue;
                }

                // Get current field value from database.
                $currentfieldvalue = $data->get_value();

                // Debug: Log the field value and its type.
                self::trace("... ... Current field value: " . var_export($currentfieldvalue, true) .
                        " (type: " . gettype($currentfieldvalue) . ")");

                // For fields of unsupported types: Always skip it as this mode should have been never set in the GUI.
                if (self::supports_empty_mode($fieldtype) == false) {
                    // Trace.
                    self::trace("... ... Field type '{$fieldtype}' does not support 'Only if empty' mode - skipping.");
                    continue;
                }

                // Use centralized logic to check if field is empty.
                $isempty = self::is_field_value_empty($fieldtype, $currentfieldvalue);

                if (!$isempty) {
                    // Trace.
                    self::trace("... ... Field '{$fieldshortname}' already has a value - skipping.");
                    continue;
                } else {
                    // Trace.
                    self::trace("... ... Field '{$fieldshortname}' is empty - will update.");
                }
            }

            // If update mode is "clear" - set field to empty value.
            if ($updatemode == TOOL_COURSEFIELDS_CLEAR) {
                // Trace.
                self::trace("... ... Update mode: Clear field.");

                // Safety net: For required fields, CLEAR mode should never have been set in the GUI.
                if ($isrequired) {
                    // Trace.
                    self::trace("... ... Field is required - 'Clear field' mode not allowed - skipping.");
                    continue;
                }

                // For fields of unsupported types: Always skip it as this mode should have been never set in the GUI.
                if (self::supports_clear_mode($fieldtype) == false) {
                    // Trace.
                    self::trace("... ... Field type '{$fieldtype}' does not support 'Clear' mode - skipping.");
                    continue;
                }

                // Set appropriate empty value based on field type.
                if ($fieldtype === 'textarea') {
                    // Textarea - set to empty array with text and format.
                    $value = ['text' => '', 'format' => FORMAT_HTML];
                } else if ($fieldtype === 'date' || $fieldtype === 'select' || $fieldtype === 'checkbox') {
                    // Date, select, and checkbox fields - set to 0.
                    $value = 0;
                } else {
                    // Text and number fields - set to empty string.
                    $value = '';
                }

                self::trace("... ... Setting field to empty value for field type '{$fieldtype}'.");
            }

            // If update mode is "all".
            if ($updatemode == TOOL_COURSEFIELDS_ALL) {
                // Trace.
                self::trace("... ... Update mode: Always.");

                // Safety net: For unique fields, ALL mode should never have been set in the GUI.
                if ($isunique) {
                    // Trace.
                    self::trace("... ... Field is unique - 'Always' mode not allowed - skipping.");
                    continue;
                }
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
            $record->{$fieldvaluekey} = $value;
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
