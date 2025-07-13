# LARP Workflow System

This document explains the LARP workflow system implementation, how it works, and how to extend it.

## Overview

The LARP workflow system manages the lifecycle of LARP events from initial creation to completion. It uses Symfony's Workflow component to enforce state transitions and business rules.

## Architecture

The workflow system consists of several key components:

### 1. Workflow Configuration (`config/packages/workflow.yaml`)

Defines the state machine with:
- **States**: Different stages of a LARP (DRAFT, WIP, PUBLISHED, INQUIRIES, CONFIRMED, CANCELLED, COMPLETED)
- **Transitions**: Allowed movements between states
- **Marking Store**: How the current state is stored (using `getMarking()`/`setMarking()` methods)

### 2. Entity Integration (`src/Entity/Larp.php`)

The `Larp` entity includes:
- `status` property storing the current `LarpStageStatus` enum
- `getMarking()` method converting enum to string for workflow
- `setMarking()` method converting string back to enum

### 3. Workflow Service (`src/Service/Larp/Workflow/LarpWorkflowService.php`)

Provides high-level workflow operations:
- Get available transitions with labels
- Apply transitions
- Check if transitions are allowed
- Get validation errors for transitions

### 4. Transition Guard Service (`src/Service/Larp/Workflow/LarpTransitionGuardService.php`)

Implements business logic validation:
- Checks requirements for each transition
- Provides detailed error messages
- Validates LARP completeness

### 5. Event Listener (`src/EventListener/LarpWorkflowGuardListener.php`)

Listens to workflow events and blocks transitions that don't meet requirements.

### 6. Controller (`src/Controller/Backoffice/LarpStatusController.php`)

Handles HTTP requests for:
- Displaying current status and available transitions
- Executing transitions
- Providing API endpoints for status information

## State Flow
```
DRAFT → WIP → PUBLISHED → INQUIRIES → CONFIRMED → COMPLETED 
                 └─────────┴─→ CANCELLED ←─┘
``` 

### State Descriptions

- **DRAFT**: Initial state, only visible to admins
- **WIP**: Work in progress, visible to organizers
- **PUBLISHED**: Published and visible to everyone
- **INQUIRIES**: Collecting player applications
- **CONFIRMED**: Event confirmed and will happen
- **CANCELLED**: Event has been cancelled
- **COMPLETED**: Event has been completed

## Validation Rules

### To PUBLISHED
- Location is required
- Description is required
- Start date is required
- End date is required

### To INQUIRIES
- All PUBLISHED requirements +
- At least one character is required
- All characters must have descriptions

### To CONFIRMED
- All INQUIRIES requirements +
- All characters must have participants assigned

## How to Extend the System

### Adding New States

1. **Update the Enum** (`src/Entity/Enum/LarpStageStatus.php`):
```php
enum LarpStageStatus: string { 
    case DRAFT = 'DRAFT'; 
    case WIP = 'WIP'; 
    case PUBLISHED = 'PUBLISHED'; 
    case INQUIRIES = 'INQUIRIES'; 
    case CONFIRMED = 'CONFIRMED'; 
    case CANCELLED = 'CANCELLED'; 
    case COMPLETED = 'COMPLETED'; 
    case NEW_STATE = 'NEW_STATE'; 
    // Add new state
}
``` 

2. **Update Workflow Configuration** (`config/packages/workflow.yaml`):
```yaml
places: 
  - DRAFT
  - WIP
  - PUBLISHED
  - INQUIRIES
  - CONFIRMED 
  - CANCELLED 
  - COMPLETED
  - NEW_STATE # Add new place
transitions: 
  to_new_state: 
    from: [SOURCE_STATE] 
    to: NEW_STATE
``` 

3. **Update Visibility Methods** in the enum:
```php
public function isVisibleForEveryone(): bool {
    return match ($this) { 
        self::PUBLISHED, 
        self::INQUIRIES, 
        self::CONFIRMED, 
        self::COMPLETED,
        self::NEW_STATE => true,
        default => false, 
    }; 
}
``` 

### Adding New Transitions

1. **Update Workflow Configuration**:
```yaml
transitions: 
  new_transition: 
      from: [SOURCE_STATE] 
      to: TARGET_STATE
``` 

2. **Add Validation Logic** in `LarpTransitionGuardService`:
```php
public function getValidationErrors(Larp larp, stringtransitionName): array { $errors = [];
    switch ($transitionName) {
        case 'to_published':
            $errors = $this->getPublishValidationErrors($larp);
            break;
        case 'new_transition':
            $errors = $this->getNewTransitionValidationErrors($larp);
            break;
    }

    return $errors;
}

private function getNewTransitionValidationErrors(Larp larp): array {errors = [];
    // Add your validation logic here
    if (!$this->customValidation($larp)) {
        $errors[] = 'Custom validation failed';
    }

    return $errors;
}
``` 

