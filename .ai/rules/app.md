---
paths:
  - 'app/**'
---

# App

## PHP forbids argument unpacking after named arguments
PHP throws "Cannot use argument unpacking after named arguments" (fatal) when a call mixes named args with a trailing `...$spread`. Don't combine variadic constructors with named arguments. If a DTO needs to receive a list, use an explicit `array $items` parameter and pass it via a named arg (`items: $array`) instead of `Field ...$fields` + `...$fields`.
