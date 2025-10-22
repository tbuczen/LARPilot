# Feedback System - Technical Documentation

## System Architecture

LARPilot uses a self-hosted feedback system that integrates a custom JavaScript widget with FreeScout helpdesk. The system captures visual feedback (screenshots with annotations) and contextual metadata to create support tickets.

### Component Stack

```
┌─────────────────┐
│  LARPilot App   │  Symfony 7.2 + Stimulus
│  (any page)     │  Frontend: html2canvas, Bootstrap 5
└────────┬────────┘
         │ User interaction → Stimulus controller
         ▼
┌─────────────────┐
│ Feedback Widget │  feedback_controller.js
│ - Screenshot    │  - Captures DOM via html2canvas
│ - Annotations   │  - Gathers context automatically
│ - Form fields   │  - Submits JSON to API
└────────┬────────┘
         │ POST /api/feedback
         ▼
┌─────────────────┐
│ LARPilot API    │  Symfony Controller
│ FeedbackController
│ - Validates     │  - JSON request validation
│ - Enriches      │  - Context enrichment
└────────┬────────┘
         │ FeedbackService
         ▼
┌─────────────────┐
│   FreeScout     │  External Helpdesk (Laravel)
│ REST API        │  POST /api/conversations
│ - Creates ticket│  - Attaches screenshot
│ - Sends email   │  - Manages workflow
└─────────────────┘
```

## Implementation Details

### Frontend Implementation

**Location:** `assets/controllers/feedback_controller.js`

**Dependencies:**
- `@hotwired/stimulus` - Controller framework
- `html2canvas` - Screenshot capture (configured in `importmap.php`)
- Bootstrap 5 - Modal and UI components

**Key Features:**
- Screenshot capture with DOM rendering (html2canvas)
- Automatic context gathering (URL, user info, browser, LARP context)
- Form validation and error handling
- Loading states and success/error feedback
- Modal management with Bootstrap

**Context Capture Logic:**
```javascript
gatherContext() {
    // Extract LARP ID from URL pattern: /larp/(\d+)
    const urlMatch = window.location.pathname.match(/\/larp\/(\d+)/);
    const larpId = urlMatch ? urlMatch[1] : null;

    // Extract LARP title from DOM attribute
    const larpTitleElement = document.querySelector('[data-larp-title]');
    const larpTitle = larpTitleElement?.dataset.larpTitle : null;

    return {
        url: window.location.href,
        route: window.location.pathname,
        userEmail: this.userEmailValue || null,
        userName: this.userNameValue || null,
        userId: this.userIdValue || null,
        larpId: larpId,
        larpTitle: larpTitle,
        browser: navigator.userAgent,
        viewport: `${window.innerWidth}x${window.innerHeight}`,
        screenResolution: `${window.screen.width}x${window.screen.height}`,
        timestamp: new Date().toISOString(),
    };
}
```

**Screenshot Workflow:**
1. Hide feedback modal temporarily
2. Wait 300ms for DOM to stabilize
3. Capture `document.body` via html2canvas with:
   - `logging: false` - No console spam
   - `useCORS: true` - Load cross-origin images
   - `allowTaint: true` - Include external resources
   - `backgroundColor: '#ffffff'` - White background
4. Convert canvas to base64 PNG data URI
5. Restore modal and show preview

**Template Integration:**
```twig
{# templates/base.html.twig #}
<div data-controller="feedback"
     data-feedback-api-url-value="{{ path('api_feedback_submit') }}"
     data-feedback-user-email-value="{{ app.user ? app.user.email : '' }}"
     data-feedback-user-name-value="{{ app.user ? (app.user.firstName ~ ' ' ~ app.user.lastName)|trim : '' }}"
     data-feedback-user-id-value="{{ app.user ? app.user.id : '' }}">
    <button type="button" class="feedback-float-button btn btn-primary"
            data-action="click->feedback#open"
            title="Send Feedback">
        <i class="bi bi-chat-left-text"></i>
    </button>
</div>
```

**Styling:**
- Location: `assets/styles/components/feedback.scss`
- Imported in: `assets/styles/app.scss`
- Features:
  - Fixed positioning (bottom-right, z-index 1040)
  - Responsive sizing (56px → 48px on mobile)
  - Hover/active animations
  - Modal customization
  - Screenshot preview container with max-height scroll

### Backend Implementation

