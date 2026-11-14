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

    public function test_trade_party_cannot_delete_evidence_uploaded_by_the_other_party(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created, 'counterparty_id' => User::factory()]);
        $evidence = Evidence::factory()->create(['trade_id' => $trade->id, 'uploaded_by_id' => $trade->owner_id]);

        try {
            app(DeleteEvidence::class)->handle($evidence, $trade->counterparty);
            $this->fail('A party must not delete evidence uploaded by the other party.');
        } catch (DomainRuleViolation $exception) {
            $this->assertSame('لا يمكن حذف دليل رفعه طرف آخر.', $exception->getMessage());
        }

        $this->assertModelExists($evidence->fresh());
    }

    public function test_evidence_image_over_pixel_limit_is_rejected(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $largeImage = $this->createImageUpload('large.jpg', 5001, 5000, 'jpeg');

        try {
            app(EvidenceFileService::class)->store($trade, null, EvidenceKind::ProductPhoto, $largeImage, $trade->owner);
            $this->fail('An image exceeding the pixel limit must be rejected.');
        } catch (DomainRuleViolation $exception) {
            $this->assertSame('أبعاد الصورة كبيرة جدًا.', $exception->getMessage());
        }
    }

    public function test_jpeg_evidence_is_saved_without_exif(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $plainJpeg = app(EvidenceFileService::class)->store($trade, null, EvidenceKind::ProductPhoto, $this->createImageUpload('plain.jpg', 2, 3, 'jpeg'), $trade->owner);
        $plainJpegContents = Storage::disk('local')->get($plainJpeg->file_path);
        $plainJpegExif = exif_read_data('data://application/octet-stream;base64,'.base64_encode($plainJpegContents), 'IFD0', true);

        $this->assertArrayNotHasKey('Orientation', $plainJpegExif['IFD0'] ?? []);
    }

    public function test_jpeg_evidence_orientation_rotates_image_dimensions(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $orientedJpeg = app(EvidenceFileService::class)->store($trade, null, EvidenceKind::ProductPhoto, $this->createImageUpload('oriented.jpg', 2, 3, 'jpeg', 6), $trade->owner);
        $orientedDimensions = getimagesizefromstring(Storage::disk('local')->get($orientedJpeg->file_path));

        $this->assertSame([3, 2], [$orientedDimensions[0], $orientedDimensions[1]]);
    }

    public function test_webp_upload_is_processed_or_rejected_when_gd_lacks_support(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $gdInfo = function_exists('gd_info') ? gd_info() : [];

        if (! function_exists('imagewebp') || empty($gdInfo['WebP Support'])) {
            $this->expectException(DomainRuleViolation::class);
            $this->expectExceptionMessage('معالجة صور WebP غير مدعومة في هذه البيئة.');

            app(EvidenceFileService::class)->store($trade, null, EvidenceKind::ProductPhoto, UploadedFile::fake()->createWithContent('photo.webp', "RIFF\x00\x00\x00\x00WEBP"), $trade->owner);

            return;
        }

        $webp = app(EvidenceFileService::class)->store($trade, null, EvidenceKind::ProductPhoto, $this->createImageUpload('photo.webp', 2, 3, 'webp'), $trade->owner);

        $this->assertTrue(Storage::disk('local')->exists($webp->file_path));
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

    private function createImageUpload(string $name, int $width, int $height, string $format, ?int $orientation = null): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();

        match ($format) {
            'jpeg' => imagejpeg($image),
            'webp' => imagewebp($image),
            default => throw new \InvalidArgumentException('Unsupported test image format.'),
        };

        $contents = ob_get_clean();
        imagedestroy($image);

        if ($orientation !== null) {
            $tiff = 'II'.pack('v', 42).pack('V', 8).pack('v', 1)
                .pack('v', 0x0112).pack('v', 3).pack('V', 1).pack('v', $orientation)
                .pack('v', 0).pack('V', 0);
            $exif = "Exif\0\0".$tiff;
            $segment = "\xff\xe1".pack('n', strlen($exif) + 2).$exif;
            $contents = substr($contents, 0, 2).$segment.substr($contents, 2);
        }

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}
