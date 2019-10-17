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
 * Admin tool "Set course fields" - Adhoc task
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursefields\task;

defined('MOODLE_INTERNAL') || die;

/**
 * Adhoc task for setting course fields.
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_course_fields_task extends \core\task\adhoc_task {
    /**
     * Return the name of the component.
     *
     * @return string The name of the component.
     */
    public function get_component() {
        return 'tool_coursefields';
    }

    /**
     * Execute the adhoc task.
     */
    public function execute() {
        $data = $this->get_custom_data();
        if (empty($data->category)) {
            mtrace("No category id");
            return;
        }
        if (!isset($data->fields)) {
            mtrace("No fields specified");
            return;
        }
        $category = \core_course_category::get($data->category);
        if (!$category) {
            mtrace("Invalid category id");
            return;
        }
        $courses = $category->get_courses(
            array(
                'recursive' => true,
                'limit' => 0
            )
        );
        foreach ($courses as $course) {
            \tool_coursefields\set_fields::maybe_alter_course_fields($course, $data->fields);
        }
    }
}
