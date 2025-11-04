# Contributing to Yii2 Scheduling Package

Thank you for considering contributing to this package! This document provides guidelines and instructions for contributors.

## Development Setup

### Prerequisites

- PHP 8.4 or higher
- Composer
- Git

### Installation

```bash
# Clone the repository
git clone https://github.com/horat1us/yii2-scheduling.git
cd yii2-scheduling

# Install dependencies
composer install
```

## Testing

### Run All Tests

```bash
composer test
# or
./vendor/bin/phpunit
```

### Run Specific Test Suites

```bash
# Unit tests only
./vendor/bin/phpunit --testsuite=Unit

# Integration tests only
./vendor/bin/phpunit --testsuite=Integration
```

### Generate Coverage Report

```bash
./vendor/bin/phpunit --coverage-html coverage
```

Coverage reports will be in the `coverage/` directory.

## Code Style

This project follows PSR-12 coding standards.

### Check Code Style

```bash
composer lint
```

### Auto-fix Code Style Issues

```bash
composer phpcbf
```

## Continuous Integration

### GitHub Actions Workflow

The project uses GitHub Actions for CI/CD with three jobs:

1. **Lint** - Checks PSR-12 code style compliance
2. **Test** - Runs the full test suite
3. **Coverage** - Generates and uploads coverage to Codecov

### Setting Up Codecov (For Maintainers)

To enable code coverage reporting:

1. **Sign up at [Codecov](https://codecov.io/)** using your GitHub account

2. **Add the repository** to Codecov

3. **Get your Codecov token** from repository settings

4. **Add the token to GitHub Secrets:**
   - Go to your repository on GitHub
   - Navigate to Settings → Secrets and variables → Actions
   - Click "New repository secret"
   - Name: `CODECOV_TOKEN`
   - Value: Your Codecov token from step 3
   - Click "Add secret"

5. **Verify the setup:**
   - Push a commit or create a PR
   - Check the Actions tab to see the CI workflow run
   - Coverage should be uploaded to Codecov automatically

### Adding New PHP Versions

To test against additional PHP versions:

1. Edit `.github/workflows/ci.yml`
2. Update the matrix strategy in both `lint` and `test` jobs:

```yaml
matrix:
  php: ['8.4', '8.5']  # Add new versions here
```

3. Test locally with the new version if possible
4. Commit and push to see results

## Pull Request Process

1. **Fork the repository** and create a feature branch

2. **Write tests** for your changes
   - All new features must have tests
   - Bug fixes should include regression tests

3. **Ensure tests pass:**
   ```bash
   composer test
   ```

4. **Check code style:**
   ```bash
   composer lint
   ```

5. **Update documentation** if needed
   - Update README.md for new features
   - Add examples where appropriate

6. **Create a pull request:**
   - Provide a clear description of changes
   - Reference any related issues
   - Ensure CI checks pass

7. **Address review feedback** if any

## Commit Message Guidelines

- Use clear, descriptive commit messages
- Start with a verb in imperative mood (Add, Fix, Update, etc.)
- Keep the first line under 72 characters
- Add details in the body if needed

Examples:
```
Add support for weekly cron expressions
Fix timeout handling for parallel commands
Update README with new filter examples
```

## Reporting Issues

When reporting issues, please include:

- PHP version
- Package version
- Minimal code to reproduce
- Expected vs actual behavior
- Error messages or stack traces

## Questions?

If you have questions about contributing, feel free to:
- Open a discussion on GitHub
- Ask in a pull request
- Review existing issues and PRs

Thank you for contributing! 🎉
