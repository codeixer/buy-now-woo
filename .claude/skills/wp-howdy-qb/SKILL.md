---
name: wp-howdy-qb
description: Use when writing database queries with the Howdy QB fluent query builder — creating tables, selecting/inserting/updating/deleting records, altering schemas, using PDO or wpdb drivers, subqueries, joins, aggregates, raw SQL, or building custom table CRUD in WordPress plugins via the CodesVault\Howdyqb library.
---

# Howdy QB — Fluent MySQL Query Builder for WordPress

A zero-dependency fluent query builder for WordPress that wraps PDO (default) or `$wpdb` (optional). Provides a Laravel-like chainable API for `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE TABLE`, and `ALTER TABLE` queries with automatic table prefixing, prepared statements, SQL injection protection via identifier validation, and subquery support.

Official docs: <https://wpqb.abmsourav.com>  
GitHub: <https://github.com/CodesVault/howdy_qb>

## When to use

- "Create a custom DB table for my plugin", "build a table with foreign keys".
- "Select records with joins and where clauses", "query with pagination".
- "Insert/update/delete records in a custom table".
- "Alter a table to add or modify columns".
- "Use a query builder instead of raw $wpdb SQL".
- "Run raw SQL with proper prepared statements".
- "Build subqueries for complex where conditions".
- "Use aggregate functions (COUNT, AVG, MIN, MAX)".

**Not for:** Core WP table queries using `$wpdb` directly — use `wp-database`. Complex WooCommerce order queries — use `wp-woocommerce`. Simple meta/option CRUD — use existing WP APIs.

## Installation

### Via Composer (recommended)

```bash
composer require codesvault/howdy-qb
```

### Manual setup (no Composer)

If your plugin doesn't use Composer, copy the `src/` directory into your plugin and add a PSR-4 autoloader:

```php
spl_autoload_register( function ( $class ) {
    $prefix = 'CodesVault\\Howdyqb\\';
    $base_dir = __DIR__ . '/vendor/howdy-qb/src/';
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) return;
    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
    if ( file_exists( $file ) ) require $file;
} );
```

## Method

### 1. Setup & connection

The `DB` facade extends `QueryFactory` and provides all entry points as static methods. Connection is automatically configured from WordPress's `wp-config.php` constants by default.

#### Auto-detect (WordPress environment)

```php
use CodesVault\Howdyqb\DB;

// Uses wpdb connection constants automatically (DB_HOST, DB_NAME, DB_USER, DB_PASSWORD)
// Default driver is 'pdo'
DB::select('id', 'name')->from('users')->get();
```

#### wpdb driver

```php
use CodesVault\Howdyqb\DB;

// Force wpdb (MySQLi) driver instead of PDO
// Call once at plugin init — sets the driver for all subsequent DB calls
```

The driver is set at the class level. To use wpdb, you subclass or call `QueryFactory::setConnection()`:

```php
// wpdb is used automatically when PDO connection fails or is unavailable
// The DB class defaults to 'pdo' but checks for wpdb at execution time
```

#### Manual connection (non-WordPress / custom credentials)

```php
use CodesVault\Howdyqb\DB;

// Load from wp-config.php or provide manually
$db = DB::setConnection([
    'dbhost'     => 'localhost',
    'dbname'     => 'mydb',
    'dbuser'     => 'root',
    'dbpassword' => 'secret',
    'prefix'     => 'wp_',
], 'pdo'); // or 'wpdb'
```

### 2. SELECT

The fluent SELECT API returns results as associative arrays.

#### Basic select

```php
$users = DB::select('id', 'name', 'email')
    ->from('users')
    ->get();
// Returns: [ ['id' => 1, 'name' => '...', 'email' => '...'], ... ]
```

#### Select all columns

```php
$users = DB::select()
    ->from('users')
    ->get();
// Equivalent to SELECT * FROM wp_users
```

#### SELECT with alias

```php
$users = DB::select('id', 'name')
    ->from('users u')  // space-separated alias
    ->get();

// Or use alias() method:
$users = DB::select('id', 'name')
    ->from('users')
    ->alias('u')
    ->get();
```

#### DISTINCT

```php
$roles = DB::select('role')
    ->distinct()
    ->from('usermeta')
    ->get();
```

#### WHERE clauses

