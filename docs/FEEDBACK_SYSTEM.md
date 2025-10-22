# Feedback System Documentation

> **Note:** This document has been split into specialized documentation:
> - **[Technical Documentation](FEEDBACK_SYSTEM_TECHNICAL.md)** - For developers (architecture, implementation, deployment)
> - **[User Guide](FEEDBACK_SYSTEM_USER_GUIDE.md)** - For end users (how to submit feedback effectively)

## Implementation Status

✅ **READY FOR DEPLOYMENT** (Configuration Required)

All code implementation is complete. To go live:
1. Deploy FreeScout instance (separate server/subdomain)
2. Configure `.env.local` with FreeScout API credentials
3. Test feedback submission
4. Optionally add rate limiting for production

See **[Technical Documentation](FEEDBACK_SYSTEM_TECHNICAL.md)** for deployment checklist.

## Overview

LARPilot uses a self-hosted feedback system that allows users to submit bug reports, feature requests, and general feedback with visual context (screenshots and context capture). The system combines:

- **FreeScout**: Self-hosted helpdesk for managing feedback tickets (hosted separately)
- **Custom Stimulus Widget**: JavaScript-based feedback widget with screenshot capture
- **LARPilot Integration**: API endpoint to bridge the widget → FreeScout

## Architecture

```
┌─────────────────┐
│  LARPilot App   │
│  (any page)     │
└────────┬────────┘
         │ 1. User clicks feedback button
         ▼
┌─────────────────┐
│ FeedbackPlus    │
│ Widget Modal    │
│ - Screenshot    │
│ - Annotations   │
│ - Form fields   │
└────────┬────────┘
         │ 2. Submit with context
         ▼
┌─────────────────┐
│ LARPilot API    │
│ /api/feedback   │
│ - Captures data │
│ - Enriches ctx  │
└────────┬────────┘
         │ 3. Create ticket via API
         ▼
┌─────────────────┐
│   FreeScout     │
│ help.larpilot   │
│ - Ticket mgmt   │
│ - Email notify  │
│ - Knowledge base│
└─────────────────┘
```

## FreeScout Setup (Separate Project)

### Installation

FreeScout is a self-hosted help desk system built with Laravel. It should be hosted on a separate subdomain (e.g., `help.larpilot.com`).

#### Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.2+
- Web server (Apache/Nginx)
- Composer
- Node.js & npm (for asset compilation)

#### Installation Methods

**Option 1: Standard Installation**

```bash
# Clone FreeScout repository
cd /var/www
git clone https://github.com/freescout-helpdesk/freescout.git help.larpilot.com
cd help.larpilot.com

# Install dependencies
composer install --no-dev

# Copy environment file
cp .env.example .env

# Configure .env
nano .env
# Set APP_URL=https://help.larpilot.com
# Set database credentials
# Set mail settings

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Compile assets
npm install
npm run prod

# Create admin user
php artisan freescout:create-user --role=admin
```

**Option 2: Docker Installation**

```bash
# Create directory
mkdir -p ~/freescout && cd ~/freescout

# Download docker-compose.yml
wget https://raw.githubusercontent.com/freescout-helpdesk/freescout/dist/docker-compose.yml

# Edit docker-compose.yml to set environment variables
nano docker-compose.yml

# Start containers
docker-compose up -d

# Create admin user
docker-compose exec freescout php artisan freescout:create-user --role=admin
```

**Option 3: Shared Hosting**

