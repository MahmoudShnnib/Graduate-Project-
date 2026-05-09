---
date: 2026-05-08
tags: [decision, navigation, badges, filament, ux, admin]
related: [[../daily-notes/2026-05-08-session-8-tasks]]
---

# Sidebar Navigation Badges — Pending Request Counts

## Context

Admins had no at-a-glance visibility into how many unreviewed requests were waiting across the 7 request-management screens. They had to navigate into each section to discover new work.

## Decision

Add `getNavigationBadge()` and `getNavigationBadgeColor()` static methods to all 7 target Resources/Pages. Filament calls these on every page render and displays the return value as a small pill badge next to the nav label.

### Count logic per nav item

| Nav item | What is counted | Color |
|---|---|---|
| طلبات المناقشة | `DiscussionStatus::Pending` | warning |
| فرص استثنائية | `ExceptionalRequestStatus::Pending` | warning |
| طلبات الإضافة/الحذف | `status = 'pending'` (plain string) | warning |
| تسجيلات الرسائل | `ThesisStatus::Pending` | warning |
| طلبات إيقاف قيد | `SuspensionStatus::Pending` | warning |
| نتائج استطلاع المقررات | `CourseSurvey.is_processed = false` for active semester | info |
| التسجيل والقبول | `ApplicationStatus::Pending` | warning |

### Zero-count behaviour

`(string) $count ?: null` — returns `null` when count is 0, which hides the badge entirely. This avoids cluttering the nav with "0" pills when there is no work to do.

### Survey results: `info` instead of `warning`

Unprocessed survey entries are not a "pending decision" — they are signal waiting to be used. Info (blue) communicates "there is data here" rather than "action required". All decision-type requests use warning (amber) for consistency.

### Why not cache the counts?

Query cost is minimal (single COUNT per nav item, no joins). Caching would introduce staleness and complexity for no measurable gain at this scale.

## Files changed

- `app/Filament/Resources/DiscussionRequests/DiscussionRequestResource.php`
- `app/Filament/Resources/AddDropRequests/AddDropRequestResource.php`
- `app/Filament/Resources/ThesisRegistrations/ThesisRegistrationResource.php`
- `app/Filament/Resources/SuspensionRequests/SuspensionRequestResource.php`
- `app/Filament/Resources/Applications/ApplicationResource.php`
- `app/Filament/Pages/ExceptionalOpportunitiesPage.php`
- `app/Filament/Pages/CoursesSurveyResults.php`