#### API Controller

**Location:** `src/Domain/Feedback/Controller/API/FeedbackController.php`

**Route:** `POST /api/feedback` (name: `api_feedback_submit`)

**Request Format:**
```json
{
  "type": "bug_report|feature_request|question|general",
  "subject": "Brief description",
  "message": "Detailed description",
  "screenshot": "data:image/png;base64,...",  // Optional
  "context": {
    "url": "https://larpilot.com/backoffice/larp/123/characters",
    "route": "/backoffice/larp/123/characters",
    "userEmail": "user@example.com",
    "userName": "John Doe",
    "userId": 42,
    "larpId": 123,
    "larpTitle": "My LARP Event",
    "browser": "Mozilla/5.0...",
    "viewport": "1920x1080",
    "screenResolution": "1920x1200",
    "timestamp": "2025-01-16T12:34:56Z"
  }
}
```

**Validation:**
- JSON parsing with error handling
- Required fields: `type`, `subject`, `message`
- Optional fields: `screenshot`, `context`

**Response Codes:**
- `201 Created` - Success, includes `ticketId`
- `400 Bad Request` - Invalid JSON or missing required fields
- `500 Internal Server Error` - FreeScout API failure

**Error Handling:**
- `\InvalidArgumentException` → 400 response
- Generic `\Exception` → 500 response with logging
- Comprehensive logging for debugging

#### Feedback Service

**Location:** `src/Domain/Feedback/Service/FeedbackService.php`

**Dependencies:**
- `Symfony\Contracts\HttpClient\HttpClientInterface` - HTTP requests
- `Psr\Log\LoggerInterface` - Logging
- Configuration parameters (injected via DI):
  - `$freescoutApiUrl` - FreeScout API base URL
  - `$freescoutApiToken` - API authentication token
  - `$freescoutMailboxId` - Target mailbox ID

**Key Methods:**

1. **`submitFeedback(array $feedbackData): ?int`**
   - Validates feedback type
   - Builds ticket subject with emoji prefix: `[🐛 Bug Report] Subject`
   - Constructs ticket body with context information
   - Parses customer name into first/last
   - Prepares screenshot attachment (base64 → FreeScout format)
   - Sends POST request to FreeScout `/api/conversations`
   - Returns ticket ID on success

2. **`buildTicketBody(string $message, array $context): string`**
   - Appends context information to message
   - Formats as markdown-style list
   - Includes: URL, route, LARP context, user ID, browser, viewport, timestamp

3. **`parseCustomerName(string $fullName): array`**
   - Splits full name on first space
   - Returns `[firstName, lastName]`
   - Defaults to `['Anonymous', 'User']` if empty

4. **`prepareScreenshotAttachment(string $screenshotData): array`**
   - Extracts base64 data from data URI: `data:image/png;base64,...`
   - Generates filename: `screenshot_2025-01-16_143256.png`
   - Returns FreeScout attachment format:
     ```php
     [
         'fileName' => 'screenshot_YYYY-MM-DD_HHmmss.{ext}',
         'mimeType' => 'image/{ext}',
         'data' => '{base64}',  // Without data URI prefix
     ]
     ```

**FreeScout API Integration:**

Endpoint: `POST {FREESCOUT_API_URL}/conversations`

Headers:
```
Content-Type: application/json
X-FreeScout-API-Key: {FREESCOUT_API_TOKEN}
```

Request Body:
```json
{
  "mailboxId": 1,
  "type": "email",
  "status": "active",
  "subject": "[🐛 Bug Report] Subject",
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
      "text": "Message\n\n---\n**Context Information:**\n...",
      "attachments": [
        {
          "fileName": "screenshot_2025-01-16_143256.png",
          "mimeType": "image/png",
          "data": "base64-encoded-data"
        }
      ]
    }
  ]
}
```

### Configuration

**Environment Variables (.env / .env.local):**
```bash
# FreeScout Integration
FREESCOUT_API_URL=https://help.larpilot.com/api
FREESCOUT_API_TOKEN=your-api-token-here
FREESCOUT_MAILBOX_ID=1
```

