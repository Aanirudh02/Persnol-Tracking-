<?php

/**
 * Windows-native MySQL -> PostgreSQL importer for LifeTracker.
 *
 * Dry run:
 *   php tools/migrate_mysql_to_supabase.php --source-password=... --target-password=...
 * Apply:
 *   php tools/migrate_mysql_to_supabase.php --apply --source-password=... --target-password=...
 *
 * Prefer transient environment variables or direct terminal input. Never commit passwords.
 */

declare(strict_types=1);

$options = getopt('', [
    'apply',
    'source-host::',
    'source-port::',
    'source-db::',
    'source-user::',
    'source-password::',
    'target-host:',
    'target-port::',
    'target-db::',
    'target-user:',
    'target-password::',
    'batch-size::',
    'debug',
]);

if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
    echo <<<'HELP'
Windows-native MySQL to Supabase PostgreSQL importer

Dry run:
    php tools/migrate_mysql_to_supabase.php --target-host=HOST --target-user=USER

Apply after reviewing the dry-run counts:
    php tools/migrate_mysql_to_supabase.php --apply --target-host=HOST --target-user=USER

The script prompts for both passwords. Do not pass passwords as command-line arguments,
because command history and process listings can expose them.

Optional source settings:
    --source-host=127.0.0.1 --source-port=3306 --source-db=my_tracker --source-user=root

Optional target settings:
    --target-port=5432 --target-db=postgres --batch-size=200
HELP;
    exit(0);
}

$sourcePassword = (string) ($options['source-password'] ?? getenv('SOURCE_DB_PASSWORD') ?: '');
$targetPassword = (string) ($options['target-password'] ?? getenv('TARGET_DB_PASSWORD') ?: '');

if ($sourcePassword === '') {
    $sourcePassword = prompt('Local MySQL password');
}
if ($targetPassword === '') {
    $targetPassword = prompt('Supabase PostgreSQL password');
}

$source = [
    'host' => (string) ($options['source-host'] ?? getenv('SOURCE_DB_HOST') ?: '127.0.0.1'),
    'port' => (string) ($options['source-port'] ?? getenv('SOURCE_DB_PORT') ?: '3306'),
    'database' => (string) ($options['source-db'] ?? getenv('SOURCE_DB_DATABASE') ?: 'my_tracker'),
    'username' => (string) ($options['source-user'] ?? getenv('SOURCE_DB_USERNAME') ?: 'root'),
    'password' => $sourcePassword,
];
$target = [
    'host' => (string) ($options['target-host'] ?? getenv('TARGET_DB_HOST') ?: ''),
    'port' => (string) ($options['target-port'] ?? getenv('TARGET_DB_PORT') ?: '5432'),
    'database' => (string) ($options['target-db'] ?? getenv('TARGET_DB_DATABASE') ?: 'postgres'),
    'username' => (string) ($options['target-user'] ?? getenv('TARGET_DB_USERNAME') ?: ''),
    'password' => $targetPassword,
];
$batchSize = max(50, (int) ($options['batch-size'] ?? 200));

if ($target['host'] === '' || $target['username'] === '') {
    fail('Provide --target-host and --target-user, or TARGET_DB_HOST and TARGET_DB_USERNAME.');
}

$sourcePdo = connectMysql($source);
$targetPdo = connectPostgres($target);

$sourceTables = $sourcePdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
$sourceTables = array_map(fn (array $row): string => (string) $row[0], $sourceTables);
$targetTables = $targetPdo->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'")
    ->fetchAll(PDO::FETCH_COLUMN);
$targetTables = array_map('strval', $targetTables);

$tables = array_values(array_intersect($sourceTables, $targetTables));
$tables = array_values(array_filter($tables, fn (string $table): bool => $table !== 'migrations'));

if ($tables === []) {
    echo PHP_EOL.'No matching tables found.'.PHP_EOL;
    echo 'Local MySQL tables ('.count($sourceTables).'): '.implode(', ', $sourceTables).PHP_EOL;
    echo 'Supabase public tables ('.count($targetTables).'): '.implode(', ', $targetTables).PHP_EOL;
    $identity = $targetPdo->query('SELECT current_database() AS database_name, current_user AS user_name, current_schema() AS schema_name')->fetch(PDO::FETCH_ASSOC);
    echo 'Supabase connection: database='.$identity['database_name'].' user='.$identity['user_name'].' schema='.$identity['schema_name'].PHP_EOL;
    $visibleTables = $targetPdo->query("SELECT schemaname, tablename FROM pg_catalog.pg_tables WHERE schemaname NOT IN ('pg_catalog', 'information_schema') ORDER BY schemaname, tablename")->fetchAll(PDO::FETCH_ASSOC);
    echo 'Visible non-system tables: '.count($visibleTables).PHP_EOL;
    foreach ($visibleTables as $visibleTable) {
        echo '  '.$visibleTable['schemaname'].'.'.$visibleTable['tablename'].PHP_EOL;
    }
    fail('Confirm the source database is my_tracker and the Supabase migrations ran in the public schema.');
}

