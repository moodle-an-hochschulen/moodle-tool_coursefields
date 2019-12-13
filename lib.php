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

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the category navigation to show the course fields tool.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param context $context The category context
 */
function tool_coursefields_extend_navigation_category_settings($navigation, $context) {
    // First check if custom course fields (ccf) are available at all.
    $ccfhandler = core_course\customfield\course_handler::create();
    $hasccf = !empty($ccfhandler->get_categories_with_fields());

    if ($hasccf && has_capability('tool/coursefields:setfields', $context)) {
         $navigation->add_node(
             navigation_node::create(
                 get_string('setfields', 'tool_coursefields'),
                    new moodle_url(
                        '/admin/tool/coursefields/index.php',
                        array('category' => $context->instanceid)
                    ),
                    navigation_node::TYPE_SETTING,
                    null,
                    null,
                    new pix_icon('i/settings', '')
                    )
                );
    }
}
