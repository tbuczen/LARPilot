# Facebook Publishing Integration - Technical Design

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Domain Model](#domain-model)
3. [Data Storage](#data-storage)
4. [Backend Services](#backend-services)
5. [Facebook Graph API Integration](#facebook-graph-api-integration)
6. [Scheduling & Workers](#scheduling--workers)
7. [API Endpoints](#api-endpoints)
8. [Security Considerations](#security-considerations)
9. [Notifications](#notifications)
10. [Observability & Error Handling](#observability--error-handling)
11. [Testing Strategy](#testing-strategy)
12. [Implementation Roadmap](#implementation-roadmap)

---

## Architecture Overview

```
┌────────────────────┐        ┌───────────────────────────┐
│  LARPilot Backoffice│        │   Facebook Graph API      │
│  (Symfony + Twig)  │        │ (Pages & Publishing APIs) │
└─────────┬──────────┘        └─────────────┬─────────────┘
          │                                 │
          │ Stimulus controllers            │ OAuth tokens & publish calls
          ▼                                 │
┌────────────────────┐        ┌─────────────▼─────────────┐
│ SocialContentModule│◄──────►│  FacebookClient (SDK)     │
│  - Draft UI        │        │  - Token refresh          │
│  - Approval UI     │        │  - Media upload           │
└─────────┬──────────┘        └─────────────┬─────────────┘
          │                                 │
          ▼                                 │
┌────────────────────┐        │
│ SocialPostService  │────────┘
│  - Draft workflow  │
│  - Scheduling      │
│  - Status tracking │
└─────────┬──────────┘
          │ Message Bus
          ▼
┌────────────────────┐
│ PublishPostHandler │  Background worker (Messenger + Symfony Scheduler)
└────────────────────┘
```

The Facebook Publishing Integration extends the existing Symfony backoffice by introducing a `Social` domain module. Draft management is handled synchronously via HTTP controllers, while scheduled publishing relies on the Symfony Messenger bus and worker processes. Communication with Facebook occurs through a dedicated `FacebookClient` that wraps the Facebook PHP SDK and handles token lifecycle management.

## Domain Model

| Aggregate | Entities | Description |
| --- | --- | --- |
| **SocialConnection** | `FacebookPageConnection` | Stores OAuth tokens and metadata for a Facebook Page connected to a LARP event. |
| **SocialDraft** | `EventPostDraft`, `DraftAsset`, `DraftApproval` | Captures draft content, attachments, and approval workflow. |
| **ScheduledPost** | `ScheduledPublish`, `PublishAttempt` | Represents an approved draft scheduled for publishing and its execution history. |

### Entity Responsibilities

- `FacebookPageConnection`
  - Fields: `id`, `larp`, `pageId`, `pageName`, `accessToken`, `tokenExpiresAt`, `createdAt`, `updatedAt`.
  - Relationships: one-to-one with `Larp` aggregate.
  - Methods: `refreshToken()`, `isExpired()`, `getPageAccessToken()`.

- `EventPostDraft`
  - Fields: `id`, `larp`, `title`, `body`, `status`, `scheduledAt`, `createdBy`, `updatedBy`, `createdAt`, `updatedAt`.
  - Status enum: `Draft`, `PendingApproval`, `Approved`, `Scheduled`, `Published`, `Failed`, `Canceled`.
  - Methods: `submitForApproval()`, `approve()`, `reject()`, `schedule()`, `unschedule()`.

- `DraftAsset`
  - Stores uploaded media metadata (type, Facebook upload session ID, storage path).
  - Handles pre-upload to Facebook for large media via resumable upload endpoints.

- `DraftApproval`
  - Records approval actions with `approver`, `decision`, `comment`, `decidedAt`.

- `ScheduledPublish`
  - Fields: `id`, `draft`, `publishAt`, `status`, `queuedAt`, `publishedAt`.
  - Methods: `markQueued()`, `markPublished(facebookPostId)`, `markFailed(error)`.

- `PublishAttempt`
  - Tracks each publish try with `attemptNumber`, `startedAt`, `finishedAt`, `responsePayload`, `errorMessage`.

## Data Storage

- Doctrine entities stored in PostgreSQL using UUID primary keys.
- `facebook_page_connections` table encrypted columns: `access_token` (using existing `EncryptedStringType`).
- `social_drafts` table stores text content (max 63k characters) and references to assets.
- `social_draft_assets` table stores blob references; media stored via existing media library (e.g. Flysystem-backed S3 bucket).
- `social_scheduled_posts` table stores scheduling metadata and status.
- `social_publish_attempts` table references scheduled posts for retry tracking.
- Indexes on `(larp_id, status)` for dashboard filtering and `(publish_at)` for worker scanning.

## Backend Services

### Namespaces & Services

- `src/Domain/Social/Controller/`
  - `DraftController` (CRUD operations for drafts).
  - `ScheduleController` (scheduling actions).
  - `ConnectionController` (OAuth callbacks and connection management).

- `src/Domain/Social/Application/`
  - `SocialPostService`: orchestrates draft lifecycle, approvals, scheduling.
  - `FacebookConnectionService`: manages OAuth tokens and connection state.
  - `DraftApprovalService`: enforces approval requirements.
  - `SchedulerService`: pushes jobs to Messenger queue.

- `src/Domain/Social/Message/`
  - `PublishFacebookPost` message with payload `draftId`, `scheduledPublishId`, `attempt`.

- `src/Domain/Social/MessageHandler/`
  - `PublishFacebookPostHandler`: executes publishing logic, updates status, dispatches retries.

- `src/Infrastructure/Facebook/`
  - `FacebookClient`: wraps Graph API, handles token refresh, uploads, error translation.

### Workflow

1. Draft created via `DraftController` → `SocialPostService::createDraft()` persists draft and assets.
2. When submitted for approval, `DraftApprovalService` validates required reviewers.
3. Upon approval, `SchedulerService::scheduleDraft()` creates `ScheduledPublish` and enqueues `PublishFacebookPost` message with `publishAt` delay.
4. Messenger worker receives message at due time, `PublishFacebookPostHandler` fetches draft, ensures token valid, uploads media if needed, and calls Graph API to publish.
5. Handler updates `ScheduledPublish` status and records `PublishAttempt`. Failures trigger exponential backoff and re-queue up to three times.

## Facebook Graph API Integration

- **Permissions:** `pages_show_list`, `pages_manage_posts`, `pages_read_engagement` requested during OAuth.
- **OAuth Flow:**
  1. Admin initiates connection; redirect to Facebook login dialog with requested scopes.
  2. Callback handled by `ConnectionController::connectAction()`; exchange short-lived token for long-lived Page token using `/oauth/access_token` and `/me/accounts` endpoints.
  3. Store Page access token and expiration.
- **Publishing Endpoints:**
  - Text posts: `POST /{page-id}/feed` with `message`, `published`, `scheduled_publish_time`.
  - Photo posts: `POST /{page-id}/photos` with `caption`, `url` or `source`.
  - Video posts: `POST /{page-id}/videos` with `description`, `file_url` or chunked upload session.
- **Media Handling:** use Facebook's Resumable Upload for files > 10MB. `FacebookClient` manages session start, chunk upload, and finalization.
- **Scheduling:** set `published=false` and `scheduled_publish_time` for future posts (minimum 10 minutes, maximum 75 days per Facebook rules).
- **Token Refresh:** schedule cron to call `/debug_token` weekly; if expiration < 7 days, re-run refresh and prompt admin if user re-auth needed.

## Scheduling & Workers

- Use Symfony Messenger with Doctrine transport for persistence.
- Configure `PublishFacebookPost` messages with `delay` metadata equal to `publishAt - now`.
- Worker command: `bin/console messenger:consume social_posting -vv`.
- Leverage Symfony Scheduler component to poll for missed publishes (e.g., worker downtime) and dispatch catch-up jobs.
- Implement locking via `Symfony\Component\Lock` to prevent duplicate publishes when multiple workers run.

## API Endpoints

| Method | Route | Description | Auth |
| --- | --- | --- | --- |
| GET | `/backoffice/larp/{larpId}/social/drafts` | List drafts & statuses. | Event Editor |
| POST | `/backoffice/larp/{larpId}/social/drafts` | Create draft with text & assets. | Marketing Coordinator |
| PUT | `/backoffice/larp/{larpId}/social/drafts/{id}` | Update draft content. | Marketing Coordinator |
| POST | `/backoffice/larp/{larpId}/social/drafts/{id}/submit` | Submit for approval. | Draft Owner |
| POST | `/backoffice/larp/{larpId}/social/drafts/{id}/approve` | Approve or reject draft. | Social Approver |
| POST | `/backoffice/larp/{larpId}/social/drafts/{id}/schedule` | Schedule approved draft. | Marketing Coordinator |
| POST | `/backoffice/larp/{larpId}/social/drafts/{id}/publish` | Manual publish. | Social Approver |
| DELETE | `/backoffice/larp/{larpId}/social/drafts/{id}` | Cancel draft (if not published). | Admin |
| GET | `/api/social/facebook/status` | Webhook endpoint for publish updates. | Facebook signature |

## Security Considerations

- Store Page access tokens using encrypted Doctrine type and rotate encryption keys via secrets vault.
- Restrict controller routes with Symfony security voters checking LARP-level permissions.
- Implement CSRF protection on Twig forms and signed URLs for webhook endpoints.
- Validate Facebook webhook signatures using app secret proof.
- Audit log stored in `social_audit_log` table capturing action, actor, timestamp, payload snapshot.

## Notifications

- Use existing notification service to emit events:
  - `SocialDraftSubmittedEvent`
  - `SocialDraftApprovedEvent`
  - `SocialPublishFailedEvent`
  - `SocialPublishSucceededEvent`
- Events trigger email via Symfony Mailer and optional Slack via webhook integration.
- Digest of upcoming posts generated daily using Symfony Scheduler cron (`0 7 * * *`).

## Observability & Error Handling

- Log all Graph API requests/responses with masked tokens (Monolog channel `facebook`).
- Metrics via Prometheus exporter:
  - `social_posts_scheduled_total`
  - `social_post_publish_success_total`
  - `social_post_publish_failure_total`
  - `facebook_api_latency_seconds`
- Alerting rules for consecutive failures and token expiration approaching.
- Retry policy: exponential backoff (5m, 15m, 45m). After max retries, status set to `Failed` and notification sent.

## Testing Strategy

- **Unit Tests:**
  - `FacebookClientTest` mocks Graph API responses for success/failure cases.
  - `SocialPostServiceTest` verifies state transitions and permission checks.
  - `SchedulerServiceTest` ensures proper message dispatch and delay calculations.
- **Integration Tests:**
  - Use Symfony's `PantherTestCase` or HTTP client to test controller endpoints with fixtures.
  - Messenger integration tests using Doctrine transport to assert job execution.
- **End-to-End Smoke Tests:**
  - Sandbox Facebook Page with test tokens to validate real publish flow in staging.
  - Automated nightly job to publish to private sandbox page and verify callback.
- **Security Tests:**
  - Ensure webhook signature validation and permission voters block unauthorized access.

## Implementation Roadmap

1. **Foundations (Sprint 1)**
   - Create database schema, entities, and migrations.
   - Implement Facebook OAuth connection management.
   - Build basic draft CRUD UI without scheduling.

2. **Approvals & Scheduling (Sprint 2)**
   - Add approval workflow and permissions.
   - Implement scheduling service, Messenger message, and worker handler.
   - Connect Graph API publishing for text posts.

3. **Media & Observability (Sprint 3)**
   - Add asset uploads and media publishing support.
   - Implement notifications, metrics, and retry policies.
   - Harden security (audit logs, webhook validation).

4. **Stabilization (Sprint 4)**
   - Conduct staging tests with sandbox Page.
   - Finalize documentation, onboarding, and administrator training.
   - Optimize UI/UX feedback and accessibility adjustments.
