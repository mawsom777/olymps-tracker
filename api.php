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
    $map = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5];
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
function markdown_cells(string $line): array {
    return preg_split('~\\\\.(*SKIP)(*F)|\|~', $line);
}

function markdown_pipes(string $line): int {
    return count(markdown_cells($line)) - 1;
}

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
        if (str_starts_with($ln, '|') && str_ends_with($ln, '|') && markdown_pipes($ln) >= 3) {
            $target = markdown_pipes($ln);
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
            if ($buf !== '' && markdown_pipes($buf) < $target) $buf .= ' ' . $ln;
            continue;
        }
        if ($buf === '') { $buf = $ln; continue; }

        $complete = markdown_pipes($buf) >= $target;   // накоплена полная строка
        $selfRow  = str_starts_with($ln, '|') && str_ends_with($ln, '|')
                    && markdown_pipes($ln) === $target; // новая целая строка
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
        $c = markdown_cells($line);
        if ($c[0] === '') array_shift($c);
        if (end($c) === '') array_pop($c);
        $c = array_map(fn($cell) => $clean(strtr($cell, ['\\\\' => '\\', '\\|' => '|'])), $c);
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

        $numbered = count($c) >= 13;
        $perRoundFormat = count($c) >= 15;
        $countColumn = $perRoundFormat ? 10 : ($numbered ? 8 : 6);
        $count = max(1, min(4, roman_int($rest[$countColumn] ?? '2')));
        $dates = array_fill(1, 4, null);
        $formats = array_fill(1, 4, '');
        if ($numbered) {
            for ($round = 1; $round <= $count; $round++) {
                $dates[$round] = parse_olymp_date($rest[$round + 1] ?? '');
            }
        } else {
            $dates[1] = parse_olymp_date($rest[2] ?? '');
            $dates[$count] ??= parse_olymp_date($rest[4] ?? '');
        }

        if ($perRoundFormat) {
            for ($round = 1; $round <= $count; $round++) {
                $formats[$round] = $rest[$round + 5] ?? '';
            }
        } else {
            $formats[1] = $rest[$numbered ? 6 : 3] ?? '';
            if ($count > 1 || $formats[1] === '' || $formats[1] === '—') {
                $formats[$count] = $rest[$numbered ? 7 : 5] ?? '';
            }
        }
        $formats = array_map('normalize_round_format', $formats);

        $rows[] = [
            'name' => $profile !== '' ? "$title ($profile)" : $title,
            'reg_start' => parse_olymp_date($rest[0] ?? ''),
            'reg_end' => parse_olymp_date($rest[1] ?? ''),
            'dates' => $dates,
            'formats' => $formats,
            'stages_count' => $count,
            'lvl' => max(1, min(3, roman_int($rest[$countColumn + 1] ?? '1'))),
        ];
    }
    return [$rows, $skipped];
}

function normalize_round_format(string $format): string {
    $format = trim(ensure_utf8($format));
    return in_array($format, ['—', '–', '-'], true) ? '' : mb_substr($format, 0, 64);
}

