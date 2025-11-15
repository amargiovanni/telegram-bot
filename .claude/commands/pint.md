---
allowed-tools: Bash(vendor:*)
description: Format code with Laravel Pint
argument-hint: [--dirty|--test|path]
---

# Laravel Pint Code Formatter

Format code using Laravel Pint following the project's style conventions.

Arguments: $ARGUMENTS

## Instructions

1. **Determine what to format:**
   - If `$ARGUMENTS` contains `--dirty`: Run `vendor/bin/pint --dirty` (only modified files)
   - If `$ARGUMENTS` contains `--test`: Run `vendor/bin/pint --test` (check without fixing)
   - If `$ARGUMENTS` contains a path: Run `vendor/bin/pint {path}`
   - If no arguments: Run `vendor/bin/pint` (format all files)

2. **Execute the command:**
   - Run the appropriate Pint command
   - Show the output to the user
   - If there are errors, explain what needs to be fixed

3. **After formatting:**
   - Summarize what was formatted
   - If using `--test`, indicate if the code passes style checks
   - Suggest running tests if significant changes were made

## Default Behavior

According to project guidelines:
- **Always run `vendor/bin/pint --dirty` before finalizing changes**
- Do NOT run `--test`, simply run Pint to fix formatting issues
- This ensures code matches the project's expected style

## Examples

- `/pint` - Format all files
- `/pint --dirty` - Format only modified files (recommended)
- `/pint app/Actions` - Format specific directory
- `/pint --test` - Check style without fixing