FreeScout can run on shared hosting with PHP 8.0+ and MySQL. Follow the [official installation guide](https://freescout.net/docs/installation/).

### Configuration

#### 1. API Setup

FreeScout needs API access enabled for the webhook integration:

```bash
# Generate API token
php artisan freescout:api-create-token

# Save the token securely - you'll need it for LARPilot configuration
```

Or via web interface:
1. Login as admin
2. Go to **Manage → API**
3. Create new API token
4. Save the token

#### 2. Mailbox Setup

Create a mailbox for feedback submissions:

1. **Manage → Mailboxes → New Mailbox**
2. Name: "User Feedback"
3. Email: `feedback@larpilot.com`
4. Configure email integration (IMAP/POP3 or custom)

#### 3. Custom Fields (Optional)

Add custom fields to capture additional context:

1. **Manage → Custom Fields → New Field**
2. Create fields:
   - **Page URL** (text, required)
   - **User ID** (text, optional)
   - **Browser Info** (text, optional)
   - **LARP Context** (text, optional)
   - **Feedback Type** (dropdown: Bug Report, Feature Request, Question, General)

#### 4. Knowledge Base Module (Optional)

Install the Knowledge Base module for self-service help:

```bash
# Download module
php artisan freescout:module-download knowledge-base

# Install module
php artisan freescout:module-install knowledge-base

# Enable module
php artisan freescout:module-enable knowledge-base
```

Or via web interface:
1. **Manage → Modules**
2. Find "Knowledge Base"
3. Click "Install" and "Activate"

### Web Server Configuration

#### Nginx

```nginx
server {
    listen 80;
    server_name help.larpilot.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name help.larpilot.com;

    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;

    root /var/www/help.larpilot.com/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache

```apache
<VirtualHost *:80>
    ServerName help.larpilot.com
    Redirect permanent / https://help.larpilot.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName help.larpilot.com
    DocumentRoot /var/www/help.larpilot.com/public

    SSLEngine on
    SSLCertificateFile /path/to/ssl/cert.pem
    SSLCertificateKeyFile /path/to/ssl/key.pem

    <Directory /var/www/help.larpilot.com/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## FeedbackPlus Widget Integration

### About FeedbackPlus

FeedbackPlus is an open-source JavaScript library that adds screenshot capture and annotation functionality to feedback forms. It's inspired by Google's "Report an Issue" widget.

- **Repository**: https://github.com/puffinsoft/feedbackplus
- **License**: MIT
- **Size**: ~50KB minified
- **Dependencies**: None (vanilla JavaScript)

### Installation in LARPilot

The widget is integrated via Symfony's AssetMapper and a custom Stimulus controller.

#### Files Created

```
assets/
├── controllers/
│   └── feedback_controller.js       # Stimulus controller
├── vendor/feedbackplus/
│   ├── feedbackplus.min.js          # FeedbackPlus library
│   └── feedbackplus.css             # Widget styles
└── styles/
    └── feedback.css                  # Custom feedback button styles

src/
├── Controller/
│   └── API/
│       └── FeedbackController.php    # Webhook endpoint
└── Service/
    └── FeedbackService.php           # FreeScout API integration

templates/
└── base.html.twig                    # Includes feedback button
```

### Configuration

Add FreeScout API credentials to `.env.local`:

```bash
# FreeScout Integration
FREESCOUT_API_URL=https://help.larpilot.com/api
FREESCOUT_API_TOKEN=your-api-token-here
FREESCOUT_MAILBOX_ID=1
```

Update `config/services.yaml`:

```yaml
parameters:
    freescout.api_url: '%env(FREESCOUT_API_URL)%'
    freescout.api_token: '%env(FREESCOUT_API_TOKEN)%'
    freescout.mailbox_id: '%env(FREESCOUT_MAILBOX_ID)%'
```

### Usage

Users can submit feedback from any page:

1. Click the **floating feedback button** (bottom-right corner)
2. **Take screenshot** with annotation tools:
   - Draw arrows
   - Add text boxes
   - Highlight areas with rectangles
3. **Fill out form**:
   - Feedback type (Bug Report, Feature Request, Question, General)
   - Subject
   - Description
4. **Submit** - creates ticket in FreeScout with:
   - Screenshot attachment
   - Full page context (URL, browser, user info)
   - LARP context (if applicable)

### Context Capture

The widget automatically captures and sends:

- **Page URL**: Current route and full URL
- **User Info**: Email, name, ID (if logged in)
- **Browser**: User agent, viewport size, screen resolution
- **LARP Context**: Current LARP ID and title (if on LARP-specific page)
- **Timestamp**: When feedback was submitted
- **Session Info**: Session ID for debugging

## API Reference

### POST /api/feedback

Webhook endpoint for receiving FeedbackPlus submissions.

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "type": "bug_report|feature_request|question|general",
  "subject": "Short description",
  "message": "Detailed description",
  "screenshot": "data:image/png;base64,...",
  "context": {
    "url": "https://larpilot.com/backoffice/larp/123/characters",
    "route": "backoffice_larp_story_character_list",
    "userEmail": "user@example.com",
    "userName": "John Doe",
    "userId": 42,
    "larpId": 123,
    "larpTitle": "My LARP Event",
    "browser": "Mozilla/5.0...",
    "viewport": "1920x1080",
    "timestamp": "2025-01-16T12:34:56Z"
  }
}
```

**Response:**
```json
{
  "success": true,
  "ticketId": 123,
  "message": "Feedback submitted successfully"
}
```

### FreeScout API Integration

The `FeedbackService` uses FreeScout's REST API to create tickets:

**Endpoint:** `POST /api/conversations`

**Request:**
```json
{
  "mailboxId": 1,
  "type": "email",
  "status": "active",
  "subject": "[Bug Report] Short description",
  "customer": {
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe"
  },
  "threads": [
    {
      "type": "customer",
      "customer": {
        "email": "user@example.com"
      },
      "text": "Detailed description\n\nContext:\n- Page: /backoffice/larp/123/characters\n- Browser: Chrome 120\n- LARP: My LARP Event (#123)",
      "attachments": [
        {
          "fileName": "screenshot.png",
          "mimeType": "image/png",
          "data": "base64-encoded-image-data"
        }
      ]
    }
  ]
}
```

## Customization

### Widget Appearance

Edit `assets/styles/feedback.css` to customize the floating button:

```css
.feedback-button {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    /* Customize colors, size, etc. */
}
```

### Feedback Types

Edit `assets/controllers/feedback_controller.js` to add/modify feedback types:

```javascript
const feedbackTypes = [
    { value: 'bug_report', label: 'Bug Report' },
    { value: 'feature_request', label: 'Feature Request' },
    { value: 'question', label: 'Question' },
    { value: 'general', label: 'General Feedback' }
];
```

### Email Templates

Customize FreeScout email templates:

1. **Manage → Settings → Email Templates**
2. Edit templates for:
   - New conversation notification
   - Customer reply notification
   - Auto-replies

## Troubleshooting

### Widget not appearing
- Check browser console for JavaScript errors
- Verify `feedback_controller.js` is loaded
- Ensure `data-controller="feedback"` attribute is in `base.html.twig`

### Submissions failing
- Check FreeScout API token in `.env.local`
- Verify FreeScout API endpoint is accessible: `curl https://help.larpilot.com/api/conversations`
- Check Symfony logs: `tail -f var/log/dev.log`

