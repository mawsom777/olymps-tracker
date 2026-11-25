<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

function out(array $a): void { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

/* Приводим любой входящий текст к валидному UTF-8, чтобы /u-регулярки не падали */
function ensure_utf8(string $s): string {
    if ($s === '' || mb_check_encoding($s, 'UTF-8')) return $s;
    $w = function_exists('iconv') ? @iconv('windows-1251', 'UTF-8', $s) : false;
    if ($w !== false && $w !== '' && preg_match('/\p{Cyrillic}/u', $w)) return $w;
    $prev = mb_substitute_character();
    mb_substitute_character('none');
    $out = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
    mb_substitute_character($prev);
    return is_string($out) ? $out : '';
}

function norm_dt(?string $s): ?string {
    $s = trim((string)$s);
    if ($s === '') return null;
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{1,2}):(\d{2}))?/', $s, $m)) return null;
    $h = ($m[4] ?? '') === '' ? 23 : max(0, min(23, (int)$m[4]));   // нет времени => 23:59
    $i = ($m[5] ?? '') === '' ? 59 : max(0, min(59, (int)$m[5]));
    return sprintf('%s-%s-%s %02d:%02d:00', $m[1], $m[2], $m[3], $h, $i);
}

/* ================= Markdown-хелперы ================= */

function roman_int(string $s): int {
    $s = strtoupper(trim($s));
    $map = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 3, 'V' => 3];
    return $map[$s] ?? (int)$s;
}

function mk($y, $m, $d, $h, $i): string {
    $h = ($h === null || $h === '') ? 23 : (int)$h;
    $i = ($i === null || $i === '') ? 59 : (int)$i;
    return sprintf('%04d-%02d-%02d %02d:%02d:00', (int)$y, (int)$m, (int)$d, $h, $i);
}

function parse_olymp_date(?string $s): ?string {
    static $MON = ['январ'=>1,'феврал'=>2,'март'=>3,'апрел'=>4,'ма'=>5,'июн'=>6,
                   'июл'=>7,'август'=>8,'сентябр'=>9,'октябр'=>10,'ноябр'=>11,'декабр'=>12];
    $s = trim((string)$s);
    if ($s === '' || preg_match('/^(уточн|не изв|н\/д|—|–|-|нет)/iu', $s)) return null;

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{1,2}):(\d{2}))?/u', $s, $m)) {
        return mk($m[1], $m[2], $m[3], $m[4] ?? null, $m[5] ?? null);
    }
    if (preg_match('/^(\d{1,2})[.\/](\d{1,2})[.\/](\d{2,4})(?:\D+(\d{1,2}):(\d{2}))?/u', $s, $m)) {
        $y = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
        return mk($y, $m[2], $m[1], $m[4] ?? null, $m[5] ?? null);
    }
    $low = mb_strtolower($s); $month = null;
    foreach ($MON as $k => $v) { if (str_contains($low, $k)) { $month = $v; break; } }
    if ($month) {
        preg_match_all('/\d{1,4}/u', $s, $nn);
        $day = null; $year = null;
        foreach ($nn[0] as $num) {
            $num = (int)$num;
            if ($year === null && $num >= 1900) { $year = $num; continue; }
            if ($day === null && $num >= 1 && $num <= 31) $day = $num;
        }
        $time = preg_match('/(\d{1,2}):(\d{2})/u', $s, $tm) ? [$tm[1], $tm[2]] : [null, null];
        if ($day) return mk($year ?? (int)date('Y'), $month, $day, $time[0], $time[1]);
        return null; // только месяц => точной даты нет
    }
    return null;
}

function split_name(string $name): array {
    if (preg_match('/^(.*?)\s*[（(]([^()（）]*)[)）]\s*$/u', trim($name), $m)) {
        return [trim($m[1]), trim($m[2])];
    }
    return [trim($name), ''];
}

/*
 * Склейка физических строк в логические строки таблицы.
 * Если длинная строка пришла с переносами (документ/буфер обмена),
 * её фрагменты дозаписываются в текущую строку, пока не наберётся
 * ожидаемое число вертикальных черт.
 */
