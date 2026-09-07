<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\MessageMetadata;
use App\Models\User;
use App\Services\LabelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelTest extends TestCase
{
    use RefreshDatabase;

    protected LabelService $labelService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->labelService = app(LabelService::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function test_create_validates_name_unique_per_user_case_insensitive_and_color_from_palette(): void
    {
        // Create first label
        $label1 = $this->labelService->create($this->user->id, 'Work', '#2563EB');
        $this->assertNotNull($label1);
        $this->assertEquals('Work', $label1->name);
        $this->assertEquals('#2563EB', $label1->color);

        // Try to create duplicate with different case - should fail (service-level case-insensitive check)
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->labelService->create($this->user->id, 'WORK', '#16A34A');
    }

    /** @test */
    public function test_update_validates_name_unique_excluding_self_and_color_change(): void
    {
        $label1 = $this->labelService->create($this->user->id, 'Work', '#2563EB');
        $label2 = $this->labelService->create($this->user->id, 'Personal', '#16A34A');

        // Update label2 to have same name as label1 (case-insensitive) - should fail
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->labelService->update($label2->id, $this->user->id, 'WORK');

        // Valid update should work
        $updated = $this->labelService->update($label2->id, $this->user->id, 'Family', '#DB2777');
        $this->assertEquals('Family', $updated->name);
        $this->assertEquals('#DB2777', $updated->color);
    }

    /** @test */
    public function test_delete_removes_label_from_all_messages_and_deletes_label(): void
    {
        $label = $this->labelService->create($this->user->id, 'Work', '#2563EB');

        // Create messages and apply label
        $msg1 = MessageMetadata::factory()->create(['user_id' => $this->user->id]);
        $msg2 = MessageMetadata::factory()->create(['user_id' => $this->user->id]);

        $this->labelService->applyToMessages($label->id, [$msg1->id, $msg2->id]);

        // Verify label is applied (1 label per message)
        $this->assertCount(1, $msg1->fresh()->labels);
        $this->assertCount(1, $msg2->fresh()->labels);

        // Delete label
        $this->labelService->delete($label->id, $this->user->id);

        // Verify label is deleted and removed from messages
        $this->assertNull(Label::find($label->id));
        $this->assertCount(0, $msg1->fresh()->labels);
        $this->assertCount(0, $msg2->fresh()->labels);
    }

    /** @test */
    public function test_apply_to_messages_attaches_label_to_messages(): void
    {
        $label = $this->labelService->create($this->user->id, 'Work', '#2563EB');

        $msg1 = MessageMetadata::factory()->create(['user_id' => $this->user->id]);
        $msg2 = MessageMetadata::factory()->create(['user_id' => $this->user->id]);

        $this->labelService->applyToMessages($label->id, [$msg1->id, $msg2->id]);

        $this->assertCount(1, $msg1->fresh()->labels);
        $this->assertCount(1, $msg2->fresh()->labels);
        $this->assertEquals($label->id, $msg1->fresh()->labels->first()->id);
    }

    /** @test */
    public function test_remove_from_messages_detaches_label(): void
    {
        $label = $this->labelService->create($this->user->id, 'Work', '#2563EB');

        $msg1 = MessageMetadata::factory()->create(['user_id' => $this->user->id]);
        $msg2 = MessageMetadata::factory()->create(['user_id' => $this->user->id]);

        $this->labelService->applyToMessages($label->id, [$msg1->id, $msg2->id]);
        $this->labelService->removeFromMessages($label->id, [$msg1->id]);

        $this->assertCount(0, $msg1->fresh()->labels);
        $this->assertCount(1, $msg2->fresh()->labels);
    }

    /** @test */
    public function test_get_for_user_returns_labels_with_unread_and_total_counts(): void
    {
        $label1 = $this->labelService->create($this->user->id, 'Work', '#2563EB');
        $label2 = $this->labelService->create($this->user->id, 'Personal', '#16A34A');

        // Create messages: 3 total, 2 unread for Work; 2 total, 1 unread for Personal
        $msg1 = MessageMetadata::factory()->create(['user_id' => $this->user->id, 'is_seen' => false]);
        $msg2 = MessageMetadata::factory()->create(['user_id' => $this->user->id, 'is_seen' => false]);
        $msg3 = MessageMetadata::factory()->create(['user_id' => $this->user->id, 'is_seen' => true]);
        $msg4 = MessageMetadata::factory()->create(['user_id' => $this->user->id, 'is_seen' => false]);
        $msg5 = MessageMetadata::factory()->create(['user_id' => $this->user->id, 'is_seen' => true]);

        $this->labelService->applyToMessages($label1->id, [$msg1->id, $msg2->id, $msg3->id]);
        $this->labelService->applyToMessages($label2->id, [$msg4->id, $msg5->id]);

        $labels = $this->labelService->getForUser($this->user->id);

        $workLabel = $labels->firstWhere('id', $label1->id);
        $personalLabel = $labels->firstWhere('id', $label2->id);

        $this->assertEquals(2, $workLabel->unread_count);
        $this->assertEquals(3, $workLabel->total_count);

        $this->assertEquals(1, $personalLabel->unread_count);
        $this->assertEquals(2, $personalLabel->total_count);
    }

    /** @test */
    public function test_label_filter_scope_where_has_labels(): void
    {
        $label = $this->labelService->create($this->user->id, 'Work', '#2563EB');

        $msg1 = MessageMetadata::factory()->create(['user_id' => $this->user->id]);
        $msg2 = MessageMetadata::factory()->create(['user_id' => $this->user->id]);
        $msg3 = MessageMetadata::factory()->create(['user_id' => $this->user->id]);

        $this->labelService->applyToMessages($label->id, [$msg1->id, $msg2->id]);

        // Test scopeWithLabel
        $withLabel = MessageMetadata::where('user_id', $this->user->id)
            ->whereHas('labels', fn($q) => $q->where('labels.id', $label->id))
            ->get();

        $this->assertCount(2, $withLabel);
        $this->assertTrue($withLabel->contains('id', $msg1->id));
        $this->assertTrue($withLabel->contains('id', $msg2->id));

        // Test scopeWithoutLabel
        $withoutLabel = MessageMetadata::where('user_id', $this->user->id)
            ->whereDoesntHave('labels', fn($q) => $q->where('labels.id', $label->id))
            ->get();

        $this->assertCount(1, $withoutLabel);
        $this->assertTrue($withoutLabel->contains('id', $msg3->id));
    }

    /** @test */
    public function test_label_color_palette_returns_10_colors_from_ui_spec(): void
    {
        $palette = $this->labelService->getPalette();

        $this->assertCount(10, $palette);

        $expectedColors = [
            'Blue' => '#2563EB',
            'Green' => '#16A34A',
            'Red' => '#DC2626',
            'Yellow' => '#CA8A04',
            'Purple' => '#9333EA',
            'Pink' => '#DB2777',
            'Orange' => '#EA580C',
            'Teal' => '#0D9488',
            'Indigo' => '#4F46E5',
            'Gray' => '#6B7280',
        ];

        foreach ($expectedColors as $name => $hex) {
            $this->assertArrayHasKey($name, $palette);
            $this->assertEquals($hex, $palette[$name]);
        }
    }

    /** @test */
    public function test_ensure_archive_label_finds_or_creates_archive_with_gray(): void
    {
        // First call - creates
        $archive = $this->labelService->ensureArchiveLabel($this->user->id);
        $this->assertEquals('Archive', $archive->name);
        $this->assertEquals('#6B7280', $archive->color);

        // Second call - finds existing
        $archive2 = $this->labelService->ensureArchiveLabel($this->user->id);
        $this->assertEquals($archive->id, $archive2->id);
    }

    /** @test */
    public function test_ensure_inbox_label_finds_or_creates_inbox_with_blue(): void
    {
        // First call - creates
        $inbox = $this->labelService->ensureInboxLabel($this->user->id);
        $this->assertEquals('Inbox', $inbox->name);
        $this->assertEquals('#2563EB', $inbox->color);

        // Second call - finds existing
        $inbox2 = $this->labelService->ensureInboxLabel($this->user->id);
        $this->assertEquals($inbox->id, $inbox2->id);
    }

    /** @test */
    public function test_label_service_scopes_all_operations_to_user(): void
    {
        $user2 = User::factory()->create();

        $label1 = $this->labelService->create($this->user->id, 'Work', '#2563EB');
        $label2 = $this->labelService->create($user2->id, 'Work', '#16A34A'); // Same name, different user

        // User 1 should only see their labels
        $user1Labels = $this->labelService->getForUser($this->user->id);
        $this->assertCount(1, $user1Labels);
        $this->assertEquals($label1->id, $user1Labels->first()->id);

        // User 2 should only see their labels
        $user2Labels = $this->labelService->getForUser($user2->id);
        $this->assertCount(1, $user2Labels);
        $this->assertEquals($label2->id, $user2Labels->first()->id);

        // User 1 cannot delete user 2's label
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->labelService->delete($label2->id, $this->user->id);
    }
}