### Screenshots not attaching
- Verify base64 encoding in browser Network tab
- Check FreeScout file upload limits in `.env`: `UPLOAD_MAX_FILESIZE=10M`
- Increase PHP memory limit if needed

### FreeScout not sending emails
- Check mail configuration in FreeScout `.env`
- Test email settings: **Manage → Settings → Mail Settings → Send Test Email**
- Verify SMTP credentials or mail server configuration

## Security Considerations

- **Rate Limiting**: Add rate limiting to `/api/feedback` endpoint to prevent spam
- **CSRF Protection**: Ensure CSRF token is included in feedback submissions
- **Authentication**: Consider requiring authentication for feedback submissions
- **Input Validation**: Sanitize all user inputs before forwarding to FreeScout
- **API Token Security**: Store FreeScout API token securely, never commit to git
- **HTTPS**: Always use HTTPS for both LARPilot and FreeScout

## Future Enhancements

Potential improvements for the feedback system:

1. **Session Replay**: Integrate session replay tool (e.g., PostHog, OpenReplay)
2. **Heatmaps**: Add heatmap tracking for user behavior analysis
3. **In-app Replies**: Allow users to view ticket status without leaving LARPilot
4. **Auto-categorization**: Use AI to automatically categorize feedback
5. **Duplicate Detection**: Check for similar existing tickets before creating new ones
6. **Priority Scoring**: Auto-assign priority based on feedback type and user role
7. **Integration with GitHub**: Auto-create GitHub issues for feature requests
8. **Analytics Dashboard**: Track feedback trends, common issues, feature requests

## Resources

- **FreeScout Documentation**: https://freescout.net/docs/
- **FreeScout API**: https://freescout.net/api-docs/
- **FeedbackPlus GitHub**: https://github.com/puffinsoft/feedbackplus
- **FeedbackPlus Demo**: https://puffinsoft.github.io/feedbackplus/
