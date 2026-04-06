<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_mutrain\local\form;

/**
 * Post credit for a user.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class credit_post extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $framework = $this->_customdata['framework'];

        $mform->addElement('text', 'activityname', get_string('activityname', 'tool_mutrain'), 'maxlength="255" size="50"');
        $mform->setType('activityname', PARAM_TEXT);
        $mform->addRule('activityname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'provider', get_string('provider', 'tool_mutrain'), 'maxlength="255" size="50"');
        $mform->setType('provider', PARAM_TEXT);
        $mform->addRule('provider', get_string('required'), 'required', null, 'client');

        // Build credit type options from category rules.
        $rules = \tool_mutrain\api::get_category_rules((int)$framework->id);
        if ($rules) {
            $options = [];
            foreach ($rules as $rule) {
                $options[$rule->categoryname] = $rule->categoryname;
            }
            // Add General if not already present.
            if (!isset($options['General'])) {
                $options = ['General' => 'General'] + $options;
            }
        } else {
            $options = [
                'General' => 'General',
                'Ethics' => 'Ethics',
                'Clinical' => 'Clinical',
                'Technical' => 'Technical',
            ];
        }
        $mform->addElement('select', 'credittype', get_string('credittype', 'tool_mutrain'), $options);

        $mform->addElement('text', 'credits', get_string('credits', 'tool_mutrain'), 'size="10"');
        $mform->setType('credits', PARAM_RAW);
        $mform->addRule('credits', get_string('required'), 'required', null, 'client');

        $mform->addElement('date_selector', 'dateofactivity', get_string('dateofactivity', 'tool_mutrain'), [
            'optional' => false,
            'startyear' => 2000,
            'stopyear' => (int)date('Y'),
        ]);

        $mform->addElement('textarea', 'notes', get_string('creditnotes', 'tool_mutrain'), ['rows' => 3, 'cols' => 50]);
        $mform->setType('notes', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('creditpost', 'tool_mutrain'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (trim($data['activityname'] ?? '') === '') {
            $errors['activityname'] = get_string('required');
        }
        if (trim($data['provider'] ?? '') === '') {
            $errors['provider'] = get_string('required');
        }

        $credits = str_replace(',', '.', $data['credits'] ?? '');
        if (!is_numeric($credits) || (float)$credits <= 0) {
            $errors['credits'] = get_string('error');
        } else if ((float)$credits > 50) {
            $errors['credits'] = get_string('error');
        }

        return $errors;
    }
}
