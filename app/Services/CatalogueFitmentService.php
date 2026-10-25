<?php

namespace App\Services;

use App\Models\EngineCode;
use App\Models\Part;
use App\Models\PartFitment;
use App\Models\VehicleMake;
use App\Models\VehicleModel;

/**
 * Turns a supplier catalogue's free-text application line
 * ("HILUX 2.4D 2L 88-97", "GOLF/JETTA I/II/III") into PartFitment rows:
 * make/model via database/data/catalogue_app_aliases.csv, engine codes
 * against the engine_codes pack, and 2-digit year ranges. Every row is
 * stamped source=catalogue + confirmed=false so a manual review can tell
 * derived links from verified ones — they re-sync on the next import.
 */
class CatalogueFitmentService
{
    public const SOURCE = 'catalogue';

    private const MAX_FITMENTS = 8;

    /** @var array<string,array{make:int,model:int|null}> token → resolution */
    private array $aliases = [];

    /** @var array<int,string> make id → name */
    private array $makes = [];

    /** @var list<string> tokens sorted longest-first for greedy matching */
    private array $tokens = [];

    /** @var list<string> seeded engine codes */
    private array $engines = [];

    private bool $loaded = false;

    public function sync(Part $part, string $text, string $spec = ''): int
    {
        $this->load();
        $fitments = $this->parse($text.' '.$spec);

        PartFitment::where('part_id', $part->id)->where('source', self::SOURCE)->delete();
        foreach ($fitments as $f) {
            PartFitment::create($f + ['part_id' => $part->id]);
        }

        return count($fitments);
    }

    /**
     * @return list<array{make_id:int,model_id:?int,year_from:?int,year_to:?int,engine_code:?string,source:string,confirmed:bool,notes:string}>
     */
    public function parse(string $text): array
    {
        $this->load();
        $upper = strtoupper($text);
        if ($upper === '') {
            return [];
        }

        $apps = [];
        foreach ($this->tokens as $token) {
            $pattern = '/\b'.preg_quote($token, '/').'\b/u';
            if (preg_match($pattern, $upper) && ! isset($apps[$this->aliases[$token]['make'].':'.($this->aliases[$token]['model'] ?? '')])) {
                $apps[$this->aliases[$token]['make'].':'.($this->aliases[$token]['model'] ?? '')] = $this->aliases[$token];
            }
        }
        if ($apps === []) {
            return [];
        }

        [$from, $to] = $this->years($upper);
        $engines = $this->engines($upper);
        $engines = array_slice($engines === [] ? [null] : $engines, 0, 3);

        $rows = [];
        $notes = mb_substr($upper, 0, 190);
        foreach (array_values($apps) as $app) {
            foreach ($engines as $engine) {
                $rows[] = [
                    'make_id' => $app['make'],
                    'model_id' => $app['model'],
                    'year_from' => $from,
                    'year_to' => $to,
                    'engine_code' => $engine,
                    'source' => self::SOURCE,
                    'confirmed' => false,
                    'notes' => $notes,
                ];
                if (count($rows) >= self::MAX_FITMENTS) {
                    break 2;
                }
            }
        }

        return $rows;
    }

    /** 2/4-digit year ranges: "82-", "-86", "96-00", "1996-2004". */
    private function years(string $upper): array
    {
        if (preg_match('/\b(19|20)(\d{2})\s*-\s*((?:19|20)?\d{2})?/', $upper, $m)) {
            return [(int) ($m[1].$m[2]), isset($m[3]) && $m[3] !== '' ? $this->year($m[3]) : null];
        }
        if (preg_match('/(?<![\d.\/])(\d{2})\s*-\s*(\d{2})(?![\d.])/', $upper, $m)) {
            return [$this->year($m[1]), $this->year($m[2])];
        }
        if (preg_match('/(?<![\d.\/])(\d{2})\s*-(?!\d)/', $upper, $m)) {
            return [$this->year($m[1]), null];
        }
        if (preg_match('/(?<![\d.])-(\d{2})(?![\d.])/', $upper, $m)) {
            return [null, $this->year($m[1])];
        }

        return [null, null];
    }

    private function year(string $yy): int
    {
        $yy = (int) $yy;

        return $yy >= 50 ? 1900 + $yy : 2000 + $yy;
    }

    /** @return list<string> */
    private function engines(string $upper): array
    {
        $found = [];
        foreach ($this->engines as $code) {
            if (preg_match('/\b'.preg_quote($code, '/').'\b/u', $upper)) {
                $found[] = $code;
            }
        }

        return $found;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;

        $this->makes = VehicleMake::pluck('name', 'id')->all();
        $models = VehicleModel::with('make:id,code')->get(['id', 'name', 'make_id']);
        $modelId = [];
        foreach ($models as $m) {
            $modelId[$m->make->code.'|'.strtoupper($m->name)] = $m->id;
        }
        $makeId = VehicleMake::pluck('id', 'code')->all();

        $handle = fopen(database_path('data/catalogue_app_aliases.csv'), 'r');
        fgetcsv($handle, null, ',', '"', '');
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if (count($row) !== 3) {
                continue;
            }
            [$token, $make, $model] = array_map('trim', $row);
            if (! isset($makeId[$make])) {
                continue;
            }
            $resolved = ['make' => $makeId[$make], 'model' => $model === '' ? null : ($modelId[$make.'|'.strtoupper($model)] ?? null)];
            // A token mapped to a model we don't seed degrades to make-level,
            // never silently dropped (the application is still genuinely that make).
            $this->aliases[$token] = $resolved;
        }
        fclose($handle);

        $this->tokens = array_keys($this->aliases);
        usort($this->tokens, fn ($a, $b) => strlen($b) <=> strlen($a));

        $this->engines = EngineCode::pluck('code')->filter(fn ($c) => strlen($c) >= 2)->sortByDesc(fn ($c) => strlen($c))->values()->all();
    }
}
