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
 * Revoke credit for a user.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class credit_revoke extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $entry = $this->_customdata['ledgerentry'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $entry->id);

        $ev = $entry->evidencejson ? json_decode($entry->evidencejson, true) : [];

        $mform->addElement('static', 'stractivity', get_string('activityname', 'tool_mutrain'), s($ev['activityname'] ?? '-'));
        $mform->addElement('static', 'strcredits', get_string('credits', 'tool_mutrain'), format_float($entry->credits, 1));
        $mform->addElement('static', 'strdate', get_string('dateofactivity', 'tool_mutrain'),
            userdate($entry->timecredited, get_string('strftimedate', 'langconfig')));

        $mform->addElement('textarea', 'reason', get_string('creditrevokereason', 'tool_mutrain'), ['rows' => 3, 'cols' => 50]);
        $mform->setType('reason', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('creditrevoke', 'tool_mutrain'));
    }

    #[\Override]
    public function validation($data, $files) {
        return parent::validation($data, $files);
    }
}
