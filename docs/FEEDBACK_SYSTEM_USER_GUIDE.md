# LARPilot Feedback System - User Guide

## Overview

LARPilot includes a built-in feedback system that allows users to easily report bugs, request features, ask questions, or provide general feedback. The system captures screenshots and context automatically to help our team understand and address your feedback quickly.

## How to Submit Feedback

### 1. Open the Feedback Widget

Look for the **blue feedback button** in the bottom-right corner of any page:

![Feedback Button](https://img.shields.io/badge/-%F0%9F%92%AC%20Feedback-0d6efd?style=for-the-badge)

Click the button to open the feedback form.

### 2. Choose Feedback Type

Select the type of feedback you're submitting:

- 🐛 **Bug Report** - Something isn't working correctly
- 💡 **Feature Request** - Suggest a new feature or improvement
- ❓ **Question** - Ask a question about how something works
- 💬 **General Feedback** - Share your thoughts or comments

### 3. Fill Out the Form

**Subject (Required)**
- Provide a brief, descriptive title for your feedback
- Example: "Character sheet not saving" or "Add export to PDF feature"

**Description (Required)**
- Describe your feedback in detail
- For bugs: Include steps to reproduce the issue
- For features: Explain what you'd like to see and why
- For questions: Be as specific as possible

### 4. Add a Screenshot (Optional)

Screenshots help us understand your feedback better, especially for bug reports.

**To capture a screenshot:**
1. Click the **"Capture Screenshot"** button
2. The modal will hide temporarily while capturing the page
3. A preview will appear in the form
4. Click **"Remove Screenshot"** if you want to retake it

**Tips:**
- Screenshots automatically include the entire page
- The system captures what's visible on your screen
- You can submit feedback without a screenshot if preferred

### 5. Review Context Information (Optional)

The feedback system automatically captures helpful context:

- **Page URL** - The page you were on when submitting feedback
- **Browser** - Your browser type and version
- **Viewport** - Your screen size
- **LARP Context** - If you were viewing a specific LARP, its details are included
- **User Info** - Your name and email (if logged in)
- **Timestamp** - When you submitted the feedback

Click **"Automatically Captured Context"** to expand and review this information.

### 6. Submit Your Feedback

Click the **"Submit Feedback"** button to send your feedback.

You'll see a success message confirming your submission. The modal will close automatically after a few seconds.

## What Happens Next?

1. **Ticket Created** - Your feedback is converted into a support ticket
2. **Email Confirmation** - You'll receive an email confirmation (if logged in)
3. **Team Review** - Our team reviews your feedback
4. **Response** - We'll respond via email with updates or questions
5. **Resolution** - Once resolved, you'll be notified

## Tips for Effective Feedback

### For Bug Reports

**Good Example:**
```
Subject: Character skills not displaying on mobile

Description:
When viewing my character sheet on mobile (iPhone 13, Safari),
the skills section shows as blank. This happens consistently
even after refreshing.

Steps to reproduce:
1. Open any character sheet
2. Scroll to skills section
3. Skills list is empty (but shows correctly on desktop)

Expected: Skills should display on mobile like on desktop
Actual: Skills section is blank
```

**Include:**
- ✅ Clear description of what's wrong
- ✅ Steps to reproduce the issue
- ✅ Expected vs. actual behavior
- ✅ Screenshot showing the problem

**Avoid:**
- ❌ Vague descriptions: "It's broken"
- ❌ Missing context: "Doesn't work"

### For Feature Requests

**Good Example:**
```
Subject: Add bulk import for character backgrounds

Description:
I'm running a LARP with 50+ players. Currently I have to copy/paste
each character's background individually from our Google Doc.

Suggested feature:
- Add a "Bulk Import" button on the characters list
- Accept CSV or paste from spreadsheet
- Map columns to character fields
- Preview before import

This would save hours of manual data entry when setting up a new LARP.
```

**Include:**
- ✅ Your use case / problem
- ✅ How the feature would help
- ✅ Suggested implementation (optional)

**Avoid:**
- ❌ Just "Add feature X" without context
- ❌ Features that already exist (check docs first)

### For Questions

**Good Example:**
```
Subject: How do I assign quests to multiple characters?

Description:
I have a quest that involves 5 different characters. I can only
see how to assign it to one character at a time on the quest
edit form. Is there a way to multi-select characters, or do I
need to create separate quest instances?

I'm on the quest edit page: /backoffice/larp/42/quests/edit/123
```

**Include:**
- ✅ Specific question
- ✅ What you've tried
- ✅ Where you're stuck

**Avoid:**
- ❌ Multiple unrelated questions in one submission
- ❌ Questions answered in documentation (check docs first)

## Privacy & Data

### What Information Is Collected?

When you submit feedback, we collect:

- **Your Email & Name** (if logged in)
- **Page URL** - To understand where you encountered an issue
- **Browser Information** - To reproduce browser-specific bugs
- **Screen Size** - To debug responsive layout issues
- **LARP Context** - If you're viewing a specific LARP
- **Screenshot** (if you provide one)
- **Your Feedback** - Subject, description, type

### How Is It Used?

- **Support** - To respond to your feedback and resolve issues
- **Product Improvement** - To identify common problems and prioritize features
- **Bug Tracking** - To reproduce and fix reported bugs

### Data Retention

- Feedback tickets are retained according to our privacy policy
- Email notifications are sent to your registered email
- You can request deletion of your feedback at any time

## Frequently Asked Questions

### Can I submit feedback anonymously?

If you're logged in, your email and name are automatically included. If you're not logged in, you can still submit feedback, but we won't be able to follow up with you via email.

### How long does it take to get a response?

Response times vary based on:
- **Bug Reports** - Usually within 1-2 business days
- **Feature Requests** - We acknowledge within a week; implementation timelines vary
- **Questions** - Usually within 1-2 business days
- **General Feedback** - We read all feedback; responses depend on the topic

### Can I track the status of my feedback?

Currently, status updates are sent via email. We're working on adding in-app ticket tracking.

### What if I don't receive a confirmation email?

1. Check your spam/junk folder
2. Verify your email address is correct in your profile
3. Contact support at feedback@larpilot.com

### Can I edit or delete my feedback after submitting?

Contact us via email referencing your feedback subject, and we can update or delete your ticket.

### The feedback button isn't appearing. What should I do?

1. Refresh the page
2. Clear your browser cache
3. Try a different browser
4. If the issue persists, email us at feedback@larpilot.com

### My screenshot didn't capture correctly. Can I upload an image instead?

Currently, screenshots are captured via the widget. If you need to share a specific image:
1. Submit your feedback without a screenshot
2. Reply to the confirmation email with your image attached

## Contact Support

If you have issues with the feedback system itself, contact us directly:

- **Email:** feedback@larpilot.com
- **Help Center:** https://help.larpilot.com (when available)

## Examples Gallery

### Example 1: Bug Report with Screenshot

![Bug Report Example](https://via.placeholder.com/800x500/dc3545/ffffff?text=Bug+Report+Example)

**Type:** 🐛 Bug Report
**Subject:** Navigation menu overlaps content on tablet
**Description:**
```
On iPad (Safari), the navigation menu overlaps the main content area
when viewing the character list. The menu appears to be positioned
incorrectly at screen widths between 768px-1024px.

Screenshot shows the overlap clearly - you can see the character
cards partially hidden behind the menu.
```

### Example 2: Feature Request

![Feature Request Example](https://via.placeholder.com/800x500/0d6efd/ffffff?text=Feature+Request+Example)

**Type:** 💡 Feature Request
**Subject:** Export LARP schedule to Google Calendar
**Description:**
```
As a LARP organizer, I'd like to export our event schedule to Google
Calendar so participants can automatically sync events to their personal
calendars.

Suggested workflow:
1. On the Event Planning calendar page, add "Export" button
2. Generate an .ics file with all scheduled events
3. Include event details, location, and participant assignments

This would reduce scheduling conflicts and improve participant attendance.
```

### Example 3: Question

![Question Example](https://via.placeholder.com/800x500/ffc107/000000?text=Question+Example)

**Type:** ❓ Question
**Subject:** How do I link characters to quests?
**Description:**
```
I'm setting up quests for my LARP and want to link specific characters
to each quest. I see the quest edit form but can't find where to assign
characters.

What I've tried:
- Checked the quest edit page - no character field visible
- Looked in character edit page - no quest assignment section
- Searched documentation for "quest character link"

Am I missing something obvious, or is this done differently?
```

### Example 4: General Feedback

![General Feedback Example](https://via.placeholder.com/800x500/28a745/ffffff?text=General+Feedback+Example)

**Type:** 💬 General Feedback
**Subject:** Love the new story graph feature!
**Description:**
```
Just wanted to say the story graph visualization is amazing! Being able
to see all the character connections and plot threads visually makes it
so much easier to manage complex LARP narratives.

Small suggestion: It would be great to filter the graph by character
type (PC vs NPC) to reduce clutter when needed.

Keep up the excellent work!
```

## Accessibility

The feedback system is designed to be accessible:

- **Keyboard Navigation** - Full keyboard support (Tab, Enter, Esc)
- **Screen Readers** - ARIA labels and semantic HTML
- **Color Contrast** - WCAG AA compliant color scheme
- **Responsive Design** - Works on all screen sizes

If you encounter accessibility issues, please report them using the feedback system itself or email accessibility@larpilot.com.

---

**Thank you for helping improve LARPilot! Your feedback drives our development priorities and helps create a better experience for all users.**
