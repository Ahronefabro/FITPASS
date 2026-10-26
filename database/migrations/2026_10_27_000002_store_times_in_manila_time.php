<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The app used to run on UTC, so every time in, time out and timestamp was
 * saved 8 hours behind the clock at the gym. The app now runs on Manila time
 * (config/app.php); this moves the times already saved to match, so old
 * visits read the same as new ones.
 */
return new class extends Migration
{
    private const HOURS = 8;

    public function up(): void
    {
        $this->shift('+');
    }

    public function down(): void
    {
        $this->shift('-');
    }

    private function shift(string $direction): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $columns = DB::select(
            "SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND DATA_TYPE IN ('datetime', 'timestamp')"
        );

        foreach (collect($columns)->groupBy('t') as $table => $tableColumns) {
            $sets = $tableColumns
                ->map(fn ($column) => "`{$column->c}` = `{$column->c}` {$direction} INTERVAL ".self::HOURS.' HOUR')
                ->implode(', ');

            // Empty values stay empty: NULL plus an interval is still NULL.
            DB::statement("UPDATE `{$table}` SET {$sets}");
        }
    }
};
