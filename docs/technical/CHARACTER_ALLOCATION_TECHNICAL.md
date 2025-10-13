# Character Allocation System - Technical Documentation

## Architecture Overview

The Character Allocation System is implemented within the `Application` domain following Domain-Driven Design principles. It consists of allocation algorithms, email notifications, and a user confirmation workflow.

## Domain Structure

```
src/Domain/Application/
├── Controller/
│   ├── Backoffice/
│   │   └── CharacterApplicationsController.php  # Allocation routes
│   └── Public/
│       └── CharacterApplicationController.php   # Confirm/decline routes
├── Entity/
│   ├── LarpApplication.php                      # Main application entity
│   ├── LarpApplicationChoice.php                # Player-character choices
│   ├── LarpApplicationVote.php                  # Organizer votes
│   └── Enum/
│       └── SubmissionStatus.php                 # Application statuses
├── Service/
│   ├── CharacterAllocationService.php           # Allocation algorithm
│   └── ApplicationMatchService.php              # Voting/matching logic
├── DTO/
│   └── AllocationSuggestionDTO.php              # Suggestion data transfer
└── Repository/
    ├── LarpApplicationRepository.php
    ├── LarpApplicationChoiceRepository.php
    └── LarpApplicationVoteRepository.php
```

## Core Components

### 1. CharacterAllocationService

**Location:** `src/Domain/Application/Service/CharacterAllocationService.php`

**Responsibility:** Calculate optimal character-player matches using weighted scoring.

**Algorithm:**

```php
class CharacterAllocationService
{
    public function suggestAllocations(Larp $larp): array
    {
        // 1. Load all applications with choices
        // 2. Build character => applicants map
        // 3. Calculate scores for each choice
        // 4. Run greedy allocation algorithm
        // 5. Return AllocationSuggestionDTO[]
    }
}
```

**Score Calculation:**

```
totalScore = (voteScore × 10) + priorityScore

where:
  voteScore = sum of all organizer votes (upvote = +1, downvote = -1)
  priorityScore = (6 - priority) × 5
    - Priority 1 (top choice) = 25 points
    - Priority 2 = 20 points
    - Priority 3 = 15 points
    - Priority 4 = 10 points
    - Priority 5 = 5 points
```

**Greedy Allocation Algorithm:**

```
1. Sort all choices by totalScore (descending)
2. Initialize: allocatedCharacters = {}, allocatedApplicants = {}
3. For each choice in sorted order:
   a. If character already allocated: skip
   b. If applicant already allocated: skip
   c. Otherwise: allocate this match
   d. Mark character and applicant as allocated
4. Return list of allocations
```

**Time Complexity:** O(n log n) where n = total number of choices
**Space Complexity:** O(n)

**Example:**

```php
$allocations = $allocationService->suggestAllocations($larp);
// Returns: [AllocationSuggestionDTO, AllocationSuggestionDTO, ...]

foreach ($allocations as $suggestion) {
    echo $suggestion->characterTitle . ' => ' . $suggestion->applicantEmail;
    echo ' (Score: ' . $suggestion->totalScore . ')';
}
```

### 2. SubmissionStatus Enum

**Location:** `src/Domain/Application/Entity/Enum/SubmissionStatus.php`

**New Cases:**

```php
enum SubmissionStatus: string
{
    case NEW = 'new';           // Initial submission
    case CONSIDER = 'consider'; // Marked for review
    case REJECTED = 'rejected'; // Rejected by organizers
    case ACCEPTED = 'accepted'; // Manually accepted (legacy)
    case OFFERED = 'offered';   // ✨ Character assigned, awaiting response
    case CONFIRMED = 'confirmed'; // ✨ Player confirmed assignment
    case DECLINED = 'declined';   // ✨ Player declined assignment
}
```

**Status Transitions:**

```
NEW → OFFERED → CONFIRMED
            ↘ DECLINED
```

**Database:** Stored as VARCHAR(50) via Doctrine enum mapping.

### 3. Controller Routes

#### Backoffice Routes

**Namespace:** `App\Domain\Application\Controller\Backoffice\CharacterApplicationsController`

**Route 1: Suggest Allocation**

```php
#[Route('/larp/{larp}/applications/suggest-allocation', name: 'backoffice_larp_applications_suggest_allocation')]
public function suggestAllocation(
    Larp $larp,
    CharacterAllocationService $allocationService
): Response
```

- **Method:** GET
- **Purpose:** Display allocation suggestions
- **Returns:** `suggest_allocation.html.twig` with suggestions array
- **Security:** Requires `ROLE_USER` (backoffice access)

**Route 2: Accept Allocation**

```php
#[Route('/larp/{larp}/applications/accept-allocation', name: 'backoffice_larp_applications_accept_allocation')]
public function acceptAllocation(
    Request $request,
    Larp $larp,
    EntityManagerInterface $em,
    LarpApplicationRepository $applicationRepository,
    MailerInterface $mailer
): Response
```

