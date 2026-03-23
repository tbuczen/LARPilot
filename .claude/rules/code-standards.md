---
type: "always"
---

# Additional code standards
- Encourage early returns over nesting
- Provide no comments at all in generated code
- Use as much as possible semantical names for variables. E.g. instead of `$entity = $this->loanRepository->find(1);`, use `$loan = $this->loanRepository->find(1);`

# Doctrine entities
- Doctrine entities are never meant to be final or readonly.
- Avoid using `readonly` in Doctrine entities' properties.
