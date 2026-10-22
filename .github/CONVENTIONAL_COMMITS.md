# Conventional Commits Guide

We follow [Conventional Commits](https://www.conventionalcommits.org/) for our commit messages.

## Format

```
<type>(<optional scope>): <short summary>

<body>

<footer>
```

## Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only changes |
| `style` | Changes that do not affect the meaning of the code |
| `refactor` | Code change that neither fixes a bug nor adds a feature |
| `perf` | Performance improvement |
| `test` | Adding tests or correcting existing tests |
| `build` | Changes to the build system or dependencies |
| `ci` | Changes to CI configuration files |
| `chore` | Other changes that don't modify src or test files |
| `security` | Security fixes |

## Scopes

| Scope | Area |
|-------|------|
| `social` | Social media module |
| `campaign` | Campaign module |
| `client` | Client CRM module |
| `invoice` | Invoice module |
| `ai` | AI Content Studio |
| `workflow` | Workflow automation |
| `webhook` | Webhook system |
| `form` | Form builder |
| `page` | Landing pages |
| `auth` | Authentication/authorization |
| `api` | API endpoints |
| `dashboard` | Dashboard |
| `ui` | User interface |
| `test` | Test suite |
| `config` | Configuration |
| `version` | Version/changelog system |

## Examples

```
feat(social): add bulk scheduling for posts

feat(ai): implement hashtag generation endpoint

fix(campaign): correct status validation in store method

security(auth): add rate limiting to login

docs(readme): update installation instructions

refactor(database): optimize query performance

test(client): add cross-agency isolation tests

chore(release): bump version to 1.1.0
```
