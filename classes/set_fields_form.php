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

            // Check field properties.
            $fieldtype = $field->get('type');
            $isunique = ($field->get_configdata_property('uniquevalues') == 1);
            $isrequired = ($field->get_configdata_property('required') == 1);
            $supportsemptymode = \tool_coursefields\set_fields::supports_empty_mode($fieldtype);

            // Create "Do not change" option (always available).
            $rgroup = [
                $mform->createElement(
                    'radio',
                    $rgroupname,
                    '',
                    get_string('overwritemode_none', 'tool_coursefields'),
                    TOOL_COURSEFIELDS_NONE
                ),
            ];

            // Create "Overwrite" option (disabled for unique fields).
            $overwriteradiostring = get_string('overwritemode_all', 'tool_coursefields');
            if ($isunique) {
                $overwriteradiostring .= ' [' . get_string('nopossibleunique', 'tool_coursefields') . ']';
            }
            $overwriteradio = $mform->createElement(
                'radio',
                $rgroupname,
                '',
                $overwriteradiostring,
                TOOL_COURSEFIELDS_ALL
            );
            if ($isunique) {
                $overwriteradio->updateAttributes(['disabled' => 'disabled']);
            }
            $rgroup[] = $overwriteradio;

            // Create "Only if empty" option (disabled if field type doesn't support it or if field is unique).
            $emptyradiostring = get_string('overwritemode_empty', 'tool_coursefields');
            $emptydisabled = false;
            if ($isunique) {
                $emptyradiostring .= ' [' . get_string('nopossibleunique', 'tool_coursefields') . ']';
                $emptydisabled = true;
            } else if (!$supportsemptymode) {
                $emptyradiostring .= ' [' . get_string('nopossiblefieldtype', 'tool_coursefields') . ']';
                $emptydisabled = true;
            }
            $emptyradio = $mform->createElement(
                'radio',
                $rgroupname,
                '',
                $emptyradiostring,
                TOOL_COURSEFIELDS_EMPTY
            );
            if ($emptydisabled) {
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

    /**
     * Form validation.
     *
     * @param array $data Form data
     * @param array $files Form files
     * @return array Validation errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Get the custom fields to check required fields.
        $handler = \core_course\customfield\course_handler::create();
        $handler->set_parent_context(\context_coursecat::instance($this->_customdata['category']));
        $editablefields = $handler->get_editable_fields(0);

        // Check each field for required validation.
        foreach ($editablefields as $field) {
            $shortname = $field->get('shortname');
            $fieldtype = $field->get('type');

            // Handle textarea special case.
            if ($fieldtype == 'textarea') {
                $shortname .= '_editor';
            }

            $elementname = 'customfield_' . $shortname;
            $updatemodename = 'customfieldupdate_' . $shortname;

            // Check if this is a required field.
            $isrequired = ($field->get_configdata_property('required') == 1);
            $isunique = ($field->get_configdata_property('uniquevalues') == 1);

            // Only validate if user selected "Overwrite" or "Only if empty" mode.
            if (isset($data[$updatemodename]) &&
                ($data[$updatemodename] == TOOL_COURSEFIELDS_ALL || $data[$updatemodename] == TOOL_COURSEFIELDS_EMPTY)) {

                // Get the field value from submitted data.
                $fieldvalue = isset($data[$elementname]) ? $data[$elementname] : null;

                // Validate required fields.
                if ($isrequired) {
                    // Use centralized logic to check if field is empty.
                    $isempty = \tool_coursefields\set_fields::is_field_value_empty($fieldtype, $fieldvalue);

                    if ($isempty) {
                        $errors[$elementname] = get_string('fieldrequirederror', 'tool_coursefields', $field->get_formatted_name());
                    }
                }

                // Validate unique fields.
                if ($isunique && !isset($errors[$elementname])) {
                    // Check if the value is not empty (we only check uniqueness for non-empty values).
                    $isempty = \tool_coursefields\set_fields::is_field_value_empty($fieldtype, $fieldvalue);

                    if (!$isempty) {
                        // Use the customfield API's built-in unique validation which checks system-wide.
                        // Create a temporary data controller instance for validation.
                        $datacontroller = \core_customfield\data_controller::create(0, null, $field);

                        // Prepare validation data in the format expected by instance_form_validation.
                        $validationdata = [$elementname => $fieldvalue];

                        // Run the built-in unique validation (checks system-wide in customfield_data table).
                        $validationerrors = $datacontroller->instance_form_validation($validationdata, []);

                        // Add any errors to our errors array.
                        if (!empty($validationerrors)) {
                            $errors = array_merge($errors, $validationerrors);
                        }
                    }
                }
            }
        }

        return $errors;
    }
}
