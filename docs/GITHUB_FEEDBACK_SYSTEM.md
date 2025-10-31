# GitHub Feedback System

## Overview

LARPilot uses GitHub Issues and Discussions for user feedback. This system allows users to submit bug reports, feature requests, questions, and general feedback directly from the application. Feedback is routed to the appropriate GitHub location based on type:

- **Bug Reports** → GitHub Issues (with `bug` label)
- **Feature Requests** → GitHub Issues (with `enhancement` label)
- **Questions** → GitHub Discussions
- **General Feedback** → GitHub Discussions

## Features

- 🎯 **Direct GitHub Integration**: Feedback goes straight to GitHub where development happens
- 📸 **Screenshot Capture**: Users can capture and annotate screenshots using html2canvas
- 🤖 **reCAPTCHA Protection**: Prevents spam submissions
- 📋 **Context Capture**: Automatically includes page URL, user info, browser details, LARP context
- 🎨 **Twig Component**: Feedback modal is a reusable Twig component
- 📱 **Responsive**: Works on desktop and mobile devices

## Architecture

```
┌─────────────────┐
│  LARPilot App   │
│  (any page)     │
└────────┬────────┘
         │ 1. User clicks feedback button
         ▼
┌─────────────────┐
│ Feedback Modal  │
│ (Twig Component)│
│ - Screenshot    │
│ - reCAPTCHA     │
│ - Form fields   │
└────────┬────────┘
         │ 2. Submit with context + reCAPTCHA
         ▼
┌─────────────────┐
│ LARPilot API    │
│ /api/feedback   │
│ - Validates     │
│ - Enriches ctx  │
└────────┬────────┘
         │ 3. Create via GitHub API
         ▼
┌─────────────────┐
│   GitHub API    │
│ REST + GraphQL  │
│ - Issues API    │
│ - Discussions   │
└─────────────────┘
```

## Setup

### 1. GitHub Configuration

#### Create Personal Access Token

1. Go to https://github.com/settings/tokens/new
2. Name: "LARPilot Feedback System"
3. Select scopes:
   - `repo` (Full control of private repositories)
   - `public_repo` (Access public repositories)
   - `write:discussion` (Write access to discussions)
4. Generate token and save it securely

#### Get Discussion Category ID

You need the category ID for where questions/general feedback will be posted.

**Option A: Using GitHub GraphQL Explorer**

1. Go to https://docs.github.com/en/graphql/overview/explorer
2. Run this query (replace `OWNER` and `REPO`):

```graphql
query {
  repository(owner: "tbuczen", name: "LARPilot") {
    discussionCategories(first: 10) {
      nodes {
        id
        name
        slug
      }
    }
  }
}
```

3. Find the category you want (e.g., "Q&A" or "Feedback")
4. Copy the `id` value (format: `DIC_kwDOABcD1M4ABCDE`)

**Option B: Using curl**

```bash
curl -H "Authorization: bearer YOUR_GITHUB_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"query":"query { repository(owner: \"tbuczen\", name: \"LARPilot\") { discussionCategories(first: 10) { nodes { id name slug } } } }"}' \
     https://api.github.com/graphql
```

### 2. Environment Configuration

Add to `.env.local`:

```bash
###> GitHub Integration (Feedback System)
GITHUB_TOKEN=ghp_your_token_here
GITHUB_REPO=tbuczen/LARPilot
GITHUB_DISCUSSION_CATEGORY_ID=DIC_kwDOABcD1M4ABCDE

###> reCAPTCHA
GOOGLE_RECAPTCHA_SITE_KEY=your_site_key
GOOGLE_RECAPTCHA_SECRET=your_secret_key
```

### 3. reCAPTCHA Setup

If you don't already have reCAPTCHA configured:

1. Go to https://www.google.com/recaptcha/admin/create
2. Choose reCAPTCHA v2 (Checkbox)
3. Add your domain(s)
4. Copy Site Key and Secret Key to `.env.local`

### 4. GitHub Repository Setup

**Enable Issues** (if not already enabled):
1. Go to your repository Settings
2. Ensure "Issues" is checked under Features

**Enable Discussions**:
1. Go to your repository Settings
2. Check "Discussions" under Features
3. Set up discussion categories:
   - **Q&A**: For questions
   - **General**: For general feedback
   - **Ideas**: Alternative for feature requests

**Recommended Issue Labels**:
- `bug` (automatically added for bug reports)
- `enhancement` (automatically added for feature requests)
- `user-feedback` (optional, for all feedback)

## Usage

### For Users

1. **Open Feedback Modal**:
   - Click the floating feedback button (bottom-right corner on any page)
   - Or trigger programmatically: `document.querySelector('[data-controller="feedback"]').click()`

2. **Select Feedback Type**:
   - 🐛 Bug Report → Creates GitHub Issue
   - 💡 Feature Request → Creates GitHub Issue
   - ❓ Question → Creates GitHub Discussion
   - 💬 General Feedback → Creates GitHub Discussion

3. **Fill Out Form**:
   - **Subject**: Brief description
   - **Description**: Detailed information
   - **Screenshot** (optional): Click "Capture Screenshot"

4. **Complete reCAPTCHA**: Check the "I'm not a robot" box

5. **Submit**: Click "Submit Feedback"

6. **View on GitHub**: Success message includes a link to the created issue/discussion

### Screenshot Feature

The screenshot capture:
- Temporarily hides the feedback modal
- Captures the entire page
- Allows users to download the screenshot
- Users can manually attach to GitHub if needed

## Technical Details

### Files Created/Modified

