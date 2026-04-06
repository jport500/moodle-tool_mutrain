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

namespace tool_mutrain\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use tool_mutrain\api;

/**
 * Revokes a ledger entry.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class revoke_credit extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'ledgerid' => new external_value(PARAM_INT, 'Ledger record id to revoke'),
        ]);
    }

    /**
     * Revokes a ledger entry.
     *
     * @param int $ledgerid
     * @return array
     */
    public static function execute(int $ledgerid): array {
        global $DB, $USER;

        ['ledgerid' => $ledgerid] = self::validate_parameters(self::execute_parameters(), [
            'ledgerid' => $ledgerid,
        ]);

        $record = $DB->get_record('tool_mutrain_ledger', ['id' => $ledgerid], '*', MUST_EXIST);
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $record->frameworkid], '*', MUST_EXIST);
        $context = \context::instance_by_id($framework->contextid);
        self::validate_context($context);
        require_capability('tool/mutrain:manage', $context);

        api::revoke_credit($ledgerid, (int)$USER->id);

        $record = $DB->get_record('tool_mutrain_ledger', ['id' => $ledgerid], '*', MUST_EXIST);

        return [
            'ledgerid' => (int)$record->id,
            'revokedtime' => (int)$record->revokedtime,
        ];
    }

    /**
     * Describes the external function return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ledgerid' => new external_value(PARAM_INT, 'Ledger record id'),
            'revokedtime' => new external_value(PARAM_INT, 'Time the record was revoked'),
        ]);
    }
}
