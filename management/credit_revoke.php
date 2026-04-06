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

/**
 * Revoke credit for a user.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

define('AJAX_SCRIPT', true);

require('../../../../config.php');
require_once("$CFG->libdir/filelib.php");

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$frameworkid = required_param('frameworkid', PARAM_INT);

require_login();

$entry = $DB->get_record('tool_mutrain_ledger', ['id' => $id], '*', MUST_EXIST);
$framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);
$user = core_user::get_user($userid, '*', MUST_EXIST);

$syscontext = context_system::instance();
require_capability('tool/mutrain:manage', $syscontext);

$currenturl = new core\url('/admin/tool/mutrain/management/credit_revoke.php', [
    'id' => $id,
    'userid' => $userid,
    'frameworkid' => $frameworkid,
]);
$PAGE->set_context($syscontext);
$PAGE->set_url($currenturl);

$returnurl = new core\url('/admin/tool/mutrain/management/user_credits.php', [
    'userid' => $userid,
    'frameworkid' => $frameworkid,
]);

// If already revoked, redirect back.
if (!empty($entry->revokedtime)) {
    redirect($returnurl);
}

$form = new \tool_mutrain\local\form\credit_revoke(null, [
    'ledgerentry' => $entry,
    'framework' => $framework,
    'user' => $user,
    'context' => $syscontext,
]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    \tool_mutrain\api::revoke_credit($id, (int)$USER->id);

    if (!empty($data->reason)) {
        $entry = $DB->get_record('tool_mutrain_ledger', ['id' => $id]);
        $ev = $entry->evidencejson ? json_decode($entry->evidencejson, true) : [];
        $ev['revocationreason'] = trim($data->reason);
        $DB->set_field('tool_mutrain_ledger', 'evidencejson', json_encode($ev), ['id' => $id]);
    }

    \tool_mutrain\local\framework::sync_credits($userid, $frameworkid);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
