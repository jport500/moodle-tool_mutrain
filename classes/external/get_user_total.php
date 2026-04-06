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
 * Returns the current credit total for a user in a framework.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_user_total extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'frameworkid' => new external_value(PARAM_INT, 'Framework id'),
            'userid' => new external_value(PARAM_INT, 'User id'),
            'windowstart' => new external_value(PARAM_INT, 'Window start timestamp, 0 means no window', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns the credit total.
     *
     * @param int $frameworkid
     * @param int $userid
     * @param int $windowstart
     * @return array
     */
    public static function execute(int $frameworkid, int $userid, int $windowstart = 0): array {
        global $DB;

        [
            'frameworkid' => $frameworkid,
            'userid' => $userid,
            'windowstart' => $windowstart,
        ] = self::validate_parameters(self::execute_parameters(), [
            'frameworkid' => $frameworkid,
            'userid' => $userid,
            'windowstart' => $windowstart,
        ]);

        $framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);
        $context = \context::instance_by_id($framework->contextid);
        self::validate_context($context);
        require_capability('tool/mutrain:view', $context);

        if ($windowstart > 0) {
            $credits = api::get_user_total_in_window($userid, $frameworkid, $windowstart);
        } else {
            $credits = api::get_user_total($userid, $frameworkid);
        }

        return [
            'userid' => $userid,
            'frameworkid' => $frameworkid,
            'credits' => $credits,
            'windowstart' => $windowstart,
        ];
    }

    /**
     * Describes the external function return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'userid' => new external_value(PARAM_INT, 'User id'),
            'frameworkid' => new external_value(PARAM_INT, 'Framework id'),
            'credits' => new external_value(PARAM_FLOAT, 'Total active credits'),
            'windowstart' => new external_value(PARAM_INT, 'Window start used, 0 if none'),
        ]);
    }
}
