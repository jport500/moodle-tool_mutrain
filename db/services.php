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
 * External functions for training credits.
 *
 * @package    tool_mutrain
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // Form element autocompletion WS.
    'tool_mutrain_form_autocomplete_framework_contextid' => [
        'classname' => tool_mutrain\external\form_autocomplete\framework_contextid::class,
        'description' => 'Return list of category contexts for framework editing.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'tool_mutrain_form_autocomplete_field_add_fieldid' => [
        'classname' => \tool_mutrain\external\form_autocomplete\field_add_fieldid::class,
        'description' => 'Return list of field candidates for adding to framework.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    // Web services.
    'tool_mutrain_get_frameworks' => [
        'classname' => \tool_mutrain\external\get_frameworks::class,
        'description' => 'Return list of non-archived frameworks visible to the caller.',
        'type' => 'read',
        'capabilities' => 'tool/mutrain:view',
    ],
    'tool_mutrain_get_user_ledger' => [
        'classname' => \tool_mutrain\external\get_user_ledger::class,
        'description' => 'Return credit ledger for a user in a framework.',
        'type' => 'read',
        'capabilities' => 'tool/mutrain:view',
    ],
    'tool_mutrain_get_user_total' => [
        'classname' => \tool_mutrain\external\get_user_total::class,
        'description' => 'Return current credit total for a user in a framework.',
        'type' => 'read',
        'capabilities' => 'tool/mutrain:view',
    ],
    'tool_mutrain_post_credit' => [
        'classname' => \tool_mutrain\external\post_credit::class,
        'description' => 'Post a credit entry to the ledger.',
        'type' => 'write',
        'capabilities' => 'tool/mutrain:manage',
    ],
    'tool_mutrain_revoke_credit' => [
        'classname' => \tool_mutrain\external\revoke_credit::class,
        'description' => 'Revoke a ledger entry.',
        'type' => 'write',
        'capabilities' => 'tool/mutrain:manage',
    ],
];
