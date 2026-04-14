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

namespace tool_mutrain;

use core\exception\coding_exception;
use core\exception\moodle_exception;
use stdClass;

/**
 * Credit ledger API.
 *
 * Single point of entry for all ledger credit operations.
 * Other plugins call this — never write to tool_mutrain_ledger directly.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class api {

    /**
     * Post credits to a user's framework ledger.
     *
     * @param int    $userid
     * @param int    $frameworkid
     * @param float  $credits        Credit value to post (must be positive)
     * @param string $sourcetype     e.g. 'external_submission', 'manual'
     * @param int    $sourceinstanceid  The relevant entity id (submissionid, etc.), 0 if N/A
     * @param int    $timecredited   Unix timestamp of when the activity was completed
     * @param array  $evidence       Optional key/value pairs serialized to evidencejson
     * @return int   The new ledger record id
     * @throws coding_exception on invalid input
     * @throws moodle_exception if framework does not exist
     */
    public static function post_credit(
        int $userid,
        int $frameworkid,
        float $credits,
        string $sourcetype,
        int $sourceinstanceid,
        int $timecredited,
        array $evidence = [],
        int $createdby = 0
    ): int {
        global $DB;

        if ($credits <= 0) {
            throw new coding_exception('Credits must be a positive number');
        }

        if (!$DB->record_exists('tool_mutrain_framework', ['id' => $frameworkid])) {
            throw new moodle_exception('invalidrecord', 'error', '', 'tool_mutrain_framework');
        }

        $record = new stdClass();
        $record->userid = $userid;
        $record->frameworkid = $frameworkid;
        $record->credits = $credits;
        $record->sourcetype = $sourcetype;
        $record->sourceinstanceid = $sourceinstanceid ?: null;
        $record->timecredited = $timecredited;
        $record->timecreated = time();
        $record->createdby = $createdby;
        $record->revokedtime = null;
        $record->revokedby = null;
        $record->evidencejson = !empty($evidence) ? json_encode($evidence) : null;

        return (int)$DB->insert_record('tool_mutrain_ledger', $record);
    }

    /**
     * Get total active (non-revoked) credits for a user in a framework.
     *
     * @param int $userid
     * @param int $frameworkid
     * @return float
     */
    public static function get_user_total(int $userid, int $frameworkid): float {
        global $DB;

        $sql = "SELECT COALESCE(SUM(credits), 0)
                  FROM {tool_mutrain_ledger}
                 WHERE userid = :userid
                       AND frameworkid = :frameworkid
                       AND revokedtime IS NULL";
        $params = ['userid' => $userid, 'frameworkid' => $frameworkid];

        return (float)$DB->get_field_sql($sql, $params);
    }

    /**
     * Get total active credits within a rolling time window.
     *
     * @param int $userid
     * @param int $frameworkid
     * @param int $windowstart  Only credits with timecredited >= this value count
     * @return float
     */
    public static function get_user_total_in_window(
        int $userid,
        int $frameworkid,
        int $windowstart
    ): float {
        global $DB;

        $sql = "SELECT COALESCE(SUM(credits), 0)
                  FROM {tool_mutrain_ledger}
                 WHERE userid = :userid
                       AND frameworkid = :frameworkid
                       AND revokedtime IS NULL
                       AND timecredited >= :windowstart";
        $params = [
            'userid' => $userid,
            'frameworkid' => $frameworkid,
            'windowstart' => $windowstart,
        ];

        return (float)$DB->get_field_sql($sql, $params);
    }

    /**
     * Get the full ledger for a user in a framework, ordered by timecredited DESC.
     *
     * @param int  $userid
     * @param int  $frameworkid
     * @param bool $include_revoked  Default false
     * @return array  Array of ledger record objects
     */
    public static function get_user_ledger(
        int $userid,
        int $frameworkid,
        bool $include_revoked = false
    ): array {
        global $DB;

        $params = ['userid' => $userid, 'frameworkid' => $frameworkid];
        $revokedfilter = '';
        if (!$include_revoked) {
            $revokedfilter = 'AND revokedtime IS NULL';
        }

        $sql = "SELECT *
                  FROM {tool_mutrain_ledger}
                 WHERE userid = :userid
                       AND frameworkid = :frameworkid
                       $revokedfilter
              ORDER BY timecredited DESC, id DESC";

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Get native course completion credits for a user/framework pair.
     * Returns an array of stdClass objects shaped like ledger rows so
     * they can be merged with ledger entries for display.
     * These rows are read-only — they cannot be revoked.
     */
    public static function get_user_course_completions(
        int $userid,
        int $frameworkid
    ): array {
        global $DB;

        $sql = "SELECT
                    ctc.timecompleted AS timecredited,
                    c.fullname        AS activityname,
                    cf.name           AS provider,
                    SUM(cd.decvalue)  AS credits,
                    'course_completion' AS sourcetype
                FROM {tool_mutrain_completion} ctc
                JOIN {context} ctx ON ctx.id = ctc.contextid
                    AND ctx.contextlevel = :courselevel
                JOIN {course} c ON c.id = ctx.instanceid
                JOIN {customfield_field} cf ON cf.id = ctc.fieldid
                JOIN {customfield_data} cd ON cd.fieldid = cf.id
                    AND cd.instanceid = ctc.instanceid
                    AND cd.decvalue IS NOT NULL
                JOIN {tool_mutrain_field} tf ON tf.fieldid = cf.id
                JOIN {tool_mutrain_framework} tfr ON tfr.id = tf.frameworkid
                    AND tfr.id = :frameworkid
                    AND tfr.archived = 0
                LEFT JOIN {tool_mulib_context_map} cm
                    ON cm.contextid = ctc.contextid
                    AND cm.relatedcontextid = tfr.contextid
               WHERE ctc.userid = :userid
                 AND (tfr.restrictafter IS NULL
                      OR ctc.timecompleted >= tfr.restrictafter)
                 AND (tfr.restrictcontext = 0 OR cm.id IS NOT NULL)
            GROUP BY ctc.timecompleted, c.fullname, cf.name";

        $params = [
            'courselevel' => CONTEXT_COURSE,
            'frameworkid' => $frameworkid,
            'userid'      => $userid,
        ];

        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * Revoke a specific ledger record.
     *
     * @param int $ledgerid
     * @param int $revokedby  userid performing the revocation
     * @throws moodle_exception if record not found or already revoked
     */
    public static function revoke_credit(int $ledgerid, int $revokedby): void {
        global $DB;

        $record = $DB->get_record('tool_mutrain_ledger', ['id' => $ledgerid]);
        if (!$record) {
            throw new moodle_exception('invalidrecord', 'error', '', 'tool_mutrain_ledger');
        }
        if ($record->revokedtime !== null) {
            throw new moodle_exception('invalidrecord', 'error', '', 'tool_mutrain_ledger');
        }

        $DB->update_record('tool_mutrain_ledger', (object)[
            'id' => $ledgerid,
            'revokedtime' => time(),
            'revokedby' => $revokedby,
        ]);
    }

    /**
     * Check whether a credit has already been posted for a given source instance.
     *
     * @param int    $userid
     * @param int    $frameworkid
     * @param string $sourcetype
     * @param int    $sourceinstanceid
     * @return bool
     */
    public static function credit_exists(
        int $userid,
        int $frameworkid,
        string $sourcetype,
        int $sourceinstanceid
    ): bool {
        global $DB;

        return $DB->record_exists_select(
            'tool_mutrain_ledger',
            'userid = :userid
             AND frameworkid = :frameworkid
             AND sourcetype = :sourcetype
             AND sourceinstanceid = :sourceinstanceid
             AND revokedtime IS NULL',
            [
                'userid' => $userid,
                'frameworkid' => $frameworkid,
                'sourcetype' => $sourcetype,
                'sourceinstanceid' => $sourceinstanceid,
            ]
        );
    }

    /**
     * Get all active ledger records across all frameworks for a user.
     *
     * @param int  $userid
     * @param bool $include_revoked
     * @return array  Keyed by frameworkid, each value an array of ledger records
     */
    public static function get_user_full_transcript(
        int $userid,
        bool $include_revoked = false
    ): array {
        global $DB;

        $params = ['userid' => $userid];
        $revokedfilter = '';
        if (!$include_revoked) {
            $revokedfilter = 'AND revokedtime IS NULL';
        }

        $sql = "SELECT *
                  FROM {tool_mutrain_ledger}
                 WHERE userid = :userid
                       $revokedfilter
              ORDER BY frameworkid ASC, timecredited DESC, id DESC";

        $records = $DB->get_records_sql($sql, $params);

        $transcript = [];
        foreach ($records as $record) {
            $fid = (int)$record->frameworkid;
            if (!isset($transcript[$fid])) {
                $transcript[$fid] = [];
            }
            $transcript[$fid][] = $record;
        }

        return $transcript;
    }

    /**
     * Get category rules for a framework.
     *
     * @param int $frameworkid
     * @return array Array of rule objects
     */
    public static function get_category_rules(int $frameworkid): array {
        global $DB;

        return array_values($DB->get_records(
            'tool_mutrain_framework_category',
            ['frameworkid' => $frameworkid],
            'sortorder, categoryname'
        ));
    }

    /**
     * Get per-category credit totals for a user from ledger entries.
     *
     * @param int $userid
     * @param int $frameworkid
     * @param int $windowstart Only include entries with timecredited >= this (0 = no window)
     * @return array Associative array: ['Ethics' => 4.0, 'General' => 18.0, ...]
     */
    public static function get_user_category_totals(int $userid, int $frameworkid, int $windowstart = 0): array {
        global $DB;

        $params = ['userid' => $userid, 'frameworkid' => $frameworkid];
        $windowclause = '';
        if ($windowstart > 0) {
            $windowclause = 'AND timecredited >= :windowstart';
            $params['windowstart'] = $windowstart;
        }
        $records = $DB->get_records_select(
            'tool_mutrain_ledger',
            "userid = :userid AND frameworkid = :frameworkid
             AND revokedtime IS NULL $windowclause",
            $params
        );
        $totals = [];
        foreach ($records as $r) {
            $evidence = $r->evidencejson ? json_decode($r->evidencejson, true) : [];
            $type = $evidence['credittype'] ?? 'Uncategorized';
            $totals[$type] = ($totals[$type] ?? 0.0) + (float)$r->credits;
        }
        return $totals;
    }

    /**
     * Get detailed category compliance status.
     *
     * @param int $userid
     * @param int $frameworkid
     * @param int $windowstart
     * @return array Array of detail arrays with keys: categoryname, mincredits, earned, compliant, gap
     */
    public static function get_category_compliance_detail(int $userid, int $frameworkid, int $windowstart = 0): array {
        $rules = self::get_category_rules($frameworkid);
        if (empty($rules)) {
            return [];
        }
        $totals = self::get_user_category_totals($userid, $frameworkid, $windowstart);
        $detail = [];
        foreach ($rules as $rule) {
            $earned = $totals[$rule->categoryname] ?? 0.0;
            $detail[] = [
                'categoryname' => $rule->categoryname,
                'mincredits' => (float)$rule->mincredits,
                'earned' => $earned,
                'compliant' => $earned >= (float)$rule->mincredits,
                'gap' => max(0.0, (float)$rule->mincredits - $earned),
            ];
        }
        return $detail;
    }

    /**
     * Check if a user meets all category requirements.
     *
     * @param int $userid
     * @param int $frameworkid
     * @param int $windowstart
     * @return bool True if no rules or all rules met
     */
    public static function is_category_compliant(int $userid, int $frameworkid, int $windowstart = 0): bool {
        $detail = self::get_category_compliance_detail($userid, $frameworkid, $windowstart);
        if (empty($detail)) {
            return true;
        }
        foreach ($detail as $d) {
            if (!$d['compliant']) {
                return false;
            }
        }
        return true;
    }

    // ── Sub-period management ─────────────────────────────────────────────────

    /**
     * Get all sub-periods for a framework, ordered by sortorder.
     */
    public static function get_subperiods(int $frameworkid): array {
        global $DB;
        return array_values($DB->get_records(
            'tool_mutrain_framework_subperiod',
            ['frameworkid' => $frameworkid],
            'sortorder ASC, id ASC'
        ));
    }

    /**
     * Get all category rules for a sub-period.
     */
    public static function get_subperiod_categories(int $subperiodid): array {
        global $DB;
        return array_values($DB->get_records(
            'tool_mutrain_subperiod_category',
            ['subperiodid' => $subperiodid],
            'mincredits DESC'
        ));
    }

    /**
     * Add a sub-period to a framework.
     * mode: 'relative' or 'absolute'
     * Relative: offsetdays + lengthdays used; startdate/enddate ignored.
     * Absolute: startdate/enddate used; offsetdays/lengthdays ignored.
     */
    public static function add_subperiod(
        int $frameworkid,
        string $name,
        string $mode,
        int $offsetdays,
        int $lengthdays,
        int $startdate,
        int $enddate,
        float $requiredcredits,
        int $sortorder = 0
    ): int {
        global $DB;

        if (!in_array($mode, ['relative', 'absolute'])) {
            throw new \coding_exception('mode must be relative or absolute');
        }
        if (trim($name) === '') {
            throw new \invalid_parameter_exception('Sub-period name cannot be empty');
        }
        if ($mode === 'relative' && $lengthdays < 1) {
            throw new \invalid_parameter_exception('lengthdays must be >= 1');
        }
        if ($mode === 'absolute' && $enddate <= $startdate) {
            throw new \invalid_parameter_exception('enddate must be after startdate');
        }

        $record = new \stdClass();
        $record->frameworkid     = $frameworkid;
        $record->name            = trim($name);
        $record->mode            = $mode;
        $record->offsetdays      = $mode === 'relative' ? (int)$offsetdays : 0;
        $record->lengthdays      = $mode === 'relative' ? (int)$lengthdays : 365;
        $record->startdate       = $mode === 'absolute' ? (int)$startdate  : 0;
        $record->enddate         = $mode === 'absolute' ? (int)$enddate    : 0;
        $record->requiredcredits = round((float)$requiredcredits, 5);
        $record->sortorder       = (int)$sortorder;
        $record->timecreated     = time();

        return (int)$DB->insert_record('tool_mutrain_framework_subperiod', $record);
    }

    /**
     * Remove a sub-period and all its category rules.
     */
    public static function remove_subperiod(int $subperiodid): void {
        global $DB;
        $DB->delete_records('tool_mutrain_subperiod_category', ['subperiodid' => $subperiodid]);
        $DB->delete_records('tool_mutrain_framework_subperiod', ['id' => $subperiodid]);
    }

    /**
     * Add a category rule to a sub-period.
     * Throws if a rule for this category already exists on the sub-period.
     */
    public static function add_subperiod_category(
        int $subperiodid,
        string $categoryname,
        float $mincredits
    ): int {
        global $DB;

        if (trim($categoryname) === '') {
            throw new \invalid_parameter_exception('Category name cannot be empty');
        }
        if ($mincredits <= 0) {
            throw new \invalid_parameter_exception('mincredits must be > 0');
        }
        if ($DB->record_exists('tool_mutrain_subperiod_category',
                ['subperiodid' => $subperiodid, 'categoryname' => trim($categoryname)])) {
            throw new \invalid_parameter_exception(
                'A rule for category "' . $categoryname . '" already exists on this sub-period'
            );
        }

        $record = new \stdClass();
        $record->subperiodid  = $subperiodid;
        $record->categoryname = trim($categoryname);
        $record->mincredits   = round((float)$mincredits, 5);

        return (int)$DB->insert_record('tool_mutrain_subperiod_category', $record);
    }

    /**
     * Remove a sub-period category rule.
     */
    public static function remove_subperiod_category(int $id): void {
        global $DB;
        $DB->delete_records('tool_mutrain_subperiod_category', ['id' => $id]);
    }

    /**
     * Get detailed sub-period compliance breakdown for a user/framework pair.
     *
     * Returns array of sub-period detail objects, each containing:
     *   ->subperiod  — the subperiod record
     *   ->windowstart — resolved unix timestamp of period start
     *   ->windowend   — resolved unix timestamp of period end
     *   ->isclosed    — bool: window_end < now
     *   ->totalearned — float: credits earned in window
     *   ->categories  — array of {categoryname, mincredits, earned, pass}
     *   ->pass        — bool: sub-period requirement fully met
     *   ->alert       — bool: closed AND failed
     */
    public static function get_user_subperiod_detail(int $userid, int $frameworkid): array {
        global $DB;

        $subperiods = self::get_subperiods($frameworkid);
        if (empty($subperiods)) {
            return [];
        }

        // Get allocation dates for this user/framework.
        // tool_mutrain_credit links to tool_muprog via frameworkid.
        // We need the allocation timestart for relative mode.
        $allocation = $DB->get_record_sql(
            "SELECT pa.timestart, pa.timeend
               FROM {tool_muprog_allocation} pa
               JOIN {tool_muprog_item} pi ON pi.programid = pa.programid
              WHERE pi.creditframeworkid = :fwid
                AND pa.userid = :uid
              ORDER BY pa.timestart DESC",
            ['fwid' => $frameworkid, 'uid' => $userid],
            IGNORE_MULTIPLE
        );

        $now = time();
        $result = [];

        foreach ($subperiods as $sp) {
            if ($sp->mode === 'absolute') {
                $wstart = (int)$sp->startdate;
                $wend   = (int)$sp->enddate;
            } else {
                // Relative — anchor to allocation start
                $anchor = $allocation ? (int)$allocation->timestart : 0;
                $wstart = $anchor + ((int)$sp->offsetdays * 86400);
                $wend   = $wstart + ((int)$sp->lengthdays * 86400);
            }

            $isclosed = $wend < $now;

            // Sum credits in window from ledger (non-revoked).
            $ledger = $DB->get_records_select(
                'tool_mutrain_ledger',
                'userid = :uid AND frameworkid = :fwid
                 AND revokedtime IS NULL
                 AND timecredited >= :wstart AND timecredited < :wend',
                ['uid' => $userid, 'fwid' => $frameworkid,
                 'wstart' => $wstart, 'wend' => $wend]
            );

            $totalearned = 0.0;
            $bycat = [];
            foreach ($ledger as $entry) {
                $totalearned += (float)$entry->credits;
                $ev = json_decode($entry->evidencejson ?? '{}', true);
                $ct = $ev['credittype'] ?? '';
                if ($ct !== '') {
                    $bycat[$ct] = ($bycat[$ct] ?? 0.0) + (float)$entry->credits;
                }
            }

            // Evaluate category rules.
            $catrules = self::get_subperiod_categories((int)$sp->id);
            $catdetail = [];
            $pass = true;

            if ((float)$sp->requiredcredits > 0 && $totalearned < (float)$sp->requiredcredits) {
                $pass = false;
            }

            foreach ($catrules as $rule) {
                $earned = $bycat[$rule->categoryname] ?? 0.0;
                $catpass = $earned >= (float)$rule->mincredits;
                if (!$catpass) {
                    $pass = false;
                }
                $catdetail[] = (object)[
                    'categoryname' => $rule->categoryname,
                    'mincredits'   => (float)$rule->mincredits,
                    'earned'       => $earned,
                    'pass'         => $catpass,
                ];
            }

            $result[] = (object)[
                'subperiod'   => $sp,
                'windowstart' => $wstart,
                'windowend'   => $wend,
                'isclosed'    => $isclosed,
                'totalearned' => $totalearned,
                'categories'  => $catdetail,
                'pass'        => $pass,
                'alert'       => $isclosed && !$pass,
            ];
        }

        return $result;
    }
}
