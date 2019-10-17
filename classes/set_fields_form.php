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

/**
 * Form for changing course fields.
 *
 * @package    tool_coursefields
 * @copyright  2019 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 *             based on tool_coursedates, copyright 2017 Lafayette College ITS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_fields_form extends \moodleform
{
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
                $shortname = $shortname.'_editor';
            }

            // Get some more field metadata.
            $elementname = 'customfield_'.$shortname;
            $formattedname = $field->get_formatted_name();

            // Add a header to help the user identify the following form element as a group.
            $headerelementname = 'customfieldheader_'.$shortname;
            $headerelement = $mform->createElement('static', $headerelementname, '<h4>'.$formattedname.'</h4>');
            $mform->insertElementBefore($headerelement, $elementname);

            // Add a checkbox element in front of the field to control if this value should be overwritten.
            $checkboxelementname = 'customfieldcheckbox_'.$shortname;
            $checkboxelement = $mform->createElement('advcheckbox', $checkboxelementname, '',
                    get_string('overwritefield', 'tool_coursefields'));
            $mform->insertElementBefore($checkboxelement, $elementname);

            // Add a static element in front of the field to inform the admin about the details of the field.
            $staticelementname = 'customfieldstatic_'.$shortname;
            $staticelementnotes = array();
            if ($field->get_configdata_property('required') == 1) {
                $staticelementnotes[] = get_string('fieldisrequired', 'tool_coursefields');
            }
            if ($field->get_configdata_property('uniquevalues') == 1) {
                $staticelementnotes[] = get_string('fieldisunique', 'tool_coursefields');
            }
            if (count($staticelementnotes) > 0) {
                $staticelement = $mform->createElement('static', $staticelementname, '', implode($staticelementnotes, '<br />'));
                $mform->insertElementBefore($staticelement, $elementname);
            }

            // Disable the field as long as the checkbox element is not activated.
            $mform->disabledIf($elementname, $checkboxelementname);

            unset($shortname, $elementname, $formattedname, $headerelementname, $headerelement, $checkboxelementname,
                    $checkboxelement, $staticelementname, $staticelementnotes, $staticelement);
        }

        // Get rid of any required rules in this form as these won't validate correctly with the checkbox elements.
        $mform->_required = array();
        $mform->_rules = array();

        // Metadata.
        $mform->addElement('hidden', 'category');
        $mform->setType('category', PARAM_INT);

        // Buttons.
        $this->add_action_buttons(true, get_string('confirm'));
    }
}
