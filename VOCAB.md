# Laravel Validation Vocabulary for JSON Schema

This document defines a custom JSON Schema vocabulary that implements Laravel's validation rules as custom keywords.

## Vocabulary Metadata

**Location:** `resources/vocabularies/laravel-validation.json`

```json
{
  "$id": "https://faustbrian.github.io/json-schema/vocabularies/laravel-validation",
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$vocabulary": {
    "https://json-schema.org/draft/2020-12/vocab/core": true
  },
  "title": "Laravel Validation Vocabulary",
  "description": "Custom keywords for Laravel-style validation rules"
}
```

## Keyword Definitions

### Booleans

#### `accepted`
**Type:** `boolean`
**Description:** Field must be `true`, `1`, `"1"`, `"yes"`, `"on"`
**Schema:**
```json
{ "accepted": true }
```

#### `acceptedIf`
**Type:** `object`
**Description:** Field must be accepted if another field equals a value
**Schema:**
```json
{
  "type": "object",
  "properties": {
    "field": { "type": "string" },
    "value": {}
  },
  "required": ["field", "value"]
}
```
**Example:**
```json
{
  "terms": { "acceptedIf": { "field": "age", "value": 18 } }
}
```

#### `boolean`
**Type:** `boolean`
**Description:** Field must be boolean-castable: `true`, `false`, `1`, `0`, `"1"`, `"0"`
**Schema:**
```json
{ "boolean": true }
```

#### `declined`
**Type:** `boolean`
**Description:** Field must be `false`, `0`, `"0"`, `"no"`, `"off"`
**Schema:**
```json
{ "declined": true }
```

#### `declinedIf`
**Type:** `object`
**Description:** Field must be declined if another field equals a value
**Schema:** Same as `acceptedIf`

---

### Strings

#### `activeUrl`
**Type:** `boolean`
**Description:** Field must be a valid A or AAAA DNS record
**Schema:**
```json
{ "activeUrl": true }
```

#### `alpha`
**Type:** `boolean`
**Description:** Field must contain only alphabetic characters
**Schema:**
```json
{ "alpha": true }
```

#### `alphaDash`
**Type:** `boolean`
**Description:** Field may contain alpha-numeric characters, dashes, underscores
**Schema:**
```json
{ "alphaDash": true }
```

#### `alphaNumeric`
**Type:** `boolean`
**Description:** Field must contain only alpha-numeric characters
**Schema:**
```json
{ "alphaNumeric": true }
```

#### `ascii`
**Type:** `boolean`
**Description:** Field must contain only 7-bit ASCII characters
**Schema:**
```json
{ "ascii": true }
```

#### `confirmed`
**Type:** `boolean` or `string`
**Description:** Field must have matching `{field}_confirmation` field (or custom suffix)
**Schema:**
```json
{
  "oneOf": [
    { "type": "boolean" },
    { "type": "string", "description": "Custom confirmation field name" }
  ]
}
```
**Example:**
```json
{
  "password": { "confirmed": true },
  "email": { "confirmed": "email_verify" }
}
```

#### `currentPassword`
**Type:** `boolean`
**Description:** Field must match authenticated user's current password
**Schema:**
```json
{ "currentPassword": true }
```

#### `different`
**Type:** `string`
**Description:** Field must differ from another field
**Schema:**
```json
{ "type": "string" }
```
**Example:**
```json
{ "new_password": { "different": "old_password" } }
```

#### `doesntStartWith`
**Type:** `array`
**Description:** Field must not start with any of the given values
**Schema:**
```json
{
  "type": "array",
  "items": { "type": "string" },
  "minItems": 1
}
```

#### `doesntEndWith`
**Type:** `array`
**Description:** Field must not end with any of the given values
**Schema:** Same as `doesntStartWith`

#### `email`
**Type:** `boolean` or `array`
**Description:** Field must be valid email. Optional validators: `rfc`, `strict`, `dns`, `spoof`, `filter`, `filter_unicode`
**Schema:**
```json
{
  "oneOf": [
    { "type": "boolean" },
    {
      "type": "array",
      "items": {
        "type": "string",
        "enum": ["rfc", "strict", "dns", "spoof", "filter", "filter_unicode"]
      }
    }
  ]
}
```

