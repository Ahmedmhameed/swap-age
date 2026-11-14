<?php

namespace Tests\Feature;

use App\Actions\AcceptTrade;
use App\Actions\AddTradeItem;
use App\Actions\CancelNegotiation;
use App\Actions\CancelTrade;
use App\Actions\CompleteExpiredTrades;
use App\Actions\ConfirmTrade;
use App\Actions\CreateDraftTrade;
use App\Actions\HideTrade;
use App\Actions\PublishTrade;
use App\Actions\RemoveTradeItem;
use App\Actions\ReviewTrade;
use App\Actions\StartNegotiation;
use App\Actions\UpdateDraftTrade;
use App\Actions\UpdateTradeCash;
use App\Actions\UpdateTradeItem;
use App\Enums\CancellationReason;
use App\Enums\EvidenceKind;
use App\Enums\TradeDirection;
use App\Enums\TradeStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Evidence;
use App\Models\Product;
use App\Models\Trade;
use App\Models\TradeItem;
use App\Models\User;
use App\Services\EvidenceFileService;
use App\Services\TradeDeadlineService;
use App\Services\TradeLimitService;
use App\Services\TradeTransitionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TradeActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_draft_action_creates_a_draft_for_the_owner(): void
    {
        $owner = User::factory()->create();
        $trade = app(CreateDraftTrade::class)->handle($owner, ['cash_currency' => 'USD']);

        $this->assertSame(TradeStatus::Draft, $trade->status);
        $this->assertSame($owner->id, $trade->owner_id);
        $this->assertSame('USD', $trade->cash_currency);
    }

    public function test_update_draft_action_changes_cash_and_adds_approved_items_without_incrementing_version(): void
    {
        $owner = User::factory()->create();
        $trade = Trade::factory()->create(['owner_id' => $owner->id, 'status' => TradeStatus::Draft]);
        $product = Product::factory()->create(['is_approved' => true]);

        app(UpdateDraftTrade::class)->handle($trade, $owner, [
            'cash_currency' => 'EUR',
            'items' => [
                [
                    'product_id' => $product->id,
                    'direction' => TradeDirection::OwnerGives->value,
                    'condition' => 'good',
                    'description' => 'No defects',
                ],
            ],
        ]);

        $this->assertSame('EUR', $trade->fresh()->cash_currency);
        $this->assertSame(1, $trade->fresh()->version);
        $this->assertDatabaseHas('trade_items', ['trade_id' => $trade->id, 'product_id' => $product->id]);
    }

    public function test_publish_action_requires_items_and_product_photos(): void
    {
        $owner = User::factory()->create();
        $trade = Trade::factory()->create(['owner_id' => $owner->id, 'status' => TradeStatus::Draft]);

        try {
            app(PublishTrade::class)->handle($trade, $owner, app(TradeLimitService::class), app(TradeTransitionService::class));
            $this->fail('Publishing an empty trade should fail.');
        } catch (DomainRuleViolation $exception) {
            $this->assertSame('يجب إضافة عنصر واحد على الأقل مما يقدمه مالك الصفقة.', $exception->getMessage());
        }

        $this->assertSame(TradeStatus::Draft, $trade->fresh()->status);
    }

    public function test_publish_action_publishes_a_complete_trade_without_changing_version(): void
    {
        $owner = User::factory()->create();
        $trade = Trade::factory()->create(['owner_id' => $owner->id, 'status' => TradeStatus::Draft]);
        $item = $this->createItem($trade, TradeDirection::OwnerGives);
        Evidence::factory()->create([
            'trade_id' => $trade->id,
            'trade_item_id' => $item->id,
            'kind' => EvidenceKind::ProductPhoto,
            'uploaded_by_id' => $owner->id,
        ]);

        app(PublishTrade::class)->handle($trade, $owner, app(TradeLimitService::class), app(TradeTransitionService::class));

        $this->assertSame(TradeStatus::Created, $trade->fresh()->status);
        $this->assertSame(1, $trade->fresh()->version);
    }

    public function test_publish_action_rejects_an_owner_at_the_free_publishing_limit(): void
    {
        $owner = User::factory()->create();
        Trade::factory()->count(2)->create(['owner_id' => $owner->id, 'status' => TradeStatus::Created]);
        $trade = Trade::factory()->create(['owner_id' => $owner->id, 'status' => TradeStatus::Draft]);
        $item = $this->createItem($trade, TradeDirection::OwnerGives);
        Evidence::factory()->create(['trade_id' => $trade->id, 'trade_item_id' => $item->id, 'kind' => EvidenceKind::ProductPhoto]);

        $this->expectException(DomainRuleViolation::class);

        app(PublishTrade::class)->handle($trade, $owner, app(TradeLimitService::class), app(TradeTransitionService::class));
    }

    public function test_start_negotiation_assigns_a_counterparty_and_changes_status(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);
        $counterparty = User::factory()->create();

        app(StartNegotiation::class)->handle($trade, $counterparty, app(TradeLimitService::class), app(TradeTransitionService::class));

        $this->assertSame(TradeStatus::Negotiating, $trade->fresh()->status);
        $this->assertSame($counterparty->id, $trade->fresh()->counterparty_id);
    }

    public function test_owner_cannot_start_negotiation_on_their_own_trade(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);

        $this->expectException(AuthorizationException::class);

        app(StartNegotiation::class)->handle($trade, $trade->owner, app(TradeLimitService::class), app(TradeTransitionService::class));
    }

    public function test_start_negotiation_rejects_a_counterparty_at_the_participation_limit(): void
    {
        $counterparty = User::factory()->create();
        Trade::factory()->count(2)->create(['counterparty_id' => $counterparty->id, 'status' => TradeStatus::Negotiating]);
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);

        $this->expectException(DomainRuleViolation::class);

        app(StartNegotiation::class)->handle($trade, $counterparty, app(TradeLimitService::class), app(TradeTransitionService::class));
    }

    public function test_cancel_negotiation_returns_to_created_and_clears_the_counterparty(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Negotiating, 'counterparty_id' => User::factory()]);
        $actor = $trade->counterparty;
        $version = $trade->version;

        app(CancelNegotiation::class)->handle($trade, $actor, app(TradeTransitionService::class));

        $this->assertSame(TradeStatus::Created, $trade->fresh()->status);
        $this->assertNull($trade->fresh()->counterparty_id);
        $this->assertSame($version + 1, $trade->fresh()->version);
    }

    public function test_add_item_during_negotiation_requires_a_photo_in_the_same_operation(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Negotiating, 'counterparty_id' => User::factory()]);
        $product = Product::factory()->create(['is_approved' => true]);
        Storage::fake('local');

        $this->expectException(DomainRuleViolation::class);

        app(AddTradeItem::class)->handle($trade, $trade->counterparty, $product->id, TradeDirection::OwnerGives, 1, 'good', 'No defects', null, app(EvidenceFileService::class));
    }

    public function test_add_item_during_negotiation_saves_its_photo_and_increments_version(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Negotiating, 'counterparty_id' => User::factory()]);
        $actor = $trade->counterparty;
        $product = Product::factory()->create(['is_approved' => true]);
        $photo = UploadedFile::fake()->image('item.jpg');
        $oldVersion = $trade->version;

        $item = app(AddTradeItem::class)->handle($trade, $actor, $product->id, TradeDirection::OwnerGives, 1, 'good', 'No defects', $photo, app(EvidenceFileService::class));

        $this->assertSame(TradeStatus::Negotiating, $trade->fresh()->status);
        $this->assertSame($oldVersion + 1, $trade->fresh()->version);
        $this->assertTrue($item->fresh()->added_during_negotiation);
        $this->assertDatabaseHas('evidence', ['trade_item_id' => $item->id, 'kind' => EvidenceKind::ProductPhoto->value]);
    }

    public function test_accept_rejects_a_stale_version(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);
        $actor = User::factory()->create();
        $trade->counterparty_id = $actor->id;
        $trade->save();
        $this->createItem($trade, TradeDirection::OwnerGives);

        $this->expectException(DomainRuleViolation::class);

        app(AcceptTrade::class)->handle($trade, $actor, $trade->version - 1, app(TradeLimitService::class), app(TradeTransitionService::class));
    }

    public function test_accept_trade_accepts_current_terms_with_item_photos(): void
    {
        $owner = User::factory()->create();
        $counterparty = User::factory()->create();
        $trade = Trade::factory()->create([
            'owner_id' => $owner->id,
            'counterparty_id' => null,
            'last_changed_by_id' => $owner->id,
            'status' => TradeStatus::Created,
        ]);
        $item = $this->createItem($trade, TradeDirection::OwnerGives);
        Evidence::factory()->create([
            'trade_id' => $trade->id,
            'trade_item_id' => $item->id,
            'kind' => EvidenceKind::ProductPhoto,
            'uploaded_by_id' => $owner->id,
        ]);

        app(AcceptTrade::class)->handle($trade, $counterparty, $trade->version, app(TradeLimitService::class), app(TradeTransitionService::class));

        $this->assertSame(TradeStatus::Accepted, $trade->fresh()->status);
        $this->assertSame($counterparty->id, $trade->fresh()->counterparty_id);
    }

    public function test_update_trade_item_changes_terms_and_increments_created_trade_version(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);
        $item = $this->createItem($trade, TradeDirection::OwnerGives);
        $oldVersion = $trade->version;

        app(UpdateTradeItem::class)->handle($item, $trade->owner, ['description' => 'No defects; lightly used']);

        $this->assertSame('No defects; lightly used', $item->fresh()->description);
        $this->assertSame($oldVersion + 1, $trade->fresh()->version);
    }

    public function test_update_trade_item_rejects_negotiating_status(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Negotiating, 'counterparty_id' => User::factory()]);
        $item = $this->createItem($trade, TradeDirection::OwnerGives);

        $this->expectException(DomainRuleViolation::class);

        app(UpdateTradeItem::class)->handle($item, $trade->counterparty, ['description' => 'Changed']);
    }

    public function test_remove_trade_item_deletes_its_evidence_and_increments_version(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);
        $item = $this->createItem($trade, TradeDirection::OwnerGives);
        $evidence = Evidence::factory()->create(['trade_id' => $trade->id, 'trade_item_id' => $item->id, 'uploaded_by_id' => $trade->owner_id]);
        $oldVersion = $trade->version;

        app(RemoveTradeItem::class)->handle($item, $trade->owner);

        $this->assertDatabaseMissing('trade_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('evidence', ['id' => $evidence->id]);
        $this->assertSame($oldVersion + 1, $trade->fresh()->version);
    }

    public function test_confirming_once_sets_the_ten_day_deadline_and_both_confirmations_complete(): void
    {
        $this->freezeTime();
        $trade = Trade::factory()->create(['status' => TradeStatus::Accepted]);
        $owner = $trade->owner;
        $counterparty = User::factory()->create();
        $trade->counterparty_id = $counterparty->id;
        $trade->save();
        $expectedDeadline = now()->addDays(10);

        app(ConfirmTrade::class)->handle($trade, $owner, app(TradeDeadlineService::class), app(TradeTransitionService::class));
        $this->assertSame($expectedDeadline->format('Y-m-d H:i:s'), $trade->fresh()->deadline_at->format('Y-m-d H:i:s'));

        app(ConfirmTrade::class)->handle($trade->fresh(), $counterparty, app(TradeDeadlineService::class), app(TradeTransitionService::class));

        $this->assertSame(TradeStatus::Completed, $trade->fresh()->status);
        $this->assertSame('confirmed_by_both', $trade->fresh()->completed_type);
    }

    public function test_complete_expired_trades_completes_only_expired_accepted_trades_even_with_a_deleted_owner(): void
    {
        $this->travelTo(now()->startOfDay());
        $deletedOwner = User::factory()->create();
        $expiredTrade = Trade::factory()->create(['owner_id' => $deletedOwner->id, 'status' => TradeStatus::Accepted, 'counterparty_id' => User::factory(), 'deadline_at' => now()->subSecond()]);
        $deletedOwner->delete();
        $healthyExpiredTrade = Trade::factory()->create(['status' => TradeStatus::Accepted, 'counterparty_id' => User::factory(), 'deadline_at' => now()->subSecond()]);
        $futureTrade = Trade::factory()->create(['status' => TradeStatus::Accepted, 'counterparty_id' => User::factory(), 'deadline_at' => now()->addDay()]);
        $createdExpiredTrade = Trade::factory()->create(['status' => TradeStatus::Created, 'deadline_at' => now()->subSecond()]);

        $completedCount = app(CompleteExpiredTrades::class)->handle(app(TradeDeadlineService::class), app(TradeTransitionService::class));

        $this->assertSame(2, $completedCount);
        $this->assertSame(TradeStatus::Completed, $expiredTrade->fresh()->status);
        $this->assertSame('auto_after_timeout', $expiredTrade->fresh()->completed_type);
        $this->assertSame(TradeStatus::Completed, $healthyExpiredTrade->fresh()->status);
        $this->assertSame(TradeStatus::Accepted, $futureTrade->fresh()->status);
        $this->assertSame(TradeStatus::Created, $createdExpiredTrade->fresh()->status);
    }

    public function test_cancel_trade_records_reason_and_previous_status(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Negotiating]);
        $cancellation = app(CancelTrade::class)->handle($trade, $trade->owner, CancellationReason::ChangedMind, null, null, app(TradeTransitionService::class), app(EvidenceFileService::class), app(TradeDeadlineService::class));

        $this->assertSame(TradeStatus::Cancelled, $trade->fresh()->status);
        $this->assertSame(TradeStatus::Negotiating->value, $cancellation->previous_status);
    }

    public function test_item_mismatch_cancellation_requires_proof_for_accepted_trade(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create(['status' => TradeStatus::Accepted, 'counterparty_id' => User::factory()]);

        $this->expectException(DomainRuleViolation::class);

        app(CancelTrade::class)->handle($trade, $trade->owner, CancellationReason::ItemMismatch, null, null, app(TradeTransitionService::class), app(EvidenceFileService::class), app(TradeDeadlineService::class));
    }

    public function test_review_trade_requires_a_final_trade_and_allows_one_review_per_party(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Completed, 'counterparty_id' => User::factory()]);
        $owner = $trade->owner;
        $review = app(ReviewTrade::class)->handle($trade, $owner, 5, 'تم التبادل بنجاح');

        $this->assertModelExists($review);
        $this->assertSame($trade->counterparty_id, $review->reviewed_id);

        try {
            app(ReviewTrade::class)->handle($trade, $owner, 4, null);
            $this->fail('A second review from the same user should be rejected.');
        } catch (DomainRuleViolation $exception) {
            $this->assertSame('سبق أن أرسلت تقييماً لهذه الصفقة.', $exception->getMessage());
        }
    }

    public function test_review_trade_rejects_a_non_party(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Completed]);

        $this->expectException(AuthorizationException::class);

        app(ReviewTrade::class)->handle($trade, User::factory()->create(), 5, null);
    }

    public function test_hide_trade_soft_deletes_only_allowed_final_or_draft_statuses(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);

        app(HideTrade::class)->handle($trade, $trade->owner);

        $this->assertSoftDeleted($trade);
    }

    public function test_hide_trade_rejects_active_statuses(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);

        $this->expectException(DomainRuleViolation::class);

        app(HideTrade::class)->handle($trade, $trade->owner);
    }

    public function test_item_mismatch_cancellation_is_rejected_after_deadline(): void
    {
        Storage::fake('local');
        $trade = Trade::factory()->create([
            'status' => TradeStatus::Accepted,
            'counterparty_id' => User::factory(),
            'deadline_at' => now()->subSecond(),
        ]);

        $this->expectException(DomainRuleViolation::class);

        app(CancelTrade::class)->handle($trade, $trade->owner, CancellationReason::ItemMismatch, null, UploadedFile::fake()->image('proof.jpg'), app(TradeTransitionService::class), app(EvidenceFileService::class), app(TradeDeadlineService::class));
    }

    public function test_update_draft_rejects_created_status(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);

        $this->expectException(DomainRuleViolation::class);

        app(UpdateDraftTrade::class)->handle($trade, $trade->owner, ['cash_currency' => 'EUR']);
    }

    public function test_update_draft_action_edits_existing_items_without_incrementing_version(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $item = $this->createItem($trade, TradeDirection::OwnerGives);

        app(UpdateDraftTrade::class)->handle($trade, $trade->owner, [
            'items' => [
                [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'direction' => TradeDirection::OwnerReceives->value,
                    'quantity' => 3,
                    'condition' => 'fair',
                    'description' => 'Some wear; no defects',
                ],
            ],
        ]);

        $this->assertSame(TradeDirection::OwnerReceives, $item->fresh()->direction);
        $this->assertSame(3, $item->fresh()->quantity);
        $this->assertSame(1, $trade->fresh()->version);
    }

    public function test_update_trade_cash_in_created_increments_version_and_records_editor(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);
        $actor = $trade->owner;
        $oldVersion = $trade->version;

        app(UpdateTradeCash::class)->handle($trade, $actor, ['cash_amount_owner' => 125.50, 'cash_currency' => 'EUR']);

        $this->assertSame('125.50', $trade->fresh()->cash_amount_owner);
        $this->assertSame('EUR', $trade->fresh()->cash_currency);
        $this->assertSame($oldVersion + 1, $trade->fresh()->version);
        $this->assertSame($actor->id, $trade->fresh()->last_changed_by_id);
    }

    public function test_update_trade_cash_rejects_draft_status(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);

        $this->expectException(DomainRuleViolation::class);

        app(UpdateTradeCash::class)->handle($trade, $trade->owner, ['cash_currency' => 'EUR']);
    }

    public function test_update_trade_cash_rejects_an_empty_update(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);

        $this->expectException(DomainRuleViolation::class);

        app(UpdateTradeCash::class)->handle($trade, $trade->owner, []);
    }

    public function test_add_item_rejects_unapproved_product(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $product = Product::factory()->create(['is_approved' => false]);

        $this->expectException(DomainRuleViolation::class);

        app(AddTradeItem::class)->handle($trade, $trade->owner, $product->id, TradeDirection::OwnerGives, 1, 'good', 'No defects', null, app(EvidenceFileService::class));
    }

    public function test_remove_item_rejects_draft_status(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Draft]);
        $item = $this->createItem($trade, TradeDirection::OwnerGives);

        $this->expectException(DomainRuleViolation::class);

        app(RemoveTradeItem::class)->handle($item, $trade->owner);
    }

    public function test_cancel_negotiation_rejects_created_status(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);

        $this->expectException(DomainRuleViolation::class);

        app(CancelNegotiation::class)->handle($trade, $trade->owner, app(TradeTransitionService::class));
    }

    public function test_accept_rejects_items_without_product_photos(): void
    {
        $owner = User::factory()->create();
        $counterparty = User::factory()->create();
        $trade = Trade::factory()->create([
            'owner_id' => $owner->id,
            'last_changed_by_id' => $owner->id,
            'status' => TradeStatus::Created,
        ]);
        $this->createItem($trade, TradeDirection::OwnerGives);

        $this->expectException(DomainRuleViolation::class);

        app(AcceptTrade::class)->handle($trade, $counterparty, $trade->version, app(TradeLimitService::class), app(TradeTransitionService::class));
    }

    public function test_confirm_rejects_a_second_confirmation_from_the_same_party(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Accepted]);
        app(ConfirmTrade::class)->handle($trade, $trade->owner, app(TradeDeadlineService::class), app(TradeTransitionService::class));

        $this->expectException(DomainRuleViolation::class);

        app(ConfirmTrade::class)->handle($trade->fresh(), $trade->owner, app(TradeDeadlineService::class), app(TradeTransitionService::class));
    }

    public function test_cancel_rejects_completed_trade(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Completed]);

        $this->expectException(DomainRuleViolation::class);

        app(CancelTrade::class)->handle($trade, $trade->owner, CancellationReason::ChangedMind, null, null, app(TradeTransitionService::class), app(EvidenceFileService::class), app(TradeDeadlineService::class));
    }

    public function test_review_rejects_created_trade(): void
    {
        $trade = Trade::factory()->create(['status' => TradeStatus::Created]);

        $this->expectException(DomainRuleViolation::class);

        app(ReviewTrade::class)->handle($trade, $trade->owner, 5, null);
    }

    private function createItem(Trade $trade, TradeDirection $direction): TradeItem
    {
        return TradeItem::factory()->create([
            'trade_id' => $trade->id,
            'product_id' => Product::factory()->create(['is_approved' => true])->id,
            'direction' => $direction,
        ]);
    }
}