- **Method:** POST
- **Input:** `allocations[]` array with `applicationId`, `characterId`, `characterTitle`
- **Process:**
  1. Validate allocations data
  2. Update application status to `OFFERED`
  3. Send email notification via `sendCharacterAssignmentEmail()`
  4. Flash success message with count
- **Returns:** Redirect to applications list

**Email Sending:**

```php
private function sendCharacterAssignmentEmail(
    MailerInterface $mailer,
    LarpApplication $application,
    Larp $larp,
    string $characterId,
    string $characterTitle
): void
```

- Generates absolute URLs for confirm/decline actions
- Renders `emails/character_assignment.html.twig`
- Uses Symfony Mailer component
- From: `noreply@larpilot.com` (configurable)

#### Public Routes

**Namespace:** `App\Domain\Application\Controller\Public\CharacterApplicationController`

**Route 3: Confirm Character**

```php
#[Route('/larp/{larp}/application/{application}/confirm/{character}',
    name: 'public_larp_application_confirm_character')]
public function confirmCharacter(
    Request $request,
    Larp $larp,
    LarpApplication $application,
    Character $character,
    EntityManagerInterface $em
): Response
```

- **Methods:** GET, POST
- **Security Checks:**
  - Verify `$application->getUser() === $this->getUser()`
  - Verify `$application->getStatus() === SubmissionStatus::OFFERED`
- **GET:** Display confirmation page with character details
- **POST:** Update status to `CONFIRMED`, redirect with success message

**Route 4: Decline Character**

```php
#[Route('/larp/{larp}/application/{application}/decline/{character}',
    name: 'public_larp_application_decline_character')]
public function declineCharacter(
    Request $request,
    Larp $larp,
    LarpApplication $application,
    Character $character,
    EntityManagerInterface $em
): Response
```

- **Methods:** GET, POST
- **Security Checks:** Same as confirm route
- **GET:** Display decline page with warning
- **POST:** Update status to `DECLINED`, redirect with info message

**Security Features:**

1. **Ownership Validation:** Only the applicant can confirm/decline
2. **Status Validation:** Only `OFFERED` applications can be confirmed/declined
3. **CSRF Protection:** Built-in via Symfony forms
4. **Two-Step Confirmation:** Page view + modal confirmation
5. **Route Parameter Validation:** Doctrine ParamConverter validates entities

### 4. Data Transfer Objects

**AllocationSuggestionDTO**

```php
readonly class AllocationSuggestionDTO
{
    public function __construct(
        public string $applicationId,
        public string $applicantEmail,
        public string $applicantUserId,
        public string $characterId,
        public string $characterTitle,
        public string $choiceId,
        public int $priority,
        public int $voteScore,
        public float $totalScore,
        public ?string $justification = null,
    ) {}
}
```

**Why DTO?**
- Decouples data structure from entities
- Reduces memory usage (no Doctrine proxies)
- Optimized for read-only operations
- Easy serialization for JSON APIs (future)

## Database Schema

**Relevant Tables:**

```sql
-- Application statuses stored as enum strings
CREATE TABLE larp_application (
    id UUID PRIMARY KEY,
    larp_id UUID NOT NULL,
    user_id UUID NOT NULL,
    status VARCHAR(50) DEFAULT 'new', -- NEW, OFFERED, CONFIRMED, DECLINED, etc.
    notes TEXT,
    contact_email VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (larp_id) REFERENCES larp(id),
    FOREIGN KEY (user_id) REFERENCES user(id)
);

-- Character choices with priorities
CREATE TABLE larp_application_choice (
    id UUID PRIMARY KEY,
    application_id UUID NOT NULL,
    character_id UUID NOT NULL,
    priority INT NOT NULL, -- 1-5
    justification TEXT,
    votes INT DEFAULT 0,   -- Cached vote total
    FOREIGN KEY (application_id) REFERENCES larp_application(id),
    FOREIGN KEY (character_id) REFERENCES character(id)
);

-- Organizer votes
CREATE TABLE larp_application_vote (
    id UUID PRIMARY KEY,
    choice_id UUID NOT NULL,
    user_id UUID NOT NULL,
    vote INT NOT NULL,     -- +1 or -1
    justification TEXT,
    created_at TIMESTAMP,
    FOREIGN KEY (choice_id) REFERENCES larp_application_choice(id),
    FOREIGN KEY (user_id) REFERENCES user(id),
    UNIQUE(choice_id, user_id) -- One vote per user per choice
);
```

**Indexes:**

