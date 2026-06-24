# Howdy QB Plugin Integration Patterns

Real-world patterns for integrating Howdy QB into a WordPress plugin — activation, uninstall, versioned schema, and common CRUD patterns.

## 1. Plugin activation — create tables

Use `register_activation_hook` to create tables when the plugin is activated:

```php
// In your main plugin file
use CodesVault\Howdyqb\DB;

register_activation_hook( __FILE__, 'my_plugin_activate_tables' );

function my_plugin_activate_tables() {
    // Howdy QB auto-detects WordPress connection from wp-config.php constants

    // Table 1: submissions
    DB::create('submissions')
        ->column('id')->bigInt()->unsigned()->autoIncrement()->primary()
        ->column('form_id')->bigInt()->unsigned()->required()
        ->column('user_id')->bigInt()->unsigned()->nullable()
        ->column('data')->longText()->nullable()
        ->column('status')->enum(['pending', 'approved', 'rejected'])->default('pending')
        ->column('created_at')->timestamp('now')
        ->column('updated_at')->timestamp(null, 'current')
        ->execute();

    // Table 2: submission_meta
    DB::create('submission_meta')
        ->column('meta_id')->bigInt()->unsigned()->autoIncrement()->primary()
        ->column('submission_id')->bigInt()->unsigned()->required()
        ->column('meta_key')->string(255)->required()->index(['meta_key'])
        ->column('meta_value')->longText()->nullable()
        ->foreignKey('submission_id', 'submissions.id', 'CASCADE')
        ->execute();
}
```

## 2. Versioned schema upgrades

Track schema version to run one-time migrations on update:

```php
define( 'MY_PLUGIN_DB_VERSION', '1.2.0' );

function my_plugin_maybe_upgrade_db() {
    $installed = get_option( 'my_plugin_db_version', '0' );
    if ( version_compare( $installed, MY_PLUGIN_DB_VERSION, '>=' ) ) {
        return;
    }

    // 1.2.0: add new column
    if ( version_compare( $installed, '1.2.0', '<' ) ) {
        DB::alter('submissions')
            ->add('reviewed_by')->bigInt()->unsigned()->nullable()
            ->execute();
    }

    update_option( 'my_plugin_db_version', MY_PLUGIN_DB_VERSION );
}
add_action( 'plugins_loaded', 'my_plugin_maybe_upgrade_db' );
```

Run on `plugins_loaded` (every request) — not just activation — so it catches updates from auto-update or version-switching.

## 3. Plugin uninstall — drop tables

```php
// uninstall.php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

use CodesVault\Howdyqb\DB;

global $wpdb;

if ( is_multisite() ) {
    $sites = get_sites( [ 'number' => 0, 'fields' => 'ids' ] );
    foreach ( $sites as $site_id ) {
        switch_to_blog( $site_id );
        DB::dropIfExists( 'submissions' );
        DB::dropIfExists( 'submission_meta' );
        delete_option( 'my_plugin_db_version' );
        restore_current_blog();
    }
} else {
    DB::dropIfExists( 'submissions' );
    DB::dropIfExists( 'submission_meta' );
    delete_option( 'my_plugin_db_version' );
}
```

## 4. Paginated list with search

```php
function my_plugin_get_submissions( array $args = [] ) {
    $defaults = [
        'search'   => '',
        'status'   => '',
        'page'     => 1,
        'per_page' => 20,
    ];
    $args = wp_parse_args( $args, $defaults );

    // Count query
    $count_query = DB::select()
        ->from('submissions')
        ->count('id', 'total');

    if ( $args['status'] ) {
        $count_query->where('status', '=', $args['status']);
    }
    if ( $args['search'] ) {
        $count_query->andWhere('data', 'LIKE', '%' . $args['search'] . '%');
    }

    $total = (int) $count_query->get()[0]['total'] ?? 0;

    // Data query
    $offset = ( $args['page'] - 1 ) * $args['per_page'];
    $query = DB::select('id', 'form_id', 'user_id', 'status', 'created_at')
        ->from('submissions')
        ->orderBy('created_at', 'DESC')
        ->limit( $args['per_page'] )
        ->offset( $offset );

    if ( $args['status'] ) {
        $query->where('status', '=', $args['status']);
    }
    if ( $args['search'] ) {
        $query->andWhere('data', 'LIKE', '%' . $args['search'] . '%');
    }

    return [
        'items'      => $query->get(),
        'total'      => $total,
        'pages'      => (int) ceil( $total / $args['per_page'] ),
        'page'       => $args['page'],
        'per_page'   => $args['per_page'],
    ];
}
```

## 5. Batch insert for imports

```php
function my_plugin_import_submissions( array $records ) {
    // Howdy QB handles multi-row INSERT with prepared statements
    DB::insert('submissions', $records)->execute();
}
```

## 6. Upsert pattern (INSERT IGNORE + update fallback)

```php
function my_plugin_upsert_log( string $event, int $user_id, array $data = [] ) {
    // Check if exists
    $existing = DB::select('id')
        ->from('event_logs')
        ->where('event', '=', $event)
        ->andWhere('user_id', '=', $user_id)
        ->get();

    if ( ! empty( $existing ) ) {
        DB::update('event_logs', [
            'data'       => wp_json_encode( $data ),
            'updated_at' => current_time( 'mysql' ),
        ])
            ->where('id', '=', $existing[0]['id'])
            ->execute();
        return $existing[0]['id'];
    }

    DB::insert('event_logs', [[
        'event'      => $event,
        'user_id'    => $user_id,
        'data'       => wp_json_encode( $data ),
        'created_at' => current_time( 'mysql' ),
    ]])->execute();
}
```

## 7. Multi-table JOIN for reports

```php
function my_plugin_get_dashboard_stats( int $days = 30 ) {
    $since = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

    return DB::select(
        's.form_id',
        DB::raw('COUNT(s.id) as total'),
        DB::raw('AVG(LENGTH(s.data)) as avg_size')
    )
        ->from('submissions s')
        ->leftJoin('submission_meta sm', 's.id', 'sm.submission_id')
        ->where('s.created_at', '>=', $since)
        ->groupBy('s.form_id')
        ->orderBy('total', 'DESC')
        ->get();
}
```