```php
// Basic where
DB::select()->from('orders')
    ->where('status', '=', 'completed')
    ->get();

// AND where
DB::select()->from('orders')
    ->where('status', '=', 'completed')
    ->andWhere('total', '>', 100)
    ->get();

// OR where
DB::select()->from('orders')
    ->where('status', '=', 'pending')
    ->orWhere('status', '=', 'on-hold')
    ->get();

// WHERE NOT
DB::select()->from('orders')
    ->whereNot('status', '=', 'cancelled')
    ->get();

// WHERE IN
DB::select()->from('orders')
    ->whereIn('status', 'completed', 'processing')
    ->get();

// AND IN
DB::select()->from('orders')
    ->where('total', '>', 50)
    ->andIn('status', 'completed', 'processing')
    ->get();

// Supported operators: =, !=, <>, <, >, <=, >=, LIKE, NOT LIKE,
//   IN, NOT IN, BETWEEN, NOT BETWEEN, IS, IS NOT,
//   REGEXP, NOT REGEXP, EXISTS, NOT EXISTS
```

#### JOINs

```php
// Basic JOIN (INNER by default)
DB::select('o.id', 'o.total', 'u.name')
    ->from('orders o')
    ->join('users u', 'o.user_id', 'u.id')
    ->get();

// INNER JOIN
DB::select()
    ->from('orders')
    ->innerJoin('order_items', 'orders.id', 'order_items.order_id')
    ->get();

// LEFT JOIN
DB::select()
    ->from('orders')
    ->leftJoin('payments', 'orders.id', 'payments.order_id')
    ->get();

// RIGHT JOIN
DB::select()
    ->from('orders')
    ->rightJoin('affiliates', 'orders.affiliate_id', 'affiliates.id')
    ->get();

// Multi-table JOIN
DB::select()
    ->from('orders')
    ->join(['orders o', 'users u'], 'o.user_id', 'u.id')
    ->get();
```

#### ORDER BY

```php
// Single column
DB::select()->from('orders')
    ->orderBy('created_at', 'DESC')
    ->get();

// Multiple columns (array syntax)
DB::select()->from('orders')
    ->orderBy(['status' => 'ASC', 'created_at' => 'DESC'])
    ->get();
```

#### GROUP BY

```php
DB::select('status', DB::raw('COUNT(*) as count'))
    ->from('orders')
    ->groupBy('status')
    ->get();
```

#### LIMIT & OFFSET

```php
DB::select()->from('orders')
    ->limit(10)
    ->offset(20)
    ->get();
```

#### Aggregates

```php
// COUNT
DB::select()
    ->from('orders')
    ->count('id', 'total_orders')
    ->get();

// AVG
DB::select()
    ->from('orders')
    ->avg('total', 'avg_order')
    ->get();

// MIN
DB::select()
    ->from('orders')
    ->min('total', 'min_order')
    ->get();

// MAX
DB::select()
    ->from('orders')
    ->max('total', 'max_order')
    ->get();
```

#### RAW SQL expressions

```php
DB::select()
    ->from('orders')
    ->raw("WHERE DATE(created_at) = CURDATE()")
    ->get();
```

#### Subqueries (WHERE)

```php
DB::select('id', 'name')
    ->from('users')
    ->where('id', 'IN', function ($query) {
        $query->select('user_id')
            ->from('orders')
            ->where('total', '>', 100);
    })
    ->get();
```

#### Subqueries (SELECT column)

```php
DB::select(
    'id',
    'name',
    function ($query) {
        $query->select('COUNT(id)')
            ->from('orders')
            ->alias('order_count')
            ->where('user_id', '=', DB::raw('users.id'));
    }
)
    ->from('users')
    ->get();
```

#### Get SQL only (no execution)

```php
$sql = DB::select('id', 'name')
    ->from('users')
    ->where('status', '=', 'active')
    ->getSql();
// Returns: ['query' => 'SELECT `id`, `name` FROM wp_users WHERE `status` = ?', 'params' => ['active']]
```

### 3. INSERT

#### Single row

```php
DB::insert('orders', [
    ['user_id' => 1, 'total' => 99.99, 'status' => 'pending'],
]);
// Automatically prepared — values are parameterised
```

#### Multiple rows