3. **Add Event Listener** (if needed):
```php
public static function getSubscribedEvents(): array { 
    return [ 
        'workflow.larp_stage_status.guard.to_published' => 'guardToPublished', 
        'workflow.larp_stage_status.guard.to_inquiries' => 'guardToInquiries', 
        'workflow.larp_stage_status.guard.to_confirmed' => 'guardToConfirmed',
        'workflow.larp_stage_status.guard.new_transition' => 'guardNewTransition',
    ]; 
}

public function guardNewTransition(GuardEvent event): void { 
    /** @var Larplarp */ larp =event->getSubject();
    if (!$this->guardService->canExecuteNewTransition($larp)) {
        $event->setBlocked(true, 'New transition validation failed');
    }
}
``` 

4. **Update Transition Labels** in `LarpWorkflowService`:

```php
private function getTransitionLabel(string transitionName): string {
    return match (transitionName) { 
        'to_wip' => 'Move to Work in Progress', 
        'to_published' => 'Publish',
        'to_inquiries' => 'Open for Inquiries',
        'to_confirmed' => 'Confirm Event',
        'to_cancelled' => 'Cancel Event',
        'to_completed' => 'Mark as Completed',
        'new_transition' => 'New Transition Label',
        default => ucfirst(str_replace('_', ' ', $transitionName)),
    }; 
}
``` 

### Adding Custom Validation Rules

1. **Create Validation Method** in `LarpTransitionGuardService`:
```php
private function hasCustomRequirement(Larp $larp): bool { 
    // Implement your custom validation logic return true;
    // or false based on validation
}
``` 

2. **Add to Validation Errors Method**:
```php
private function getCustomValidationErrors(Larp $larp): array {
    $errors = [];
    if (!$this->hasCustomRequirement($larp)) {
        $errors[] = 'Custom requirement not met';
    }
    
    return $errors;
}
``` 

3. **Include in Transition Validation**:
```php
case 'target_transition': 
    $errors = array_merge(this->getPublishValidationErrors(larp), $this->getCustomValidationErrors($larp) ); 
    break;
``` 

### Adding Workflow Events

You can listen to various workflow events:
```php
public static function getSubscribedEvents(): array { 
    return [ // Before transition is applied 'workflow.larp_stage_status.guard.to_published' => 'guardToPublished',
        // After transition is applied
        'workflow.larp_stage_status.entered.PUBLISHED' => 'onPublished',
    
        // Before leaving a state
        'workflow.larp_stage_status.leave.DRAFT' => 'onLeaveDraft',
        
        // On transition
        'workflow.larp_stage_status.transition.to_published' => 'onTransitionToPublished',
    ];
}
``` 

## Best Practices

1. **Validation Logic**: Keep validation logic in the `LarpTransitionGuardService` for consistency
2. **Error Messages**: Provide clear, actionable error messages
3. **State Visibility**: Update visibility methods in the enum when adding new states
4. **Database Migrations**: Create migrations when adding new enum values
5. **Translations**: Add translations for new states and transitions
6. **Testing**: Write tests for new validation rules and transitions

## Troubleshooting

### Common Issues

1. **Transition Not Showing**: Check workflow configuration and ensure the transition is defined
2. **Validation Errors Not Displaying**: Verify the transition name matches in the guard service
3. **State Not Persisting**: Ensure `getMarking()` and `setMarking()` methods are correctly implemented
4. **Permission Errors**: Check the voter permissions for `MANAGE_LARP_GENERAL_SETTINGS`

### Debugging

1. **Check Workflow Registry**: Verify the workflow is properly registered
2. **Inspect Transitions**: Use `getEnabledTransitions()` to see what's available
3. **Validate Enum Values**: Ensure enum values match workflow configuration
4. **Test Guard Logic**: Run validation methods independently

## File Structure
```
src/ 
├── Controller/Backoffice/LarpStatusController.php 
├── Entity/ 
│       ├── Larp.php 
│       └── Enum/LarpStageStatus.php 
├── EventListener/LarpWorkflowGuardListener.php 
└── Service/Larp/Workflow/ 
    ├── LarpWorkflowService.php 
    └── LarpTransitionGuardService.php
config/packages/workflow.yaml
templates/backoffice/larp/status/index.html.twig
``` 

This architecture provides a flexible, maintainable workflow system that can be easily extended as business requirements evolve.