**Service Configuration (config/services.yaml):**
```yaml
parameters:
    freescout.api_url: '%env(default::FREESCOUT_API_URL)%'
    freescout.api_token: '%env(default::FREESCOUT_API_TOKEN)%'
    freescout.mailbox_id: '%env(int:default::FREESCOUT_MAILBOX_ID)%'

services:
    App\Domain\Feedback\Service\FeedbackService:
        arguments:
            $freescoutApiUrl: '%freescout.api_url%'
            $freescoutApiToken: '%freescout.api_token%'
            $freescoutMailboxId: '%freescout.mailbox_id%'
```

**Asset Configuration:**

`importmap.php`:
```php
'html2canvas' => [
    'version' => 'latest',
],
```

`assets/styles/app.scss`:
```scss
@import "./components/feedback";
```

## FreeScout Setup (External System)

FreeScout is a separate Laravel-based helpdesk application. It must be deployed independently.

### Installation Methods

**Option 1: Standard Laravel Installation**

```bash
cd /var/www
git clone https://github.com/freescout-helpdesk/freescout.git help.larpilot.com
cd help.larpilot.com

composer install --no-dev
cp .env.example .env
# Configure .env: APP_URL, database, mail

php artisan key:generate
php artisan migrate --force

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

npm install && npm run prod
php artisan freescout:create-user --role=admin
```

**Option 2: Docker Deployment**

```bash
mkdir -p ~/freescout && cd ~/freescout
wget https://raw.githubusercontent.com/freescout-helpdesk/freescout/dist/docker-compose.yml
# Edit docker-compose.yml for environment variables
docker-compose up -d
docker-compose exec freescout php artisan freescout:create-user --role=admin
```

### API Configuration

**Generate API Token:**

Method 1 (CLI):
```bash
php artisan freescout:api-create-token
# Save the output token
```

Method 2 (Web UI):
1. Login as admin
2. Navigate: **Manage → API**
3. Click "Create new API token"
4. Copy token to LARPilot `.env.local`

### Mailbox Setup

1. **Manage → Mailboxes → New Mailbox**
2. Configure:
   - Name: "User Feedback"
   - Email: `feedback@larpilot.com`
   - Email Integration: IMAP/POP3 or custom
3. Note the Mailbox ID (visible in URL or database)
4. Update `FREESCOUT_MAILBOX_ID` in LARPilot

### Web Server Configuration

**Nginx:**
```nginx
server {
    listen 443 ssl http2;
    server_name help.larpilot.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /var/www/help.larpilot.com/public;
    index index.php;

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

**Apache:**
```apache
<VirtualHost *:443>
    ServerName help.larpilot.com
    DocumentRoot /var/www/help.larpilot.com/public

    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    <Directory /var/www/help.larpilot.com/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Deployment Checklist

- [ ] Deploy FreeScout instance (separate server/subdomain)
- [ ] Configure web server with HTTPS
- [ ] Create admin user in FreeScout
- [ ] Generate API token in FreeScout
- [ ] Create "User Feedback" mailbox
- [ ] Note mailbox ID
- [ ] Update `.env.local` in LARPilot with:
  - `FREESCOUT_API_URL`
  - `FREESCOUT_API_TOKEN`
  - `FREESCOUT_MAILBOX_ID`
- [ ] Test API connection: `curl -H "X-FreeScout-API-Key: TOKEN" https://help.larpilot.com/api/conversations`
- [ ] Clear Symfony cache: `php bin/console cache:clear`
- [ ] Test feedback submission from LARPilot UI
- [ ] Verify ticket creation in FreeScout
- [ ] Configure email notifications in FreeScout
- [ ] Optional: Setup Knowledge Base module
- [ ] Optional: Add rate limiting to `/api/feedback` endpoint

## Troubleshooting

### Widget Not Appearing

**Symptoms:** Feedback button missing from page

**Debugging:**
```javascript
// Browser console
console.log('Stimulus controllers:', application.controllers);
// Should show 'feedback' controller
```

**Solutions:**
- Check `templates/base.html.twig` includes feedback div
- Verify `data-controller="feedback"` attribute
- Check browser console for JavaScript errors
- Rebuild assets: `php bin/console asset-map:compile`

### Submissions Failing

**Symptoms:** Error message after clicking "Submit Feedback"

**Debugging:**
```bash
# Check Symfony logs
tail -f var/log/dev.log

# Test FreeScout API manually
curl -X POST https://help.larpilot.com/api/conversations \
  -H "X-FreeScout-API-Key: YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"mailboxId":1,"type":"email","status":"active","subject":"Test",...}'
```