function assemble_md_rows(string $text): array {
    $text  = str_replace("\r", '', $text);
    $text  = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text; // BOM
    $lines = preg_split('/\R+/', trim($text)) ?: [];

    $isSep = static fn(string $l): bool => (bool)preg_match('/^[\s|:\-–—]+$/u', $l);

    // ожидаемое число «|» в полной строке — по первой целой строке (обычно шапка)
    $target = null;
    foreach ($lines as $ln) {
        $ln = trim($ln);
        if ($ln === '' || $isSep($ln)) continue;
        if (str_starts_with($ln, '|') && str_ends_with($ln, '|') && substr_count($ln, '|') >= 3) {
            $target = substr_count($ln, '|');
            break;
        }
    }
    if ($target === null) { // целых строк нет — разбор «как есть»
        $out = [];
        foreach ($lines as $ln) {
            $ln = trim($ln);
            if ($ln !== '' && !$isSep($ln) && str_contains($ln, '|')) $out[] = $ln;
        }
        return $out;
    }

    $rows = []; $buf = '';
    foreach ($lines as $ln) {
        $ln = trim($ln);
        if ($ln === '' || $isSep($ln)) continue;

        if (!str_contains($ln, '|')) { // кусок ячейки вообще без «|»
            if ($buf !== '' && substr_count($buf, '|') < $target) $buf .= ' ' . $ln;
            continue;
        }
        if ($buf === '') { $buf = $ln; continue; }

        $complete = substr_count($buf, '|') >= $target;   // накоплена полная строка
        $selfRow  = str_starts_with($ln, '|') && str_ends_with($ln, '|')
                    && substr_count($ln, '|') === $target; // новая целая строка
        if ($complete || $selfRow) {
            $rows[] = $buf;
            $buf = $ln;
        } else {
            $buf .= ' ' . $ln; // доклеиваем хвост перенесённой строки
        }
    }
    if ($buf !== '') $rows[] = $buf;
    return $rows;
}

function parse_md_table(string $text): array {
    $rows = []; $skipped = 0; $first = true;

    $clean = static function ($x): string {
        $x = (string)$x;
        $r = preg_replace('/\x{00A0}/u', ' ', $x);
        return trim($r === null ? $x : $r);
    };

    foreach (assemble_md_rows($text) as $line) {
        if (preg_match('/^[\s|:\-–—]+$/u', $line)) continue;              // разделитель |---|
        $c = array_map($clean, explode('|', trim($line, '|')));
        if (count($c) < 2) { $skipped++; continue; }

        // шапка — только самая первая строка, по явным признакам обоих форматов
        if ($first) {
            $first = false;
            $c1 = (string)mb_strtolower($c[1] ?? '');
            $c2 = (string)mb_strtolower($c[2] ?? '');
            if (($c[0] ?? '') === '№'
                || (str_contains($c1, 'олимпиада')
                    && (str_contains($c2, 'регистрац') || str_contains($c2, 'профил')))) {
                continue;
            }
        }

        if (count($c) >= 11) {
            // формат: № | Олимпиада | Профиль | Начало | Окончание | ...
            $title   = trim($c[1] ?? '');
            $profile = trim($c[2] ?? '');
            $rest    = array_slice($c, 3);
        } else {
            // старый формат: № | Олимпиада (Профиль) | Начало | ...
            [$title, $profile] = split_name((string)($c[1] ?? ''));
            $rest = array_slice($c, 2);
        }
        if ($title === '') { $skipped++; continue; }

        $rows[] = [
            'name'          => $profile !== '' ? "$title ($profile)" : $title,
            'reg_start'     => parse_olymp_date($rest[0] ?? ''),
            'reg_end'       => parse_olymp_date($rest[1] ?? ''),
            'stage1_date'   => parse_olymp_date($rest[2] ?? ''),
            'stage1_format' => trim((string)($rest[3] ?? '')),
            'final_date'    => parse_olymp_date($rest[4] ?? ''),
            'final_format'  => trim((string)($rest[5] ?? '')),
            'stages_count'  => max(1, min(9, roman_int($rest[6] ?? '2'))) ?: 2,
            'lvl'           => max(1, min(3, roman_int($rest[7] ?? '1'))) ?: 1,
        ];
    }
    return [$rows, $skipped];
}

function markdown_export(array $rows): string {
    $out = "| № | Олимпиада | Профиль | Начало регистрации | Окончание регистрации | Дата первого этапа | Формат первого этапа | Дата финального этапа | Формат заключительного этапа | Кол-во этапов | Уровень |\n";
    $out .= '|' . implode('|', array_fill(0, 11, '---')) . "|\n";
    foreach (array_values($rows) as $i => $r) {
        [$title, $profile] = split_name((string)$r['name']);
        $cells = [$i + 1, $title, $profile,
            fmt_md_date($r['reg_start']), fmt_md_date($r['reg_end']),
            fmt_md_date($r['stage1_date']), $r['stage1_format'] ?: '—',
            fmt_md_date($r['final_date']), $r['final_format'] ?: '—',
            $r['stages_count'], $r['lvl']];
        $out .= '| ' . implode(' | ', array_map(fn($x) => str_replace('|', '\\|', (string)$x), $cells)) . " |\n";
    }
    return $out;
}

