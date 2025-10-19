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
 * Admin tool "Set course fields" - Index page
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$categoryid = required_param('category', PARAM_INT);
$category = \core_course_category::get($categoryid);
$context = \context_coursecat::instance($categoryid);

// Ensure the user can be here.
require_login(0, false);
require_capability('tool/coursefields:setfields', $context);
$returnurl = new \core\url('/course/management.php', ['categoryid' => $categoryid]);

// Current location.
$url = new \core\url(
    '/' . $CFG->admin . '/tool/coursefields/index.php',
    [
        'category' => $categoryid,
    ]
);

// Setup page.
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url($url);
$PAGE->set_title(get_string('coursecatmanagement') . ': ' . get_string('setfields', 'tool_coursefields'));
$PAGE->set_heading($SITE->fullname);

// Create form.
$mform = new \tool_coursefields\set_fields_form('index.php', ['category' => $categoryid]);
if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $mform->get_data()) {
    // Process data.
    $task = new \tool_coursefields\task\set_course_fields_task();
    $task->set_custom_data(
        [
            'category' => $categoryid,
            'fields' => $data,
        ]
    );
    \core\task\manager::queue_adhoc_task($task);
    redirect($returnurl, get_string(
        'updatequeued',
        'tool_coursefields',
        format_string($category->name, true, ['context' => $context])
    ));
} else {
    // Prepare the form.
    $mform->set_data(['category' => $categoryid]);
}

// Print page.
echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
