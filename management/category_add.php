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
 * Add category rule to training framework.
 *
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutrain\local\framework;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

define('AJAX_SCRIPT', true);

require('../../../../config.php');
require_once("$CFG->libdir/filelib.php");

$frameworkid = required_param('id', PARAM_INT);

require_login();

$framework = $DB->get_record('tool_mutrain_framework', ['id' => $frameworkid], '*', MUST_EXIST);
$context = context::instance_by_id($framework->contextid);
require_capability('tool/mutrain:manageframeworks', $context);

$currenturl = new core\url('/admin/tool/mutrain/management/category_add.php', ['id' => $frameworkid]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new core\url('/admin/tool/mutrain/management/framework.php', ['id' => $frameworkid]);

if ($framework->archived) {
    redirect($returnurl);
}

$form = new \tool_mutrain\local\form\category_add(null, ['framework' => $framework, 'context' => $context]);

if ($form->is_cancelled()) {
    $form->ajax_form_cancelled($returnurl);
} else if ($data = $form->get_data()) {
    $maxsort = (int)$DB->get_field_sql(
        "SELECT COALESCE(MAX(sortorder), 0) FROM {tool_mutrain_framework_category} WHERE frameworkid = :fwid",
        ['fwid' => $framework->id]
    );
    $mincredits = str_replace(',', '.', $data->mincredits);
    $DB->insert_record('tool_mutrain_framework_category', (object)[
        'frameworkid' => $framework->id,
        'categoryname' => trim($data->categoryname),
        'mincredits' => (float)$mincredits,
        'sortorder' => $maxsort + 1,
    ]);
    framework::sync_credits(null, (int)$framework->id);
    $form->ajax_form_submitted($returnurl);
}

$form->ajax_form_render();
