# Howdy QB Quick API Reference

## DB Facade — Static Entry Points

| Method | Signature | Returns | Description |
|--------|-----------|---------|-------------|
| `DB::select()` | `(...$columns): SelectInterface` | Query builder | Read records from a table |
| `DB::insert()` | `(string $table, array $data)` | Insert instance | Insert one or more rows |
| `DB::update()` | `(string $table, array $data): UpdateInterface` | Query builder | Update records |
| `DB::delete()` | `(string $table): DeleteInterface` | Query builder | Delete records |
| `DB::create()` | `(string $table): CreateInterface` | Schema builder | Create a new table |
| `DB::alter()` | `(string $table): AlterInterface` | Schema builder | Modify an existing table |
| `DB::drop()` | `(string $table)` | void | Drop a table |
| `DB::dropIfExists()` | `(string $table)` | void | Drop table if it exists |
| `DB::truncate()` | `(string $table)` | void | Remove all rows from a table |
| `DB::raw()` | `(string $sql): string` | string | Raw SQL expression marker |
| `DB::setConnection()` | `(array $configs, string $driver): DB` | DB instance | Manual connection setup |

## SelectInterface Methods

| Method | Chainable | Description |
|--------|-----------|-------------|
| `->columns(...$names)` | ✓ | Select specific columns |
| `->distinct()` | ✓ | Add DISTINCT keyword |
| `->alias(string $name)` | ✓ | Table alias |
| `->from(string $table)` | ✓ | Set the source table |
| `->join(...)` / `->innerJoin(...)` / `->leftJoin(...)` / `->rightJoin(...)` | ✓ | JOIN clauses |
| `->where(...)` / `->andWhere(...)` / `->orWhere(...)` | ✓ | WHERE conditions |
| `->whereNot(...)` / `->andNot(...)` | ✓ | Negative conditions |
| `->whereIn(...)` / `->andIn(...)` | ✓ | IN conditions |
| `->orderBy($col, $dir)` | ✓ | Sorting |
| `->groupBy($col)` | ✓ | Grouping |
| `->limit(int $n)` / `->offset(int $n)` | ✓ | Pagination |
| `->count($col, $alias)` / `->avg(...)` / `->min(...)` / `->max(...)` | ✓ | Aggregates |
| `->raw(string $sql)` | ✓ | Raw SQL fragment |
| `->get()` | **terminal** | Execute & return results (array of assoc arrays) |
| `->getSql()` | **terminal** | Return `['query' => string, 'params' => array]` |

## CreateInterface Methods

Chain: `DB::create('table')->column('name')->type()->constraint()->...->execute()`

| Method | Chainable | Description |
|--------|-----------|-------------|
| `->column(string $name)` | ✓ | Start defining a column |
| `->int($size)` / `->bigInt($size)` / `->double()` / `->boolean()` / `->string($size)` / `->text($size)` / `->longText()` / `->json()` / `->date()` / `->dateTime()` / `->timestamp(...)` / `->decimal($p,$s)` / `->float()` / `->enum([...])` | ✓ | Column types |
| `->required()` / `->nullable()` / `->unsigned()` / `->autoIncrement()` / `->default($val)` / `->primary()` / `->unique()` / `->index([...])` | ✓ | Constraints |
| `->foreignKey($col, 'ref_table.ref_col', $onDelete)` | ✓ | Foreign key |
| `->onDelete($action)` | ✓ | Separate FK on-delete action |
| `->execute()` | **terminal** | Run CREATE TABLE |
| `->getSql()` | **terminal** | Return raw SQL |

## AlterInterface Methods

| Method | Chainable | Description |
|--------|-----------|-------------|
| `->add(string $col)` | ✓ | Start ADD COLUMN |
| `->modify(string $old, string $new)` | ✓ | Start MODIFY COLUMN |
| `->drop(string $col)` | ✓ | DROP COLUMN (terminal) |
| `->int(...)` ... (all type methods) | ✓ | Column type after add/modify |
| `->foreignKey(...)` / `->onDelete(...)` | ✓ | Foreign key via ALTER |
| `->execute()` | **terminal** | Run ALTER TABLE |
| `->getSql()` | **terminal** | Return raw SQL |

## Insert Methods

| Method | Chainable | Description |
|--------|-----------|-------------|
| `->ignoreDuplicates()` | ✓ | Use INSERT IGNORE |
| `->select(...)` | ✓ | Convert to INSERT...SELECT |
| `->execute()` | **terminal** | Run INSERT |
| `->getSql()` | **terminal** | Return raw SQL |

## Driver Support

| Driver | When used | Connection source |
|--------|-----------|-------------------|
| `pdo` (default) | Default/standalone | `wp-config.php` constants or manual config |
| `wpdb` | Fallback in WP context | `global $wpdb` |

## Table Prefix

All table names passed to Howdy QB are **automatically prefixed** with the WordPress table prefix (e.g., `wp_`). Do NOT include the prefix in table name arguments.
