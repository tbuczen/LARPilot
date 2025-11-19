# Repository Guidelines for Codex Agents

## Code style
- PHP code follows PSR-12. Indent with 4 spaces. Use typed properties, attributes, and `readonly` where suitable. Traits such as `UuidTraitEntity` and `CreatorAwareTrait` are used to share behavior between entities.
- DTOs and command objects are lightweight `readonly` classes with public properties.
- Doctrine ORM mappings use PHP attributes.
- Enumerations define constants (e.g., `LarpStageStatus`).

## Filtering forms
- Filtering is implemented via Spiriit Form Filter Bundle. Filter types extend `AbstractType` and typically add fields using `Filters\*` classes. Options should set `'method' => 'GET'`, `'validation_groups' => ['filtering']`, disable CSRF and expect a `Larp` instance for `larp` option.

## Frontend
- JavaScript is written as ES6 modules and organized under `assets/controllers/` using Stimulus. Use 4 space indentation and define `targets`, `values`, and life‑cycle methods (e.g., `connect`). Webpack Encore bundles assets and the entry point is `assets/app.js`.

## Tests and checks
- Prepare tests with `make test-build`.
- Run unit tests with `make test`.
- Run code style checks with `make ecs-fix`.
- Run static analysis with `make stan`.

Make sure these checks pass before committing changes.
