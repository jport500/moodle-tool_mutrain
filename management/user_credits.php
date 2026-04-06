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
// phpcs:disable moodle.Files.LineLength.TooLong

/**
 * User credits management page.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require_once('../../../../config.php');

$userid = required_param('userid', PARAM_INT);
$frameworkid = required_param('frameworkid', PARAM_INT);

require_login();

$framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);
$user = core_user::get_user($userid, '*', MUST_EXIST);

$syscontext = context_system::instance();
require_capability('tool/mutrain:manage', $syscontext);

$url = new core\url('/admin/tool/mutrain/management/user_credits.php', ['userid' => $userid, 'frameworkid' => $frameworkid]);
$PAGE->set_url($url);
$PAGE->set_context($syscontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(fullname($user) . ' — ' . format_string($framework->name));
$PAGE->set_heading(get_string('usercredits', 'tool_mutrain'));
$PAGE->set_secondary_navigation(false);

$PAGE->navbar->add(get_string('management_frameworks', 'tool_mutrain'),
    new core\url('/admin/tool/mutrain/management/index.php'));
$PAGE->navbar->add(format_string($framework->name),
    new core\url('/admin/tool/mutrain/management/framework.php', ['id' => $frameworkid]));
$PAGE->navbar->add(fullname($user));

echo $OUTPUT->header();

echo $OUTPUT->heading($OUTPUT->user_picture($user, ['size' => 35]) . ' ' . fullname($user), 3);

// Credit summary.
$total = \tool_mutrain\api::get_user_total($userid, $frameworkid);
$required = (float)$framework->requiredcredits;
$compliant = $total >= $required;
$statusbadge = $compliant
    ? html_writer::span(get_string('status_compliant', 'local_cesubmit'), 'badge badge-success ml-2')
    : html_writer::span(get_string('status_inprogress', 'local_cesubmit'), 'badge badge-warning ml-2');
echo html_writer::tag('p',
    format_float($total, 1) . ' / ' . format_float($required, 1) . ' '
    . get_string('credits', 'tool_mutrain') . $statusbadge
);

// Ledger table.
echo $OUTPUT->heading(get_string('credithistory', 'local_cesubmit'), 4);

$entries = \tool_mutrain\api::get_user_ledger($userid, $frameworkid, true);

if ($entries) {
    $table = new html_table();
    $table->head = [
        get_string('dateofactivity', 'tool_mutrain'),
        get_string('activityname', 'tool_mutrain'),
        get_string('provider', 'tool_mutrain'),
        get_string('credittype', 'tool_mutrain'),
        get_string('credits', 'tool_mutrain'),
        get_string('sourcetype', 'tool_mutrain'),
        '',
    ];
    $table->attributes['class'] = 'admintable generaltable';

    foreach ($entries as $entry) {
        $ev = $entry->evidencejson ? json_decode($entry->evidencejson, true) : [];
        $isrevoked = !empty($entry->revokedtime);

        $row = new html_table_row();
        if ($isrevoked) {
            $row->attributes['class'] = 'text-muted';
        }

        $row->cells[] = userdate($entry->timecredited, get_string('strftimedate', 'langconfig'));

        $activitytext = s($ev['activityname'] ?? '-');
        if ($isrevoked) {
            $activitytext .= ' ' . html_writer::span('[Revoked]', 'badge badge-secondary');
        }
        $row->cells[] = $activitytext;
        $row->cells[] = s($ev['provider'] ?? '-');
        $row->cells[] = s($ev['credittype'] ?? '-');
        $row->cells[] = format_float($entry->credits, 1);
        $row->cells[] = s($entry->sourcetype);

        if (!$isrevoked) {
            $revokeurl = new core\url('/admin/tool/mutrain/management/credit_revoke.php', [
                'id' => $entry->id,
                'userid' => $userid,
                'frameworkid' => $frameworkid,
            ]);
            $revokeicon = new \tool_mulib\output\ajax_form\icon($revokeurl, get_string('creditrevoke', 'tool_mutrain'), 'i/delete');
            $row->cells[] = $OUTPUT->render($revokeicon);
        } else {
            $row->cells[] = '';
        }

        $table->data[] = $row;
    }
    echo html_writer::table($table);
} else {
    echo html_writer::tag('p', get_string('nocreditentries', 'tool_mutrain'), ['class' => 'text-muted']);
}

// Post credit button.
$posturl = new core\url('/admin/tool/mutrain/management/credit_post.php', [
    'userid' => $userid,
    'frameworkid' => $frameworkid,
]);
$postbutton = new \tool_mulib\output\ajax_form\button($posturl, get_string('creditpost', 'tool_mutrain'));
echo '<br /><div class="buttons">' . $OUTPUT->render($postbutton) . '</div>';

echo $OUTPUT->footer();
