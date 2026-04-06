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
 * Posts a credit entry to the ledger.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class post_credit extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User id'),
            'frameworkid' => new external_value(PARAM_INT, 'Framework id'),
            'credits' => new external_value(PARAM_FLOAT, 'Credit value (positive)'),
            'sourcetype' => new external_value(PARAM_ALPHANUMEXT, 'Source type, e.g. external_submission, manual'),
            'sourceinstanceid' => new external_value(PARAM_INT, 'Source instance id', VALUE_DEFAULT, 0),
            'timecredited' => new external_value(PARAM_INT, 'Time activity was completed'),
            'evidence' => new external_multiple_structure(
                new external_single_structure([
                    'key' => new external_value(PARAM_ALPHANUMEXT, 'Evidence key'),
                    'value' => new external_value(PARAM_RAW, 'Evidence value'),
                ]),
                'Optional evidence key/value pairs',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Posts a credit entry.
     *
     * @param int $userid
     * @param int $frameworkid
     * @param float $credits
     * @param string $sourcetype
     * @param int $sourceinstanceid
     * @param int $timecredited
     * @param array $evidence
     * @return array
     */
    public static function execute(
        int $userid,
        int $frameworkid,
        float $credits,
        string $sourcetype,
        int $sourceinstanceid,
        int $timecredited,
        array $evidence = []
    ): array {
        global $DB;

        [
            'userid' => $userid,
            'frameworkid' => $frameworkid,
            'credits' => $credits,
            'sourcetype' => $sourcetype,
            'sourceinstanceid' => $sourceinstanceid,
            'timecredited' => $timecredited,
            'evidence' => $evidence,
        ] = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'frameworkid' => $frameworkid,
            'credits' => $credits,
            'sourcetype' => $sourcetype,
            'sourceinstanceid' => $sourceinstanceid,
            'timecredited' => $timecredited,
            'evidence' => $evidence,
        ]);

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);
        $context = \context::instance_by_id($framework->contextid);
        self::validate_context($context);
        require_capability('tool/mutrain:manage', $context);

        // Convert evidence array of {key, value} objects to associative array.
        $evidencedata = [];
        foreach ($evidence as $item) {
            $evidencedata[$item['key']] = $item['value'];
        }

        $ledgerid = api::post_credit(
            $userid,
            $frameworkid,
            $credits,
            $sourcetype,
            $sourceinstanceid,
            $timecredited,
            $evidencedata
        );

        return [
            'ledgerid' => $ledgerid,
            'credits' => $credits,
        ];
    }

    /**
     * Describes the external function return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ledgerid' => new external_value(PARAM_INT, 'New ledger record id'),
            'credits' => new external_value(PARAM_FLOAT, 'Credits posted'),
        ]);
    }
}