```sql
CREATE INDEX idx_application_larp ON larp_application(larp_id);
CREATE INDEX idx_application_user ON larp_application(user_id);
CREATE INDEX idx_choice_application ON larp_application_choice(application_id);
CREATE INDEX idx_choice_character ON larp_application_choice(character_id);
CREATE INDEX idx_vote_choice ON larp_application_vote(choice_id);
CREATE INDEX idx_vote_user ON larp_application_vote(user_id);
```

## Email Templates

**Template:** `templates/emails/character_assignment.html.twig`

**Structure:**

```html
<!DOCTYPE html>
<html>
  <head>
    <style>/* Inline CSS */</style>
  </head>
  <body>
    <div class="header">Character Assignment</div>
    <div class="content">
      <div class="character-box">{{ characterTitle }}</div>
      <div class="button-container">
        <a href="{{ confirmUrl }}">Confirm</a>
        <a href="{{ declineUrl }}">Decline</a>
      </div>
    </div>
    <div class="footer">Powered by LARPilot</div>
  </body>
</html>
```

**Variables:**

- `larp`: Larp entity
- `application`: LarpApplication entity
- `characterTitle`: String
- `confirmUrl`: Absolute URL to confirm route
- `declineUrl`: Absolute URL to decline route

**Styling:**
- Inline CSS for email client compatibility
- Responsive design for mobile
- High-contrast buttons for accessibility
- Max-width: 600px for readability

## Frontend Components

**Suggest Allocation Page:**

```twig
{# templates/backoffice/larp/application/suggest_allocation.html.twig #}

{% for suggestion in suggestions %}
  <tr>
    <td>
      <input type="checkbox" name="allocations[{{ loop.index0 }}][selected]" checked>
      <input type="hidden" name="allocations[{{ loop.index0 }}][applicationId]"
             value="{{ suggestion.applicationId }}">
      <input type="hidden" name="allocations[{{ loop.index0 }}][characterId]"
             value="{{ suggestion.characterId }}">
      <input type="hidden" name="allocations[{{ loop.index0 }}][characterTitle]"
             value="{{ suggestion.characterTitle }}">
    </td>
    <td>{{ suggestion.characterTitle }}</td>
    <td>{{ suggestion.applicantEmail }}</td>
    <td><span class="badge">Priority #{{ suggestion.priority }}</span></td>
    <td><span class="badge">{{ suggestion.voteScore }}</span></td>
    <td><strong>{{ suggestion.totalScore|number_format(1) }}</strong></td>
  </tr>
{% endfor %}
```

**JavaScript:**

```javascript
// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.allocation-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
});

// Update selected count
function updateSelectedCount() {
    const count = document.querySelectorAll('.allocation-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = `${count} allocations selected`;
}
```

## Configuration

**Mailer Configuration:** `config/packages/mailer.yaml`

```yaml
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'
        envelope:
            sender: 'noreply@larpilot.com'
```

**Environment Variables:**

```env
MAILER_DSN=smtp://localhost:1025  # Development (Mailhog)
# MAILER_DSN=smtp://user:pass@smtp.example.com:587  # Production
```

## Testing

**Unit Tests:**

```php
// tests/Service/CharacterAllocationServiceTest.php

public function testSuggestAllocations_SingleMatch()
{
    $larp = $this->createLarp();
    $character = $this->createCharacter($larp);
    $application = $this->createApplication($larp);
    $choice = $this->createChoice($application, $character, priority: 1);

    $suggestions = $this->service->suggestAllocations($larp);

    $this->assertCount(1, $suggestions);
    $this->assertEquals($character->getId(), $suggestions[0]->characterId);
    $this->assertEquals($application->getId(), $suggestions[0]->applicationId);
}

public function testSuggestAllocations_PriorityScoring()
{
    // Test that priority 1 gets higher score than priority 5
    $this->assertEquals(25, $this->calculatePriorityScore(1));
    $this->assertEquals(5, $this->calculatePriorityScore(5));
}

public function testSuggestAllocations_VoteInfluence()
{
    // Test that organizer votes influence allocation
    $choice1 = $this->createChoice($app1, $char, priority: 2); // 20 points
    $choice2 = $this->createChoice($app2, $char, priority: 1); // 25 points

    $this->voteForChoice($choice1, vote: +3); // +30 points
    // choice1 total: 50, choice2 total: 25

    $suggestions = $this->service->suggestAllocations($larp);
    $this->assertEquals($app1->getId(), $suggestions[0]->applicationId);
}
```

**Integration Tests:**