function fmt_md_date(?string $dt): string {
    if (!$dt) return 'Уточняется на сайте';
    static $M = [1=>'января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
    [$d, $t] = array_pad(explode(' ', $dt), 2, '23:59:00');
    [$y, $m, $dd] = array_map('intval', explode('-', $d));
    $base = sprintf('%d %s %d', $dd, $M[$m], $y);
    return ($t !== '23:59:00') ? "$base (" . substr($t, 0, 5) . " МСК)" : $base;
}

/* ================= Действия ================= */

$action = (string)($_POST['action'] ?? '');

try {
    switch ($action) {

        case 'list': {
            $rows = db()->query('SELECT * FROM olympiads ORDER BY id')->fetchAll();
            out(['ok' => true, 'rows' => $rows]);
        }

        case 'create': {
            db()->exec("INSERT INTO olympiads (name) VALUES ('Новая олимпиада')");
            $id = (int)db()->lastInsertId();
            $st = db()->prepare('SELECT * FROM olympiads WHERE id=?');
            $st->execute([$id]);
            out(['ok' => true, 'row' => $st->fetch()]);
        }

        case 'update': {
            $id = (int)($_POST['id'] ?? 0);
            $f  = (string)($_POST['field'] ?? '');
            $TXT = ['name', 'stage1_format', 'final_format'];
            $DT  = ['reg_start', 'reg_end', 'stage1_date', 'final_date'];
            $INT = ['stages_count' => [1, 9], 'lvl' => [1, 3]];
            if ($id <= 0) out(['ok' => false, 'error' => 'нет id']);

            if (in_array($f, $TXT, true)) {
                $v = mb_substr(trim(ensure_utf8((string)($_POST['value'] ?? ''))), 0, 255);
                db()->prepare("UPDATE olympiads SET `$f`=? WHERE id=?")->execute([$v, $id]);
            } elseif (in_array($f, $DT, true)) {
                $v = norm_dt((string)($_POST['value'] ?? ''));
                db()->prepare("UPDATE olympiads SET `$f`=? WHERE id=?")->execute([$v, $id]);
            } elseif (isset($INT[$f])) {
                [$lo, $hi] = $INT[$f];
                $v = max($lo, min($hi, (int)($_POST['value'] ?? $lo)));
                db()->prepare("UPDATE olympiads SET `$f`=? WHERE id=?")->execute([$v, $id]);
            } else {
                out(['ok' => false, 'error' => 'неизвестное поле']);
            }
            $st = db()->prepare('SELECT * FROM olympiads WHERE id=?');
            $st->execute([$id]);
            out(['ok' => true, 'row' => $st->fetch()]);
        }

        case 'delete': {
            db()->prepare('DELETE FROM olympiads WHERE id=?')->execute([(int)($_POST['id'] ?? 0)]);
            out(['ok' => true]);
        }

        case 'toggle': {
            $id = (int)($_POST['id'] ?? 0);
            db()->prepare('UPDATE olympiads SET registered = 1 - registered WHERE id=?')->execute([$id]);
            $st = db()->prepare('SELECT registered FROM olympiads WHERE id=?');
            $st->execute([$id]);
            out(['ok' => true, 'registered' => (int)$st->fetchColumn()]);
        }

        case 'export': {
            $rows = db()->query('SELECT * FROM olympiads ORDER BY id')->fetchAll();
            out(['ok' => true, 'md' => markdown_export($rows)]);
        }

        case 'import': {
            $md   = ensure_utf8((string)($_POST['md'] ?? ''));
            $mode = ($_POST['mode'] ?? 'append') === 'replace' ? 'replace' : 'append';
            [$parsed, $skipped] = parse_md_table($md);
            if ($mode === 'replace' && $parsed) db()->exec('TRUNCATE TABLE olympiads');
            $ins = db()->prepare('INSERT INTO olympiads
                (name, reg_start, reg_end, stage1_date, stage1_format, final_date, final_format, stages_count, lvl)
                VALUES (?,?,?,?,?,?,?,?,?)');
            foreach ($parsed as $r) {
                $ins->execute([
                    ensure_utf8($r['name']), $r['reg_start'], $r['reg_end'],
                    $r['stage1_date'], ensure_utf8($r['stage1_format']),
                    $r['final_date'], ensure_utf8($r['final_format']),
                    $r['stages_count'], $r['lvl'],
                ]);
            }
            out(['ok' => true, 'added' => count($parsed), 'skipped' => $skipped]);
        }

        default:
            out(['ok' => false, 'error' => 'неизвестное действие']);
    }
} catch (Throwable $e) {
    out(['ok' => false, 'error' => 'БД: ' . $e->getMessage()]);
}