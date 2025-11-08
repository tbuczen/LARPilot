# Comments & Discussions System - Proof of Concept

## Overview

This POC implements a comments and discussions system that can be attached to any StoryObject (Character, Thread, Quest, Event, Faction, Item, Place, Relation). Story writers and organizers can use this to discuss and collaborate on story elements.

## Features

### Core Functionality

- **Comment on any StoryObject**: Add comments to Characters, Threads, Quests, Events, Factions, Items, Places, and Relations
- **Threaded discussions**: Support for replies to create conversation threads
- **Resolution tracking**: Mark comments as resolved/unresolved to track discussion status
- **Author tracking**: Each comment is linked to the user who created it
- **Timestamps**: Automatic tracking of creation and update times
- **Cascade deletion**: Comments are automatically deleted when their parent StoryObject is deleted

### User Interface

- **Comments list page**: View all discussions for a StoryObject with threaded replies
- **Stats dashboard**: Shows total comments and unresolved comments count
- **Create/Edit forms**: Simple forms for posting and editing comments
- **Reply functionality**: Reply to specific comments to create threaded discussions
- **Quick actions**: Resolve/unresolve, edit, delete comments via dropdown menus
- **Integration in StoryObject views**: Collapsible comments section in Character edit page (can be extended to other StoryObject types)

## Architecture

### Domain Organization

Following the project's Domain-Driven Design approach, the comments system is organized under the `StoryObject` domain:

```
src/Domain/StoryObject/
├── Entity/
│   └── Comment.php                     # Comment entity
├── Repository/
│   └── CommentRepository.php           # Data access layer
├── Form/
│   └── Type/
│       └── CommentType.php             # Comment form
└── Controller/
    └── Backoffice/
        └── CommentController.php       # CRUD operations

templates/backoffice/larp/story/comment/
├── list.html.twig                      # Comments list view
├── form.html.twig                      # Create/edit form
└── _tab.html.twig                      # Reusable partial (for future use)
```

### Database Schema

**Table: `comment`**

| Column | Type | Description |
|--------|------|-------------|
| id | SERIAL | Primary key |
| story_object_id | INT | Foreign key to story_object (CASCADE on delete) |
| author_id | INT | Foreign key to user |
| parent_id | INT | Foreign key to comment (nullable, for threaded replies, CASCADE on delete) |
| content | TEXT | Comment content |
| is_resolved | BOOLEAN | Resolution status (default: false) |
| created_at | TIMESTAMP | Creation timestamp (auto-managed by Gedmo) |
| updated_at | TIMESTAMP | Update timestamp (auto-managed by Gedmo) |

**Indexes:**
- `idx_comment_story_object` on `story_object_id` (performance)
- `idx_comment_parent` on `parent_id` (threaded replies)

### Routes

All routes are under the `/larp/{larp}/story/{storyObject}` prefix with `backoffice_larp_story_comment_` namespace:

- `GET /comments` - List all comments for a StoryObject
- `GET|POST /comment/create` - Create new comment
- `GET|POST /comment/{comment}/reply` - Reply to a comment
- `GET|POST /comment/{comment}/edit` - Edit existing comment
- `POST /comment/{comment}/delete` - Delete a comment
- `POST /comment/{comment}/resolve` - Toggle resolved status

## Usage

### Accessing Comments

1. Navigate to any Character edit page (e.g., `/larp/{larp}/story/character/{character}`)
2. Scroll to the "Discussions" collapsible card section
3. Click "View All Discussions" to see all comments
4. Or click "Add Comment" to create a new comment directly

### Creating a Comment

1. Go to the comments list page for any StoryObject
2. Click "Add Comment"
3. Enter your comment text
4. Optionally mark it as resolved
5. Click "Post Comment"

### Replying to a Comment

1. On the comments list page, click the three-dots menu on any comment
2. Select "Reply"
3. Enter your reply text
4. Click "Post Comment"

### Managing Comments

- **Edit**: Click the three-dots menu → "Edit"
- **Resolve/Unresolve**: Click the three-dots menu → "Mark as Resolved/Unresolved"
- **Delete**: Click the three-dots menu → "Delete" (with confirmation)

## Extension Points

### 1. Add Comments to Other StoryObject Types

The system is designed to work with any StoryObject. To add comments to other types (Thread, Quest, Event, etc.):

1. Copy the comments card section from `templates/backoffice/larp/characters/modify.html.twig` (lines 148-195)
2. Paste it into the corresponding template (e.g., `templates/backoffice/larp/threads/modify.html.twig`)
3. Update the `storyObject` variable to match the entity (e.g., `thread`, `quest`, `event`)

