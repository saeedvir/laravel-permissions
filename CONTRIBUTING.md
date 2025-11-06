# Contributing to Laravel Permissions

First off, thank you for considering contributing to Laravel Permissions! 🎉

## Code of Conduct

This project and everyone participating in it is governed by respect and professionalism. Please be kind and courteous.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates.

When creating a bug report, include:
- Clear and descriptive title
- Exact steps to reproduce the problem
- Expected behavior vs actual behavior
- Laravel, PHP, and package versions
- Code samples
- Stack traces if applicable

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:
- Use a clear and descriptive title
- Provide a detailed description of the suggested enhancement
- Explain why this enhancement would be useful
- Provide code examples if applicable

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Install dependencies**: `composer install`
3. **Make your changes**
4. **Test your changes** thoroughly
5. **Update documentation** if needed
6. **Commit your changes** with clear commit messages
7. **Push to your fork** and submit a pull request

#### Pull Request Guidelines

- Follow PSR-12 coding standards
- Write clear, descriptive commit messages
- Update the README.md with details of changes if applicable
- Ensure all tests pass
- Add tests for new features
- One feature/fix per pull request

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/laravel-permissions.git
cd laravel-permissions

# Install dependencies
composer install

# Run tests (when available)
composer test
```

## Coding Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Use type hints where possible
- Write docblocks for classes and methods
- Keep methods focused and single-purpose
- Write self-documenting code with clear variable names

## Testing

- Write tests for new features
- Ensure existing tests pass
- Test with multiple Laravel versions if applicable

```bash
composer test
```

## Documentation

- Update README.md for user-facing changes
- Update inline documentation (docblocks)
- Add examples in the documentation
- Keep documentation clear and concise

## Commit Messages

Write clear commit messages:

```
feat: add wildcard permission support
fix: resolve cache flush bug
docs: update installation guide
refactor: improve query performance
test: add tests for role scopes
```

Prefixes:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation
- `refactor:` Code refactoring
- `test:` Adding tests
- `chore:` Maintenance tasks

## Questions?

Feel free to open an issue with your question or contact the maintainers.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

Thank you for your contributions! 🙏
