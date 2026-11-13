<?php

namespace Tests\Feature;

use App\Actions\DeleteEvidence;
use App\Actions\UploadEvidence;
use App\Enums\EvidenceKind;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Evidence;
use App\Models\Product;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use App\Services\EvidenceFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_evidence_stores_a_product_photo_for_a_trade_item(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $item = TradeItem::factory()->create(['trade_id' => $trade->id, 'product_id' => Product::factory()->create(['is_approved' => true])->id]);
        $evidence = app(UploadEvidence::class)->handle($trade, $trade->owner, EvidenceKind::ProductPhoto, UploadedFile::fake()->image('photo.jpg'), app(EvidenceFileService::class), $item);

        $this->assertSame($item->id, $evidence->trade_item_id);
        $this->assertSame(EvidenceKind::ProductPhoto, $evidence->kind);
        $this->assertStringStartsWith("evidence/{$trade->id}/", $evidence->file_path);
        $this->assertTrue(Storage::disk('local')->exists($evidence->file_path));
    }

    public function test_upload_evidence_rejects_all_uploads_after_acceptance(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Accepted]);

        $this->expectException(DomainRuleViolation::class);

        app(UploadEvidence::class)->handle($trade, $trade->owner, EvidenceKind::Document, UploadedFile::fake()->create('manual.pdf', 20, 'application/pdf'), app(EvidenceFileService::class));
    }

    public function test_upload_evidence_rejects_negotiation_photos_outside_add_item_operation(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Negotiating, 'counterparty_id' => User::factory()]);
        $item = TradeItem::factory()->create(['trade_id' => $trade->id, 'added_during_negotiation' => true]);

        $this->expectException(DomainRuleViolation::class);

        app(UploadEvidence::class)->handle($trade, $trade->counterparty, EvidenceKind::ProductPhoto, UploadedFile::fake()->image('photo.jpg'), app(EvidenceFileService::class), $item);
    }

    public function test_document_upload_in_created_trade_increments_version(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);
        $previousVersion = $trade->version;

        $evidence = app(UploadEvidence::class)->handle($trade, $trade->owner, EvidenceKind::Document, UploadedFile::fake()->create('manual.pdf', 20, 'application/pdf'), app(EvidenceFileService::class));

        $this->assertModelExists($evidence);
        $this->assertSame($previousVersion + 1, $trade->fresh()->version);
    }

    public function test_delete_evidence_rejects_accepted_trades(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Accepted]);
        $evidence = Evidence::factory()->create(['trade_id' => $trade->id]);

        $this->expectException(DomainRuleViolation::class);

        app(DeleteEvidence::class)->handle($evidence, $trade->owner);
    }

    public function test_delete_evidence_removes_record_and_increments_version_in_created_trade(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);
        $evidence = Evidence::factory()->create(['trade_id' => $trade->id, 'uploaded_by_id' => $trade->owner_id]);
        $oldVersion = $trade->version;

        app(DeleteEvidence::class)->handle($evidence, $trade->owner);

        $this->assertDatabaseMissing('evidence', ['id' => $evidence->id]);
        $this->assertSame($oldVersion + 1, $trade->fresh()->version);
    }

    public function test_rolling_back_evidence_upload_leaves_no_database_record_or_file(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $item = TradeItem::factory()->create(['trade_id' => $trade->id]);
        $file = UploadedFile::fake()->image('rollback.jpg');

        try {
            DB::transaction(function () use ($trade, $item, $file): void {
                app(UploadEvidence::class)->handle($trade, $trade->owner, EvidenceKind::ProductPhoto, $file, app(EvidenceFileService::class), $item);

                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('rollback', $exception->getMessage());
        }

        $this->assertDatabaseMissing('evidence', ['trade_item_id' => $item->id]);
        $this->assertSame([], Storage::disk('local')->allFiles("evidence/{$trade->id}"));
    }

    public function test_guest_can_browse_public_marketplace_pages(): void
    {
        $this->get(route('trades.index'))->assertOk();
        $this->get(route('products.index'))->assertOk();
    }

    public function test_trade_party_can_download_private_evidence(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);
        $filePath = "evidence/{$trade->id}/manual.pdf";
        Storage::disk('local')->put($filePath, 'manual');
        $evidence = Evidence::factory()->create([
            'trade_id' => $trade->id,
            'uploaded_by_id' => $trade->owner_id,
            'file_path' => $filePath,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($trade->owner)->get(route('evidence.download', $evidence));

        $response->assertOk();
        $this->assertSame('manual', $response->streamedContent());
    }

    public function test_non_party_cannot_download_private_evidence_from_public_trade(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);
        $filePath = "evidence/{$trade->id}/manual.pdf";
        Storage::disk('local')->put($filePath, 'manual');
        $evidence = Evidence::factory()->create([
            'trade_id' => $trade->id,
            'uploaded_by_id' => $trade->owner_id,
            'file_path' => $filePath,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('evidence.download', $evidence));

        $response->assertForbidden();
    }
}