```
src/Domain/Feedback/
├── Controller/API/
│   └── FeedbackController.php          # API endpoint (updated)
└── Service/
    └── GitHubFeedbackService.php       # GitHub API integration (new)

templates/components/
└── FeedbackModal.html.twig             # Feedback modal component (new)

assets/controllers/
└── feedback_controller.js              # Stimulus controller (updated)

translations/
└── messages.en.yaml                    # Feedback translations (updated)

config/
├── services.yaml                       # Service configuration (updated)
└── packages/twig.yaml                  # Twig globals (updated)

.env                                    # Environment template (updated)
```

### API Endpoints

**POST /api/feedback**

Request:
```json
{
  "type": "bug_report|feature_request|question|general",
  "subject": "Brief description",
  "message": "Detailed description",
  "screenshot": "data:image/png;base64,...",
  "context": {
    "url": "https://larpilot.com/backoffice/larp/123",
    "route": "backoffice_larp_story_character_list",
    "userEmail": "user@example.com",
    "userName": "John Doe",
    "userId": 42,
    "larpId": 123,
    "larpTitle": "My LARP Event",
    "browser": "Mozilla/5.0...",
    "viewport": "1920x1080",
    "screenResolution": "1920x1080",
    "timestamp": "2025-01-31T12:34:56Z"
  },
  "recaptchaToken": "03AGdBq24..."
}
```

Response (Success):
```json
{
  "success": true,
  "id": 123,
  "url": "https://github.com/tbuczen/LARPilot/issues/123",
  "type": "issue",
  "message": "Feedback submitted successfully to GitHub"
}
```

Response (Error):
```json
{
  "success": false,
  "message": "reCAPTCHA verification failed. Please try again."
}
```

### GitHub APIs Used

**Issues API** (REST):
```
POST https://api.github.com/repos/{owner}/{repo}/issues
```

**Discussions API** (GraphQL):
```graphql
mutation {
  createDiscussion(input: {
    repositoryId: "...",
    categoryId: "...",
    title: "...",
    body: "..."
  }) {
    discussion {
      id
      number
      url
    }
  }
}
```

## Context Information Captured

The system automatically captures and includes:

- **Page URL**: Full URL where feedback was submitted
- **Route**: Symfony route name
- **User Info**: Email, name, ID (if logged in)
- **LARP Context**: LARP ID and title (if applicable)
- **Browser**: User agent string
- **Viewport**: Browser window size
- **Screen**: Display resolution
- **Timestamp**: When feedback was submitted

Example context section in GitHub:

```markdown
---

### 📋 Context Information

- **Page URL:** https://larpilot.com/backoffice/larp/123/characters
- **Route:** `backoffice_larp_story_character_list`
- **LARP:** Summer LARP 2025 (#123)
- **User ID:** 42
- **User Email:** user@example.com
- **Browser:** Mozilla/5.0 (Windows NT 10.0; Win64; x64)...
- **Viewport:** 1920x1080
- **Screen Resolution:** 1920x1080
- **Timestamp:** 2025-01-31T12:34:56Z
```

## Troubleshooting

### Feedback widget not appearing
- Check browser console for errors
- Verify `FeedbackModal.html.twig` is included in `base.html.twig`
- Ensure Stimulus controller is loaded

### Submissions failing
- Verify GitHub token has correct scopes
- Check GitHub API rate limits: https://docs.github.com/en/rest/overview/resources-in-the-rest-api#rate-limiting
- Verify discussion category ID is correct
- Check Symfony logs: `tail -f var/log/dev.log`

### reCAPTCHA not working
- Verify site key and secret key in `.env.local`
- Check domain is whitelisted in reCAPTCHA admin console
- Ensure reCAPTCHA script is loaded (check browser console)

### Screenshots not capturing
- html2canvas may fail with CORS errors for external images
- Try disabling browser extensions that might interfere
- Screenshot capture is optional - feedback can be submitted without it

## Security Considerations

- ✅ **reCAPTCHA**: Prevents spam submissions
- ✅ **Rate Limiting**: Consider adding rate limiting to `/api/feedback` endpoint
- ✅ **Token Security**: GitHub token stored in environment variables (never committed)
- ✅ **Input Validation**: All inputs validated and sanitized
- ✅ **HTTPS**: Always use HTTPS in production
- ⚠️ **Public Visibility**: GitHub Issues/Discussions are public - sensitive data may be exposed

## Best Practices

### For Administrators

1. **Monitor Feedback**: Enable GitHub notifications for new issues/discussions
2. **Use Labels**: Add custom labels to categorize feedback (e.g., `priority:high`, `frontend`, `backend`)
3. **Create Templates**: Use GitHub issue templates to guide users
4. **Respond Quickly**: Acknowledge feedback within 24-48 hours
5. **Close Duplicates**: Link duplicate issues/discussions
6. **Rotate Tokens**: Regenerate GitHub token periodically

### For Users

1. **Search First**: Check if issue already exists before submitting
2. **Be Specific**: Provide clear, reproducible steps for bugs
3. **Add Context**: More context = faster resolution
4. **Attach Screenshots**: Visual aids help immensely
5. **Follow Up**: Respond to questions from developers

## Future Enhancements

Potential improvements:

- [ ] Screenshot upload to GitHub (requires external image hosting or gists)
- [ ] Draft saving (localStorage)
- [ ] Feedback history for logged-in users
- [ ] Auto-duplicate detection
- [ ] Sentiment analysis for prioritization
- [ ] Integration with project boards
- [ ] Webhook for real-time notifications in app

## Resources

- **GitHub REST API**: https://docs.github.com/en/rest
- **GitHub GraphQL API**: https://docs.github.com/en/graphql
- **GitHub Discussions Guide**: https://docs.github.com/en/discussions
- **reCAPTCHA Documentation**: https://developers.google.com/recaptcha/docs/display
- **html2canvas**: https://html2canvas.hertzen.com/
