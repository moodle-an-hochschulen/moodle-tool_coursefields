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
     * Alter course fields information for a single course.
     *
     * @param stdClass $course
     * @param stdClass $fields
     */
    public static function maybe_alter_course_fields($course, $fields) {
        // Do not do anything if the user can't edit the course.
        if (!$course->can_edit()) {
            return;
        }

        // Get the original course record.
        $record = get_course($course->id);

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

                // Continue if the corresponding checkbox element was not activated.
                if (
                    !isset($fields->{'customfieldcheckbox_' . $fieldname}) ||
                        $fields->{'customfieldcheckbox_' . $fieldname} != true
                ) {
                    continue;
                }

                // Set the field value in the course record to be stored later.
                $record->{$key} = $value;
            }
        }

        // Update the course.
        try {
            update_course($record);
        } catch (\moodle_exception $e) {
            debugging($e->getMessage());
        }
    }
}
