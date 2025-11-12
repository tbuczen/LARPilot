# Google-Like Comments & Discussions Implementation

## Overview

This document describes the implementation of a Google Docs-style commenting system for StoryObjects in LARPilot. The system provides inline commenting, real-time updates via long-polling, resolvable discussions, and threaded replies.

## Features Implemented

### 1. **UUID Migration**
- **Issue Fixed**: Comment entity was using `INTEGER` for ID while database migration created `UUID` columns
- **Changes**:
  - Updated `Comment` entity to use `Uuid` type with proper Doctrine configuration
  - Updated `CommentController::post()` to handle UUID parent IDs
  - Updated `CommentController::serializeComment()` to cast UUIDs to strings for JSON responses
  - Fixed `CommentController::api()` to use timestamp-based filtering instead of integer comparison

### 2. **Discussions Tab System**
- **Location**: `templates/includes/story_object_tabs.html.twig`
- **Features**:
  - Added "Discussions" tab to all story object pages (Character, Thread, Quest, etc.)
  - Shows comment count badge (total comments)
  - Shows warning badge for unresolved comments
  - Integrated with existing tab navigation system

### 3. **Inline Comment UI (Google Docs-style)**
- **Templates**:
  - `templates/backoffice/larp/story/comment/discussions.html.twig` - Main discussions view
  - `templates/backoffice/larp/story/comment/_comment_thread.html.twig` - Comment thread component
- **Features**:
  - Inline comment posting without page navigation
  - Threaded replies with nested display
  - Avatar circles with user initials
  - Timestamp display with "edited" indicator
  - Resolve/unresolve toggle buttons
  - Edit/delete actions (owner only)
  - Reply forms that appear inline
  - Visual distinction for resolved comments (faded opacity)

### 4. **Real-Time Updates (Long-Polling)**
- **Controller**: `assets/controllers/comments_controller.js` (Stimulus)
- **Features**:
  - Polls server every 5 seconds for new comments
  - Timestamp-based filtering to avoid duplicate fetches
  - Automatically adds new comments to DOM
  - Smooth scroll to newly added comments
  - Toast notifications for user actions
  - Optimistic UI updates

### 5. **AJAX Operations**
- **Resolve/Unresolve**: `POST /larp/{larp}/story/{storyObject}/comment/{comment}/resolve`
  - Accepts `X-Requested-With: XMLHttpRequest` header
  - Returns JSON with success status and new resolved state
  - Updates UI without page reload
  
- **Post Comment**: `POST /larp/{larp}/story/{storyObject}/comments/post`
  - Accepts `content` and optional `parentId` parameters
  - Returns newly created comment as JSON
  
- **Fetch Updates**: `GET /larp/{larp}/story/{storyObject}/comments/api`
  - Parameters: `lastCommentId`, `since` (ISO8601 timestamp)
  - Returns new comments, counts, and updated timestamp

### 6. **Controller Updates**

#### CommentController (`src/Domain/StoryObject/Controller/Backoffice/CommentController.php`)
- Added `discussions()` method for generic story object discussions
- Updated `resolve()` to handle AJAX requests
- Fixed `api()` to use timestamp-based filtering with UUID support
- Updated `serializeComment()` to properly cast UUIDs to strings

#### CharacterController (`src/Domain/StoryObject/Controller/Backoffice/CharacterController.php`)
- Added `discussions()` method for character-specific discussions route
- Added comment count parameters to `modify()` method
- Integrated `CommentRepository` for fetching counts
- Passes `commentsCount` and `unresolvedCommentsCount` to template

### 7. **Translation Strings**
Added to `translations/messages.en.yaml`:
```yaml
comment:
  add_new_placeholder: "Add a comment or start a discussion..."
  max_5000_chars: "Maximum 5000 characters"
  clear: "Clear"
  write_reply: "Write a reply..."
  delete: "Delete"
  delete_confirmation: "Delete Comment"
  delete_warning: "Are you sure you want to delete this comment? This action cannot be undone."
  resolve: "Resolve"
  unresolve: "Unresolve"
```

### 8. **Removed Legacy UI**
- Removed collapsible card-based comments section from `templates/backoffice/larp/characters/modify.html.twig`
- Comments now accessible exclusively through the "Discussions" tab

## How to Use

