# Barter Marketplace (Laravel Trade Platform)

A web platform where users trade products with each other, optionally adding cash to balance the exchange. The platform is a marketplace, not a payment processor or logistics provider. It does not handle payment, delivery, or dispute resolution. Users arrange the physical exchange themselves, and the platform records the agreement, the evidence, and the outcome.

This project is a graduation project and is in an early design stage.

---

## 1. Problem Statement

People often have items they no longer need and want something else in return. Existing marketplaces assume a fixed price and a cash payment. A barter exchange is different: the value is negotiated, the terms change during discussion, and both parties need protection against misrepresented items.

The platform aims to:

- Let users publish what they offer and what they want in return.
- Let users browse and negotiate with others' offers.
- Record each agreement with a clear history of what was agreed.
- Reduce misrepresentation through required photos, written descriptions, and post-trade reviews.

---

## 2. Core Concepts

| Concept          | Meaning                                                                                                  |
| ---------------- | -------------------------------------------------------------------------------------------------------- |
| **Product**      | A catalog entry describing a model (for example, iPhone 17). Shared by all users. Not owned by any user. |
| **Trade**        | An exchange proposal between two users, containing items and cash on each side.                          |
| **Trade Item**   | A single product line within a trade. Belongs to exactly one trade.                                      |
| **Owner**        | The user who published the trade.                                                                        |
| **Counterparty** | The second user who negotiates or accepts the trade.                                                     |
| **Evidence**     | Photos, documents, recordings, or cancellation proofs attached to a trade or to a specific item.         |
| **Review**       | A rating and optional comment left by one party about the other after the trade ends.                    |

Products are a predefined catalog. Users select a product and then describe their specific item (condition, defects, usage). Users cannot create free-text product definitions in the trade itself, but they can request new catalog entries, which must be approved.

---

## 3. Actors

- **Guest:** can browse public information only.
- **Free user:** can publish and negotiate within the basic limits.
- **Subscriber:** same as free, plus higher limits and access to review text and additional profile information.
- **Administrator:** approves catalog entries.

---

## 4. Functional Requirements

### 4.1 Accounts

- Register and log in with email and password.
- Email verification.
- Edit profile: name, email, phone, gender.
- Soft delete account. Trades and reviews remain for records.

### 4.2 Product Catalog

- Browse and search products by category, brand, and name.
- Users may request new products. Requests stay pending until an administrator approves them.
- Only approved products can appear in trades.

### 4.3 Trades

- Create a trade as a draft.
- Add items to each side of the trade (`owner_gives` or `owner_receives`).
- Add cash on each side, with a currency.
- Attach evidence: at least one product photo per item.
- Publish the trade, which moves it from `draft` to `created`.

### 4.4 Negotiation and Acceptance

- A non-owner user may negotiate or accept a published trade.
- During `negotiating`, both parties may change items and cash. Newly added items require their own photo evidence.
- Additional documents (for example, a manual or a receipt) may be uploaded during negotiation.
- Either party can cancel a negotiation, which returns the trade to `created` with its current items.
- Both parties must agree on the current version to move to `accepted`.

### 4.5 Completion and Cancellation

- `accepted` moves to `completed` when both parties confirm.
- If only one party confirms, a 10-day timer starts. If no one objects before it expires, the trade moves to `completed` automatically and is marked `auto_after_timeout`.
- Cancellation is possible from `created`, `negotiating`, or `accepted`, with a reason from a fixed list.
- A cancellation from `accepted` for item mismatch is allowed until the timer ends. It releases the trade immediately and cannot be reopened.
- `completed` and `cancelled` are final states.

### 4.6 Reviews

- After a trade is `completed` or `cancelled`, each party may leave one review of the other.
- A review contains a star rating (1 to 5) and an optional comment.
- Star ratings are visible to everyone.
- Comment text is visible to subscribers only.

### 4.7 Profiles

- Show the user's average rating and review count.
- Show trade history summary (completed, cancelled).
- Subscribers see extended information, including review text and cancellation statistics.

---

## 5. Trade Lifecycle

```
draft ──► created ──► negotiating ──► accepted ──► completed
             │             │             │
             │             ▼             │
             │         created           │
             ▼             (cancel)      ▼
          cancelled ◄──────────────── cancelled
```

| From                      | To            | Condition                                                                                           |
| ------------------------- | ------------- | --------------------------------------------------------------------------------------------------- |
| `draft`                   | `created`     | Trade is complete, including items, photos, and descriptions. Owner is within the publishing limit. |
| `created`                 | `negotiating` | Counterparty is within the negotiating limit. Items are still available.                            |
| `created`                 | `accepted`    | Both parties agree to the current terms.                                                            |
| `negotiating`             | `created`     | Negotiation is cancelled. Trade keeps its current items.                                            |
| `negotiating`             | `accepted`    | Both parties agree to the current version.                                                          |
| `accepted`                | `completed`   | Both parties confirm, or the 10-day timer expires after one confirmation with no objection.         |
| `accepted`                | `cancelled`   | Reason from the fixed list. Item mismatch is allowed until the timer ends.                          |
| `created` / `negotiating` | `cancelled`   | Reason from the fixed list.                                                                         |

