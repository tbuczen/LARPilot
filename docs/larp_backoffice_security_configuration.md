# Security Configuration

## LARP Backoffice Access Control

### Overview
The LARP backoffice section (`/backoffice/larp/{larpId}/`) is protected by a multi-layered security system that ensures only authorized users can access specific LARP management areas.

### Security Layers

#### 1. Basic Authentication
- **Path**: `^/backoffice/larp/[^/]+/`
- **Requirement**: `IS_AUTHENTICATED_FULLY`
- **Purpose**: Ensures user is logged in before accessing any LARP backoffice area

#### 2. Automatic Permission Checking
- **Implementation**: `LarpBackofficeSecurityListener`
- **Trigger**: `kernel.controller` event
- **Scope**: All routes starting with `backoffice_larp_`
- **Voter**: `LarpDetailsVoter::VIEW`

#### 3. Permission Logic
Users must meet **all** of the following criteria:
- Be authenticated (logged in)
- Be a participant in the specific LARP
- Have organizer role for that LARP

### Organizer Roles
The following roles are considered "organizer" roles:
- `ROLE_ORGANIZER`
- `ROLE_STAFF`
- `ROLE_MAIN_STORY_WRITER`
- `ROLE_STORY_WRITER`
- `ROLE_PHOTOGRAPHER`
- `ROLE_CRAFTER`
- `ROLE_MAKEUP_ARTIST`
- `ROLE_GAME_MASTER`
- `ROLE_NPC_LONG`
- `ROLE_NPC_SHORT`
- `ROLE_MEDIC`
- `ROLE_TRASHER`
- `ROLE_TRUST_PERSON`
- `ROLE_OUTFIT_APPROVER`
- `ROLE_ACCOUNTANT`
- `ROLE_GASTRONOMY`

### How It Works

1. **Route Pattern Detection**: The security listener automatically detects routes starting with `backoffice_larp_`
2. **LARP Resolution**: Extracts the LARP ID from route parameters and loads the LARP entity
3. **Permission Check**: Uses the `LarpDetailsVoter` to verify if the current user has `VIEW_BO_LARP_DETAILS` permission for the specific LARP
4. **Access Decision**: Grants or denies access based on the voter's decision

### Benefits

- **Automatic**: No need to add security annotations to individual controllers
- **Centralized**: All LARP backoffice security logic in one place
- **Consistent**: Same security rules apply to all LARP backoffice routes
- **Maintainable**: Easy to modify security rules across the entire backoffice

### Error Handling
- **Unauthenticated users**: Redirected to login page
- **Authenticated but unauthorized users**: Receive `AccessDeniedHttpException` (403 error)

### Adding New Controllers
New controllers under the LARP backoffice will automatically inherit this security model if they:
- Use route names starting with `backoffice_larp_`
- Have a `larp` parameter in their route that resolves to a LARP entity
- Follow the `/backoffice/larp/{larp}/` URL pattern