### 2. Add Comments Tab to Navigation

To add comments as a separate tab instead of a collapsible section:

1. Edit `templates/includes/story_object_tabs.html.twig`
2. Add a new tab item:
   ```twig
   <li class="nav-item" role="presentation">
       <a class="nav-link {{ currentTab == 'comments' ? 'active' : '' }}"
          href="{{ path('backoffice_larp_story_comment_list', {
              larp: larp.id,
              storyObject: storyObject.id
          }) }}">
           <i class="bi bi-chat-dots me-2"></i>{{ 'comment.discussions'|trans }}
           {% if commentCount > 0 %}
               <span class="badge bg-primary ms-1">{{ commentCount }}</span>
           {% endif %}
       </a>
   </li>
   ```
3. Pass `commentCount` from controller to template

### 3. Add Notifications

Future enhancement: Send notifications when:
- Someone comments on your StoryObject
- Someone replies to your comment
- A comment is resolved

Suggested approach:
- Create a `CommentNotificationService`
- Trigger notifications in `CommentController` after save operations
- Use Symfony Messenger for async processing

### 4. Add Rich Text Editor

Replace the simple textarea with a WYSIWYG editor (e.g., Quill):

1. Update `CommentType.php`:
   ```php
   ->add('content', TextareaType::class, [
       'attr' => [
           'data-controller' => 'wysiwyg',
           'class' => 'wysiwyg-content'
       ]
   ])
   ```
2. The existing `wysiwyg_controller.js` will automatically enhance the field

### 5. Add Mentions Support

Enable @mentions in comments:

1. Extend the WYSIWYG controller to support mentions (similar to existing implementation)
2. Add a `mentions` relationship to `Comment` entity
3. Trigger notifications when users are mentioned

### 6. Add Comment Attachments

Allow file attachments:

1. Add `attachments` field to `Comment` entity (JSON or separate table)
2. Update `CommentType` form to include file upload
3. Store files using Symfony's VichUploader or similar
4. Display attachments in comment templates

## Testing

### Manual Testing Checklist

- [ ] Create a comment on a Character
- [ ] Edit the comment
- [ ] Reply to the comment
- [ ] Mark comment as resolved
- [ ] Mark comment as unresolved
- [ ] Delete a comment
- [ ] Delete a parent comment (should cascade to replies)
- [ ] Delete a StoryObject with comments (should cascade delete comments)
- [ ] View comments list with multiple threads
- [ ] Verify comment count and unresolved count statistics

### Database Migration

To apply the database changes:

```bash
# Run migration
make migrate
# OR: docker compose exec -T php bin/console doctrine:migrations:migrate

# Verify tables
docker compose exec -T php bin/console doctrine:schema:validate
```

## Translation Keys

All translation keys are in `translations/forms.en.yaml` under the `comment` namespace:

```yaml
comment:
  content: "Comment"
  discussions: "Discussions"
  add_new: "Add Comment"
  create: "New Comment"
  edit: "Edit Comment"
  reply: "Reply to Comment"
  # ... and more
```

## Known Limitations

1. **No pagination**: Comments list shows all comments (fine for POC, needs pagination for production)
2. **No email notifications**: Users aren't notified of new comments
3. **No real-time updates**: Page refresh required to see new comments
4. **No rich text**: Plain text only (can be extended with WYSIWYG)
5. **No search/filter**: Can't filter comments by author, date, or status
6. **No attachments**: Text-only comments

## Future Enhancements

1. **Real-time updates**: Use Mercure/WebSockets for live comment updates
2. **Pagination**: Add pagination for large comment threads
3. **Search & Filter**: Filter by author, date range, resolved status
4. **Export**: Export discussions as PDF or Markdown
5. **Analytics**: Track discussion activity per StoryObject
6. **Permissions**: Fine-grained permissions (who can comment, resolve, delete)
7. **Comment templates**: Pre-defined comment templates for common discussions
8. **Integration with story graph**: Link comments to specific parts of decision trees or story graphs

## Code Quality

All code follows LARPilot standards:
- **PSR-12** coding standard
- **Domain-Driven Design** architecture
- **Type safety** with PHP 8.2+ features
- **Translation-ready** with Symfony translation system
- **Responsive UI** with Bootstrap 5.3
- **Accessibility** with proper ARIA labels and semantic HTML

## Conclusion

This POC demonstrates a fully functional comments and discussions system that integrates seamlessly with LARPilot's existing architecture. It's designed to be extensible and can be easily enhanced with additional features as needed.
