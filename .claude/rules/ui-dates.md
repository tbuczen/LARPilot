# Dates (templates, Stimulus controllers, forms)

Full spec: `docs/UI_DATES.md`. Visual reference: `docs/ui/ui-guide.html` ("Dates & time"). Read the spec before showing or entering a date.

- Show dates as `d-m-Y`, date-times as `d-m-Y H:i`, ranges as `start → end` (both ends in full). Never seconds, never browser-locale formats (`toLocale*String`), never ISO or month names to people.
- Machine values stay ISO: form posts, JSON parsed by code, stored data, exports.
- Every `DateType`/`DateTimeType` gets the Air Datepicker-based picker via `DatePickerExtension`/`DateTimePickerExtension`; don't set `widget`/`html5`/`format` yourself.
- A from–to pair is one picker: `'range_end' => '<end field>'` on the start field, labelled for the whole range.
- Range filters use `DateRangeFilter::from()` / `::to()` ("within the period") on `DateFilterType` fields.
- Never use `<input type="date|datetime-local|time">` or another picker library. JS display formatting goes through `formatDisplayDate()` / `formatDisplayDateTime()` in `assets/utils/larpDatePicker.js`.
