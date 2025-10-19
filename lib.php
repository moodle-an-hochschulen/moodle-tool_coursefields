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
 * Admin tool "Set course fields" - Library
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extends the category navigation to show the course fields tool.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $context The category context
 */
function tool_coursefields_extend_navigation_category_settings($navigation, $context) {
    global $CFG;

    // First check if any custom course fields (ccf) are configured at all.
    // Just check for existing categories and not for existing course fields for performance
    // reasons.
    // In the remaining edge case where the admin has created custom course field categories
    // but not any single custom course field, the plugin's UI remains confusing, but nothing
    // will break. This is a reasonable trade-off.
    $ccfhandler = core_course\customfield\course_handler::create();
    $hasccf = !empty($ccfhandler->get_fields());

    // If there are custom course field category, add an item to the category navigation.
    if ($hasccf && has_capability('tool/coursefields:setfields', $context)) {
         $navigation->add_node(
             navigation_node::create(
                 get_string('setfields', 'tool_coursefields'),
                 new \core\url(
                     '/' . $CFG->admin . '/tool/coursefields/index.php',
                     ['category' => $context->instanceid]
                 ),
                 navigation_node::TYPE_SETTING,
                 null,
                 null,
                 new \core\output\pix_icon('i/settings', '')
             )
         );
    }
}
