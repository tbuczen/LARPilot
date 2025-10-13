# Character Allocation System

## Overview

The Character Allocation System helps LARP organizers efficiently match players to characters based on:
- Player preferences and priorities
- Organizer votes and assessments
- Fair distribution (one character per player)

This automated system analyzes all applications and suggests optimal character assignments, saving hours of manual work while ensuring player satisfaction.

## How It Works

### For Organizers

#### Step 1: Review Applications

Navigate to **Backoffice → Your LARP → Applications** to see all submitted applications.

The applications list shows:
- Applicant contact information
- Application status (NEW, CONSIDER, ACCEPTED, etc.)
- Character choices for each applicant
- Submission date

#### Step 2: Vote on Applications (Optional but Recommended)

Before suggesting allocations, organizers can vote on character-player matches:

1. Go to **Applications → Match** view
2. Review each applicant's choices
3. Cast votes (upvote ✓ or downvote ✗) for each choice
4. Add justification for your votes (optional)
5. Vote scores are aggregated across all organizers

**Voting helps the algorithm:**
- Prioritize matches the organizing team supports
- Flag potential mismatches
- Record team consensus for later reference

#### Step 3: Generate Allocation Suggestions

1. Click the **"Suggest Allocation"** button on the Applications page
2. The algorithm analyzes all applications and calculates optimal matches
3. Review the suggested allocations table

**The table shows:**
- Character name
- Assigned applicant email
- Player's priority (1-5, where 1 is their top choice)
- Vote score from organizers
- Total calculated score
- Player's justification (click "View" to read)

**Score Breakdown:**
- **Organizer Votes**: Each vote × 10 points (can be negative)
- **Player Priority**: 25 points for 1st choice, 20 for 2nd, 15 for 3rd, etc.
- **Total Score**: Sum of both factors

Higher scores indicate better matches. The algorithm ensures no player gets multiple characters and no character goes to multiple players.

#### Step 4: Review and Adjust

By default, all suggestions are selected. You can:
- **Deselect matches** you disagree with (uncheck the box)
- **Review justifications** to understand player motivation
- **Check vote details** to see team consensus
- **Use "Select All"** checkbox to toggle all at once

The selection counter shows how many allocations will be sent.

#### Step 5: Send Allocation Emails

1. Click **"Send Allocation Emails"** button
2. Review the confirmation modal (shows selected count)
3. Click **"Confirm & Send Emails"**

Each selected player receives an email with:
- LARP details (title, dates, location)
- Assigned character name
- Two action buttons: **Confirm** or **Decline**

Applications are updated to `OFFERED` status automatically.

### For Players

#### Receiving Character Assignment

Players receive an email titled **"Character Assignment for [LARP Name]"** with:
- Event information
- Assigned character details
- Two prominent buttons for response

#### Confirming Assignment

1. Click **"I Confirm - I Will Play This Character"** in the email
2. Review character details on the confirmation page
3. Click **"Yes, I Confirm"** button
4. A confirmation modal appears for final verification
5. Click **"Confirm Character"** to finalize

**Result:** Application status changes to `CONFIRMED`. You're committed to playing this character!

#### Declining Assignment

1. Click **"I Decline - I Cannot Play This Character"** in the email
2. Review the warning about consequences
3. Click **"Yes, I Decline"** button
4. A confirmation modal appears for final verification
5. Click **"Decline Character"** to finalize

**Result:** Application status changes to `DECLINED`. Organizers are notified and may reassign the character.

**Important:** Both actions are final and cannot be undone. Choose carefully!

## Application Status Flow

```
NEW → CONSIDER → OFFERED → CONFIRMED
                         ↘ DECLINED

NEW → REJECTED
```

- **NEW**: Application just submitted
- **CONSIDER**: Marked for review by organizers
- **OFFERED**: Character assigned, awaiting player response
- **CONFIRMED**: Player accepted the character
- **DECLINED**: Player rejected the character
- **REJECTED**: Organizers rejected the application
- **ACCEPTED**: Manually accepted by organizers (pre-allocation system)

## Best Practices

### For Organizers

1. **Encourage Complete Applications**: Ask players to provide justifications for their choices
2. **Vote Early**: Cast votes before generating suggestions for better results
3. **Review Before Sending**: Check suggested matches carefully before sending emails
4. **Set Expectations**: Tell players when to expect character assignments
5. **Respond to Declines**: Have backup plans if players decline characters
6. **Monitor Confirmations**: Track who has responded and follow up if needed

### For Players

1. **Prioritize Honestly**: Put your actual top choice as Priority 1
2. **Write Justifications**: Explain why you want each character (helps organizers)
3. **Respond Quickly**: Don't leave organizers waiting for your decision
4. **Be Committed**: Only confirm if you can truly attend the event
5. **Communicate Issues**: Contact organizers if you have concerns before confirming

## Common Scenarios

### "All my top choices went to other players"

The algorithm considers both player preferences and organizer votes. If you received a lower-priority choice:
- Other players may have had stronger matches for your top choices
- Organizer votes influenced the allocation
- Your assigned character may still be a great fit!

Consider confirming and giving it a chance, or contact organizers to discuss alternatives.

### "I want to decline but I'm worried about missing out"

It's better to decline early than to commit and cancel later. Organizers can:
- Offer you a different character
- Re-run allocation with remaining characters
- Work with you to find a better match

Be honest about your preferences rather than accepting a poor fit.

### "I'm an organizer and the suggestions seem wrong"

The algorithm is a tool, not a mandate. You can:
- Deselect any matches you disagree with
- Manually assign characters instead
- Adjust votes and re-run the algorithm
- Mix automated and manual assignments

Use the algorithm to handle obvious matches, then manually resolve complex cases.

## Troubleshooting

### No Suggestions Generated

**Possible causes:**
- No applications submitted yet
- No character choices in applications
- All applications already processed

**Solution:** Verify applications exist and have character choices selected.

### Email Not Received

**Possible causes:**
- Email in spam folder
- Wrong email address in application
- Email server issues

**Solution:**
- Check spam/junk folders
- Verify email address in application
- Contact organizers for manual confirmation

### Can't Access Confirm/Decline Links

**Possible causes:**
- Link expired or malformed
- Not logged in as the applicant
- Application status changed

**Solution:**
- Log in with the account that submitted the application
- Contact organizers if link doesn't work
- Check application status hasn't already been updated

## Technical Details

For technical implementation details, see:
- [Technical Documentation](technical/CHARACTER_ALLOCATION_TECHNICAL.md)
- [Domain Architecture](DOMAIN_ARCHITECTURE.md)
- [Application Domain README](../src/Domain/Application/README.md) (if exists)

## Related Features

- **Application Matching**: Vote on player-character matches before allocation
- **Application Dashboard**: Overview statistics for all applications
- **Participant Management**: Track confirmed players
- **Email Notifications**: Automated communication with players

## Feedback & Support

If you encounter issues or have suggestions for improving the allocation system:
1. Check this documentation first
2. Review the [GitHub Issues](https://github.com/anthropics/larpilot/issues)
3. Contact the LARPilot development team
4. Submit feature requests through the issue tracker
