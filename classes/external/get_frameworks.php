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

/**
 * Returns all non-archived frameworks visible to the caller.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_frameworks extends external_api {
    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Optional context id filter', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns list of non-archived frameworks.
     *
     * @param int $contextid Optional context filter.
     * @return array
     */
    public static function execute(int $contextid = 0): array {
        global $DB;

        ['contextid' => $contextid] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
        ]);

        $params = ['archived' => 0];
        $contextwhere = '';
        if ($contextid) {
            $contextwhere = 'AND f.contextid = :contextid';
            $params['contextid'] = $contextid;
        }

        $sql = "SELECT f.*
                  FROM {tool_mutrain_framework} f
                 WHERE f.archived = :archived
                       $contextwhere
              ORDER BY f.id ASC";
        $frameworks = $DB->get_records_sql($sql, $params);

        $results = [];
        $validated = [];
        foreach ($frameworks as $framework) {
            if (!isset($validated[$framework->contextid])) {
                $context = \context::instance_by_id($framework->contextid);
                if (!has_capability('tool/mutrain:view', $context)) {
                    $validated[$framework->contextid] = false;
                    continue;
                }
                self::validate_context($context);
                $validated[$framework->contextid] = true;
            } else if (!$validated[$framework->contextid]) {
                continue;
            }
            $results[] = [
                'id' => (int)$framework->id,
                'name' => $framework->name,
                'idnumber' => $framework->idnumber ?? '',
                'contextid' => (int)$framework->contextid,
                'requiredcredits' => (float)$framework->requiredcredits,
                'archived' => (int)$framework->archived,
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
                'id' => new external_value(PARAM_INT, 'Framework id'),
                'name' => new external_value(PARAM_TEXT, 'Framework name'),
                'idnumber' => new external_value(PARAM_RAW, 'Framework idnumber'),
                'contextid' => new external_value(PARAM_INT, 'Context id'),
                'requiredcredits' => new external_value(PARAM_FLOAT, 'Required credits for completion'),
                'archived' => new external_value(PARAM_BOOL, 'Archived flag'),
            ], 'Framework')
        );
    }
}
