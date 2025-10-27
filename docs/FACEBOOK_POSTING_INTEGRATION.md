# Facebook Publishing Integration - Business Requirements

## Table of Contents
1. [Executive Summary](#executive-summary)
2. [Business Goals](#business-goals)
3. [Stakeholders & User Personas](#stakeholders--user-personas)
4. [In-Scope Functionality](#in-scope-functionality)
5. [User Stories](#user-stories)
6. [Process Overview](#process-overview)
7. [Business Rules](#business-rules)
8. [Operational Considerations](#operational-considerations)
9. [Success Metrics](#success-metrics)
10. [Risks & Mitigations](#risks--mitigations)

---

## Executive Summary

LARPilot needs to streamline how marketing teams publish Facebook content for live action role-playing (LARP) events. Currently, social teams prepare posts outside the platform, creating visibility gaps and compliance risks. The Facebook Publishing Integration introduces native tooling to draft, approve, and schedule Facebook posts directly from each connected LARP event. The feature focuses on reducing coordination overhead, ensuring brand consistency, and providing auditable records of planned social content.

## Business Goals

1. **Centralize social content planning** within LARPilot so event coordinators and marketers collaborate in one environment.
2. **Reduce time to publish** Facebook updates by providing reusable templates and event-specific context.
3. **Improve governance** by capturing approvals, ownership, and scheduling history per post.
4. **Increase visibility** of upcoming Facebook activity across marketing, production, and storytelling teams.

## Stakeholders & User Personas

| Persona | Description | Needs |
| --- | --- | --- |
| **Marketing Coordinator** | Plans promotional beats for each LARP event. | Draft posts, reuse event assets, schedule content. |
| **Event Producer** | Owns narrative & logistics for a specific LARP. | Validate post accuracy, ensure alignment with event milestones. |
| **Social Media Manager** | Oversees brand tone and publishing cadence. | Manage approvals, monitor queue, escalate failed publishes. |
| **Executive Sponsor** | Reviews campaign performance. | Understand planned vs. published content, ensure compliance. |

## In-Scope Functionality

- Connect approved Facebook Pages to individual LARP events using existing OAuth flow.
- Create, edit, and delete Facebook post drafts tied to an event timeline milestone.
- Schedule draft posts for automatic publishing on the connected Facebook Page.
- Optional manual publishing trigger for last-minute changes.
- Track publishing status (Draft, Scheduled, Published, Failed, Canceled).
- Display audit trail (creator, approvals, publish attempts) for each post.
- Respect Facebook rate limits and permissions per Page access token.

## User Stories

### Draft Management

**US-FB-1: Create Draft**
```
As a marketing coordinator
I want to create a Facebook draft post for my event
So that I can capture messaging, assets, and timing in one place
```
**Acceptance Criteria:**
- Draft requires title, post body (plain text + placeholders), optional image/video.
- Draft linked to specific LARP event and optional event milestone.
- Auto-save versions and track author.

**US-FB-2: Edit Draft**
```
As an event producer
I want to edit existing Facebook drafts
So that the content reflects the latest event updates
```
**Acceptance Criteria:**
- Version history retains previous content.
- Changes trigger notification to draft owner.
- Editing locked within 5 minutes before scheduled publish.

**US-FB-3: Approve Draft**
```
As a social media manager
I want to approve drafts before they are scheduled or published
So that tone and messaging stay on brand
```
**Acceptance Criteria:**
- Approval required before scheduling.
- Approver recorded with timestamp and comment.
- Pending approvals visible in event dashboard.

### Scheduling & Publishing

**US-FB-4: Schedule Post**
```
As a marketing coordinator
I want to schedule an approved draft for Facebook publication
So that posts go live automatically at the planned time
```
**Acceptance Criteria:**
- Scheduling requires approved draft and valid Facebook Page token.
- Scheduler validates publish window (minimum 15 minutes in future, within Page restrictions).
- Multiple posts can be queued; conflicts flagged but allowed with warning.

**US-FB-5: Manual Publish**
```
As a social media manager
I want to manually publish a draft immediately
So that I can respond to last-minute changes
```
**Acceptance Criteria:**
- Manual publish bypasses scheduled time but retains audit trail.
- Requires confirmation modal and reason for manual action.
- Publishes only if Facebook API available and token valid.

**US-FB-6: Monitor Status**
```
As any stakeholder
I want to see the status of scheduled Facebook posts
So that I know what has been published, is pending, or requires intervention
```
**Acceptance Criteria:**
- Status board lists Draft, Scheduled, Published, Failed, Canceled.
- Failed publishes display API error message and retry action.
- Published posts store Facebook post ID and link.

### Permissions & Notifications

**US-FB-7: Manage Access**
```
As an administrator
I want to restrict who can create, approve, and publish Facebook posts
So that only authorized staff modify social content
```
**Acceptance Criteria:**
- Role-based permissions configurable per LARP event.
- Audit log records permission changes.
- Unauthorized actions blocked with explanatory message.

**US-FB-8: Receive Alerts**
```
As a marketing coordinator
I want to receive alerts when a scheduled post fails
So that I can fix the issue before audiences notice
```
**Acceptance Criteria:**
- Email/Slack notifications for failures and manual publishes.
- Daily digest of upcoming scheduled posts.
- Alerts include remediation guidance.

## Process Overview

1. Connect Facebook Page through OAuth and store long-lived Page access token for the LARP event.
2. Marketing coordinator drafts post with text, media, and target publish window.
3. Draft routed for approval; approver reviews and approves/rejects with comments.
4. Upon approval, coordinator schedules post; scheduler validates timing and enqueues job.
5. Background worker executes publish at scheduled time using Facebook Graph API.
6. Status updates propagate to event dashboard and notifications dispatched.
7. Published post recorded with Facebook post ID and analytics hook for performance tracking.

## Business Rules

- Only one Facebook Page may be connected to an event at a time; switching requires re-authorization.
- Drafts must be approved by a user with "Social Approver" role before scheduling.
- Scheduled posts cannot be edited less than 5 minutes before publish; editing forces unschedule + reschedule.
- Publishing window must fall within event's active marketing period (configurable per event).
- Failed publishes automatically retry up to 3 times before escalating to manual intervention.
- All tokens stored encrypted and rotated according to security policy.

## Operational Considerations

- Provide dashboard widget summarizing scheduled posts per event week.
- Support attachments up to Facebook's media limits; large files uploaded via Facebook's chunked upload API.
- Respect Facebook API rate limits; queue operations and exponential backoff on 429 responses.
- Log all API interactions for auditing and troubleshooting.
- Ensure data residency and privacy compliance for stored tokens and content.

## Success Metrics

- **Adoption:** 80% of active LARPs connect a Facebook Page within three months of launch.
- **Efficiency:** Reduce average time spent preparing a Facebook post by 40% compared to manual workflow.
- **Reliability:** 99% of scheduled posts publish successfully without manual intervention.
- **Visibility:** 100% of scheduled posts visible to producers and marketing staff in dashboard at least 24h in advance.

## Risks & Mitigations

| Risk | Impact | Mitigation |
| --- | --- | --- |
| Facebook API quota changes | Posts fail unexpectedly | Monitor API deprecations, maintain proactive alerts, adjust scheduling lead times. |
| Token expiration | Scheduled posts miss publish window | Implement automated token refresh and pre-publish validation. |
| Content approval bottleneck | Delays publishing cadence | Provide reminders, escalation rules, and visibility of pending approvals. |
| Compliance/privacy concerns | Regulatory exposure | Encrypt stored content, respect retention policies, provide deletion tooling. |
| Platform downtime | Missed announcements | Offer manual fallback messaging and cross-post suggestions. |