function markdown_export(array $rows): string {
    $out = "| № | Олимпиада | Профиль | Начало регистрации | Окончание регистрации | Дата 1 тура | Дата 2 тура | Дата 3 тура | Дата 4 тура | Формат 1 тура | Формат 2 тура | Формат 3 тура | Формат 4 тура | Кол-во этапов | Уровень |\n";
    $out .= '|' . implode('|', array_fill(0, 15, '---')) . "|\n";
    foreach (array_values($rows) as $i => $r) {
        [$title, $profile] = split_name((string)$r['name']);
        $cells = [$i + 1, $title, $profile, fmt_md_date($r['reg_start']), fmt_md_date($r['reg_end'])];
        for ($round = 1; $round <= 4; $round++) {
            $cells[] = $round <= $r['stages_count'] ? fmt_md_date($r["stage{$round}_date"]) : '—';
        }
        for ($round = 1; $round <= 4; $round++) {
            $cells[] = $round <= $r['stages_count'] ? $r["stage{$round}_format"] : '';
        }
        array_push($cells, $r['stages_count'], $r['lvl']);
        $escaped = array_map(fn($x) => strtr((string)$x, ['\\' => '\\\\', '|' => '\\|', "\r" => ' ', "\n" => ' ']), $cells);
        $out .= '| ' . implode(' | ', $escaped) . " |\n";
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
            $DT  = ['reg_start', 'reg_end'];
            $roundFields = ['stage1_date', 'stage2_date', 'stage3_date', 'stage4_date'];
            $formatFields = ['stage1_format', 'stage2_format', 'stage3_format', 'stage4_format'];
            if ($id <= 0) out(['ok' => false, 'error' => 'нет id']);

            if ($f === 'name') {
                $v = mb_substr(trim(ensure_utf8((string)($_POST['value'] ?? ''))), 0, 255);
                db()->prepare('UPDATE olympiads SET name=? WHERE id=?')->execute([$v, $id]);
            } elseif (($round = array_search($f, $formatFields, true)) !== false) {
                $round++;
                $v = normalize_round_format((string)($_POST['value'] ?? ''));
                db()->prepare("UPDATE olympiads SET `$f`=?, final_format=IF(stages_count=?, ?, final_format)
                    WHERE id=? AND stages_count>=?")->execute([$v, $round, $v, $id, $round]);
            } elseif ($f === 'final_format') {
                $v = normalize_round_format((string)($_POST['value'] ?? ''));
                db()->prepare('UPDATE olympiads SET final_format=?,
                    stage1_format=IF(stages_count=1, ?, stage1_format),
                    stage2_format=IF(stages_count=2, ?, stage2_format),
                    stage3_format=IF(stages_count=3, ?, stage3_format),
                    stage4_format=IF(stages_count=4, ?, stage4_format)
                    WHERE id=?')->execute([$v, $v, $v, $v, $v, $id]);
            } elseif (in_array($f, $DT, true)) {
                $v = norm_dt((string)($_POST['value'] ?? ''));
                db()->prepare("UPDATE olympiads SET `$f`=? WHERE id=?")->execute([$v, $id]);
            } elseif (($round = array_search($f, $roundFields, true)) !== false) {
                $round++;
                $v = norm_dt((string)($_POST['value'] ?? ''));
                db()->prepare("UPDATE olympiads SET `$f`=?, final_date=IF(stages_count=?, ?, final_date)
                    WHERE id=? AND stages_count>=?")->execute([$v, $round, $v, $id, $round]);
            } elseif ($f === 'final_date') {
                $v = norm_dt((string)($_POST['value'] ?? ''));
                db()->prepare('UPDATE olympiads SET final_date=?,
                    stage1_date=IF(stages_count=1, ?, stage1_date),
                    stage2_date=IF(stages_count=2, ?, stage2_date),
                    stage3_date=IF(stages_count=3, ?, stage3_date),
                    stage4_date=IF(stages_count=4, ?, stage4_date)
                    WHERE id=?')->execute([$v, $v, $v, $v, $v, $id]);
            } elseif ($f === 'stages_count') {
                $count = max(1, min(4, (int)($_POST['value'] ?? 1)));
                $lastField = $roundFields[$count - 1];
                $lastFormat = $formatFields[$count - 1];
                db()->prepare("UPDATE olympiads SET stages_count=?, final_date=`$lastField`, final_format=`$lastFormat`,
                    stage2_date=IF(? >= 2, stage2_date, NULL),
                    stage3_date=IF(? >= 3, stage3_date, NULL),
                    stage4_date=IF(? >= 4, stage4_date, NULL),
                    stage2_format=IF(? >= 2, stage2_format, ''),
                    stage3_format=IF(? >= 3, stage3_format, ''),
                    stage4_format=IF(? >= 4, stage4_format, ''),
                    stage_attendance=stage_attendance & ? WHERE id=?")
                    ->execute([$count, $count, $count, $count, $count, $count, $count, (1 << $count) - 1, $id]);
            } elseif ($f === 'lvl') {
                $level = max(1, min(3, (int)($_POST['value'] ?? 1)));
                db()->prepare('UPDATE olympiads SET lvl=? WHERE id=?')->execute([$level, $id]);
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

        case 'participation': {
            $id = (int)($_POST['id'] ?? 0);
            $round = (int)($_POST['round'] ?? 0);
            if ($id <= 0 || $round < 1 || $round > 4) {
                out(['ok' => false, 'error' => 'Неверный номер тура']);
            }
            $bit = 1 << ($round - 1);
            $attended = ($_POST['attended'] ?? '') === '1';
            db()->prepare('UPDATE olympiads SET stage_attendance=(stage_attendance & ?) | ?
                WHERE id=? AND stages_count>=?')
                ->execute([15 ^ $bit, $attended ? $bit : 0, $id, $round]);
            $st = db()->prepare('SELECT stage_attendance FROM olympiads WHERE id=? AND stages_count>=?');
            $st->execute([$id, $round]);
            $mask = $st->fetchColumn();
            if ($mask === false) out(['ok' => false, 'error' => 'Тур не найден']);
            out(['ok' => true, 'stage_attendance' => (int)$mask]);
        }

        case 'export': {
            $rows = db()->query('SELECT * FROM olympiads ORDER BY id')->fetchAll();
            out(['ok' => true, 'md' => markdown_export($rows)]);
        }

        case 'import': {
            $md   = ensure_utf8((string)($_POST['md'] ?? ''));
            $mode = ($_POST['mode'] ?? 'append') === 'replace' ? 'replace' : 'append';
            [$parsed, $skipped] = parse_md_table($md);
            if ($parsed) {
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    if ($mode === 'replace') $pdo->exec('DELETE FROM olympiads');
                    $ins = $pdo->prepare('INSERT INTO olympiads
                        (name, reg_start, reg_end, stage1_date, stage2_date, stage3_date, stage4_date,
                         stage1_format, stage2_format, stage3_format, stage4_format, final_date, final_format,
                         stages_count, lvl)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    foreach ($parsed as $r) {
                        $ins->execute([
                            $r['name'], $r['reg_start'], $r['reg_end'], ...array_values($r['dates']),
                            ...array_values($r['formats']), $r['dates'][$r['stages_count']], $r['formats'][$r['stages_count']],
                            $r['stages_count'], $r['lvl'],
                        ]);
                    }
                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            out(['ok' => true, 'added' => count($parsed), 'skipped' => $skipped]);
        }

        default:
            out(['ok' => false, 'error' => 'неизвестное действие']);
    }
} catch (Throwable $e) {
    out(['ok' => false, 'error' => 'БД: ' . $e->getMessage()]);
}