<?php

namespace Tests\Feature;

use App\Models\Evidence;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class EvidenceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_deleting_evidence_removes_its_file_after_commit(): void
    {
        Storage::fake('local');
        $filePath = 'evidence/product-photo.jpg';
        Storage::disk('local')->put($filePath, 'photo');
        $evidence = Evidence::factory()->create(['file_path' => $filePath]);

        DB::transaction(function () use ($evidence, $filePath): void {
            $evidence->delete();
            $this->assertTrue(Storage::disk('local')->exists($filePath));
        });

        $this->assertFalse(Storage::disk('local')->exists($filePath));
    }

    public function test_rolling_back_evidence_deletion_keeps_its_file(): void
    {
        Storage::fake('local');
        $filePath = 'evidence/rollback-photo.jpg';
        Storage::disk('local')->put($filePath, 'photo');
        $evidence = Evidence::factory()->create(['file_path' => $filePath]);

        try {
            DB::transaction(function () use ($evidence): void {
                $evidence->delete();

                throw new RuntimeException('Roll back evidence deletion.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Roll back evidence deletion.', $exception->getMessage());
        }

        $this->assertTrue(Storage::disk('local')->exists($filePath));
        $this->assertModelExists($evidence);
    }
}
