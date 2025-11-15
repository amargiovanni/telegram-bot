---
allowed-tools: Read, Write, Bash(artisan:*), AskUserQuestion
description: Create a new Action class in app/Actions with handle method
argument-hint: [ActionName]
---

# Add Laravel Action

Create a new Action class in `app/Actions` following Laravel conventions.

Action name: $ARGUMENTS

## Requirements

1. **Ask the user these questions using AskUserQuestion:**
   - What parameters should the `handle()` method accept? (Ask for parameter names, types, and if they're optional)
   - What should the `handle()` method return? (Ask for return type: void, bool, Model, array, etc.)
   - Should this action be invokable (implement `__invoke` instead of `handle`)? Default: No
   - Does this action need to interact with the database? (helps determine imports)
   - Should we create a test for this action? Default: Yes

2. **Generate the Action class with:**
   - Proper namespace: `App\Actions`
   - PHP 8.4 constructor property promotion
   - Explicit return type declarations
   - Type hints for all parameters
   - PHPDoc block if the logic is complex or uses array shapes
   - Follow existing Action conventions in the codebase (check sibling files first)

3. **File location:**
   - Single action: `app/Actions/{ActionName}.php`
   - Namespaced action: `app/Actions/{Namespace}/{ActionName}.php` (if user provides namespace like "Users/CreateUser")

4. **After creating the action:**
   - If user wants a test, create it in `tests/Unit/Actions/` or `tests/Feature/Actions/` depending on complexity
   - Use Pest syntax with Arrange/Act/Assert pattern
   - Show the file path with line numbers for key methods

5. **Best Practices:**
   - Keep actions focused on a single responsibility
   - Use dependency injection in constructor
   - Return meaningful values (the created model, boolean success, etc.)
   - Add validation if needed
   - Consider using Form Requests for validation instead of inline validation

## Example Action Structure

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class CreateUserAction
{
    public function handle(string $name, string $email, string $password): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }
}
```

## Example Invokable Action

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Order;

final class ProcessOrderAction
{
    public function __invoke(Order $order): bool
    {
        // Process the order
        $order->update(['status' => 'processed']);

        return true;
    }
}
```

Check for existing Actions in the codebase to follow the same conventions and structure.