#### `endsWith`
**Type:** `array`
**Description:** Field must end with one of the given values
**Schema:**
```json
{
  "type": "array",
  "items": { "type": "string" },
  "minItems": 1
}
```

#### `enum`
**Type:** `string`
**Description:** Field must be valid enum case (PHP class name)
**Schema:**
```json
{ "type": "string", "description": "Fully qualified enum class name" }
```

#### `hexColor`
**Type:** `boolean`
**Description:** Field must be valid hex color (#RGB, #RRGGBB, #RRGGBBAA)
**Schema:**
```json
{ "hexColor": true }
```

#### `in`
**Type:** `array`
**Description:** Field must be included in the given list of values
**Schema:**
```json
{
  "type": "array",
  "items": {},
  "minItems": 1
}
```

#### `ipAddress`
**Type:** `boolean` or `string`
**Description:** Field must be valid IP address. Optional: `ipv4`, `ipv6`
**Schema:**
```json
{
  "oneOf": [
    { "type": "boolean" },
    { "type": "string", "enum": ["ipv4", "ipv6"] }
  ]
}
```

#### `json`
**Type:** `boolean`
**Description:** Field must be valid JSON string
**Schema:**
```json
{ "json": true }
```

#### `lowercase`
**Type:** `boolean`
**Description:** Field must be lowercase
**Schema:**
```json
{ "lowercase": true }
```

#### `macAddress`
**Type:** `boolean`
**Description:** Field must be valid MAC address
**Schema:**
```json
{ "macAddress": true }
```

#### `notIn`
**Type:** `array`
**Description:** Field must not be included in the given list
**Schema:** Same as `in`

#### `regex`
**Type:** `string`
**Description:** Field must match the regular expression
**Schema:**
```json
{ "type": "string", "pattern": "^/.+/[a-z]*$" }
```

#### `notRegex`
**Type:** `string`
**Description:** Field must not match the regular expression
**Schema:** Same as `regex`

#### `same`
**Type:** `string`
**Description:** Field must match another field
**Schema:**
```json
{ "type": "string" }
```
**Example:**
```json
{ "password_confirmation": { "same": "password" } }
```

#### `startsWith`
**Type:** `array`
**Description:** Field must start with one of the given values
**Schema:** Same as `endsWith`

#### `uppercase`
**Type:** `boolean`
**Description:** Field must be uppercase
**Schema:**
```json
{ "uppercase": true }
```

#### `url`
**Type:** `boolean`
**Description:** Field must be valid URL
**Schema:**
```json
{ "url": true }
```

#### `ulid`
**Type:** `boolean`
**Description:** Field must be valid ULID
**Schema:**
```json
{ "ulid": true }
```

#### `uuid`
**Type:** `boolean`
**Description:** Field must be valid UUID
**Schema:**
```json
{ "uuid": true }
```

---

### Numbers

#### `between`
**Type:** `object`
**Description:** Field must be between min and max (inclusive)
**Schema:**
```json
{
  "type": "object",
  "properties": {
    "min": { "type": "number" },
    "max": { "type": "number" }
  },
  "required": ["min", "max"]
}
```

#### `decimal`
**Type:** `integer` or `object`
**Description:** Field must have specified decimal places
**Schema:**
```json
{
  "oneOf": [
    { "type": "integer", "minimum": 0 },
    {
      "type": "object",
      "properties": {
        "min": { "type": "integer", "minimum": 0 },
        "max": { "type": "integer", "minimum": 0 }
      },
      "required": ["min", "max"]
    }
  ]
}
```
**Example:**
```json
{
  "price": { "decimal": 2 },
  "percentage": { "decimal": { "min": 1, "max": 4 } }
}
```

#### `digits`
**Type:** `integer`
**Description:** Field must be numeric with exact length
**Schema:**
```json
{ "type": "integer", "minimum": 1 }
```

#### `digitsBetween`
**Type:** `object`
**Description:** Field must have length between min and max digits
**Schema:**
```json
{
  "type": "object",
  "properties": {
    "min": { "type": "integer", "minimum": 0 },
    "max": { "type": "integer", "minimum": 0 }
  },
  "required": ["min", "max"]
}
```

#### `greaterThan`
**Type:** `string` or `number`
**Description:** Field must be greater than another field or value
**Schema:**
```json
{
  "oneOf": [
    { "type": "string", "description": "Field name" },
    { "type": "number" }
  ]
}
```

#### `greaterThanOrEqual`
**Type:** Same as `greaterThan`

#### `integer`
**Type:** `boolean`
**Description:** Field must be an integer
**Schema:**
```json
{ "integer": true }
```

#### `lessThan`
**Type:** Same as `greaterThan`

#### `lessThanOrEqual`
**Type:** Same as `greaterThan`

#### `maxDigits`
**Type:** `integer`
**Description:** Field must have at most this many digits
**Schema:**
```json
{ "type": "integer", "minimum": 1 }
```

#### `minDigits`
**Type:** `integer`
**Description:** Field must have at least this many digits
**Schema:**
```json
{ "type": "integer", "minimum": 1 }
```

#### `multipleOf`
**Type:** `number`
**Description:** Field must be a multiple of the given value
**Schema:**
```json
{ "type": "number", "exclusiveMinimum": 0 }
```

#### `numeric`
**Type:** `boolean`
**Description:** Field must be numeric
**Schema:**
```json
{ "numeric": true }
```

---

### Arrays

#### `array`
**Type:** `boolean` or `array`
**Description:** Field must be an array. Optional: specify allowed keys
**Schema:**
```json
{
  "oneOf": [
    { "type": "boolean" },
    {
      "type": "array",
      "items": { "type": "string" },
      "description": "Allowed keys"
    }
  ]
}
```

#### `contains`
**Type:** `array`
**Description:** Array must contain all specified values
**Schema:**
```json
{
  "type": "array",
  "items": {},
  "minItems": 1
}
```

#### `doesntContain`
**Type:** `array`
**Description:** Array must not contain any specified values
**Schema:** Same as `contains`

#### `distinct`
**Type:** `boolean` or `string`
**Description:** Array must not have duplicate values. Optional: `strict`, `ignore_case`
**Schema:**
```json
{
  "oneOf": [
    { "type": "boolean" },
    { "type": "string", "enum": ["strict", "ignore_case"] }
  ]
}
```

#### `inArray`
**Type:** `string`
**Description:** Field must exist in another array field
**Schema:**
```json
{ "type": "string", "description": "Field name containing array" }
```

#### `inArrayKeys`
**Type:** `string`
**Description:** Field must exist in keys of another array field
**Schema:** Same as `inArray`

#### `list`
**Type:** `boolean`
**Description:** Array must have sequential numeric keys starting from 0
**Schema:**
```json
{ "list": true }
```

---

### Dates

#### `after`
**Type:** `string`
**Description:** Date must be after another date field or value
**Schema:**
```json
{ "type": "string" }
```
**Example:**
```json
{
  "end_date": { "after": "start_date" },
  "appointment": { "after": "2024-01-01" }
}
```

#### `afterOrEqual`
**Type:** `string`
**Schema:** Same as `after`

#### `before`
**Type:** `string`
**Schema:** Same as `after`

#### `beforeOrEqual`
**Type:** `string`
**Schema:** Same as `after`

#### `date`
**Type:** `boolean`
**Description:** Field must be valid date per `strtotime()`
**Schema:**
```json
{ "date": true }
```

#### `dateEquals`
**Type:** `string`
**Description:** Date must equal the given date
**Schema:**
```json
{ "type": "string" }
```

#### `dateFormat`
**Type:** `string` or `array`
**Description:** Date must match the given format(s) (PHP date format)
**Schema:**
```json
{
  "oneOf": [
    { "type": "string" },
    {
      "type": "array",
      "items": { "type": "string" },
      "minItems": 1
    }
  ]
}
```

#### `timezone`
**Type:** `boolean`
**Description:** Field must be valid timezone identifier
**Schema:**
```json
{ "timezone": true }
```

---

### Files

> **Note:** File validation keywords are primarily useful when validating multipart form data metadata or file descriptors, not raw JSON.

#### `encoding`
**Type:** `array`
**Description:** File must use specified encoding(s)
**Schema:**
```json
{
  "type": "array",
  "items": { "type": "string" },
  "minItems": 1
}
```

#### `extensions`
**Type:** `array`
**Description:** File must have one of the specified extensions
**Schema:**
```json
{
  "type": "array",
  "items": { "type": "string" },
  "minItems": 1
}
```

#### `file`
**Type:** `boolean`
**Description:** Field must be a successfully uploaded file
**Schema:**
```json
{ "file": true }
```

#### `image`
**Type:** `boolean`
**Description:** File must be an image (jpeg, png, bmp, gif, svg, webp)
**Schema:**
```json
{ "image": true }
```

#### `dimensions`
**Type:** `object`
**Description:** Image must satisfy dimension constraints
**Schema:**
```json
{
  "type": "object",
  "properties": {
    "min_width": { "type": "integer" },
    "max_width": { "type": "integer" },
    "min_height": { "type": "integer" },
    "max_height": { "type": "integer" },
    "width": { "type": "integer" },
    "height": { "type": "integer" },
    "ratio": { "type": "string", "pattern": "^\\d+/\\d+$" }
  }
}
```

#### `mimes`
**Type:** `array`
**Description:** File must match one of the MIME types
**Schema:**
```json
{
  "type": "array",
  "items": { "type": "string" },
  "minItems": 1
}
```

#### `mimetypes`
**Type:** `array`
**Description:** File must match one of the MIME types (by content, not extension)
**Schema:** Same as `mimes`

---

### Database

#### `exists`
**Type:** `object`
**Description:** Field must exist in database table
**Schema:**
```json
{
  "type": "object",
  "properties": {
    "table": { "type": "string" },
    "column": { "type": "string" },
    "where": {
      "type": "object",
      "additionalProperties": {}
    },
    "whereNot": {
      "type": "object",
      "additionalProperties": {}
    }
  },
  "required": ["table"]
}
```
**Example:**
```json
{
  "user_id": {
    "exists": {
      "table": "users",
      "column": "id",
      "where": { "active": true }
    }
  }
}
```

#### `unique`
**Type:** `object`
**Description:** Field must be unique in database table
**Schema:**
```json
{
  "type": "object",
  "properties": {
    "table": { "type": "string" },
    "column": { "type": "string" },
    "ignore": {},
    "ignoreColumn": { "type": "string" },
    "where": {
      "type": "object",
      "additionalProperties": {}
    },
    "whereNot": {
      "type": "object",
      "additionalProperties": {}
    }
  },
  "required": ["table"]
}
```
**Example:**
```json
{
  "email": {
    "unique": {
      "table": "users",
      "ignore": 123,
      "ignoreColumn": "id"
    }
  }
}
```

---

### Utilities - Conditional Presence

#### `required`
**Type:** `boolean`
**Description:** Field must be present and not empty
**Schema:**
```json
{ "required": true }
```

#### `requiredIf`
**Type:** `object`
**Description:** Field required if another field has specific value
**Schema:**
```json
{
  "type": "object",
  "properties": {
    "field": { "type": "string" },
    "value": {}
  },
  "required": ["field", "value"]
}
```
**Example:**
```json
{
  "billing_address": {
    "requiredIf": { "field": "shipping_address", "value": null }
  }
}
```

#### `requiredIfAccepted`
**Type:** `string`
**Description:** Field required if another field is accepted
**Schema:**
```json
{ "type": "string", "description": "Field name" }
```

#### `requiredIfDeclined`
**Type:** `string`
**Description:** Field required if another field is declined
**Schema:** Same as `requiredIfAccepted`

#### `requiredUnless`
**Type:** `object`
**Description:** Field required unless another field has specific value
**Schema:**
```json
{
  "type": "object",
  "properties": {
    "field": { "type": "string" },
    "value": {}
  },
  "required": ["field", "value"]
}
```

#### `requiredWith`
**Type:** `array`
**Description:** Field required if any other specified fields are present
**Schema:**
```json
{
  "type": "array",
  "items": { "type": "string" },
  "minItems": 1
}
```

#### `requiredWithAll`
**Type:** `array`
**Description:** Field required if all other specified fields are present
**Schema:** Same as `requiredWith`

#### `requiredWithout`
**Type:** `array`
**Description:** Field required if any other specified fields are not present
**Schema:** Same as `requiredWith`

#### `requiredWithoutAll`
**Type:** `array`
**Description:** Field required if all other specified fields are not present
**Schema:** Same as `requiredWith`

#### `requiredArrayKeys`
**Type:** `array`
**Description:** Array must contain all specified keys
**Schema:**
```json
{
  "type": "array",
  "items": { "type": "string" },
  "minItems": 1
}
```

#### `present`
**Type:** `boolean`
**Description:** Field must be present (can be empty)
**Schema:**
```json
{ "present": true }
```

#### `presentIf`
**Type:** `object`
**Description:** Field must be present if condition met
**Schema:** Same as `requiredIf`

#### `presentUnless`
**Type:** `object`
**Description:** Field must be present unless condition met
**Schema:** Same as `requiredUnless`

#### `presentWith`
**Type:** `array`
**Description:** Field must be present if any specified fields present
**Schema:** Same as `requiredWith`

#### `presentWithAll`
**Type:** `array`
**Description:** Field must be present if all specified fields present
**Schema:** Same as `requiredWith`

#### `filled`
**Type:** `boolean`
**Description:** Field must be present and not empty
**Schema:**
```json
{ "filled": true }
```

#### `nullable`
**Type:** `boolean`
**Description:** Field may be null
**Schema:**
```json
{ "nullable": true }
```

---

### Utilities - Conditional Prohibition

#### `prohibited`
**Type:** `boolean`
**Description:** Field must not be present
**Schema:**
```json
{ "prohibited": true }
```

#### `prohibitedIf`
**Type:** `object`
**Description:** Field prohibited if condition met
**Schema:** Same as `requiredIf`

#### `prohibitedIfAccepted`
**Type:** `string`
**Description:** Field prohibited if another field is accepted
**Schema:**
```json
{ "type": "string" }
```

#### `prohibitedIfDeclined`
**Type:** `string`
**Description:** Field prohibited if another field is declined
**Schema:** Same as `prohibitedIfAccepted`

#### `prohibitedUnless`
**Type:** `object`
**Description:** Field prohibited unless condition met
**Schema:** Same as `requiredUnless`

#### `prohibits`
**Type:** `array`
**Description:** If field present, specified fields must not be
**Schema:**
```json
{
  "type": "array",
  "items": { "type": "string" },
  "minItems": 1
}
```

---

### Utilities - Conditional Exclusion

#### `exclude`
**Type:** `boolean`
**Description:** Exclude field from validated data
**Schema:**
```json
{ "exclude": true }
```

#### `excludeIf`
**Type:** `object`
**Description:** Exclude field if condition met
**Schema:** Same as `requiredIf`

#### `excludeUnless`
**Type:** `object`
**Description:** Exclude field unless condition met
**Schema:** Same as `requiredUnless`

#### `excludeWith`
**Type:** `string`
**Description:** Exclude field if another field is present
**Schema:**
```json
{ "type": "string" }
```

#### `excludeWithout`
**Type:** `string`
**Description:** Exclude field if another field is not present
**Schema:** Same as `excludeWith`

---

### Utilities - Conditional Presence Check

#### `missing`
**Type:** `boolean`
**Description:** Field must be missing
**Schema:**
```json
{ "missing": true }
```

#### `missingIf`
**Type:** `object`
**Description:** Field must be missing if condition met
**Schema:** Same as `requiredIf`

#### `missingUnless`
**Type:** `object`
**Description:** Field must be missing unless condition met
**Schema:** Same as `requiredUnless`

#### `missingWith`
**Type:** `array`
**Description:** Field must be missing if any specified fields present
**Schema:** Same as `requiredWith`

#### `missingWithAll`
**Type:** `array`
**Description:** Field must be missing if all specified fields present
**Schema:** Same as `requiredWith`

---

### Utilities - Meta

#### `anyOf`
**Type:** `array`
**Description:** At least one of the specified rules must pass
**Schema:**
```json
{
  "type": "array",
  "items": { "type": "object" },
  "minItems": 1
}
```
**Example:**
```json
{
  "identifier": {
    "anyOf": [
      { "email": true },
      { "uuid": true }
    ]
  }
}
```

#### `bail`
**Type:** `boolean`
**Description:** Stop validation on first failure for this field
**Schema:**
```json
{ "bail": true }
```

#### `sometimes`
**Type:** `boolean`
**Description:** Only validate if field is present
**Schema:**
```json
{ "sometimes": true }
```

---

## Usage in Schemas

### Declaring the Vocabulary

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$vocabulary": {
    "https://json-schema.org/draft/2020-12/vocab/core": true,
    "https://json-schema.org/draft/2020-12/vocab/applicator": true,
    "https://json-schema.org/draft/2020-12/vocab/validation": true,
    "https://faustbrian.github.io/json-schema/vocabularies/laravel-validation": true
  }
}
```

### Example Schema

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$vocabulary": {
    "https://json-schema.org/draft/2020-12/vocab/core": true,
    "https://faustbrian.github.io/json-schema/vocabularies/laravel-validation": true
  },
  "type": "object",
  "properties": {
    "email": {
      "type": "string",
      "email": ["rfc", "dns"],
      "unique": {
        "table": "users",
        "column": "email"
      }
    },
    "password": {
      "type": "string",
      "confirmed": true,
      "minDigits": 8,
      "regex": "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)/"
    },
    "password_confirmation": {
      "type": "string",
      "requiredWith": ["password"]
    },
    "accept_terms": {
      "accepted": true
    },
    "newsletter": {
      "type": "boolean",
      "requiredIfAccepted": "accept_terms"
    },
    "role": {
      "type": "string",
      "in": ["user", "admin", "moderator"]
    },
    "phone": {
      "type": "string",
      "requiredIf": { "field": "role", "value": "admin" }
    },
    "avatar": {
      "file": true,
      "image": true,
      "mimes": ["jpg", "png", "webp"],
      "dimensions": {
        "min_width": 100,
        "min_height": 100,
        "max_width": 2000,
        "max_height": 2000
      }
    },
    "tags": {
      "array": true,
      "distinct": true,
      "min": 1,
      "max": 5
    },
    "birth_date": {
      "date": true,
      "dateFormat": "Y-m-d",
      "before": "today"
    }
  }
}
```

## Implementation Notes

### Cross-Field Validation
Keywords like `same`, `different`, `requiredIf`, etc. require access to sibling fields during validation. Implementation must pass parent object context to validators.

### Database Rules
`exists` and `unique` require database connection. Implementation should:
- Support connection configuration via constructor/config
- Allow custom query builders for complex conditions
- Cache results where appropriate

### File Validation
File keywords validate uploaded file metadata/descriptors. When validating JSON:
- Accept file descriptor objects with properties: `name`, `size`, `mime`, `path`
- Or validate metadata separately from actual file upload

### Size/Length Context
`min`, `max`, `size`, `between` apply differently based on field type:
- **String:** Character count
- **Numeric:** Numeric value
- **Array:** Element count
- **File:** Kilobytes

### Conditional Logic Evaluation
Conditional keywords (`*If`, `*Unless`, `*With`, etc.) evaluate conditions at validation time, not schema compile time. Implementation must support dynamic evaluation.

### Bail Behavior
The `bail` keyword affects validator behavior, not schema validation. Implementation should short-circuit remaining validators for the field on first failure.
