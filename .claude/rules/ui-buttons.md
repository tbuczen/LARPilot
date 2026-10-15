# Buttons (templates and Stimulus controllers)

Full spec: `docs/UI_BUTTONS.md`. Visual reference: `docs/ui/ui-guide.html`. Read the spec before adding or restyling any button or `a.btn`.

Role → class (color always encodes the role, never the neighbours):
- Create / Add / New / Invite → `btn-success` + `bi-plus-lg`
- Save / Submit / Accept / Approve / Apply filter / form submit → `btn-primary`
- Edit / Modify / Manage / Configure → `btn-outline-primary` + `bi-pencil`
- View / Details / Preview / Open → `btn-outline-info` + `bi-eye`
- Archive / Unpublish / Suspend / Reset → `btn-warning`
- Delete / Remove / Reject / Ban → `btn-outline-danger` + `bi-trash`; solid `btn-danger` only for the final confirm in a modal or confirm page
- Back / Cancel / Close / Clear / secondary tools → `btn-outline-secondary`

Sizes by group: default in page/card headers, form footers, modal footers and the filter form; `btn-sm` in table rows and toolbars; `btn-lg` only on public landing pages. One size per group, one solid button per group, primary action last.

Never: `btn-secondary`, `btn-info`, `btn-link`, `btn-dark` for actions; `btn-light` outside dark surfaces; `me-*`/`ms-*` on icons inside buttons; per-page CSS restyling `.btn-*`. Prefer the `ui.*` button macros in `templates/macros/ui_components.html.twig`.
