<?php

namespace Tests\Feature;

use App\Models\Evidence;
use App\Models\Trade;
use App\Models\TradeItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TradeItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_trade_item_removes_its_evidence_and_file_after_commit(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create();
        $tradeItem = TradeItem::factory()->create(['trade_id' => $trade->id]);
        $filePath = 'evidence/item-photo.jpg';
        Storage::disk('local')->put($filePath, 'photo');
        $evidence = Evidence::factory()->create([
            'trade_id' => $trade->id,
            'trade_item_id' => $tradeItem->id,
            'uploaded_by_id' => $trade->owner_id,
            'file_path' => $filePath,
        ]);

        DB::transaction(function () use ($tradeItem, $evidence, $filePath): void {
            $tradeItem->delete();

            $this->assertDatabaseMissing('evidence', ['id' => $evidence->id]);
            $this->assertTrue(Storage::disk('local')->exists($filePath));
        });

        $this->assertFalse(Storage::disk('local')->exists($filePath));
    }

    public function test_deleting_trade_item_keeps_evidence_for_other_items_in_same_trade(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create();
        $deletedItem = TradeItem::factory()->create(['trade_id' => $trade->id]);
        $remainingItem = TradeItem::factory()->create(['trade_id' => $trade->id]);
        $deletedPath = 'evidence/deleted-item.jpg';
        $remainingPath = 'evidence/remaining-item.jpg';
        Storage::disk('local')->put($deletedPath, 'photo');
        Storage::disk('local')->put($remainingPath, 'photo');
        $deletedEvidence = Evidence::factory()->create([
            'trade_id' => $trade->id,
            'trade_item_id' => $deletedItem->id,
            'uploaded_by_id' => $trade->owner_id,
            'file_path' => $deletedPath,
        ]);
        $remainingEvidence = Evidence::factory()->create([
            'trade_id' => $trade->id,
            'trade_item_id' => $remainingItem->id,
            'uploaded_by_id' => $trade->owner_id,
            'file_path' => $remainingPath,
        ]);

        DB::transaction(function () use ($deletedItem): void {
            $deletedItem->delete();
        });

        $this->assertDatabaseMissing('evidence', ['id' => $deletedEvidence->id]);
        $this->assertDatabaseHas('evidence', ['id' => $remainingEvidence->id]);
        $this->assertFalse(Storage::disk('local')->exists($deletedPath));
        $this->assertTrue(Storage::disk('local')->exists($remainingPath));
    }
}
