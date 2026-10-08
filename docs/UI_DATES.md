# Dates & Time

Every date shown or entered in LARPilot follows this guide.

- **Visual reference**: [`docs/ui/ui-guide.html`](ui/ui-guide.html), section "Dates & time". It runs the real picker code (English/Polish, phone layout).
- **Picker core**: `assets/utils/larpDatePicker.js` (+ `assets/utils/datePickerLocales.js`), built on [Air Datepicker](https://air-datepicker.com) 3.6 (importmap).
- **Stimulus controller**: `assets/controllers/date-picker_controller.js`.
- **Form wiring**: `App\Domain\Core\Form\Extension\DatePickerExtension` (DateType) and `DateTimePickerExtension` (DateTimeType).
- **Styles**: `assets/styles/components/_date_picker.scss`.
- **Range filters**: `App\Domain\Core\Form\Filter\DateRangeFilter`.
- **Format constants (single source)**: `App\Domain\Core\Service\Helper\DateFormat`. Never type a format string like `'d-m-Y'` or `'Y-m-d H:i'` anywhere else.

| Constant | Value | Use |
|---|---|---|
| `DateFormat::DATE` | `d-m-Y` | dates shown to people |
| `DateFormat::DATETIME` | `d-m-Y H:i` | date-times shown to people |
| `DateFormat::TIME` | `H:i` | times shown to people |
| `DateFormat::ISO_DATE` / `ISO_DATETIME` | `Y-m-d` / `Y-m-d H:i` | machine values |
| `DateFormat::ISO_DATETIME_SECONDS` | `Y-m-d H:i:s` | stored timestamps (e.g. activity logs) |
| `DateFormat::FORM_DATE` / `FORM_DATETIME` | `yyyy-MM-dd` / `yyyy-MM-dd HH:mm` | ICU patterns the form extensions parse |

## 1. Display formats

| What | Format | Example |
|---|---|---|
| Date | `d-m-Y` | `12-06-2026` |
| Date and time | `d-m-Y H:i` (24h, never seconds) | `12-06-2026 18:00` |
| Range | both ends in full, joined by ` → ` | `12-06-2026 18:00 → 14-06-2026 16:00` |
| One-day date range | single date | `12-06-2026` |
| Time only | `H:i` | `18:00` |

- Twig: `{{ value|display_date }}`, `{{ value|display_datetime }}`, `{{ value|display_time }}` (`App\Twig\DateFormatExtension`). Never `|date('…')` with a literal format. Twig's default `|date` format reads `DateFormat::DATETIME` in `config/packages/twig.yaml` (`!php/const`).
- JavaScript: `formatDisplayDate()` / `formatDisplayDateTime()` from `assets/utils/larpDatePicker.js`, the one JS module that knows the format (it also exports `PICKER_DATE_FORMAT` for Air Datepicker). It mirrors `DateFormat`; change both together. Never `toLocaleString()` / `toLocaleDateString()` / `toLocaleTimeString()`.
- PHP values sent to JSON only to be shown to people (comment timestamps, vote times, staff position times): `->format(DateFormat::DATETIME)`.
- **Machine values stay ISO**: form posts (`Y-m-d`, `Y-m-d H:i`), API payloads parsed by code, stored JSON, logs, CSV/exports, `format('c')`.

## 2. Entering dates

Every `DateType` and `DateTimeType` field gets the picker automatically. Nothing to add per form.

```php
$builder->add('dueDate', DateTimeType::class, [
    'label' => 'kanban.due_date',
    'required' => false,
]);
```

- The real input becomes a hidden field holding ISO (`2026-06-12 18:00`); Symfony parses it with `DateFormat::FORM_DATETIME` (`DateFormat::FORM_DATE` for `DateType`). A read-only display input shows `12-06-2026 18:00`.
- Times are picked with hour and minute selects on a **5-minute step** (native scroll wheels on phones).
- On phones (≤ 575px or touch) the calendar opens as a centred dialog. Inside a Bootstrap modal it opens as a dropdown inside the modal.
- Optional fields get a clear (×) button; required ones do not.
- Opt out (rare): `'date_picker' => false` restores Symfony's defaults.

### From–to pairs are one picker

Put `range_end` on the start field. The end field is rendered by the template as usual and hidden by the controller (its column too, when it is alone in one).

```php
$builder
    ->add('startDate', DateTimeType::class, [
        'label' => 'larp.dates',
        'range_end' => 'endDate',
    ])
    ->add('endDate', DateTimeType::class, [
        'label' => 'larp.end_date',
    ]);
```

- Label the start field for the whole range (`larp.dates`, `event.time_range`, `planning_resource.availability`).
- One click then **Done** gives a one-day range. The panel warns when the end is before the start.
- `includes/filter_form.html.twig` gives range pickers a `col-md-6` column.

### Range filters mean "within the period"

```php
->add('startDate', Filters\DateFilterType::class, [
    'label' => 'event.dates',
    'required' => false,
    'range_end' => 'endDate',
    'apply_filter' => DateRangeFilter::from('startDate'),
])
->add('endDate', Filters\DateFilterType::class, [
    'required' => false,
    'apply_filter' => DateRangeFilter::to('startDate', 'endDate'),
])
```

`from` keeps rows starting on or after that day; `to` keeps rows ending (or, without an end, starting) on or before that day. Use `DateFilterType` (date only) for filters.

## 3. Do not

- Use `<input type="date">`, `datetime-local` or `time`, or add another picker library.
- Render a from–to pair as two pickers.
- Show seconds, month names in tables, US order (`m/d/Y`) or ISO (`Y-m-d`) to people.

## 4. Pickers built outside Symfony forms

```js
import AirDatepicker from 'air-datepicker';
import { LarpDatePicker } from '../utils/larpDatePicker.js';
import { datePickerLabels, datePickerLocales } from '../utils/datePickerLocales.js';

new LarpDatePicker(AirDatepicker, {
    startInput, endInput, withTime: true, required: false,
    locale: datePickerLocales.en, labels: datePickerLabels.en,
});
```

Or add `data-controller="date-picker"` (+ `data-date-picker-time-value="true"`, `data-date-picker-end-value="<end input id>"`) to a text input holding an ISO value.

## 5. Keeping the visual guide in sync

`docs/ui/ui-guide.html` embeds copies of `larpDatePicker.js`, `datePickerLocales.js` and the compiled `_date_picker.scss` between `BEGIN date picker …` / `END date picker …` markers. After changing those files, replace the blocks between the markers (compile the SCSS with `var/dart-sass/sass --no-source-map --style=compressed assets/styles/components/_date_picker.scss`, and drop the `export` keywords from the JS).
