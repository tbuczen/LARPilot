# Button System

Every `<button>`, `<a class="btn">` and `<label class="btn">` in Twig templates and Stimulus controllers follows this guide.

- **Visual reference**: [`docs/ui/button-guide.html`](ui/button-guide.html) — open in a browser to see every role, size and real page context in light and dark.
- **Styles**: `assets/styles/components/_buttons.scss` (configures Bootstrap 5.3 `--bs-btn-*` variables; no per-page overrides).
- **Macros**: `templates/macros/ui_components.html.twig` (`ui.create_button`, `ui.primary_button`, `ui.edit_button`, `ui.view_button`, `ui.delete_button`, `ui.back_button`, `ui.secondary_button`, `ui.form_actions`).

## 1. Roles — pick the class by what the action does

| Role | Class | Icon | Use for |
|---|---|---|---|
| **Create** | `btn-success` | `bi-plus-lg` | Starting something new: Create, Add, New, Invite, Add option. Entry points, not form submits. |
| **Primary** | `btn-primary` | `bi-check-lg` (or `bi-send`) | Committing the current form or main step: Save, Submit, Apply filter, Accept, Approve, Assign, Confirm (non-destructive), the submit of a create form, the Import submit inside an import modal. |
| **Edit** | `btn-outline-primary` | `bi-pencil` | Opening something for change: Edit, Modify, Manage, Configure, Settings. Also equal-weight choices (e.g. "Import from file" / "Import from Google"). |
| **Info** | `btn-outline-info` | `bi-eye` / `bi-info-circle` | Read-only look: View, Details, Preview, Open, Timeline, Version history, Show more. |
| **Alert** | `btn-warning` | `bi-exclamation-triangle` | Significant but reversible: Archive, Unpublish, Suspend, Reset, Revoke. |
| **Delete** | `btn-outline-danger` | `bi-trash` | Destroying or rejecting: Delete, Remove, Reject, Ban, Decline. Opens a confirmation. |
| **Delete — final confirm** | `btn-danger` | `bi-trash` | Only the confirm button inside the confirmation modal or a dedicated "are you sure" page. |
| **Neutral** | `btn-outline-secondary` | `bi-arrow-left` for Back | Leaving without change or secondary tools: Back, Cancel, Close, Clear filters, Copy link, Download, Fit image, Refresh. |

Rules:
- At most **one solid** button per group; everything else in the group is outline.
- Order inside a group: neutral → info/edit → alert → destructive → primary/create. The primary action is last (rightmost).
- `<a>` for navigation (GET), `<button type="...">` for submits and JS actions. Same classes on both.
- Plain text links inside prose stay plain `<a>` without `btn`.

## 2. Sizes — decided by the group, not the button

Every button in one group shares one size. Space buttons with the wrapper's `gap-*`, never with `me-*`/`ms-*` on buttons or icons.

| Group | Size | Wrapper |
|---|---|---|
| Page / card header actions | default | `d-flex gap-2` |
| Form footer | default | `d-flex justify-content-end gap-2` (or `ui.form_actions`) |
| Modal footer | default | `modal-footer` |
| Filter form (`includes/filter_form.html.twig`) | default (matches input height) | filter row |
| Table row actions | `btn-sm` | `d-flex gap-1 justify-content-end` or `btn-group btn-group-sm` |
| Toolbars, inline card tools, comment actions | `btn-sm` | `d-flex gap-1` / `gap-2` |
| Public landing / hero CTA | `btn-lg` | public pages only |
| Mobile field pages for staff (e.g. map position update) | `btn-lg` | whole group `btn-lg` for touch targets |

Icon-only buttons are allowed only in `btn-sm` groups (or large mobile groups) and must carry `title` and `aria-label`.

## 3. Icons

- Bootstrap Icons (`bi bi-*`), placed before the label: `<i class="bi bi-pencil"></i>{{ 'edit'|trans }}`.
- No margin utilities on icons inside buttons: `.btn` is `inline-flex` with a `gap`.

## 4. Do not use

- `btn-secondary` (solid grey), `btn-info` (solid cyan), `btn-dark`, `btn-link` for actions.
- `btn-light` / `btn-outline-light` except on dark or colored surfaces (photo headers, cookie banner, dark card headers).
- Hover lifts (`transform: translateY`), gradients or shadows on buttons, or page-level `<style>` blocks restyling `.btn-*`. Hover only darkens solid fills or tints outline backgrounds.
- Coloring a button to match its neighbours. Color always encodes the role.

## 5. Allowed exceptions

- **Decision tree toolbar** (`decision_tree_controller.js`): the "+ Start / Decision / Outcome / Reference / End" buttons use the node-type colors as a legend.
- **Vote toggles** (`btn-check` + `btn-outline-success` / `btn-outline-danger`, thumbs up/down): semantic up/down, not create/delete.
- **Brand buttons**: SSO providers (`templates/sso/`) and social links on location pages keep their brand-ish colors.

## 6. Snippets

```twig
{# Card header #}
<div class="d-flex gap-2">
    {{ ui.back_button(path('backoffice_larp_dashboard', { larp: larp.id })) }}
    {{ ui.create_button(path('backoffice_larp_story_character_modify', { larp: larp.id })) }}
</div>

{# Table row #}
<td>
    <div class="d-flex gap-1 justify-content-end">
        {{ ui.view_button(path('..._view', { larp: larp.id, item: item.id })) }}
        {{ ui.edit_button(path('..._modify', { larp: larp.id, item: item.id })) }}
        {{ ui.delete_button(item.id, item.title, path('..._delete', { larp: larp.id, item: item.id })) }}
    </div>
</td>

{# Form footer #}
{{ ui.form_actions('save', 'bi-check-lg', path('..._list', { larp: larp.id })) }}

{# Confirmation modal footer #}
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ 'cancel'|trans }}</button>
    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i>{{ 'delete'|trans }}</button>
</div>
```

```js
// Stimulus controllers building HTML use the same classes
`<button type="button" class="btn btn-sm btn-outline-danger" data-action="click->x#remove"><i class="bi bi-trash"></i> Remove</button>`
```
