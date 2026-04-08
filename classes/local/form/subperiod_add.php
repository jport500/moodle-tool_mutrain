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
 * Add sub-period requirement to training framework.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class subperiod_add extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $framework = $this->_customdata['framework'];

        $mform->addElement('hidden', 'frameworkid');
        $mform->setType('frameworkid', PARAM_INT);
        $mform->setDefault('frameworkid', $framework->id);

        $mform->addElement('text', 'name', get_string('subperiodname', 'tool_mutrain'), 'maxlength="255" size="40"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $modes = [
            'relative' => get_string('subperiodmoderelative', 'tool_mutrain'),
            'absolute' => get_string('subperiodmodeabsolute', 'tool_mutrain'),
        ];
        $mform->addElement('select', 'mode', get_string('subperiodmode', 'tool_mutrain'), $modes);
        $mform->setDefault('mode', 'relative');

        $mform->addElement('text', 'offsetdays', get_string('subperiodoffsetdays', 'tool_mutrain'), 'size="10"');
        $mform->setType('offsetdays', PARAM_INT);
        $mform->setDefault('offsetdays', 0);
        $mform->hideIf('offsetdays', 'mode', 'neq', 'relative');

        $mform->addElement('text', 'lengthdays', get_string('subperiodlengthdays', 'tool_mutrain'), 'size="10"');
        $mform->setType('lengthdays', PARAM_INT);
        $mform->setDefault('lengthdays', 365);
        $mform->hideIf('lengthdays', 'mode', 'neq', 'relative');

        $mform->addElement('date_selector', 'startdate', get_string('subperiodstartdate', 'tool_mutrain'));
        $mform->hideIf('startdate', 'mode', 'neq', 'absolute');

        $mform->addElement('date_selector', 'enddate', get_string('subperiodenddate', 'tool_mutrain'));
        $mform->hideIf('enddate', 'mode', 'neq', 'absolute');

        $mform->addElement('text', 'requiredcredits', get_string('subperiodrequiredcredits', 'tool_mutrain'), 'size="10"');
        $mform->setType('requiredcredits', PARAM_RAW);
        $mform->setDefault('requiredcredits', '0');

        $this->add_action_buttons(true, get_string('subperiodadd', 'tool_mutrain'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (trim($data['name'] ?? '') === '') {
            $errors['name'] = get_string('subperiodnameinvalid', 'tool_mutrain');
        }

        $mode = $data['mode'] ?? 'relative';
        if ($mode === 'relative') {
            if ((int)($data['lengthdays'] ?? 0) < 1) {
                $errors['lengthdays'] = get_string('subperiodlengthinvalid', 'tool_mutrain');
            }
        } else if ($mode === 'absolute') {
            if ((int)($data['enddate'] ?? 0) <= (int)($data['startdate'] ?? 0)) {
                $errors['enddate'] = get_string('subperioddatesinvalid', 'tool_mutrain');
            }
        }

        $rc = str_replace(',', '.', (string)($data['requiredcredits'] ?? '0'));
        if (!is_numeric($rc) || (float)$rc < 0) {
            $errors['requiredcredits'] = get_string('subperiodrequiredcreditsinvalid', 'tool_mutrain');
        }

        return $errors;
    }
}
