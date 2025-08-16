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
 * Admin tool "Set course fields" - Form
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_coursefields;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/admin/tool/coursefields/lib.php');

/**
 * Form for changing course fields.
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_fields_form extends \moodleform {
    /**
     * Defines the form.
     */
    public function definition() {
        // Get the form.
        $mform = $this->_form;

        // Explanatory instruction.
        $mform->addElement('html', '<p>' . get_string('setfieldsinstruction', 'tool_coursefields') . '</p>');

        // Add all existing custom fields with the Custom Field API.
        $handler = \core_course\customfield\course_handler::create();
        $handler->set_parent_context(\context_coursecat::instance($this->_customdata['category']));
        $handler->instance_form_definition($mform);

        // Get all existing custom fields once more as a list for modifying the form.
        $editablefields = $handler->get_editable_fields(0);

        // Iterate over all existing custom fields.
        foreach ($editablefields as $field) {
            // Get the field shortname.
            $shortname = $field->get('shortname');

            // If we are dealing with a customfield_textarea fieldtype, the shortname needs special treatment.
            // For now, this special treatment is hardcoded.
            if ($field->get('type') == 'textarea') {
                $shortname = $shortname . '_editor';
            }

            // Get some more field metadata.
            $elementname = 'customfield_' . $shortname;
            $formattedname = $field->get_formatted_name();

            // Add a header to help the user identify the following form element as a group.
            $headerelementname = 'customfieldheader_' . $shortname;
            $headerelement = $mform->createElement('static', $headerelementname, '<h4>' . $formattedname . '</h4>');
            $mform->insertElementBefore($headerelement, $elementname);

            // Add a radio button group element in front of the field to control if and how this value should be updated.
            $rgroupname = 'customfieldupdate_' . $shortname;
            $rgroup = [
                $mform->createElement(
                    'radio',
                    $rgroupname,
                    '',
                    get_string('overwritemode_none', 'tool_coursefields'),
                    TOOL_COURSEFIELDS_NONE
                ),
                $mform->createElement(
                    'radio',
                    $rgroupname,
                    '',
                    get_string('overwritemode_all', 'tool_coursefields'),
                    TOOL_COURSEFIELDS_ALL
                ),
            ];

            // Check if field type supports "Only if empty" mode.
            $fieldtype = $field->get('type');
            $supportsemptymode = \tool_coursefields\set_fields::supports_empty_mode($fieldtype);

            // Add "Only if empty" option (disabled if not supported).
            if (!$supportsemptymode) {
                $emptyradiostring = get_string('overwritemode_empty', 'tool_coursefields').' ['.get_string('nopossiblefieldtype', 'tool_coursefields').']';
            } else {
                $emptyradiostring = get_string('overwritemode_empty', 'tool_coursefields');
            }
            $emptyradio = $mform->createElement(
                'radio',
                $rgroupname,
                '',
                $emptyradiostring,
                TOOL_COURSEFIELDS_EMPTY
            );
            if (!$supportsemptymode) {
                $emptyradio->updateAttributes(['disabled' => 'disabled']);
            }
            $rgroup[] = $emptyradio;

            // Finish the radio group element and add it to the form.
            $mform->addGroup(
                $rgroup,
                'customfieldgroup_' . $shortname,
                get_string('overwritemode', 'tool_coursefields'),
                '<br>',
                false
            );
            $mform->addHelpButton('customfieldgroup_' . $shortname, 'overwritemode', 'tool_coursefields');
            $mform->setDefault($rgroupname, TOOL_COURSEFIELDS_NONE);
            $mform->insertElementBefore($mform->removeElement('customfieldgroup_' . $shortname, false), $elementname);

            // Add a static element in front of the field to inform the admin about the details of the field.
            $staticelementname = 'customfieldstatic_' . $shortname;
            $staticelementnotes = [];
            if ($field->get_configdata_property('required') == 1) {
                $staticelementnotes[] = get_string('fieldisrequired', 'tool_coursefields');
            }
            if ($field->get_configdata_property('uniquevalues') == 1) {
                $staticelementnotes[] = get_string('fieldisunique', 'tool_coursefields');
            }
            if (count($staticelementnotes) > 0) {
                $staticelement = $mform->createElement('static', $staticelementname, '', implode('<br />', $staticelementnotes));
                $mform->insertElementBefore($staticelement, $elementname);
            }

            // Hide the field if "Do not change" or "Clear" is selected.
            $mform->hideIf($elementname, 'customfieldupdate_' . $shortname, 'eq', TOOL_COURSEFIELDS_NONE);

            unset(
                $shortname,
                $elementname,
                $formattedname,
                $headerelementname,
                $headerelement,
                $rgroupname,
                $rgroup,
                $staticelementname,
                $staticelementnotes,
                $staticelement
            );
        }

        // Get rid of any required rules in this form as these won't validate correctly with the checkbox elements.
        $mform->_required = [];
        $mform->_rules = [];

        // Metadata.
        $mform->addElement('hidden', 'category');
        $mform->setType('category', PARAM_INT);

        // Buttons.
        $this->add_action_buttons(true, get_string('confirm'));
    }
}
