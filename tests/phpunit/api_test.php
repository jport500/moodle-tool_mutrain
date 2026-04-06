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

namespace tool_mutrain\phpunit;

use tool_mutrain\api;
use core\exception\coding_exception;
use core\exception\moodle_exception;

/**
 * Tests for the credit ledger API.
 *
 * @group      MuTMS
 * @package    tool_mutrain
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutrain\api
 */
final class api_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Helper: create a framework via the test generator.
     */
    private function create_framework(array $overrides = []): \stdClass {
        /** @var \tool_mutrain_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');
        return $generator->create_framework($overrides);
    }

    // 1. post_credit() creates a record with correct field values.
    public function test_post_credit_creates_record(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework(['requiredcredits' => '100']);

        $now = time();
        $id = api::post_credit(
            $user->id,
            (int)$framework->id,
            5.25,
            'external_submission',
            42,
            $now - 3600,
            ['provider' => 'ACME Corp']
        );

        $record = $DB->get_record('tool_mutrain_ledger', ['id' => $id], '*', MUST_EXIST);
        $this->assertEquals($user->id, $record->userid);
        $this->assertEquals($framework->id, $record->frameworkid);
        $this->assertEquals(5.25, (float)$record->credits);
        $this->assertSame('external_submission', $record->sourcetype);
        $this->assertEquals(42, $record->sourceinstanceid);
        $this->assertEquals($now - 3600, $record->timecredited);
        $this->assertGreaterThanOrEqual($now, (int)$record->timecreated);
        $this->assertEquals(0, $record->createdby);
        $this->assertNull($record->revokedtime);
        $this->assertNull($record->revokedby);

        $evidence = json_decode($record->evidencejson, true);
        $this->assertSame('ACME Corp', $evidence['provider']);
    }

    // 1b. post_credit() with explicit createdby sets the field.
    public function test_post_credit_with_createdby(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $creator = $this->getDataGenerator()->create_user();

        $id = api::post_credit(
            $user->id,
            (int)$framework->id,
            3.0,
            'manual',
            1,
            time(),
            [],
            (int)$creator->id
        );

        $record = $DB->get_record('tool_mutrain_ledger', ['id' => $id], '*', MUST_EXIST);
        $this->assertEquals($creator->id, $record->createdby);
    }

    // 2. post_credit() returns the new ledger record id.
    public function test_post_credit_returns_id(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();

        $id = api::post_credit($user->id, (int)$framework->id, 10.0, 'manual', 0, time());

        $this->assertIsInt($id);
        $this->assertTrue($DB->record_exists('tool_mutrain_ledger', ['id' => $id]));
    }

    // 3. credit_exists() returns false before posting, true after.
    public function test_credit_exists(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();

        $this->assertFalse(api::credit_exists($user->id, (int)$framework->id, 'external_submission', 99));

        api::post_credit($user->id, (int)$framework->id, 5.0, 'external_submission', 99, time());

        $this->assertTrue(api::credit_exists($user->id, (int)$framework->id, 'external_submission', 99));
    }

    // 3b. credit_exists() returns false for revoked credits, true after re-posting.
    public function test_credit_exists_ignores_revoked(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $admin = get_admin();

        $id = api::post_credit($user->id, (int)$framework->id, 5.0, 'external_submission', 200, time());
        $this->assertTrue(api::credit_exists($user->id, (int)$framework->id, 'external_submission', 200));

        api::revoke_credit($id, (int)$admin->id);
        $this->assertFalse(api::credit_exists($user->id, (int)$framework->id, 'external_submission', 200));

        // Re-posting with same source should now be allowed.
        api::post_credit($user->id, (int)$framework->id, 5.0, 'external_submission', 200, time());
        $this->assertTrue(api::credit_exists($user->id, (int)$framework->id, 'external_submission', 200));
    }

    // 4. post_credit() throws coding_exception if credits <= 0.
    public function test_post_credit_zero_credits_throws(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();

        $this->expectException(coding_exception::class);
        api::post_credit($user->id, (int)$framework->id, 0, 'manual', 0, time());
    }

    // 4b. Negative credits also throw.
    public function test_post_credit_negative_credits_throws(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();

        $this->expectException(coding_exception::class);
        api::post_credit($user->id, (int)$framework->id, -5.0, 'manual', 0, time());
    }

    // 5. post_credit() throws moodle_exception if framework does not exist.
    public function test_post_credit_invalid_framework_throws(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->expectException(moodle_exception::class);
        api::post_credit($user->id, 999999, 10.0, 'manual', 0, time());
    }

    // 6. get_user_total() returns sum of non-revoked credits only.
    public function test_get_user_total_excludes_revoked(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        api::post_credit($user->id, $fid, 10.0, 'manual', 1, time());
        api::post_credit($user->id, $fid, 5.0, 'manual', 2, time());
        $id3 = api::post_credit($user->id, $fid, 3.0, 'manual', 3, time());

        // Revoke the third entry.
        $admin = get_admin();
        api::revoke_credit($id3, (int)$admin->id);

        $total = api::get_user_total($user->id, $fid);
        $this->assertEquals(15.0, $total);
    }

    // 7. get_user_total() returns 0.0 for a user with no ledger records.
    public function test_get_user_total_no_records(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();

        $total = api::get_user_total($user->id, (int)$framework->id);
        $this->assertSame(0.0, $total);
    }

    // 8. get_user_total_in_window() excludes older records.
    public function test_get_user_total_in_window(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        $now = time();
        api::post_credit($user->id, $fid, 10.0, 'manual', 1, $now - 86400 * 365);  // Old.
        api::post_credit($user->id, $fid, 7.0, 'manual', 2, $now - 3600);           // Recent.
        api::post_credit($user->id, $fid, 3.0, 'manual', 3, $now);                  // Now.

        $windowstart = $now - 86400; // Last 24 hours.
        $total = api::get_user_total_in_window($user->id, $fid, $windowstart);
        $this->assertEquals(10.0, $total); // 7 + 3
    }

    // 9. Boundary: record with timecredited exactly equal to windowstart IS included.
    public function test_get_user_total_in_window_boundary(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        $boundary = time() - 86400;
        api::post_credit($user->id, $fid, 5.0, 'manual', 1, $boundary);      // Exactly at boundary.
        api::post_credit($user->id, $fid, 2.0, 'manual', 2, $boundary - 1);  // Just before.

        $total = api::get_user_total_in_window($user->id, $fid, $boundary);
        $this->assertEquals(5.0, $total);
    }

    // 10. get_user_ledger() returns records ordered by timecredited DESC.
    public function test_get_user_ledger_order(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        $now = time();
        api::post_credit($user->id, $fid, 1.0, 'manual', 1, $now - 200);
        api::post_credit($user->id, $fid, 2.0, 'manual', 2, $now - 100);
        api::post_credit($user->id, $fid, 3.0, 'manual', 3, $now);

        $ledger = api::get_user_ledger($user->id, $fid);
        $this->assertCount(3, $ledger);

        $credits = array_map(fn($r) => (float)$r->credits, $ledger);
        $this->assertSame([3.0, 2.0, 1.0], $credits);
    }

    // 11. get_user_ledger() excludes revoked records by default.
    public function test_get_user_ledger_excludes_revoked(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        api::post_credit($user->id, $fid, 10.0, 'manual', 1, time());
        $id2 = api::post_credit($user->id, $fid, 5.0, 'manual', 2, time());

        $admin = get_admin();
        api::revoke_credit($id2, (int)$admin->id);

        $ledger = api::get_user_ledger($user->id, $fid);
        $this->assertCount(1, $ledger);
        $this->assertEquals(10.0, (float)reset($ledger)->credits);
    }

    // 12. get_user_ledger() includes revoked when include_revoked = true.
    public function test_get_user_ledger_includes_revoked(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        api::post_credit($user->id, $fid, 10.0, 'manual', 1, time());
        $id2 = api::post_credit($user->id, $fid, 5.0, 'manual', 2, time());

        $admin = get_admin();
        api::revoke_credit($id2, (int)$admin->id);

        $ledger = api::get_user_ledger($user->id, $fid, true);
        $this->assertCount(2, $ledger);
    }

    // 13. revoke_credit() sets revokedtime and revokedby.
    public function test_revoke_credit(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $admin = get_admin();

        $id = api::post_credit($user->id, (int)$framework->id, 10.0, 'manual', 1, time());

        $before = time();
        api::revoke_credit($id, (int)$admin->id);

        $record = $DB->get_record('tool_mutrain_ledger', ['id' => $id], '*', MUST_EXIST);
        $this->assertNotNull($record->revokedtime);
        $this->assertGreaterThanOrEqual($before, (int)$record->revokedtime);
        $this->assertEquals($admin->id, $record->revokedby);
    }

    // 14. revoke_credit() on already-revoked record throws moodle_exception.
    public function test_revoke_credit_already_revoked_throws(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $admin = get_admin();

        $id = api::post_credit($user->id, (int)$framework->id, 10.0, 'manual', 1, time());
        api::revoke_credit($id, (int)$admin->id);

        $this->expectException(moodle_exception::class);
        api::revoke_credit($id, (int)$admin->id);
    }

    // 15. get_user_full_transcript() returns records grouped by frameworkid.
    public function test_get_user_full_transcript(): void {
        $user = $this->getDataGenerator()->create_user();
        $fw1 = $this->create_framework(['name' => 'FW1']);
        $fw2 = $this->create_framework(['name' => 'FW2']);
        $admin = get_admin();

        api::post_credit($user->id, (int)$fw1->id, 10.0, 'manual', 1, time());
        api::post_credit($user->id, (int)$fw1->id, 5.0, 'manual', 2, time());
        api::post_credit($user->id, (int)$fw2->id, 20.0, 'manual', 3, time());
        $revid = api::post_credit($user->id, (int)$fw2->id, 3.0, 'manual', 4, time());

        api::revoke_credit($revid, (int)$admin->id);

        $transcript = api::get_user_full_transcript($user->id);

        $this->assertArrayHasKey((int)$fw1->id, $transcript);
        $this->assertArrayHasKey((int)$fw2->id, $transcript);
        $this->assertCount(2, $transcript[(int)$fw1->id]);
        $this->assertCount(1, $transcript[(int)$fw2->id]); // Revoked excluded.
    }

    // --- Category compliance tests ---

    public function test_get_category_rules_empty(): void {
        $framework = $this->create_framework();
        $rules = api::get_category_rules((int)$framework->id);
        $this->assertEmpty($rules);
    }

    public function test_get_category_rules_returns_rules(): void {
        global $DB;

        $framework = $this->create_framework();
        $DB->insert_record('tool_mutrain_framework_category', (object)[
            'frameworkid' => $framework->id, 'categoryname' => 'Ethics', 'mincredits' => 2.0, 'sortorder' => 1,
        ]);
        $DB->insert_record('tool_mutrain_framework_category', (object)[
            'frameworkid' => $framework->id, 'categoryname' => 'General', 'mincredits' => 5.0, 'sortorder' => 2,
        ]);

        $rules = api::get_category_rules((int)$framework->id);
        $this->assertCount(2, $rules);
        $this->assertSame('Ethics', $rules[0]->categoryname);
        $this->assertEquals(2.0, (float)$rules[0]->mincredits);
        $this->assertSame('General', $rules[1]->categoryname);
    }

    public function test_get_user_category_totals(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        api::post_credit($user->id, $fid, 3.0, 'manual', 1, time(), ['credittype' => 'Ethics']);
        api::post_credit($user->id, $fid, 5.0, 'manual', 2, time(), ['credittype' => 'General']);

        $totals = api::get_user_category_totals($user->id, $fid);
        $this->assertEquals(3.0, $totals['Ethics']);
        $this->assertEquals(5.0, $totals['General']);
    }

    public function test_get_user_category_totals_with_window(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;
        $now = time();

        api::post_credit($user->id, $fid, 4.0, 'manual', 1, $now - (400 * DAYSECS), ['credittype' => 'Ethics']);
        api::post_credit($user->id, $fid, 6.0, 'manual', 2, $now - 3600, ['credittype' => 'General']);

        $totals = api::get_user_category_totals($user->id, $fid, $now - (365 * DAYSECS));
        $this->assertArrayNotHasKey('Ethics', $totals);
        $this->assertEquals(6.0, $totals['General']);
    }

    public function test_is_category_compliant_no_rules(): void {
        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $this->assertTrue(api::is_category_compliant($user->id, (int)$framework->id));
    }

    public function test_is_category_compliant_passes(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        $DB->insert_record('tool_mutrain_framework_category', (object)[
            'frameworkid' => $fid, 'categoryname' => 'Ethics', 'mincredits' => 2.0, 'sortorder' => 1,
        ]);
        api::post_credit($user->id, $fid, 3.0, 'manual', 1, time(), ['credittype' => 'Ethics']);

        $this->assertTrue(api::is_category_compliant($user->id, $fid));
    }

    public function test_is_category_compliant_fails(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        $DB->insert_record('tool_mutrain_framework_category', (object)[
            'frameworkid' => $fid, 'categoryname' => 'Ethics', 'mincredits' => 2.0, 'sortorder' => 1,
        ]);
        api::post_credit($user->id, $fid, 1.0, 'manual', 1, time(), ['credittype' => 'Ethics']);

        $this->assertFalse(api::is_category_compliant($user->id, $fid));
    }

    public function test_get_category_compliance_detail(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework();
        $fid = (int)$framework->id;

        $DB->insert_record('tool_mutrain_framework_category', (object)[
            'frameworkid' => $fid, 'categoryname' => 'Ethics', 'mincredits' => 2.0, 'sortorder' => 1,
        ]);
        $DB->insert_record('tool_mutrain_framework_category', (object)[
            'frameworkid' => $fid, 'categoryname' => 'General', 'mincredits' => 5.0, 'sortorder' => 2,
        ]);

        api::post_credit($user->id, $fid, 3.0, 'manual', 1, time(), ['credittype' => 'Ethics']);
        api::post_credit($user->id, $fid, 2.0, 'manual', 2, time(), ['credittype' => 'General']);

        $detail = api::get_category_compliance_detail($user->id, $fid);
        $this->assertCount(2, $detail);

        $this->assertSame('Ethics', $detail[0]['categoryname']);
        $this->assertEquals(3.0, $detail[0]['earned']);
        $this->assertTrue($detail[0]['compliant']);
        $this->assertEquals(0.0, $detail[0]['gap']);

        $this->assertSame('General', $detail[1]['categoryname']);
        $this->assertEquals(2.0, $detail[1]['earned']);
        $this->assertFalse($detail[1]['compliant']);
        $this->assertEquals(3.0, $detail[1]['gap']);
    }

    // --- categorycompliant integration tests ---

    public function test_categorycompliant_set_by_sync(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework(['requiredcredits' => '10']);
        $fid = (int)$framework->id;

        // Add category rule: Ethics min 2.
        $DB->insert_record('tool_mutrain_framework_category', (object)[
            'frameworkid' => $fid, 'categoryname' => 'Ethics', 'mincredits' => 2.0, 'sortorder' => 1,
        ]);

        // Post 10 General credits (no Ethics).
        api::post_credit($user->id, $fid, 10.0, 'manual', 1, time(), ['credittype' => 'General']);

        // Sync.
        \tool_mutrain\local\framework::sync_credits($user->id, $fid);

        $credit = $DB->get_record('tool_mutrain_credit', ['frameworkid' => $fid, 'userid' => $user->id]);
        $this->assertNotEmpty($credit);
        $this->assertGreaterThanOrEqual(10.0, (float)$credit->credits);
        $this->assertEquals(0, (int)$credit->categorycompliant);

        // Now post 3 Ethics credits.
        api::post_credit($user->id, $fid, 3.0, 'manual', 2, time(), ['credittype' => 'Ethics']);
        \tool_mutrain\local\framework::sync_credits($user->id, $fid);

        $credit = $DB->get_record('tool_mutrain_credit', ['frameworkid' => $fid, 'userid' => $user->id]);
        $this->assertEquals(1, (int)$credit->categorycompliant);
    }

    public function test_categorycompliant_default_no_rules(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $framework = $this->create_framework(['requiredcredits' => '10']);
        $fid = (int)$framework->id;

        api::post_credit($user->id, $fid, 5.0, 'manual', 1, time());
        \tool_mutrain\local\framework::sync_credits($user->id, $fid);

        $credit = $DB->get_record('tool_mutrain_credit', ['frameworkid' => $fid, 'userid' => $user->id]);
        $this->assertNotEmpty($credit);
        $this->assertEquals(1, (int)$credit->categorycompliant);
    }
}
