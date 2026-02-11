# Contributing to Koomky CRM

Thank you for your interest in contributing to Koomky CRM!

## Development Setup

1. Fork the repository
2. Clone your fork: `git clone https://github.com/YOUR_USERNAME/admin_koomky.git`
3. Navigate to the project: `cd admin_koomky`
4. Install dependencies:
   - Backend: `cd backend && composer install`
   - Frontend: `cd frontend && pnpm install`
5. Start Docker services: `make up`
6. Run database migrations: `make migrate`
7. Start development servers:
   - Backend: `cd backend && php artisan serve`
   - Frontend: `cd frontend && pnpm run dev`

## Code Style

- **Backend**: Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Run `vendor/bin/pint` to auto-fix code style issues
- Run `vendor/bin/phpstan analyse` for static analysis

- **Frontend**: Follow ESLint and Prettier configurations
- Run `pnpm lint` to check code style
- Run `pnpm format` to auto-fix code style issues

## Testing

Run tests before committing changes:

```bash
# Backend tests (Pest)
cd backend && vendor/bin/pest

# Frontend tests (Vitest)
cd frontend && pnpm vitest run

# E2E tests (Playwright)
cd frontend && pnpm playwright test
```

## Commit Messages

Follow conventional commits format:
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `refactor:` - Code refactoring
- `test:` - Test updates
- `chore:` - Maintenance tasks

Example: `feat: add client export to CSV functionality`

## Pull Requests

1. Keep PRs focused and small
2. Link to related issues
3. Add screenshots for UI changes
4. Ensure all tests pass
5. Maintain 80%+ code coverage

## Questions?

Open an issue for bugs or feature requests.