Each trade has a `version` number that increases with every edit made while in `created`. Acceptance is tied to a specific version. If the terms change after acceptance, the trade must be re-accepted.

---

## 6. Business Rules

### 6.1 Limits

| Role       | Published trades (`created`) | Active participation (`negotiating` or `accepted`) |
| ---------- | ---------------------------- | -------------------------------------------------- |
| Free user  | 2                            | 2                                                  |
| Subscriber | 4                            | 4                                                  |

- Publishing limit counts only trades the user owns and that are in `created`.
- Participation limit counts trades the user does not own and that are in `negotiating` or `accepted`.
- A trade frees its slot only when it reaches `completed` or `cancelled`.
- Drafts do not count toward any limit.
- When a user reaches a limit, they cannot start a new negotiation or accept a new trade. They can still cancel existing trades.

### 6.2 Evidence

- Every item requires at least one product photo.
- Photos are locked from `negotiating` onward. The only way to change them is to return the trade to `draft` or `created`, which does not happen after acceptance.
- Descriptions are required. They must include condition, usage period, modifications, and defects. "No defects" must be stated explicitly.
- Evidence files are stored in private storage. EXIF location data is removed on upload.

### 6.3 Cash

- Each side may include a cash amount in a single currency.
- Amounts are stored as unsigned decimals. The expected maximum is 5,000 to 10,000, enforced at the application level.

### 6.4 Items and Deletion

- Items belong to one trade only.
- A removed item during negotiation is deleted permanently. Its evidence is deleted with it.
- Trades are soft-deleted when a user deletes them. Their data remains for statistics and review.
- Users are soft-deleted. A user with trades cannot be hard-deleted.

### 6.5 Catalog

- Products are shared. Deleting the user who created a product does not delete the product.
- Products have an approval state. Only approved products appear in trade forms.

---

## 7. Cancellation Reasons

Reasons come from a fixed list in code, and are used for statistics. The list includes:

- Changed mind
- Found a better option
- Disagreement on value or terms
- Items changed during negotiation
- Item does not match the description
- Other party is not responding

Each cancellation may include an optional note. Reasons are visible to subscribers of the other party.

---

## 8. Non-Functional Requirements

- **Data integrity:** Foreign keys enforce relationships. Deletion rules preserve financial and historical records.
- **Performance:** Indexes support browsing by owner and status, searching by product, and reviews by reviewed user. Pagination uses cursors on `(created_at, id)`.
- **Concurrency:** Accepting and changing a trade must lock the trade row to prevent two users from accepting conflicting versions.
- **Privacy:** Uploaded files are stored privately. Location metadata is stripped. Subscriber-only content is enforced in the backend, not only in the interface.
- **Auditability:** Cancellations and confirmations record who acted and when.

---

## 9. Tech Stack

- **Backend:** Laravel 13, PHP 8.3 or higher
- **Frontend:** Blade for the first version
- **Database:** MySQL or MariaDB (uses indexes and foreign keys)
- **Storage:** Laravel private disk for evidence
- **Testing:** PHPUnit / Pest

---

## 10. Setup

```bash
git clone <repository-url>
cd <project>
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Configure the database and private storage in `.env` before migrating.

---

## 11. Database Schema Overview

| Table                 | Purpose                                                                           |
| --------------------- | --------------------------------------------------------------------------------- |
| `users`               | Accounts, subscription tier, soft delete                                          |
| `products`            | Shared catalog with approval state                                                |
| `trades`              | Trade header: owner, counterparty, status, version, cash, confirmation timestamps |
| `trade_items`         | Items within a trade, with direction and description                              |
| `evidence`            | Photos and documents linked to trades or items                                    |
| `trade_cancellations` | One cancellation record per trade, with reason and previous status                |
| `reviews`             | One review per party per trade                                                    |

Migrations are ordered by dependency: `users`, `products`, `trades`, `trade_items`, `evidence`, `trade_cancellations`, `reviews`.

---

## 12. Open Questions

These are deliberately postponed:

1. **Subscriptions:** Pricing, billing cycle, and payment provider are not defined.
2. **Negotiation history:** Whether changes during negotiation should be logged for dispute analysis.
3. **Review reporting:** How users flag abusive reviews, and whether flagged reviews are hidden.
4. **Auto-completion direction:** When the owner confirms and the counterparty stays silent, whether the outcome should be `completed` or `cancelled`.
5. **Review comment codes:** Whether comments need structured tags for analytics.
6. **Product request workflow:** Who reviews catalog requests and how quickly.

---

## 13. Roadmap

- **Phase 1:** Accounts, catalog, trade creation, negotiation, acceptance, and completion without subscriptions.
- **Phase 2:** Reviews, cancellation statistics, and evidence management.
- **Phase 3:** Subscription tiers and extended profile information.
- **Phase 4:** Notifications, admin dashboard, and reporting.

Payment, delivery, and dispute mediation are outside the current scope.