**Common Issues:**
1. Invalid API token → Check `.env.local` and FreeScout settings
2. Wrong mailbox ID → Verify in FreeScout UI (Manage → Mailboxes)
3. FreeScout unreachable → Check network, firewall, HTTPS certificate
4. JSON parsing error → Check request format in browser Network tab

### Screenshots Not Attaching

**Symptoms:** Ticket created but no screenshot attachment

**Debugging:**
```bash
# Check FreeScout upload limits
grep UPLOAD_MAX_FILESIZE /path/to/freescout/.env

# Check PHP upload limits
php -i | grep -E "upload_max_filesize|post_max_size|memory_limit"
```

**Solutions:**
- Increase FreeScout `.env`: `UPLOAD_MAX_FILESIZE=10M`
- Increase PHP limits in `php.ini`:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 10M
  memory_limit = 256M
  ```
- Restart PHP-FPM: `systemctl restart php8.2-fpm`

### Email Notifications Not Sending

**Symptoms:** Tickets created but no emails received

**Debugging:**
```bash
# Check FreeScout mail config
cd /path/to/freescout
grep -E "MAIL_" .env
```

**Solutions:**
1. Configure SMTP in FreeScout `.env`:
   ```bash
   MAIL_DRIVER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your@email.com
   MAIL_PASSWORD=your-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=your@email.com
   MAIL_FROM_NAME="LARPilot Support"
   ```
2. Test email in FreeScout UI: **Manage → Settings → Mail Settings → Send Test Email**
3. Check spam folders
4. Verify SMTP credentials and firewall rules

## Security Considerations

### Rate Limiting

The `/api/feedback` endpoint is currently **not rate-limited**. Recommended implementation:

**Using Symfony Rate Limiter:**

```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        feedback_api:
            policy: 'sliding_window'
            limit: 5
            interval: '15 minutes'
```

```php
// FeedbackController.php
use Symfony\Component\RateLimiter\RateLimiterFactory;

public function __construct(
    private readonly FeedbackService $feedbackService,
    private readonly LoggerInterface $logger,
    private readonly RateLimiterFactory $feedbackApiLimiter,
) {}

#[Route('/api/feedback', name: 'api_feedback_submit', methods: ['POST'])]
public function submit(Request $request): JsonResponse
{
    $limiter = $this->feedbackApiLimiter->create($request->getClientIp());

    if (!$limiter->consume(1)->isAccepted()) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Too many feedback submissions. Please try again later.',
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }

    // ... rest of implementation
}
```

### Input Validation

**Current Implementation:**
- ✅ JSON parsing validation
- ✅ Required field validation
- ✅ Feedback type enum validation
- ✅ Exception handling with sanitized error messages

**Additional Recommendations:**
- Add max length validation for `subject` (e.g., 255 chars) and `message` (e.g., 5000 chars)
- Validate screenshot size (e.g., max 5MB base64 data)
- Sanitize context fields before forwarding to FreeScout

### Authentication

**Current Implementation:** Anonymous submissions allowed

**Production Recommendations:**
- Require authentication: Add `#[IsGranted('ROLE_USER')]` to controller
- OR: Implement CAPTCHA for anonymous submissions (reCAPTCHA)
- Log anonymous submissions separately for spam monitoring

### API Token Security

- ✅ Token stored in `.env.local` (not committed to git)
- ✅ Token passed in header (not URL)
- ⚠️ Consider token rotation policy (manual or automated)
- ⚠️ Use environment-specific tokens (dev/staging/prod)

### HTTPS Enforcement

