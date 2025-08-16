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
 * Admin tool "Set course fields" - Language pack
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['coursefields:setfields'] = 'Set the course fields of all courses in a category.';
$string['fieldisrequired'] = '<strong>This custom field is set to be required.</strong> With this tool, you are able override this rule and overwrite this field with empty values. Please do this only if you know what you are doing.';
$string['fieldisunique'] = '<strong>This custom field is set to be unique.</strong> With this tool, you are able override this rule and overwrite this field with all the same values. Please do this only if you know what you are doing.';
$string['nopossiblefieldtype'] = 'Not possible for this field type';
$string['overwritemode'] = 'Update mode';
$string['overwritemode_all'] = 'Overwrite the field for all courses';
$string['overwritemode_empty'] = 'Only set the field for courses where the field is empty';
$string['overwritemode_help'] = 'Choose how you want to update the field values.<br /><ul><li>Do not change the field at all:<br />If you select this option, the field will not be touched at all after you submit this form.</li><li>Overwrite the field for all courses:<br />If you select this option, the field will be overwritten with the given value, regardless of its previous value. This option can also be used to clear the field for all courses, simply by overwriting it with an empty value.</li><li>Only set the field for courses where the field is empty:<br />If you select this option, the field will be only be set to the given value if it does not contain a non-empty value yet. Please note that this mode is only available for selected field types.</li></ul>';
$string['overwritemode_none'] = 'Do not change the field at all';
$string['pluginname'] = 'Set course fields';
$string['privacy:metadata'] = 'The Set course fields plugin does not store any personal data.';
$string['setfields'] = 'Set course fields';
$string['setfieldsinstruction'] = 'Set the course fields for all courses in a category, including subcategories. Choose your options and click "Confirm". On confirmation, Moodle will create an "adhoc task" to set all the course fields in the background. This requires that cron be enabled.';
$string['updatequeued'] = 'An adhoc task has been queued to update all the courses in the category <strong>{$a}</strong>. It will run the next time cron executes.';
