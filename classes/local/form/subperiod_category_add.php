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
 * Add category rule to a sub-period.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class subperiod_category_add extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $subperiod = $this->_customdata['subperiod'];

        $mform->addElement('hidden', 'subperiodid');
        $mform->setType('subperiodid', PARAM_INT);
        $mform->setDefault('subperiodid', $subperiod->id);

        $mform->addElement('text', 'categoryname', get_string('categoryname', 'tool_mutrain'), 'maxlength="255" size="30"');
        $mform->setType('categoryname', PARAM_TEXT);
        $mform->addRule('categoryname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'mincredits', get_string('categorymincredits', 'tool_mutrain'), 'size="10"');
        $mform->setType('mincredits', PARAM_RAW);
        $mform->addRule('mincredits', get_string('required'), 'required', null, 'client');

        $this->add_action_buttons(true, get_string('subperiodcategoryadd', 'tool_mutrain'));
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $subperiod = $this->_customdata['subperiod'];

        if (trim($data['categoryname']) === '') {
            $errors['categoryname'] = get_string('required');
        } else if ($DB->record_exists('tool_mutrain_subperiod_category', [
            'subperiodid' => $subperiod->id,
            'categoryname' => trim($data['categoryname']),
        ])) {
            $errors['categoryname'] = get_string('subperiodcategoryduplicate', 'tool_mutrain');
        }

        $mincredits = str_replace(',', '.', $data['mincredits']);
        if (!is_numeric($mincredits) || (float)$mincredits <= 0) {
            $errors['mincredits'] = get_string('subperiodmincreditsinvalid', 'tool_mutrain');
        }

        return $errors;
    }
}