```php
DB::insert('orders', [
    ['user_id' => 1, 'total' => 99.99, 'status' => 'pending'],
    ['user_id' => 2, 'total' => 49.50, 'status' => 'completed'],
    ['user_id' => 3, 'total' => 25.00, 'status' => 'refunded'],
]);
```

#### INSERT IGNORE (skip duplicates)

```php
DB::insert('logs', [
    ['event' => 'login', 'user_id' => 1],
])->ignoreDuplicates()
  ->execute();
```

#### INSERT...SELECT

```php
DB::insert('archived_orders', ['id', 'total', 'status'])
    ->select('id', 'total', 'status')
    ->from('orders')
    ->where('created_at', '<', '2024-01-01')
    ->execute();
```

### 4. UPDATE

```php
DB::update('orders', [
    'status' => 'completed',
    'updated_at' => current_time('mysql'),
])
    ->where('id', '=', 42)
    ->execute();
```

### 5. DELETE

```php
// Delete with condition
DB::delete('logs')
    ->where('created_at', '<', '2024-01-01')
    ->execute();
```

### 6. CREATE TABLE

Fluent table schema builder with automatic `$wpdb->prefix`.

```php
DB::create('products')
    ->column('id')->bigInt()->unsigned()->autoIncrement()->primary()
    ->column('name')->string(200)->required()
    ->column('slug')->string(200)->required()->unique()
    ->column('description')->longText()->nullable()
    ->column('price')->decimal(10, 2)->required()->default(0.00)
    ->column('stock')->int(10)->unsigned()->default(0)
    ->column('status')->enum(['draft', 'published', 'archived'])->default('draft')
    ->column('category_id')->bigInt()->unsigned()->nullable()
    ->column('created_at')->timestamp('now')
    ->column('updated_at')->timestamp(null, 'current')
    ->foreignKey('category_id', 'categories.id', 'CASCADE')
    ->execute();
```

#### All column types

| Method | SQL Type | Notes |
|--------|----------|-------|
| `->int(255)` | `INT(size)` | Default 255 |
| `->bigInt(255)` | `BIGINT(size)` | Default 255 |
| `->double()` | `DOUBLE` | |
| `->boolean()` | `BOOLEAN` | |
| `->string(255)` | `VARCHAR(size)` | Default 255 |
| `->text(10000)` | `TEXT(size)` | Default 10000 |
| `->longText()` | `LONGTEXT` | |
| `->json()` | `JSON` | |
| `->date()` | `DATE` | |
| `->dateTime()` | `DATETIME` | |
| `->timestamp(default, onUpdate)` | `TIMESTAMP` | Pass `'now'` for CURRENT_TIMESTAMP |
| `->decimal(8, 2)` | `DECIMAL(p, s)` | Default 8, 2 |
| `->float()` | `FLOAT` | |
| `->enum(['a', 'b'])` | `ENUM(...)` | |

#### Column constraints

| Method | Effect |
|--------|--------|
| `->required()` | `NOT NULL` |
| `->nullable()` | `DEFAULT NULL` |
| `->unsigned()` | `UNSIGNED` (for numeric) |
| `->autoIncrement()` | `AUTO_INCREMENT` |
| `->default($value)` | `DEFAULT value` |
| `->primary()` | Single-column `PRIMARY KEY` |
| `->unique()` | `UNIQUE` constraint (composite OK) |
| `->index(['col1', 'col2'])` | Multi-column `INDEX` |

#### Foreign keys

```php
DB::create('order_items')
    ->column('id')->bigInt()->unsigned()->autoIncrement()->primary()
    ->column('order_id')->bigInt()->unsigned()
    ->column('product_id')->bigInt()->unsigned()
    // Single column reference: 'other_table.column'
    ->foreignKey('order_id', 'orders.id', 'CASCADE')
    ->foreignKey('product_id', 'products.id', 'CASCADE')
    ->execute();

// onDelete can be chained separately:
->foreignKey('user_id', 'users.id')
->onDelete('CASCADE')
```

#### Primary key on multiple columns

```php
DB::create('product_tag_relations')
    ->column('product_id')->bigInt()->unsigned()
    ->column('tag_id')->bigInt()->unsigned()
    ->primary(['product_id', 'tag_id'])
    ->execute();
```

### 7. ALTER TABLE

#### ADD column

