# Contributing Guide

This project is maintained in a production-oriented workflow.

## Branching
- `main` must stay deployable.
- Create feature branches: `feature/<short-name>`, `fix/<short-name>`, `chore/<short-name>`.
- Open a Pull Request for review before merge.

## Commit Standard
Use clear, explicit commit messages:
- First line: short intent
- Body: what changed, why, and impact
- Mention database/schema/config implications

Example:
`Fix monitoring interval source on index page`

## Pull Requests
- Use the full PR template.
- Describe problem, scope, technical approach, and validation.
- Include screenshots or response samples for UI/API changes.

## Testing Expectations
- Validate main flows manually:
  - IP/Host resolve
  - Add to monitoring
  - Auto-recheck interval behavior
  - Query history updates
  - Language/session persistence
- Run syntax checks for changed PHP files:
`php -l <file>`

## Database Changes
- Never apply schema changes silently.
- Document SQL in a dedicated text file and reference it in PR.

## Security
- Do not commit secrets (`.env`, tokens, credentials).
- Sanitize user input and keep output escaped.

