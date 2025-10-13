# Claude Code Optimization Guide

This guide provides step-by-step instructions for optimizing Claude Code's effectiveness when working on the LARPilot project, specifically tailored for Ubuntu on WSL2 (Windows 10).

## Table of Contents

1. [MCP Servers for Enhanced Capabilities](#mcp-servers-for-enhanced-capabilities)
2. [Custom Slash Commands](#custom-slash-commands)
3. [Claude Code Hooks](#claude-code-hooks)
4. [Environment Setup](#environment-setup)
5. [Project-Specific Optimizations](#project-specific-optimizations)

---

## MCP Servers for Enhanced Capabilities

MCP (Model Context Protocol) servers extend Claude Code's capabilities by providing specialized tools. Here are recommended MCPs for this Symfony/PostgreSQL project:

### 1. Database MCP (PostgreSQL)

**Purpose**: Direct database queries, schema inspection, and data analysis without leaving Claude Code.

**Installation**:

```bash
# Install Node.js if not already installed (required for most MCPs)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install the PostgreSQL MCP
cd ~/
mkdir -p mcp-servers
cd mcp-servers
npx -y @modelcontextprotocol/create-server postgresql-mcp
cd postgresql-mcp
npm install
```

**Configuration** (`~/.config/claude-code/mcp_config.json`):

```json
{
  "mcpServers": {
    "postgresql": {
      "command": "node",
      "args": [
        "/home/urias/mcp-servers/postgresql-mcp/build/index.js"
      ],
      "env": {
        "POSTGRES_HOST": "localhost",
        "POSTGRES_PORT": "5432",
        "POSTGRES_DB": "larpilot",
        "POSTGRES_USER": "your_db_user",
        "POSTGRES_PASSWORD": "your_db_password"
      }
    }
  }
}
```

**Security Note**: For production, use connection strings or env file references instead of hardcoded credentials.

**Benefits for LARPilot**:
- Query application/character data directly
- Inspect complex relationships between StoryObjects
- Analyze performance of queries
- Debug Doctrine mapping issues

### 2. Git MCP (Enhanced Git Operations)

**Purpose**: Advanced git operations, commit history analysis, and PR management.

**Installation**:

```bash
cd ~/mcp-servers
git clone https://github.com/modelcontextprotocol/servers.git mcp-git-server
cd mcp-git-server/src/git
npm install
npm run build
```

**Configuration** (`~/.config/claude-code/mcp_config.json`):

```json
{
  "mcpServers": {
    "git": {
      "command": "node",
      "args": [
        "/home/urias/mcp-servers/mcp-git-server/src/git/dist/index.js"
      ],
      "env": {
        "GIT_REPO_PATH": "/home/urias/Projects/LARPilot"
      }
    }
  }
}
```

**Benefits for LARPilot**:
- Better understanding of domain refactoring history
- Analyze changes across DDD domain boundaries
- Track entity migration patterns

### 3. Filesystem MCP (Enhanced File Operations)

**Purpose**: Advanced file search, batch operations, and directory watching.

**Installation**:

```bash
cd ~/mcp-servers/mcp-git-server/src/filesystem
npm install
npm run build
```

**Configuration** (`~/.config/claude-code/mcp_config.json`):

```json
{
  "mcpServers": {
    "filesystem": {
      "command": "node",
      "args": [
        "/home/urias/mcp-servers/mcp-git-server/src/filesystem/dist/index.js"
      ],
      "env": {
        "ALLOWED_DIRECTORIES": "/home/urias/Projects/LARPilot"
      }
    }
  }
}
```

### 4. Web Search MCP (Documentation Lookup)

**Purpose**: Search Symfony, Doctrine, and Twig documentation without context switching.

**Installation**:

```bash
cd ~/mcp-servers
npx -y @modelcontextprotocol/create-server web-search-mcp
cd web-search-mcp
npm install
```

**Configuration** (`~/.config/claude-code/mcp_config.json`):

```json
{
  "mcpServers": {
    "web-search": {
      "command": "node",
      "args": [
        "/home/urias/mcp-servers/web-search-mcp/build/index.js"
      ],
      "env": {
        "BRAVE_API_KEY": "your_brave_api_key_here"
      }
    }
  }
}
```

**Get Brave Search API Key**: https://brave.com/search/api/ (free tier available)

**Benefits for LARPilot**:
- Quick Symfony 7.2 documentation lookups
- Doctrine ORM best practices
- PostgreSQL JSON/JSONB function references
- TomSelect and Stimulus documentation

### 5. Custom Symfony MCP (Optional Advanced)

**Purpose**: Project-specific tools for Symfony console commands, cache operations, and fixture loading.

**Create Custom MCP** (`~/mcp-servers/symfony-larpilot/src/index.ts`):

```typescript
#!/usr/bin/env node
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify(exec);
const PROJECT_PATH = process.env.SYMFONY_PROJECT_PATH || '/home/urias/Projects/LARPilot';

const server = new Server(
  {
    name: 'symfony-larpilot',
    version: '1.0.0',
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

server.setRequestHandler('tools/list', async () => {
  return {
    tools: [
      {
        name: 'symfony_console',
        description: 'Run Symfony console commands',
        inputSchema: {
          type: 'object',
          properties: {
            command: {
              type: 'string',
              description: 'The console command to run (e.g., "debug:router")',
            },
          },
          required: ['command'],
        },
      },
      {
        name: 'load_fixtures',
        description: 'Load development fixtures',
        inputSchema: {
          type: 'object',
          properties: {
            append: {
              type: 'boolean',
              description: 'Append fixtures instead of purging',
              default: false,
            },
          },
        },
      },
      {
        name: 'clear_cache',
        description: 'Clear Symfony cache',
        inputSchema: {
          type: 'object',
          properties: {
            env: {
              type: 'string',
              description: 'Environment (dev, test, prod)',
              default: 'dev',
            },
          },
        },
      },
    ],
  };
});

server.setRequestHandler('tools/call', async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case 'symfony_console': {
        const { command } = args as { command: string };
        const { stdout, stderr } = await execAsync(
          `cd ${PROJECT_PATH} && php bin/console ${command}`,
          { maxBuffer: 10 * 1024 * 1024 }
        );
        return {
          content: [{ type: 'text', text: stdout || stderr }],
        };
      }

      case 'load_fixtures': {
        const { append } = args as { append?: boolean };
        const appendFlag = append ? '--append' : '';
        const { stdout, stderr } = await execAsync(
          `cd ${PROJECT_PATH} && php bin/console doctrine:fixtures:load ${appendFlag} --no-interaction`
        );
        return {
          content: [{ type: 'text', text: stdout || stderr }],
        };
      }

      case 'clear_cache': {
        const { env } = args as { env?: string };
        const { stdout, stderr } = await execAsync(
          `cd ${PROJECT_PATH} && php bin/console cache:clear --env=${env || 'dev'}`
        );
        return {
          content: [{ type: 'text', text: stdout || stderr }],
        };
      }

      default:
        throw new Error(`Unknown tool: ${name}`);
    }
  } catch (error) {
    return {
      content: [
        {
          type: 'text',
          text: `Error: ${error instanceof Error ? error.message : String(error)}`,
        },
      ],
      isError: true,
    };
  }
});

const transport = new StdioServerTransport();
server.connect(transport);
```

**Build and Configure**:

```bash
cd ~/mcp-servers
mkdir -p symfony-larpilot/src
# Copy the TypeScript file above to ~/mcp-servers/symfony-larpilot/src/index.ts

cd symfony-larpilot
npm init -y
npm install @modelcontextprotocol/sdk
npm install -D @types/node typescript tsx

# Add to package.json:
# "scripts": {
#   "build": "tsc",
#   "start": "node dist/index.js"
# }

# Create tsconfig.json
cat > tsconfig.json << 'EOF'
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "Node16",
    "moduleResolution": "Node16",
    "outDir": "./dist",
    "rootDir": "./src",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true
  },
  "include": ["src/**/*"]
}
EOF

npm run build
```

**Add to MCP Config**:

```json
{
  "mcpServers": {
    "symfony-larpilot": {
      "command": "node",
      "args": [
        "/home/urias/mcp-servers/symfony-larpilot/dist/index.js"
      ],
      "env": {
        "SYMFONY_PROJECT_PATH": "/home/urias/Projects/LARPilot"
      }
    }
  }
}
```

---

## Custom Slash Commands

Slash commands provide quick access to common workflows. Create them in `.claude/commands/` directory.

### Setup Commands Directory

```bash
cd /home/urias/Projects/LARPilot
mkdir -p .claude/commands
```

### 1. Code Quality Check (`/quality`)

```bash
cat > .claude/commands/quality.md << 'EOF'
---
description: Run all code quality checks (ECS, PHPStan, Tests)
---

Run the following code quality checks in sequence:

1. **ECS (Code Style)**: Run `make ecs` to check for PSR-12 violations
2. **PHPStan (Static Analysis)**: Run `make stan` to check for type errors
3. **PHPUnit (Tests)**: Run `make test` to run the test suite

Report any failures found and suggest fixes. If all checks pass, confirm with a summary.
EOF
```

### 2. Database Migration (`/migrate`)

```bash
cat > .claude/commands/migrate.md << 'EOF'
---
description: Create and run a database migration
---

Help me create and apply a database migration:

1. Ask what entity changes were made
2. Run `php bin/console make:migration` to generate migration
3. Show the generated migration file for review
4. If approved, run `php bin/console doctrine:migrations:migrate` to apply it
5. Verify the migration was successful by checking `doctrine:migrations:status`

Always show the migration SQL before applying.
EOF
```

### 3. Domain Search (`/domain-search`)

```bash
cat > .claude/commands/domain-search.md << 'EOF'
---
description: Search for entities, services, or forms across domains
---

Search for files across the domain-driven architecture:

Ask me what I'm looking for (entity, service, repository, controller, form, etc.) and the domain context.

Then search using the Glob tool in these patterns:
- Entities: `src/Domain/*/Entity/**/*.php`
- Services: `src/Domain/*/Service/**/*.php`
- Repositories: `src/Domain/*/Repository/**/*.php`
- Controllers: `src/Domain/*/Controller/**/*.php`
- Forms: `src/Domain/*/Form/**/*.php`

Show results grouped by domain with file paths.
EOF
```

### 4. Story Graph Analysis (`/story-graph`)

```bash
cat > .claude/commands/story-graph.md << 'EOF'
---
description: Analyze StoryObject relationships and dependencies
---

Analyze the story graph structure for a LARP:

1. Ask which LARP ID to analyze
2. Query the database for StoryObjects (Character, Thread, Quest, Event, Faction, Place, Item)
3. Analyze Relation entities to show connections
4. Generate a summary of:
   - Total objects by type
   - Most connected characters/threads
   - Orphaned objects (no relations)
   - Circular dependencies

Present findings in a structured report.
EOF
```

### 5. Fixture Generator (`/fixtures`)

```bash
cat > .claude/commands/fixtures.md << 'EOF'
---
description: Generate development fixtures for testing
---

Help create development fixtures:

1. Ask what entities need fixtures (Larp, Character, Thread, Quest, etc.)
2. Ask for quantity and relationship requirements
3. Generate a fixture class in `src/DataFixtures/Dev/` following the existing `DevSampleFixtures.php` pattern
4. Use Faker for realistic data
5. Ensure proper relationships and dependencies are set up
6. Run `php bin/console doctrine:fixtures:load --append` to load fixtures

Show the generated fixture code for review before loading.
EOF
```

### 6. Security Voter Generator (`/voter`)

```bash
cat > .claude/commands/voter.md << 'EOF'
---
description: Generate a Symfony security voter
---

Create a security voter for authorization:

1. Ask what entity/action needs authorization (e.g., "Edit LarpIncident")
2. Ask what roles/conditions should grant access
3. Generate a voter in appropriate `src/Domain/*/Security/Voter/` directory
4. Follow the pattern from existing voters (e.g., `LarpDetailsVoter`)
5. Include proper attribute constants and security checks
6. Add PHPDoc with examples
7. Explain how to use the voter in controllers with `$this->denyAccessUnlessGranted()`

Show the generated voter code for review.
EOF
```

### 7. Form Type Generator (`/form`)

```bash
cat > .claude/commands/form.md << 'EOF'
---
description: Generate a Symfony form type with proper configuration
---

Create a Symfony form type:

1. Ask what entity the form is for
2. Ask which fields should be included
3. Determine if it needs:
   - AJAX autocomplete fields (TomSelect)
   - WYSIWYG editor (Quill)
   - Money/JSON transformers
   - Custom validation
4. Generate form in `src/Domain/*/Form/` directory
5. Follow existing patterns (translations, larp context, autocomplete)
6. Include proper `configureOptions()` with translation_domain
7. Add form theme overrides if needed

Show the generated form code and explain usage.
EOF
```

### 8. API Endpoint Generator (`/api`)

```bash
cat > .claude/commands/api.md << 'EOF'
---
description: Generate a JSON API endpoint
---

Create a JSON API endpoint:

1. Ask what data should be exposed
2. Ask about authentication requirements (ROLE_API_USER, ROLE_USER, public)
3. Generate controller in `src/Domain/*/Controller/API/`
4. Use proper JSON responses with status codes
5. Add parameter validation
6. Include rate limiting considerations
7. Add OpenAPI/Swagger documentation comments
8. Consider pagination for list endpoints

Show the generated controller and example curl commands.
EOF
```

### 9. Test Generator (`/test`)

```bash
cat > .claude/commands/test.md << 'EOF'
---
description: Generate PHPUnit tests for a class
---

Create PHPUnit tests:

1. Ask what class needs testing (Entity, Service, Repository, Controller, Form)
2. Analyze the class to determine test cases
3. Generate test in `tests/` mirroring the source structure
4. Follow existing patterns:
   - `WebTestCase` for controllers
   - `KernelTestCase` for services/repositories
   - Unit tests for simple classes
5. Include:
   - Setup/teardown methods
   - Data providers for multiple scenarios
   - Assertions for happy path and edge cases
   - Mock dependencies where appropriate
6. Run the generated test with `vendor/bin/phpunit tests/path/to/TestFile.php`

Show test code and execution results.
EOF
```

---

## Claude Code Hooks

Hooks allow you to run custom scripts before/after Claude Code performs actions. Configure in `~/.config/claude-code/settings.json`.

### Pre-Tool Hooks (Validation)

**Example: Prevent Committing Secrets**

```json
{
  "hooks": {
    "preToolCall": {
      "Bash": {
        "command": "bash",
        "args": ["-c", "if echo \"$TOOL_INPUT\" | grep -qE '(git.*commit|git.*add.*\\.env)'; then echo 'ERROR: Attempting to commit sensitive files'; exit 1; fi"],
        "blockOnFailure": true
      }
    }
  }
}
```

### Post-Tool Hooks (Automation)

**Example: Auto-run ECS After File Edits**

```json
{
  "hooks": {
    "postToolCall": {
      "Edit": {
        "command": "bash",
        "args": ["-c", "cd /home/urias/Projects/LARPilot && docker compose exec -T php vendor/bin/ecs check --fix \"$TOOL_OUTPUT_FILE\" || true"],
        "blockOnFailure": false
      },
      "Write": {
        "command": "bash",
        "args": ["-c", "cd /home/urias/Projects/LARPilot && docker compose exec -T php vendor/bin/ecs check --fix \"$TOOL_OUTPUT_FILE\" || true"],
        "blockOnFailure": false
      }
    }
  }
}
```

**Example: Auto-clear Cache After Config Changes**

```json
{
  "hooks": {
    "postToolCall": {
      "Edit": {
        "command": "bash",
        "args": [
          "-c",
          "if echo \"$TOOL_OUTPUT_FILE\" | grep -q 'config/'; then cd /home/urias/Projects/LARPilot && docker compose exec -T php php bin/console cache:clear; fi"
        ],
        "blockOnFailure": false
      }
    }
  }
}
```

### User Prompt Submit Hook (Pre-flight Checks)

**Example: Check Git Status Before Large Requests**

```json
{
  "hooks": {
    "userPromptSubmit": {
      "command": "bash",
      "args": [
        "-c",
        "cd /home/urias/Projects/LARPilot && git diff --stat | head -20"
      ],
      "blockOnFailure": false,
      "showOutputToAssistant": true
    }
  }
}
```

---

## Environment Setup

### Global vs Project-Specific Configuration

Claude Code supports two levels of configuration:

1. **Global Settings** (`~/.config/claude-code/`): Shared across all projects
2. **Project Settings** (`.claude/` in repo): Project-specific, can be committed to Git

**Recommended Approach for Multi-Project Setup**:
- Put **generic MCPs** in global config (Git, Filesystem, Web Search)
- Put **project-specific MCPs** in `.claude/mcp_config.json` (PostgreSQL with LARPilot credentials, Symfony MCP)
- Put **slash commands** in `.claude/commands/` (always project-specific)
- Put **hooks** in `.claude/settings.json` (project-specific code quality rules)

### Project-Specific MCP Configuration

**Create** `.claude/mcp_config.json` in your LARPilot repository:

```json
{
  "mcpServers": {
    "postgresql-larpilot": {
      "command": "node",
      "args": ["/home/urias/mcp-servers/postgresql-mcp/build/index.js"],
      "env": {
        "POSTGRES_HOST": "localhost",
        "POSTGRES_PORT": "5432",
        "POSTGRES_DB": "larpilot",
        "POSTGRES_USER": "larpilot_user",
        "POSTGRES_PASSWORD": "${LARPILOT_DB_PASSWORD}"
      }
    },
    "symfony-larpilot": {
      "command": "node",
      "args": ["/home/urias/mcp-servers/symfony-larpilot/dist/index.js"],
      "env": {
        "SYMFONY_PROJECT_PATH": "/home/urias/Projects/LARPilot"
      }
    }
  }
}
```

**Note**: Use `${ENV_VAR}` syntax to reference environment variables for sensitive data. Set them in your shell:

```bash
# Add to ~/.bashrc or ~/.zshrc
export LARPILOT_DB_PASSWORD="your_secure_password"
```

### Global MCP Configuration

**Create/Edit** `~/.config/claude-code/mcp_config.json` for shared MCPs:

```json
{
  "mcpServers": {
    "git": {
      "command": "node",
      "args": ["/home/urias/mcp-servers/mcp-git-server/src/git/dist/index.js"]
    },
    "filesystem": {
      "command": "node",
      "args": ["/home/urias/mcp-servers/mcp-git-server/src/filesystem/dist/index.js"]
    },
    "web-search": {
      "command": "node",
      "args": ["/home/urias/mcp-servers/web-search-mcp/build/index.js"],
      "env": {
        "BRAVE_API_KEY": "${BRAVE_API_KEY}"
      }
    }
  }
}
```

**Note**: These MCPs work across all projects. Git/Filesystem MCPs detect the current project automatically.

### Project-Specific Settings (Hooks)

**Create** `.claude/settings.json` in your LARPilot repository:

```json
{
  "hooks": {
    "preToolCall": {
      "Bash": {
        "command": "bash",
        "args": [
          "-c",
          "if echo \"$TOOL_INPUT\" | grep -qE '(git.*commit|git.*add.*\\.env)'; then echo 'ERROR: Attempting to commit sensitive files'; exit 1; fi"
        ],
        "blockOnFailure": true
      }
    },
    "postToolCall": {
      "Edit": {
        "command": "bash",
        "args": [
          "-c",
          "if echo \"$TOOL_OUTPUT_FILE\" | grep -q '\\.php$'; then docker compose exec -T php vendor/bin/ecs check --fix \"$TOOL_OUTPUT_FILE\" 2>/dev/null || true; fi"
        ],
        "blockOnFailure": false
      },
      "Write": {
        "command": "bash",
        "args": [
          "-c",
          "if echo \"$TOOL_OUTPUT_FILE\" | grep -q '\\.php$'; then docker compose exec -T php vendor/bin/ecs check --fix \"$TOOL_OUTPUT_FILE\" 2>/dev/null || true; fi"
        ],
        "blockOnFailure": false
      }
    },
    "userPromptSubmit": {
      "command": "bash",
      "args": [
        "-c",
        "echo '=== Git Status ===' && git status -s | head -15"
      ],
      "blockOnFailure": false,
      "showOutputToAssistant": true
    }
  }
}
```

**Note**: Hooks run from the project directory, so you don't need `cd /home/urias/Projects/LARPilot` in commands.

### WSL2-Specific Optimizations

**1. Performance: Use WSL2 filesystem, not Windows mount**

✅ **Good**: `/home/urias/Projects/LARPilot` (WSL2 native)
❌ **Bad**: `/mnt/c/Users/urias/Projects/LARPilot` (Windows mount, slower)

**2. Docker Integration**

Ensure Docker Desktop is set to use WSL2 backend:
- Docker Desktop Settings → General → "Use the WSL 2 based engine"
- Resources → WSL Integration → Enable for your distro (Ubuntu)

**3. Git Configuration for Line Endings**

```bash
cd /home/urias/Projects/LARPilot
git config core.autocrlf input
git config core.eol lf
```

**4. Node.js Memory Limits for MCPs**

```bash
# Add to ~/.bashrc
export NODE_OPTIONS="--max-old-space-size=4096"
```

**5. Fast File Watching**

```bash
# Increase inotify watchers for Symfony dev server
echo "fs.inotify.max_user_watches=524288" | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

---

## Project-Specific Optimizations

### 1. Custom Context Files

**Create** `.claude/context/symfony_patterns.md`:

```markdown
# Symfony Patterns for LARPilot

## Controller Pattern
- Extend `BaseController` which provides `filterBuilderUpdater`, `getPagination()`
- Use `#[Route]` attributes with names like `backoffice_larp_story_character_list`
- Check access with `$this->denyAccessUnlessGranted()` and custom voters

## Service Pattern
- Services are autowired by default
- Place in `src/Domain/{Domain}/Service/`
- Use constructor injection
- Tag services manually in `services.yaml` only if needed

## Repository Pattern
- Extend `BaseRepository`
- Implement `ListableRepositoryInterface` for filterable lists
- Use QueryBuilder for complex queries
- Scope queries by `Larp` context

## Form Pattern
- Set `translation_domain` to 'forms'
- Pass `larp` option for LARP-scoped entities
- Use `autocomplete => true` for TomSelect fields
- Add `apply_filter` callbacks for FilterBundle forms
```

**Create** `.claude/context/ddd_domains.md`:

```markdown
# DDD Domain Boundaries

Never mix these domains:
- **Account**: User authentication only, no LARP business logic
- **Application**: Character application workflow only
- **StoryObject**: Story elements (Character, Quest, Thread, etc.)
- **Larp**: Core LARP entity and lifecycle
- **Integration**: External services (Google, Discord)

When creating new features:
1. Identify which domain it belongs to
2. Check if cross-domain communication is needed (use services/events)
3. Never create direct entity associations across domain boundaries
4. Use DTOs for cross-domain data transfer
```

### 2. Quick Reference Files

**Create** `.claude/snippets/voter.php`:

```php
<?php

namespace App\Security\Voter;

use App\Entity\SomeEntity;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SomeEntityVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof SomeEntity;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var SomeEntity $entity */
        $entity = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($entity, $user),
            self::EDIT => $this->canEdit($entity, $user),
            default => false,
        };
    }

    private function canView(SomeEntity $entity, User $user): bool
    {
        // Your logic here
        return true;
    }

    private function canEdit(SomeEntity $entity, User $user): bool
    {
        // Your logic here
        return true;
    }
}
```

### 3. Makefile Integration

Claude Code can use your Makefile commands directly. Ensure these are documented:

```makefile
# Add to Makefile if not present
.PHONY: help
help: ## Show this help message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: quality
quality: ## Run all quality checks
	$(MAKE) ecs
	$(MAKE) stan
	$(MAKE) test
```

---

## Testing Your Setup

### 1. Verify MCP Servers

```bash
# Test PostgreSQL MCP
cd ~/mcp-servers/postgresql-mcp
node build/index.js
# Should start without errors, Ctrl+C to exit

# Test Symfony MCP
cd ~/mcp-servers/symfony-larpilot
node dist/index.js
# Should start without errors, Ctrl+C to exit
```

### 2. Test Slash Commands

In Claude Code, try:
```
/quality
```

Should execute ECS, PHPStan, and tests.

### 3. Test Hooks

Edit any PHP file and save. Check if ECS auto-fixes are applied.

---

## Troubleshooting

### MCP Server Not Found

**Error**: `MCP server "postgresql" failed to start`

**Fix**:
```bash
# Check the path exists
ls -la ~/mcp-servers/postgresql-mcp/build/index.js

# Check Node.js is installed
node --version

# Test the server manually
cd ~/mcp-servers/postgresql-mcp
node build/index.js
```

### Hooks Not Running

**Error**: Hooks silently fail

**Fix**:
```bash
# Check hook script permissions
chmod +x ~/.config/claude-code/hooks/*

# Test hook command manually
bash -c "cd /home/urias/Projects/LARPilot && git status"
```

### Docker Commands Failing in Hooks

**Error**: `docker compose: command not found`

**Fix**:
```bash
# Add Docker to PATH in hook scripts
export PATH="/usr/bin:$PATH"

# Or use full path
/usr/bin/docker compose exec -T php vendor/bin/ecs check
```

### WSL2 Performance Issues

**Symptoms**: Slow file operations, Claude Code timeouts

**Fix**:
```bash
# Move project to WSL2 filesystem if on Windows mount
cd ~
mkdir -p Projects
cp -r /mnt/c/Users/urias/Projects/LARPilot ~/Projects/

# Update all configs to use ~/Projects/LARPilot

# Increase WSL2 memory in Windows (C:\Users\urias\.wslconfig):
[wsl2]
memory=8GB
processors=4
```

---

## Advanced: CI/CD Integration

**GitHub Actions Workflow** (`.github/workflows/claude-code-quality.yml`):

```yaml
name: Claude Code Quality

on:
  pull_request:
    branches: [ main ]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run ECS
        run: vendor/bin/ecs check

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse -c phpstan.neon

      - name: Run Tests
        run: vendor/bin/phpunit
```

This ensures the same quality checks Claude Code runs locally are enforced in CI.

---

## Configuration File Location Summary

### For Multi-Project Setup (Recommended)

**Global Config** (`~/.config/claude-code/`):
- `mcp_config.json` - Generic MCPs (Git, Filesystem, Web Search)
- Keep minimal, shared across all projects

**Project Config** (`.claude/` in LARPilot repo):
- `mcp_config.json` - LARPilot-specific MCPs (PostgreSQL, Symfony)
- `settings.json` - Project-specific hooks (ECS auto-fix, etc.)
- `commands/*.md` - Slash commands (always project-specific)
- `context/*.md` - Project context files (Symfony patterns, DDD domains)
- `snippets/*.php` - Code templates

**Environment Variables** (`~/.bashrc` or `~/.zshrc`):
```bash
export LARPILOT_DB_PASSWORD="your_secure_password"
export BRAVE_API_KEY="your_brave_api_key"
```

### Git Ignore Configuration

Add to `.gitignore` to avoid committing sensitive local paths:

```gitignore
# Claude Code - ignore local environment-specific configs
.claude/mcp_config.local.json
.claude/settings.local.json
```

You can create `mcp_config.local.json` for machine-specific overrides (e.g., different database passwords on different developer machines).

### Configuration Precedence

Claude Code merges configs in this order (later overrides earlier):
1. Global config (`~/.config/claude-code/`)
2. Project config (`.claude/`)
3. Local overrides (`.claude/*.local.json`)

**Example Workflow**:
- Commit `.claude/mcp_config.json` with `${ENV_VAR}` placeholders
- Each developer sets their own environment variables
- Machine-specific settings go in `.claude/mcp_config.local.json` (gitignored)

---

## Summary

**Quick Setup Checklist**:

- [ ] Install Node.js 20+
- [ ] Install PostgreSQL MCP (global installation)
- [ ] Install Git MCP (global installation)
- [ ] Install Filesystem MCP (global installation)
- [ ] Create custom Symfony MCP (optional but recommended)
- [ ] Set up global MCP config (`~/.config/claude-code/mcp_config.json`) with generic MCPs
- [ ] Set up project MCP config (`.claude/mcp_config.json`) with LARPilot-specific MCPs
- [ ] Create slash commands in `.claude/commands/`
- [ ] Configure hooks in `.claude/settings.json`
- [ ] Add context files in `.claude/context/`
- [ ] Add `.claude/*.local.json` to `.gitignore`
- [ ] Test all MCPs and commands
- [ ] Ensure Docker WSL2 integration is working
- [ ] Configure Git line endings for WSL2

**Result**: Claude Code will have:
- ✅ Direct database access for queries
- ✅ Advanced git operations
- ✅ Symfony console command execution
- ✅ Auto-formatting on file saves
- ✅ Quick access to common workflows via slash commands
- ✅ Protection against committing secrets
- ✅ Optimized for WSL2 performance

**Estimated Setup Time**: 30-45 minutes

**Maintenance**: MCPs update automatically via npm. Review hooks/commands quarterly for relevance.