```php
DB::alter('products')
    ->add('discount_price')->decimal(10, 2)->nullable()
    ->execute();
```

#### MODIFY column

```php
DB::alter('products')
    ->modify('price', 'new_price')->decimal(12, 2)->required()
    ->execute();
```

#### DROP column

```php
DB::alter('products')
    ->drop('old_column')
    ->execute();
```

#### ADD foreign key via ALTER

```php
DB::alter('orders')
    ->add('affiliate_id')->bigInt()->unsigned()
    ->foreignKey('affiliate_id', 'affiliates.id')
    ->onDelete('SET NULL')
    ->execute();
```

### 8. TABLE operations

```php
// Drop table
DB::drop('old_logs');

// Drop if exists
DB::dropIfExists('temporary_data');

// Truncate (remove all rows)
DB::truncate('session_logs');
```

### 9. Get raw SQL

Every statement type has a `getSql()` method that returns the generated query string and params without executing:

```php
$sql = DB::select('id')->from('users')->where('id', '=', 1)->getSql();
// ['query' => 'SELECT `id` FROM wp_users WHERE `id` = ?', 'params' => [1]]

$sql = DB::create('test')->column('id')->int()->getSql();
// ['query' => 'CREATE TABLE IF NOT EXISTS wp_test ( `id` INT(255) )']

$sql = DB::delete('logs')->where('id', '=', 5)->getSql();
// ['query' => 'DELETE FROM wp_logs WHERE `id` = ?', 'params' => [5]]
```

### 10. Raw expressions in fluent API

Use `DB::raw()` anywhere a raw SQL string is needed (column select, where values, etc.):

```php
DB::select(DB::raw("DISTINCT DATE(created_at) as day"))
    ->from('orders')
    ->where(DB::raw("YEAR(created_at)"), '=', date('Y'))
    ->get();
```

### 11. Subqueries in WHERE IN

```php
DB::select('id', 'name')
    ->from('users')
    ->whereIn('id', function ($query) {
        $query->select('user_id')
            ->from('orders')
            ->where('total', '>', 500);
    })
    ->get();
```

### 12. Error handling

All database errors throw exceptions via `Utilities::throughException()`. In CLI contexts, exceptions are thrown directly. In WordPress admin contexts, formatted error HTML is output before the exception.

```php
try {
    DB::select()->from('nonexistent_table')->get();
} catch (\Exception $e) {
    error_log('Howdy QB error: ' . $e->getMessage());
    // handle gracefully
}
```

## Notes

- **Automatic table prefixing** — Howdy QB automatically prepends the `$wpdb->prefix` (e.g., `wp_`) to all table names. Do NOT include the prefix in table names passed to `from()`, `insert()`, `create()`, etc. Exception: `DB::raw()` expressions receive the raw string as-is.
- **Two drivers** — PDO (default) and `wpdb` (MySQLi). PDO is preferred for standalone/CLI use. The `wpdb` driver is useful in WordPress admin contexts where PDO extensions may not be available.
- **SQL injection protection** — All column names and table names pass through `IdentifierValidator` which rejects names containing dangerous patterns (SQL comments, OR/AND/UNION/DROP/DELETE/INSERT/UPDATE keywords, hex literals, quotes, semicolons). Values are always parameterised via prepared statements.
- **Placeholder differences** — PDO uses `?` placeholders, wpdb uses `%s`/`%d`/`%f`. The library handles this automatically via `Utilities::get_placeholder()`.
- **Call `->execute()`** for INSERT, UPDATE, DELETE, CREATE, and ALTER operations to run the query. SELECT queries use `->get()` to run and return results.
- **`->getSql()` returns an array** — `['query' => string, 'params' => array]`, not a plain string. Use `getSql()['query']` if you only need the SQL string.
- **`->get()` returns an array of associative arrays** — not objects or stdClass. Each row is `['column' => value]`.
- **ENUM values** — strings are auto-quoted, numbers are not. Pass string values for quoted ENUM members.
- **Foreign keys in CREATE** — Pass `'other_table.column'` as the second argument. The prefix is automatically prepended to the referenced table.
- **`ALTER` add is idempotent** — if the column already exists, `->execute()` is a no-op. The library checks with `DESCRIBE` before applying.
- **INSERT returns the number of affected rows** via the underlying driver; use the return value of `->execute()` for result checking.
