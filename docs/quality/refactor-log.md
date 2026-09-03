# Refactoring Log

Per DESIGN-03: minimum 3 entries (smell -> technique -> before/after), one
SRP audit note, and at least one `refactor:` commit on real prior code
(Boy Scout Rule) - not on the feature currently being written.

## Entry template
### N. <Smell name> in `<file>`
- **Technique**: <Extract Method / Extract Class / Replace Conditional with Polymorphism / ...>
- **Why**: <what made it a problem - hard to test, duplicated logic, etc.>
- **Before**:
  ```php
  // paste the smelly snippet
  ```
- **After**:
  ```php
  // paste the refactored snippet
  ```
- **Commit**: `refactor: <short description>` (hash: TBD)

---

## SRP audit
One class from the draft that took on more than one responsibility, and how
it was split. Fill in once a first draft of a Service exists (a common
candidate: a Service that validates input, persists it, AND formats a
response/report in one method).

- **Class**: TBD
- **Responsibilities it had**: TBD
- **How it was split**: TBD
