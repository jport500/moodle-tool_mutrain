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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use tool_mutrain\api;

/**
 * Returns the credit ledger for a user in a framework.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_user_ledger extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'frameworkid' => new external_value(PARAM_INT, 'Framework id'),
            'userid' => new external_value(PARAM_INT, 'User id'),
            'include_revoked' => new external_value(PARAM_BOOL, 'Include revoked records', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Returns the user's ledger records.
     *
     * @param int $frameworkid
     * @param int $userid
     * @param bool $include_revoked
     * @return array
     */
    public static function execute(int $frameworkid, int $userid, bool $include_revoked = false): array {
        global $DB;

        [
            'frameworkid' => $frameworkid,
            'userid' => $userid,
            'include_revoked' => $include_revoked,
        ] = self::validate_parameters(self::execute_parameters(), [
            'frameworkid' => $frameworkid,
            'userid' => $userid,
            'include_revoked' => $include_revoked,
        ]);

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);
        $context = \context::instance_by_id($framework->contextid);
        self::validate_context($context);
        require_capability('tool/mutrain:view', $context);

        $records = api::get_user_ledger($userid, $frameworkid, $include_revoked);

        $results = [];
        foreach ($records as $record) {
            $results[] = [
                'id' => (int)$record->id,
                'userid' => (int)$record->userid,
                'frameworkid' => (int)$record->frameworkid,
                'credits' => (float)$record->credits,
                'sourcetype' => $record->sourcetype,
                'sourceinstanceid' => $record->sourceinstanceid !== null ? (int)$record->sourceinstanceid : null,
                'timecredited' => (int)$record->timecredited,
                'timecreated' => (int)$record->timecreated,
                'createdby' => (int)$record->createdby,
                'revokedtime' => $record->revokedtime !== null ? (int)$record->revokedtime : null,
                'revokedby' => $record->revokedby !== null ? (int)$record->revokedby : null,
                'evidencejson' => $record->evidencejson ?? '',
            ];
        }

        return $results;
    }

    /**
     * Describes the external function return value.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Ledger record id'),
                'userid' => new external_value(PARAM_INT, 'User id'),
                'frameworkid' => new external_value(PARAM_INT, 'Framework id'),
                'credits' => new external_value(PARAM_FLOAT, 'Credit value'),
                'sourcetype' => new external_value(PARAM_ALPHANUMEXT, 'Source type'),
                'sourceinstanceid' => new external_value(PARAM_INT, 'Source instance id', VALUE_OPTIONAL),
                'timecredited' => new external_value(PARAM_INT, 'Time activity was completed'),
                'timecreated' => new external_value(PARAM_INT, 'Time record was created'),
                'createdby' => new external_value(PARAM_INT, 'User who created the record'),
                'revokedtime' => new external_value(PARAM_INT, 'Time revoked', VALUE_OPTIONAL),
                'revokedby' => new external_value(PARAM_INT, 'User who revoked', VALUE_OPTIONAL),
                'evidencejson' => new external_value(PARAM_RAW, 'JSON metadata'),
            ], 'Ledger record')
        );
    }
}
