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
 * Credit frameworks.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\output\header_actions;
use tool_mutrain\local\management;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require_once('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$framework = $DB->get_record('tool_mutrain_framework', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($framework->contextid);
require_capability('tool/mutrain:viewframeworks', $context);

$pageurl = new \core\url('/admin/tool/mutrain/management/framework.php', ['id' => $framework->id]);

management::setup_framework_page($pageurl, $context, $framework);

/** @var \tool_mutrain\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_mutrain', 'management');

echo $OUTPUT->header();

echo $managementoutput->render_framework($framework);

echo $OUTPUT->heading(get_string('fields', 'tool_mutrain'), 4);

$table = new \tool_mutrain\table\fields($pageurl, $framework);
$table->out($table->pagesize, false);

if (!$framework->archived && has_capability('tool/mutrain:manageframeworks', $context)) {
    $url = new \core\url('/admin/tool/mutrain/management/field_add.php', ['frameworkid' => $framework->id]);
    $button = get_string('field_add', 'tool_mutrain');
    $button = new \tool_mulib\output\ajax_form\button($url, $button);
    $addbutton = $OUTPUT->render($button);
    echo '<br /><div class="buttons">' . $addbutton . '</div>';
}

// Category rules section.
echo $OUTPUT->heading(get_string('categoryrules', 'tool_mutrain'), 4);

$categoryrules = $DB->get_records('tool_mutrain_framework_category', ['frameworkid' => $framework->id], 'sortorder, categoryname');

if ($categoryrules) {
    $cattable = new html_table();
    $cattable->head = [
        get_string('categoryname', 'tool_mutrain'),
        get_string('categorymincredits', 'tool_mutrain'),
        '',
    ];
    $cattable->attributes['class'] = 'admintable generaltable';
    foreach ($categoryrules as $rule) {
        $row = new html_table_row();
        $row->cells[] = s($rule->categoryname);
        $row->cells[] = format_float($rule->mincredits, 1);
        if (!$framework->archived && has_capability('tool/mutrain:manageframeworks', $context)) {
            $removeurl = new \core\url('/admin/tool/mutrain/management/category_remove.php', ['id' => $rule->id]);
            $removebutton = new \tool_mulib\output\ajax_form\icon($removeurl, get_string('remove'), 'i/delete');
            $removebutton->set_form_size('sm');
            $row->cells[] = $OUTPUT->render($removebutton);
        } else {
            $row->cells[] = '';
        }
        $cattable->data[] = $row;
    }
    echo html_writer::table($cattable);
} else {
    echo html_writer::tag('p', get_string('nocategoryrules', 'tool_mutrain'), ['class' => 'text-muted']);
}

if (!$framework->archived && has_capability('tool/mutrain:manageframeworks', $context)) {
    $addurl = new \core\url('/admin/tool/mutrain/management/category_add.php', ['id' => $framework->id]);
    $addbutton = new \tool_mulib\output\ajax_form\button($addurl, get_string('categoryruleadd', 'tool_mutrain'));
    echo '<br /><div class="buttons">' . $OUTPUT->render($addbutton) . '</div>';
}

// Sub-period requirements section.
echo $OUTPUT->heading(get_string('subperiodrequirements', 'tool_mutrain'), 4);

$subperiods = \tool_mutrain\api::get_subperiods((int)$framework->id);

if ($subperiods) {
    $sptable = new html_table();
    $sptable->head = [
        get_string('subperiodname', 'tool_mutrain'),
        get_string('subperiodmode', 'tool_mutrain'),
        get_string('subperiodwindow', 'tool_mutrain'),
        get_string('requiredcredits', 'tool_mutrain'),
        get_string('subperiodcategoryrules', 'tool_mutrain'),
        '',
    ];
    $sptable->attributes['class'] = 'admintable generaltable';
    foreach ($subperiods as $sp) {
        if ($sp->mode === 'absolute') {
            $window = userdate((int)$sp->startdate, '%Y-%m-%d') . ' – ' . userdate((int)$sp->enddate, '%Y-%m-%d');
        } else {
            $window = 'Offset ' . (int)$sp->offsetdays . 'd, length ' . (int)$sp->lengthdays . 'd';
        }

        $catrules = \tool_mutrain\api::get_subperiod_categories((int)$sp->id);
        $catcell = '';
        if ($catrules) {
            $parts = [];
            foreach ($catrules as $rule) {
                $part = s($rule->categoryname) . ': ' . format_float($rule->mincredits, 1);
                if (!$framework->archived && has_capability('tool/mutrain:manageframeworks', $context)) {
                    $rmurl = new \core\url('/admin/tool/mutrain/management/subperiod_category_remove.php', ['id' => $rule->id]);
                    $rmicon = new \tool_mulib\output\ajax_form\icon($rmurl, get_string('remove'), 'i/delete');
                    $rmicon->set_form_size('sm');
                    $part .= ' ' . $OUTPUT->render($rmicon);
                }
                $parts[] = html_writer::tag('span', $part, ['class' => 'badge badge-secondary mr-1']);
            }
            $catcell = implode(' ', $parts);
        } else {
            $catcell = html_writer::tag('span', get_string('none'), ['class' => 'text-muted small']);
        }
        if (!$framework->archived && has_capability('tool/mutrain:manageframeworks', $context)) {
            $addcaturl = new \core\url('/admin/tool/mutrain/management/subperiod_category_add.php', ['id' => $sp->id]);
            $addcatbtn = new \tool_mulib\output\ajax_form\button($addcaturl, get_string('subperiodcategoryadd', 'tool_mutrain'));
            $addcatbtn->set_form_size('sm');
            $catcell .= ' ' . $OUTPUT->render($addcatbtn);
        }

        $row = new html_table_row();
        $row->cells[] = s($sp->name);
        $row->cells[] = s($sp->mode);
        $row->cells[] = $window;
        $row->cells[] = (float)$sp->requiredcredits > 0 ? format_float($sp->requiredcredits, 1) : '—';
        $row->cells[] = $catcell;
        if (!$framework->archived && has_capability('tool/mutrain:manageframeworks', $context)) {
            $removeurl = new \core\url('/admin/tool/mutrain/management/subperiod_remove.php', ['id' => $sp->id]);
            $removebutton = new \tool_mulib\output\ajax_form\icon($removeurl, get_string('remove'), 'i/delete');
            $removebutton->set_form_size('sm');
            $row->cells[] = $OUTPUT->render($removebutton);
        } else {
            $row->cells[] = '';
        }
        $sptable->data[] = $row;
    }
    echo html_writer::table($sptable);
} else {
    echo html_writer::tag('p', get_string('nosubperiods', 'tool_mutrain'), ['class' => 'text-muted']);
}

if (!$framework->archived && has_capability('tool/mutrain:manageframeworks', $context)) {
    $addurl = new \core\url('/admin/tool/mutrain/management/subperiod_add.php', ['id' => $framework->id]);
    $addbutton = new \tool_mulib\output\ajax_form\button($addurl, get_string('subperiodadd', 'tool_mutrain'));
    echo '<br /><div class="buttons">' . $OUTPUT->render($addbutton) . '</div>';
}

echo $OUTPUT->footer();