$targetMeta = targetMetadata($targetPdo, $tables);
$order = dependencyOrder($tables, $targetMeta['foreign_keys']);
$counts = [];
foreach ($tables as $table) {
    $counts[$table] = (int) $sourcePdo->query('SELECT COUNT(*) FROM '.quoteMysql($table))->fetchColumn();
}

print_r($counts);

echo PHP_EOL.'Mode: '.(isset($options['apply']) ? 'APPLY' : 'DRY RUN').PHP_EOL;
echo 'Tables: '.count($order).PHP_EOL;
echo 'Source: '.$source['username'].'@'.$source['host'].':'.$source['port'].'/'.$source['database'].PHP_EOL;
echo 'Target: '.$target['username'].'@'.$target['host'].':'.$target['port'].'/'.$target['database'].PHP_EOL;

if (! isset($options['apply'])) {
    echo PHP_EOL.'Dry run complete. Re-run with --apply only after reviewing the table counts.'.PHP_EOL;
    exit(0);
}

if (! confirm('Import these rows into Supabase? This writes to Supabase but does not modify MySQL')) {
    echo 'Cancelled.'.PHP_EOL;
    exit(0);
}

$deferred = [];
$imported = [];
$targetPdo->beginTransaction();
try {
    foreach ($order as $table) {
        importTable($sourcePdo, $targetPdo, $table, $targetMeta, $imported, $deferred, $batchSize);
        $imported[$table] = true;
    }

    resolveDeferredForeignKeys($sourcePdo, $targetPdo, $deferred, $targetMeta);
    resetSequences($targetPdo, $tables, $targetMeta['primary_keys']);
    $targetPdo->commit();
} catch (Throwable $exception) {
    if ($targetPdo->inTransaction()) {
        $targetPdo->rollBack();
    }

    throw $exception;
}

foreach ($tables as $table) {
    $sourceCount = $counts[$table];
    $targetCount = (int) $targetPdo->query('SELECT COUNT(*) FROM '.quotePg($table))->fetchColumn();
    echo $table.': source='.$sourceCount.' target='.$targetCount.($sourceCount === $targetCount ? ' OK' : ' MISMATCH').PHP_EOL;
}

echo PHP_EOL.'Import complete. Uploads are separate: archive storage/app/public and migrate them to persistent storage.'.PHP_EOL;

