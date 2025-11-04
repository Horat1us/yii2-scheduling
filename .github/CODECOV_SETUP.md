# Codecov Setup - Quick Guide

## Prerequisites
- GitHub repository with CI/CD workflow
- Codecov account

## Step-by-Step Setup

### 1. Create Codecov Account
Visit [https://codecov.io/](https://codecov.io/) and sign in with GitHub.

### 2. Add Repository
- Click on your profile/organization
- Click "Add new repository"
- Select this repository from the list
- Click "Setup repo"

### 3. Get Upload Token
For private repositories, you need an upload token:

1. Go to repository settings in Codecov
2. Find "Repository Upload Token"
3. Copy the token (looks like: `a1b2c3d4-e5f6-7890-a1b2-c3d4e5f67890`)

### 4. Add Token to GitHub Secrets

1. Go to your GitHub repository
2. Navigate to: **Settings** → **Secrets and variables** → **Actions**
3. Click **"New repository secret"**
4. Enter:
   - **Name:** `CODECOV_TOKEN`
   - **Value:** Paste the token from step 3
5. Click **"Add secret"**

### 5. Verify Setup

Push a commit or create a PR to trigger the CI workflow:

```bash
git commit --allow-empty -m "Test Codecov integration"
git push
```

Then:
1. Go to **Actions** tab in GitHub
2. Watch the "Coverage" job
3. Check for "Upload coverage to Codecov" step
4. Should see: ✅ "Coverage reports uploaded successfully"

### 6. View Coverage Reports

Visit your repository on Codecov:
```
https://codecov.io/gh/horat1us/yii2-scheduling
```

You should see:
- Overall coverage percentage
- Coverage trends
- File-by-file coverage
- PR coverage comparisons

## Configuration

The `.codecov.yml` file controls coverage reporting:

```yaml
coverage:
  range: "70...100"    # Green for 70-100%, yellow/red below

ignore:
  - "tests/**/*"       # Don't count test files
  - "legacy/**/*"      # Ignore legacy code

status:
  project:
    target: auto       # Maintain current coverage
    threshold: 1%      # Allow 1% decrease
```

## Badges

Add badges to README.md:

```markdown
[![codecov](https://codecov.io/gh/horat1us/yii2-scheduling/branch/master/graph/badge.svg)](https://codecov.io/gh/horat1us/yii2-scheduling)
```

## Troubleshooting

### "Error uploading to Codecov"

**Check:**
1. Is `CODECOV_TOKEN` secret set correctly?
2. Does token match this repository?
3. Is repository enabled in Codecov?

**Fix:**
1. Verify secret name is exactly: `CODECOV_TOKEN`
2. Regenerate token in Codecov if needed
3. Update secret in GitHub

### No coverage data visible

**Check:**
1. Is coverage.xml file being generated?
2. Does phpunit.xml have coverage configuration?
3. Are source files being covered by tests?

**Fix:**
1. Run locally: `./vendor/bin/phpunit --coverage-clover=coverage.xml`
2. Check that coverage.xml exists and has data
3. Verify source paths in coverage report

### Coverage shows 0% or incorrect

**Check:**
1. Are paths in .codecov.yml correct?
2. Is coverage being generated with correct paths?
3. Are too many files ignored?

**Fix:**
1. Update `ignore` patterns in .codecov.yml
2. Check that `src/` directory is included
3. Verify coverage locally first

## Environment Variables

In GitHub Actions workflow:

```yaml
- name: Upload coverage to Codecov
  uses: codecov/codecov-action@v4
  with:
    token: ${{ secrets.CODECOV_TOKEN }}  # Required for private repos
    files: ./coverage.xml                 # Coverage report file
    flags: unittests                      # Tag for filtering
    fail_ci_if_error: true               # Fail if upload fails
    verbose: true                         # Show debug output
  env:
    CODECOV_TOKEN: ${{ secrets.CODECOV_TOKEN }}  # Also set in env
```

## Best Practices

1. **Always check coverage locally before pushing:**
   ```bash
   ./vendor/bin/phpunit --coverage-html coverage
   open coverage/index.html
   ```

2. **Aim for high coverage:**
   - 80%+ is good
   - 90%+ is great
   - 100% is not always necessary

3. **Focus on critical code:**
   - Core business logic should have 100%
   - Console commands can have lower coverage
   - Test fixtures don't need coverage

4. **Monitor trends:**
   - Check Codecov after each PR
   - Don't let coverage decrease
   - Fix uncovered code or add tests

5. **Use coverage badges:**
   - Shows project health at a glance
   - Encourages maintaining high coverage

## Resources

- [Codecov Documentation](https://docs.codecov.com/)
- [GitHub Actions Integration](https://docs.codecov.com/docs/github-actions)
- [Coverage Configuration](https://docs.codecov.com/docs/codecov-yaml)
- [Troubleshooting Guide](https://docs.codecov.com/docs/common-errors)

## Support

If you encounter issues:
1. Check [Codecov Status](https://status.codecov.io/)
2. Review [Common Errors](https://docs.codecov.com/docs/common-errors)
3. Contact [Codecov Support](https://codecov.io/support)
