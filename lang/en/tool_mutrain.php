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
 * Training credits plugin language pack.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allframeworks'] = 'All frameworks';
$string['archived'] = 'Archived';
$string['area'] = 'Area';
$string['area_core_course_course'] = 'Course completion';
$string['area_tool_muprog_program'] = 'Program completion';
$string['categorymincredits'] = 'Minimum credits';
$string['categorymincreditsinvalid'] = 'Minimum credits must be a positive number.';
$string['categoryname'] = 'Credit type';
$string['categoryruleduplicate'] = 'A rule for this credit type already exists for this framework.';
$string['categoryruleadd'] = 'Add category rule';
$string['categoryruleremoveconfirm'] = 'Remove category rule for \'{$a}\'?';
$string['categoryrules'] = 'Category rules';
$string['completion'] = 'Completion';
$string['cycledays'] = 'Full cycle length (days)';
$string['cycledays_help'] = 'The full length of one CE cycle in days. Used to calculate prorated requirements for mid-cycle joiners. Set to 730 for a 2-year cycle. Required when proration is enabled.';
$string['completion_instance'] = 'Name';
$string['completion_type'] = 'Type';
$string['component'] = 'Component';
$string['credit'] = 'Credit';
$string['credits'] = 'Credits';
$string['credits_current'] = 'Current credits';
$string['credits_my'] = 'My credits';
$string['currentcontextonly'] = 'Exclude sub-categories';
$string['error_incompatiblearea'] = 'Training credits aggregation is not supported in this area.';
$string['error_nocompletions'] = 'No completions related to credit framework found';
$string['error_nocredits'] = 'No credits were obtained yet';
$string['error_noframeworks'] = 'No credit frameworks found';
$string['error_notrainingfields'] = 'No training credits custom fields available';
$string['event_required_credits_reached'] = 'User reached required credits';
$string['event_required_credits_unreached'] = 'User does not have required credits any more';
$string['field'] = 'Custom field';
$string['field_add'] = 'Add field';
$string['field_remove'] = 'Remove field';
$string['fields'] = 'Custom fields';
$string['framework'] = 'Credit framework';
$string['framework_archive'] = 'Archive framework';
$string['framework_create'] = 'Add framework';
$string['framework_delete'] = 'Delete framework';
$string['framework_idnumber'] = 'Framework ID';
$string['framework_move'] = 'Move framework';
$string['framework_name'] = 'Framework name';
$string['framework_restore'] = 'Restore framework';
$string['framework_update'] = 'Update framework';
$string['frameworks'] = 'Credit frameworks';
$string['management_frameworks'] = 'Credit frameworks';
$string['nocategoryrules'] = 'No category rules configured. All credits count toward the total regardless of type.';
$string['mutrain:manageframeworks'] = 'Manage credit frameworks';
$string['mutrain:viewframeworks'] = 'View credit frameworks';
$string['mutrain:viewusercredits'] = 'View user credits';
$string['pluginname'] = 'Training credits';
$string['privacy:metadata'] = 'Training credits plugin does not store any personal data except completion and credit caches.
You can request purging of course and program completions to delete all cached data.';
$string['privacy:metadata:credits'] = 'Obtained credits';
$string['privacy:metadata:fieldid'] = 'Field id';
$string['privacy:metadata:frameworkid'] = 'Credit framework id';
$string['privacy:metadata:instanceid'] = 'Instance id';
$string['privacy:metadata:timecompleted'] = 'Time completed';
$string['privacy:metadata:timereached'] = 'Time when required credits reached event triggered';
$string['privacy:metadata:tool_mutrain_completion:tableexplanation'] = 'Completion cache';
$string['privacy:metadata:tool_mutrain_credit:tableexplanation'] = 'Obtained credits cache';
$string['privacy:metadata:tool_mutrain_ledger:tableexplanation'] = 'Audit ledger of all credit postings from external sources';
$string['privacy:metadata:sourcetype'] = 'Source type of the credit posting';
$string['privacy:metadata:sourceinstanceid'] = 'Source instance identifier';
$string['privacy:metadata:timecredited'] = 'Time when the activity was completed';
$string['privacy:metadata:timecreated'] = 'Time when the ledger record was created';
$string['privacy:metadata:createdby'] = 'User who created the record';
$string['privacy:metadata:revokedtime'] = 'Time when the credit was revoked';
$string['privacy:metadata:revokedby'] = 'User who revoked the credit';
$string['privacy:metadata:evidencejson'] = 'JSON metadata about the credit evidence';
$string['privacy:metadata:userid'] = 'User ID';
$string['publicaccess'] = 'Public';
$string['requiredcredits'] = 'Required credits';
$string['restrictafter'] = 'Only obtained after';
$string['restrictcontext'] = 'Restricted to category';
$string['selectcategory'] = 'Select category';
$string['specificsettings'] = 'Training credits custom field settings';
$string['activityname'] = 'Activity name';
$string['creditnotes'] = 'Notes';
$string['creditpost'] = 'Post credit';
$string['creditrevoke'] = 'Revoke credit';
$string['creditrevokereason'] = 'Reason for revocation';
$string['credittype'] = 'Credit type';
$string['dateofactivity'] = 'Date of activity';
$string['nocreditentries'] = 'No credit entries found.';
$string['proratejoins'] = 'Prorate mid-cycle joiners';
$string['proratejoins_help'] = 'When enabled, members who join partway through a cycle will have their credit requirement reduced proportionally to the time remaining in their cycle.';
$string['provider'] = 'Provider / sponsor';
$string['rollingsynctask'] = 'Synchronise rolling window CE credits';
$string['sourcetype'] = 'Source';
$string['taskcron'] = 'Completed credits caching';
$string['usercredits'] = 'User credits';
$string['windowdays'] = 'Rolling window (days)';
$string['windowdays_help'] = 'Set to 0 for fixed or anniversary cycles. Set to 730 for a 24-month rolling window. Credits older than this many days will not count toward the member\'s total.';