function connectMysql(array $config): PDO
{
    return new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

function connectPostgres(array $config): PDO
{
    return new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']};sslmode=require",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

/** @return array{columns: array<string, array<string, string>>, primary_keys: array<string, string>, foreign_keys: array<string, array<int, array{column: string, foreign_table: string, foreign_column: string, nullable: bool}>>} */
function targetMetadata(PDO $pdo, array $tables): array
{
    $placeholders = implode(',', array_fill(0, count($tables), '?'));
    $columns = [];
    $statement = $pdo->prepare("SELECT table_name, column_name, data_type, is_nullable FROM information_schema.columns WHERE table_schema = 'public' AND table_name IN ({$placeholders}) ORDER BY ordinal_position");
    $statement->execute($tables);
    foreach ($statement->fetchAll() as $row) {
        $columns[$row['table_name']][$row['column_name']] = [
            'data_type' => $row['data_type'],
            'is_nullable' => $row['is_nullable'],
        ];
    }

    $primaryKeys = [];
    $statement = $pdo->query("SELECT tc.table_name, kcu.column_name FROM information_schema.table_constraints tc JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema WHERE tc.table_schema = 'public' AND tc.constraint_type = 'PRIMARY KEY'");
    foreach ($statement->fetchAll() as $row) {
        $primaryKeys[$row['table_name']] = $row['column_name'];
    }

    $foreignKeys = [];
    $statement = $pdo->query("SELECT tc.table_name, kcu.column_name, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name, c.is_nullable FROM information_schema.table_constraints tc JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema JOIN information_schema.constraint_column_usage ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema JOIN information_schema.columns c ON c.table_schema = tc.table_schema AND c.table_name = tc.table_name AND c.column_name = kcu.column_name WHERE tc.table_schema = 'public' AND tc.constraint_type = 'FOREIGN KEY'");
    foreach ($statement->fetchAll() as $row) {
        $foreignKeys[$row['table_name']][] = [
            'column' => $row['column_name'],
            'foreign_table' => $row['foreign_table_name'],
            'foreign_column' => $row['foreign_column_name'],
            'nullable' => $row['is_nullable'] === 'YES',
        ];
    }

    return ['columns' => $columns, 'primary_keys' => $primaryKeys, 'foreign_keys' => $foreignKeys];
}

/** @param array<string, array<int, array{column: string, foreign_table: string, foreign_column: string, nullable: bool}>> $foreignKeys */
function dependencyOrder(array $tables, array $foreignKeys): array
{
    $remaining = array_fill_keys($tables, true);
    $order = [];
    while ($remaining !== []) {
        $progress = false;
        foreach (array_keys($remaining) as $table) {
            $dependencies = array_filter($foreignKeys[$table] ?? [], fn (array $foreignKey): bool => isset($remaining[$foreignKey['foreign_table']]) && ! $foreignKey['nullable']);
            if ($dependencies === []) {
                $order[] = $table;
                unset($remaining[$table]);
                $progress = true;
            }
        }
        if (! $progress) {
            foreach (array_keys($remaining) as $table) {
                $order[] = $table;
                unset($remaining[$table]);
            }
        }
    }

    return $order;
}

function importTable(PDO $source, PDO $target, string $table, array $meta, array $imported, array &$deferred, int $batchSize): void
{
    $sourceColumns = $source->query('SHOW COLUMNS FROM '.quoteMysql($table))->fetchAll(PDO::FETCH_COLUMN);
    $columns = array_values(array_intersect($sourceColumns, array_keys($meta['columns'][$table] ?? [])));
    if ($columns === []) {
        return;
    }

    $foreignKeys = $meta['foreign_keys'][$table] ?? [];
    $deferredColumns = array_values(array_filter($foreignKeys, fn (array $foreignKey): bool => ! isset($imported[$foreignKey['foreign_table']])));
    $select = 'SELECT '.implode(', ', array_map('quoteMysql', $columns)).' FROM '.quoteMysql($table);
    $rows = $source->query($select);
    $quotedColumns = implode(', ', array_map('quotePg', $columns));
    $placeholders = implode(', ', array_map(fn (int $index): string => '$'.($index + 1), array_keys($columns)));
    $insert = $target->prepare('INSERT INTO '.quotePg($table).' ('.$quotedColumns.') VALUES ('.$placeholders.') ON CONFLICT DO NOTHING');
    $count = 0;

    while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
        $values = [];
        foreach ($columns as $column) {
            $value = $row[$column];
            $columnMeta = $meta['columns'][$table][$column];
            foreach ($deferredColumns as $foreignKey) {
                if ($foreignKey['column'] === $column && $value !== null) {
                    $deferred[] = ['table' => $table, 'pk' => $meta['primary_keys'][$table] ?? null, 'pk_value' => $row[$meta['primary_keys'][$table] ?? $column] ?? null, 'column' => $column, 'value' => $value];
                    $value = null;
                }
            }
            $values[] = normalizeValue($value, $columnMeta['data_type']);
        }
        $insert->execute($values);
        $count++;
        if ($count % $batchSize === 0) {
            echo $table.': '.$count.' rows'.PHP_EOL;
        }
    }
    echo $table.': imported '.$count.' rows'.PHP_EOL;
}

function resolveDeferredForeignKeys(PDO $source, PDO $target, array $deferred, array $meta): void
{
    foreach ($deferred as $item) {
        if ($item['pk'] === null || $item['pk_value'] === null) {
            fail('Cannot resolve deferred foreign key in '.$item['table'].'. Missing primary key.');
        }
        $query = 'UPDATE '.quotePg($item['table']).' SET '.quotePg($item['column']).' = :value WHERE '.quotePg($item['pk']).' = :pk';
        $statement = $target->prepare($query);
        $statement->execute(['value' => $item['value'], 'pk' => $item['pk_value']]);
    }
}

function resetSequences(PDO $pdo, array $tables, array $primaryKeys): void
{
    foreach ($tables as $table) {
        $primaryKey = $primaryKeys[$table] ?? null;
        if ($primaryKey === null) {
            continue;
        }
        $sequence = $pdo->query("SELECT pg_get_serial_sequence('public.".$table."', '".$primaryKey."')")->fetchColumn();
        if (! $sequence) {
            continue;
        }
        $pdo->exec("SELECT setval('".$sequence."', COALESCE((SELECT MAX(".quotePg($primaryKey).') FROM '.quotePg($table).'), 1), true)');
    }
}

function normalizeValue(mixed $value, string $type): mixed
{
    if ($value === null) {
        return null;
    }
    if ($type === 'boolean') {
        return ((string) $value === '1' || strtolower((string) $value) === 'true') ? 'true' : 'false';
    }
    if (in_array($type, ['json', 'jsonb'], true)) {
        json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
    }

    return $value;
}

function quotePg(string $identifier): string
{
    return '"'.str_replace('"', '""', $identifier).'"';
}

function quoteMysql(string $identifier): string
{
    return '`'.str_replace('`', '``', $identifier).'`';
}

function prompt(string $label): string
{
    fwrite(STDOUT, $label.': ');

    return trim((string) fgets(STDIN));
}

function confirm(string $message): bool
{
    fwrite(STDOUT, $message.' [type YES]: ');

    return trim((string) fgets(STDIN)) === 'YES';
}

function fail(string $message): never
{
    fwrite(STDERR, 'ERROR: '.$message.PHP_EOL);
    exit(1);
}