```php
// tests/Controller/CharacterApplicationsControllerTest.php

public function testSuggestAllocationRoute()
{
    $this->loginAsOrganizer();
    $larp = $this->createLarpWithApplications();

    $this->client->request('GET', "/backoffice/larp/{$larp->getId()}/applications/suggest-allocation");

    $this->assertResponseIsSuccessful();
    $this->assertSelectorTextContains('h5', 'Suggested Character Allocations');
}

public function testAcceptAllocation_SendsEmail()
{
    $this->loginAsOrganizer();

    $this->client->request('POST', '/backoffice/larp/' . $larp->getId() . '/applications/accept-allocation', [
        'allocations' => [
            ['applicationId' => $app->getId(), 'characterId' => $char->getId(), 'characterTitle' => 'Hero']
        ]
    ]);

    $this->assertEmailCount(1);
    $email = $this->getMailerMessage();
    $this->assertEmailHtmlBodyContains($email, 'You Have Been Assigned a Character');
}
```

## Performance Considerations

**Query Optimization:**

```php
// Load all data in one query with eager loading
$qb = $repository->createQueryBuilder('a')
    ->leftJoin('a.choices', 'c')->addSelect('c')
    ->leftJoin('c.character', 'ch')->addSelect('ch')
    ->leftJoin('a.user', 'u')->addSelect('u')
    ->where('a.larp = :larp')
    ->setParameter('larp', $larp);
```

**Caching:**

- Vote totals are cached in `LarpApplicationChoice.votes` column
- Updated on each vote cast via event listener (future enhancement)
- Reduces N+1 queries when displaying matches

**Scalability:**

- Algorithm complexity: O(n log n)
- For 100 characters × 50 applicants × 5 choices = 25,000 choices
- Sort time: ~0.1 seconds
- Database queries: 2-3 total (with eager loading)
- **Recommendation:** Works efficiently for LARPs up to 500 participants

## Error Handling

**Validation:**

```php
// Controller validation
if (empty($allocations)) {
    $this->addFlash('error', 'no_allocations_selected');
    return $this->redirectToRoute('suggest_allocation');
}

// Entity validation
if ($application->getStatus() !== SubmissionStatus::OFFERED) {
    throw $this->createAccessDeniedException('not_offered');
}
```

**Exception Handling:**

```php
try {
    $application->setStatus(SubmissionStatus::OFFERED);
    $this->sendEmail($application, $character);
    $successCount++;
} catch (\Exception $e) {
    $this->addFlash('warning', 'allocation_failed: ' . $e->getMessage());
    continue; // Skip to next allocation
}
```

## Security Considerations

1. **Route Protection:** All backoffice routes require authentication
2. **Ownership Verification:** Players can only confirm their own applications
3. **Status Guards:** Prevent replaying confirmations on already-processed applications
4. **CSRF Tokens:** All POST forms include CSRF protection
5. **SQL Injection:** Doctrine ORM prevents SQL injection via parameterized queries
6. **XSS Protection:** Twig auto-escapes all output
7. **Email Rate Limiting:** Consider implementing rate limiting for bulk emails (future)

## Migration Guide

**Creating Migration:**

```bash
php bin/console doctrine:migrations:diff
```

**Generated Migration:**

```php
// migrations/VersionXXX.php

public function up(Schema $schema): void
{
    // Add new enum values to check constraint
    $this->addSql("ALTER TABLE larp_application DROP CONSTRAINT IF EXISTS submission_status_check");
    $this->addSql("ALTER TABLE larp_application ADD CONSTRAINT submission_status_check
        CHECK (status IN ('new', 'consider', 'rejected', 'accepted', 'offered', 'confirmed', 'declined'))");
}
```

**Running Migration:**

```bash
php bin/console doctrine:migrations:migrate
```

## Future Enhancements

**Potential Improvements:**

1. **Conflict Detection:** Warn if character has conflicting assignments
2. **Batch Email Queue:** Use message queue for large-scale emails
3. **Deadline Enforcement:** Auto-decline after X days without response
4. **Re-allocation:** Algorithm for declined characters
5. **Multiple Rounds:** Support iterative allocation rounds
6. **Character Preferences:** Consider character-side preferences (gender, experience level)
7. **Tag Matching:** Factor in preferred/unwanted tags
8. **API Endpoints:** REST API for mobile apps
9. **Webhooks:** Notify external systems of confirmations
10. **Analytics Dashboard:** Allocation success rate metrics

## Monitoring

**Key Metrics:**

- Allocation suggestion success rate (confirmations / offers sent)
- Average response time (offer sent → player response)
- Decline rate by character/larp
- Email delivery success rate

**Logging:**

```php
$logger->info('Character allocation sent', [
    'larp_id' => $larp->getId(),
    'application_id' => $application->getId(),
    'character_id' => $characterId,
    'applicant_email' => $application->getContactEmail(),
]);
```

## Related Documentation

- [User Guide](../CHARACTER_ALLOCATION_SYSTEM.md)
- [Domain Architecture](../DOMAIN_ARCHITECTURE.md)
- [Application Domain](../../src/Domain/Application/README.md) (if exists)
- [Email System](./EMAIL_SYSTEM.md) (if exists)
- [Security Guidelines](./SECURITY.md) (if exists)
