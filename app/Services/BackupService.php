<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Database backups (operations/backup-and-resilience.md). Produces a gzipped
 * SQL dump in storage/app/backups — via mysqldump when one can be found,
 * otherwise a pure-PHP dump through the PDO connection (works for MySQL and
 * SQLite, so a backup can always be taken). Applies retention afterwards.
 */
class BackupService
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function directory(): string
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    /** Take a backup now. Returns the absolute path of the file written. */
    public function run(): string
    {
        $dir = $this->directory();
        $name = 'backup-'.now()->format('Ymd-His').'.sql';
        $target = $dir.DIRECTORY_SEPARATOR.$name;

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $dump = $driver === 'mysql' ? $this->mysqldumpBinary() : null;
        if ($dump !== null) {
            $this->runMysqldump($dump, $connection, $target);
            $method = 'mysqldump';
        } else {
            $this->phpDump($target);
            $method = 'php';
        }

        $final = $this->gzip($target);
        $this->applyRetention();

        Log::info('backup.completed', ['file' => basename($final), 'bytes' => filesize($final), 'method' => $method]);

        return $final;
    }

    /** @return array<int, array{name:string,size_kb:float,at:string,age_hours:float}> newest first */
    public function list(): array
    {
        $files = collect(File::files($this->directory()))
            ->filter(fn ($f) => preg_match('/^backup-.*\.sql(\.gz)?$/', $f->getFilename()))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size_kb' => round($f->getSize() / 1024, 1),
                'at' => date('Y-m-d H:i', $f->getMTime()),
                'age_hours' => round((time() - $f->getMTime()) / 3600, 1),
            ])->values()->all();

        return $files;
    }

    public function latestAt(): ?CarbonImmutable
    {
        $latest = $this->list()[0] ?? null;

        return $latest ? CarbonImmutable::parse($latest['at']) : null;
    }

    public function path(string $file): ?string
    {
        if (! preg_match('/^backup-[A-Za-z0-9._-]+$/', $file)) {
            return null;
        }
        $path = $this->directory().DIRECTORY_SEPARATOR.$file;

        return is_file($path) ? $path : null;
    }

    public function mysqldumpBinary(): ?string
    {
        $candidates = array_filter([
            env('MYSQLDUMP_PATH'),
            'mysqldump',
            'C:/xampp/mysql/bin/mysqldump.exe',
            'C:/wamp64/bin/mysql/mysql8.0.31/bin/mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
        ]);
        foreach ($candidates as $bin) {
            if ($bin === 'mysqldump') {
                $probe = Process::fromShellCommandline(PHP_OS_FAMILY === 'Windows' ? 'where mysqldump' : 'command -v mysqldump');
                $probe->run();
                if ($probe->isSuccessful()) {
                    return 'mysqldump';
                }
                continue;
            }
            if (is_file($bin)) {
                return $bin;
            }
        }
        // Laragon keeps versioned folders.
        foreach (glob('C:/laragon/bin/mysql/*/bin/mysqldump.exe') ?: [] as $bin) {
            return $bin;
        }

        return null;
    }

    private function runMysqldump(string $bin, string $connection, string $target): void
    {
        $cfg = config("database.connections.{$connection}");
        $args = [
            $bin, '--host='.$cfg['host'], '--port='.($cfg['port'] ?? 3306), '--user='.$cfg['username'],
            '--single-transaction', '--routines', '--triggers', '--skip-lock-tables', '--result-file='.$target, $cfg['database'],
        ];
        $process = new Process($args, null, ['MYSQL_PWD' => (string) ($cfg['password'] ?? '')], null, 900);
        $process->run();
        if (! $process->isSuccessful() || ! is_file($target) || filesize($target) === 0) {
            @unlink($target);
            throw new \RuntimeException('mysqldump failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    /** Portable dump through PDO: DDL + batched INSERTs. */
    private function phpDump(string $target): void
    {
        $pdo = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();
        $out = fopen($target, 'w');
        if ($out === false) {
            throw new \RuntimeException("Cannot write {$target}");
        }
        fwrite($out, "-- SparesPro backup ".now()->toDateTimeString()." ({$driver})\n");
        if ($driver === 'mysql') {
            fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\n");
        }

        $tables = $driver === 'sqlite'
            ? array_column($pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_ASSOC), 'name')
            : array_map(fn ($r) => array_values($r)[0], $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_ASSOC));

        foreach ($tables as $table) {
            $q = fn ($id) => $driver === 'mysql' ? "`{$id}`" : "\"{$id}\"";
            $ddl = $driver === 'sqlite'
                ? $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name=".$pdo->quote($table))->fetchColumn()
                : $pdo->query("SHOW CREATE TABLE {$q($table)}")->fetch(\PDO::FETCH_NUM)[1];
            fwrite($out, "\nDROP TABLE IF EXISTS {$q($table)};\n{$ddl};\n");

            $stmt = $pdo->query("SELECT * FROM {$q($table)}");
            $batch = [];
            $columns = null;
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $columns ??= '('.implode(',', array_map($q, array_keys($row))).')';
                $batch[] = '('.implode(',', array_map(fn ($v) => $v === null ? 'NULL' : (is_int($v) || is_float($v) ? (string) $v : $pdo->quote((string) $v)), $row)).')';
                if (count($batch) >= 200) {
                    fwrite($out, "INSERT INTO {$q($table)} {$columns} VALUES\n".implode(",\n", $batch).";\n");
                    $batch = [];
                }
            }
            if ($batch !== []) {
                fwrite($out, "INSERT INTO {$q($table)} {$columns} VALUES\n".implode(",\n", $batch).";\n");
            }
        }
        if ($driver === 'mysql') {
            fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        }
        fclose($out);
    }

    private function gzip(string $path): string
    {
        if (! function_exists('gzopen')) {
            return $path;
        }
        $gz = gzopen($path.'.gz', 'wb6');
        $in = fopen($path, 'rb');
        while (! feof($in)) {
            gzwrite($gz, fread($in, 1024 * 512));
        }
        fclose($in);
        gzclose($gz);
        unlink($path);

        return $path.'.gz';
    }

    public function applyRetention(): int
    {
        $days = max(1, (int) $this->settings->get('backup.retention_days'));
        $cutoff = now()->subDays($days)->getTimestamp();
        $deleted = 0;
        foreach (File::files($this->directory()) as $f) {
            if (preg_match('/^backup-.*\.sql(\.gz)?$/', $f->getFilename()) && $f->getMTime() < $cutoff) {
                File::delete($f->getPathname());
                $deleted++;
            }
        }

        return $deleted;
    }
}