- ⚠️ Ensure both LARPilot and FreeScout use HTTPS
- ⚠️ Configure HSTS headers in web server
- ⚠️ Use valid SSL certificates (Let's Encrypt recommended)

## Testing

### Manual Testing

1. **Widget Display:**
   - Navigate to any LARPilot page
   - Verify feedback button appears in bottom-right corner
   - Click button → Modal should open

2. **Screenshot Capture:**
   - Open modal
   - Click "Capture Screenshot"
   - Verify preview appears
   - Check base64 data in browser DevTools (Network tab)

3. **Form Submission:**
   - Fill all required fields
   - Submit with/without screenshot
   - Verify success message
   - Check FreeScout for new ticket

4. **Context Capture:**
   - Submit feedback from LARP-specific page (e.g., `/backoffice/larp/123/characters`)
   - Verify ticket includes LARP ID and title in context

### Automated Testing (Future)

**Unit Tests:**
```php
// tests/Domain/Feedback/Service/FeedbackServiceTest.php
class FeedbackServiceTest extends TestCase
{
    public function testSubmitFeedbackCreateTicket(): void
    {
        $mockClient = $this->createMock(HttpClientInterface::class);
        // Mock successful API response

        $service = new FeedbackService($mockClient, $logger, $apiUrl, $token, $mailboxId);
        $ticketId = $service->submitFeedback([
            'type' => 'bug_report',
            'subject' => 'Test',
            'message' => 'Test message',
        ]);

        $this->assertNotNull($ticketId);
    }
}
```

**Integration Tests:**
```php
// tests/Domain/Feedback/Controller/API/FeedbackControllerTest.php
class FeedbackControllerTest extends WebTestCase
{
    public function testSubmitFeedbackEndpoint(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/feedback', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'type' => 'bug_report',
                'subject' => 'Test Bug',
                'message' => 'Description',
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
    }
}
```

## Performance Considerations

### Screenshot Size Optimization

**Current:** Full-page screenshots can be large (2-5MB base64)

**Optimization Options:**
1. **Client-side compression:**
   ```javascript
   // Reduce image quality
   const screenshotData = canvas.toDataURL('image/jpeg', 0.7); // 70% quality
   ```

2. **Resize before upload:**
   ```javascript
   // Max width/height
   const MAX_WIDTH = 1920;
   const MAX_HEIGHT = 1080;

   if (canvas.width > MAX_WIDTH || canvas.height > MAX_HEIGHT) {
       const scale = Math.min(MAX_WIDTH / canvas.width, MAX_HEIGHT / canvas.height);
       const resized = document.createElement('canvas');
       resized.width = canvas.width * scale;
       resized.height = canvas.height * scale;
       const ctx = resized.getContext('2d');
       ctx.drawImage(canvas, 0, 0, resized.width, resized.height);
       canvas = resized;
   }
   ```

3. **Upload to S3/CDN instead of base64 in JSON:**
   - Upload screenshot to object storage
   - Send URL reference instead of base64
   - Attach URL to ticket instead of inline attachment

### HTTP Request Optimization

**Current:** Single HTTP request with potentially large payload

**Consideration:** If screenshots regularly exceed 5MB, consider multipart/form-data upload instead of JSON.

## Future Enhancements

### Session Replay Integration

**Tools:** PostHog, OpenReplay, LogRocket

**Benefits:**
- See user actions leading to bug
- Understand user intent
- Reduce "works on my machine" issues

**Implementation:**
```javascript
// Add session replay URL to context
context.sessionReplayUrl = window.posthog?.get_session_replay_url();
```

### Auto-Categorization with AI

**Idea:** Use OpenAI/Claude API to categorize feedback automatically

**Implementation:**
```php
// FeedbackService.php
private function categorizeFeedback(string $message): array
{
    $response = $this->openAiClient->chat([
        'model' => 'gpt-4',
        'messages' => [
            ['role' => 'system', 'content' => 'Categorize this feedback...'],
            ['role' => 'user', 'content' => $message],
        ],
    ]);

    return json_decode($response['choices'][0]['message']['content'], true);
}
```

### GitHub Integration

**Idea:** Auto-create GitHub issues for feature requests

**Implementation:**
```php
// After creating FreeScout ticket
if ($feedbackData['type'] === 'feature_request') {
    $this->githubService->createIssue([
        'title' => $feedbackData['subject'],
        'body' => $this->buildGitHubIssueBody($feedbackData),
        'labels' => ['enhancement', 'user-request'],
    ]);
}
```

### In-App Ticket Status

**Idea:** Allow users to view their feedback status without leaving LARPilot

**Implementation:**
- Add FreeScout webhook for ticket updates
- Store ticket metadata in LARPilot database
- Show ticket list in user dashboard
- Real-time status updates via Mercure/WebSockets

## Resources

- **FreeScout Documentation:** https://freescout.net/docs/
- **FreeScout API Reference:** https://freescout.net/api-docs/
- **html2canvas Documentation:** https://html2canvas.hertzen.com/
- **Stimulus Handbook:** https://stimulus.hotwired.dev/
- **Symfony Rate Limiter:** https://symfony.com/doc/current/rate_limiter.html
