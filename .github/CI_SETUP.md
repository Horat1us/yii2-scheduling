# CI/CD Setup Guide

This document explains how the Continuous Integration and Deployment is configured for this project.

## Overview

The project uses **GitHub Actions** for automated testing, linting, and code coverage reporting. Every push and pull request triggers the CI pipeline.

## Workflow Structure

### File Location
`.github/workflows/ci.yml`

### Jobs

#### 1. Lint Job
**Purpose:** Validate PSR-12 code style compliance

**Steps:**
- Checkout code
- Setup PHP with extensions
- Cache Composer dependencies
- Install dependencies
- Run `composer lint` (PHPCS)

**Matrix:** PHP 8.4

**When it runs:** On every push and PR

#### 2. Test Job
**Purpose:** Run the full test suite

**Steps:**
- Checkout code
- Setup PHP with PCOV extension
- Cache Composer dependencies
- Install dependencies
- Run `composer test` (PHPUnit)

**Matrix:** PHP 8.4

**When it runs:** On every push and PR

#### 3. Coverage Job
**Purpose:** Generate and upload code coverage

**Steps:**
- Checkout code
- Setup PHP with PCOV extension
- Cache Composer dependencies
- Install dependencies
- Generate coverage report (Clover XML format)
- Upload to Codecov

**PHP Version:** 8.4 only (no need to generate coverage multiple times)

**When it runs:** On every push and PR

## Codecov Configuration

### Setup Instructions

1. **Visit [Codecov.io](https://codecov.io/)**
   - Sign in with your GitHub account

2. **Add Repository**
   - Navigate to your organization/account
   - Find and select this repository
   - Click "Setup repo"

3. **Get Token**
   - Copy the repository upload token
   - This is needed for private repositories

4. **Add Secret to GitHub**
   - Go to repository Settings → Secrets and variables → Actions
   - Click "New repository secret"
   - Name: `CODECOV_TOKEN`
   - Paste your token
   - Save

### Codecov Configuration File

**File:** `.codecov.yml`

**Key Settings:**
```yaml
coverage:
  range: "70...100"  # Target coverage range
  precision: 2        # Decimal precision

ignore:
  - "tests/**/*"      # Don't count test files
  - "legacy/**/*"     # Ignore legacy code
  - "vendor/**/*"     # Ignore dependencies
```

## Caching Strategy

### Composer Dependencies

The workflow caches Composer dependencies to speed up builds:

```yaml
- name: Get Composer cache directory
  id: composer-cache
  run: echo "dir=$(composer config cache-files-dir)" >> $GITHUB_OUTPUT

- name: Cache Composer dependencies
  uses: actions/cache@v4
  with:
    path: ${{ steps.composer-cache.outputs.dir }}
    key: ${{ runner.os }}-php-${{ matrix.php }}-composer-${{ hashFiles('**/composer.lock') }}
```

**Cache Key Components:**
- OS (ubuntu-latest)
- PHP version (8.4)
- composer.lock hash (invalidates when dependencies change)

## Adding PHP Versions

To test against multiple PHP versions:

1. **Update the matrix in ci.yml:**

```yaml
strategy:
  fail-fast: false
  matrix:
    php: ['8.4', '8.5']  # Add new versions
```

2. **Apply to both lint and test jobs**

3. **Test locally first** if possible:
```bash
docker run --rm -v $(pwd):/app -w /app php:8.5-cli composer test
```

4. **Commit and push** to see results

## Coverage Thresholds

### Project-level Coverage
- **Target:** Auto (maintains current level)
- **Threshold:** 1% (allows small decreases)

### Patch Coverage
- **Target:** Auto
- **Threshold:** 1%

These settings prevent PRs from significantly reducing coverage.

## Troubleshooting

### Coverage Upload Fails

**Error:** `Error uploading coverage reports`

**Solution:**
1. Verify `CODECOV_TOKEN` is set in GitHub Secrets
2. Check token hasn't expired
3. Ensure token matches the repository

### Cache Not Working

**Error:** Build takes too long, dependencies reinstalled every time

**Solution:**
1. Check `composer.lock` is committed
2. Verify cache key is correct
3. Clear GitHub Actions cache (Settings → Actions → Caches)

### Tests Fail on CI but Pass Locally

**Common causes:**
1. Different PHP versions
2. Missing extensions
3. Environment-specific issues

**Solution:**
1. Check PHP version in workflow matches your local
2. Verify all extensions are listed in workflow
3. Run tests in Docker with same PHP version:
   ```bash
   docker run --rm -v $(pwd):/app -w /app php:8.4-cli composer test
   ```

### PCOV Not Generating Coverage

**Error:** No coverage data generated

**Solution:**
1. Ensure PCOV is enabled in setup-php:
   ```yaml
   coverage: pcov
   ```
2. Verify phpunit.xml has proper coverage configuration
3. Check that source files are included in coverage

## Environment Variables

### Required Secrets
- `CODECOV_TOKEN` - Codecov upload token (for private repos)

### Available in Workflow
- `GITHUB_TOKEN` - Automatically provided by GitHub
- `GITHUB_REPOSITORY` - Repository name
- `GITHUB_SHA` - Commit SHA
- `GITHUB_REF` - Branch/tag reference

## Performance Tips

1. **Use PCOV instead of Xdebug** - Faster coverage generation
2. **Cache Composer dependencies** - Saves ~30-60 seconds per run
3. **Run jobs in parallel** - Lint, test, and coverage run simultaneously
4. **Use fail-fast: false** - Continue testing other versions if one fails

## Monitoring

### View Workflow Runs
- Go to Actions tab in GitHub repository
- Click on CI workflow
- View individual job logs

### View Coverage Reports
- Visit [Codecov dashboard](https://codecov.io/gh/horat1us/yii2-scheduling)
- See coverage trends over time
- Compare coverage between branches/PRs

## Best Practices

1. **Always run tests locally** before pushing
2. **Keep test suite fast** - Currently ~40ms total
3. **Monitor coverage trends** - Don't let it decrease
4. **Fix failing CI immediately** - Don't merge with failing tests
5. **Update dependencies regularly** - Keep composer.lock up to date

## Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Codecov Documentation](https://docs.codecov.com/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PCOV Extension](https://github.com/krakjoe/pcov)