### Viewing Discussions
1. Navigate to any story object (e.g., Character)
2. Click the "Discussions" tab in the navigation
3. View all comment threads with replies

### Creating Comments
1. In the Discussions tab, use the inline form at the top
2. Type your comment (max 5000 characters)
3. Click "Post Comment" or press Enter
4. Comment appears immediately without page reload

### Replying to Comments
1. Click "Reply" button on any comment
2. Inline reply form appears
3. Type your reply and click "Reply"
4. Reply is added to the thread immediately

### Resolving Discussions
1. Click "Resolve" button on any top-level comment
2. Comment thread fades to indicate resolved status
3. Badge shows "Resolved" status
4. Click "Unresolve" to reopen discussion
5. Tab navigation shows unresolved count badge

### Real-Time Updates
- System polls every 5 seconds for new comments
- New comments from other users appear automatically
- No manual refresh needed
- Works across multiple users simultaneously

## Technical Details

### Database Schema
```sql
CREATE TABLE comment (
    id UUID NOT NULL PRIMARY KEY,
    story_object_id UUID NOT NULL,
    author_id UUID NOT NULL,
    parent_id UUID DEFAULT NULL,
    content TEXT NOT NULL,
    is_resolved BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMP(0) NOT NULL,
    updated_at TIMESTAMP(0) NOT NULL
);
```

### Stimulus Controller Configuration
```html
<div data-controller="comments"
     data-comments-larp-value="{{ larp.id }}"
     data-comments-story-object-value="{{ storyObject.id }}"
     data-comments-story-object-type-value="{{ storyObjectType }}"
     data-comments-poll-interval-value="5000">
```

### Routes
- **List (legacy)**: `/larp/{larp}/story/{storyObject}/comments`
- **Discussions**: `/larp/{larp}/story/{storyObject}/discussions`
- **Character Discussions**: `/larp/{larp}/story/character/{character}/discussions`
- **API (fetch)**: `/larp/{larp}/story/{storyObject}/comments/api`
- **API (post)**: `/larp/{larp}/story/{storyObject}/comments/post`
- **Resolve**: `/larp/{larp}/story/{storyObject}/comment/{comment}/resolve`

## Future Enhancements

Potential improvements for future iterations:

1. **WebSocket Support**: Replace long-polling with WebSocket for true real-time updates
2. **Mentions**: @mention other users in comments
3. **Markdown Support**: Rich text formatting in comments
4. **File Attachments**: Attach images or documents to comments
5. **Email Notifications**: Notify users of new comments via email
6. **Comment Search**: Search within discussions
7. **Comment Filters**: Filter by resolved/unresolved, author, date range
8. **Draft Comments**: Auto-save comment drafts
9. **Reaction Emojis**: Quick reactions to comments (👍, ❤️, etc.)
10. **Thread Collapsing**: Collapse/expand long comment threads

## Testing Checklist

- [x] UUID database migration applied successfully
- [x] Comments can be created inline without navigation
- [x] Replies can be posted to comments
- [x] Comments can be resolved/unresolved via AJAX
- [x] Long-polling fetches new comments automatically
- [x] Comment counts appear in tab badges
- [x] Unresolved count shows warning badge
- [x] Discussions tab accessible from all story objects
- [ ] Multiple users can comment simultaneously (test with 2+ users)
- [ ] Toast notifications appear for all actions
- [ ] Deleted comments are removed from UI
- [ ] Edited comments show "edited" indicator

## Migration Notes

If upgrading from the POC version:

1. Run database migration: `php bin/console doctrine:migrations:migrate`
2. Clear cache: `php bin/console cache:clear`
3. Rebuild assets if using asset compilation
4. Existing comments will retain their data (migration preserves UUID structure)

## Performance Considerations

- Long-polling interval is configurable (default: 5 seconds)
- API responses are limited to new comments only (timestamp filtering)
- Database queries use indexed columns (`story_object_id`, `parent_id`)
- Frontend uses optimistic UI updates to feel instant
- Comment threads are preloaded with replies in single query

## Browser Compatibility

- Tested with modern browsers (Chrome, Firefox, Safari, Edge)
- Requires JavaScript enabled
- Uses Bootstrap 5 components (modals, toasts)
- Stimulus controllers for progressive enhancement
