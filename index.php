<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$rows = []; $dbOk = true; $dbErr = '';
try {
    $rows = db()->query('SELECT id, name, reg_start, reg_end, stage1_date, stage2_date, stage3_date, stage4_date,
            stage1_end, stage2_end, stage3_end, stage4_end,
            stage1_format, stage2_format, stage3_format, stage4_format, final_date, final_format,
            stages_count, lvl, registered, stage_attendance
        FROM olympiads ORDER BY id')->fetchAll();
} catch (Throwable $e) {
    $dbOk = false; $dbErr = $e->getMessage();
}
function row_out(array $r): array {
    foreach (['reg_start','reg_end','stage1_date','stage2_date','stage3_date','stage4_date',
        'stage1_end','stage2_end','stage3_end','stage4_end','final_date'] as $f)
        if (!empty($r[$f])) $r[$f] = str_replace(' ', 'T', $r[$f]);
    return $r;
}
$rows = array_map('row_out', $rows);
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Олимп·Трекер — контроль олимпиад</title>
<style>
:root{
  --bg:#eef3ed; --panel:#f9fbf7; --line:#d4dfd6;
  --surface:linear-gradient(145deg,#fafcf8,#e9f0e9); --well:#e5ede7;
  --shade:#425e4c36; --shine:#ffffffd1;
  --shadow-raised:6px 7px 16px var(--shade),-5px -5px 14px var(--shine);
  --shadow-raised-sm:3px 4px 8px #425e4c2b,-3px -3px 8px var(--shine);
  --shadow-inset:inset 2px 2px 5px #4764542c,inset -2px -2px 5px var(--shine);
  --radius-lg:20px; --radius-md:12px;
  --ink:#172720; --ink2:#3d5046; --mut:#55665d;
  --green:#21654c; --green-soft:#e5f3e9; --red:#9b3c34; --gray:#a1aea6;
  --mono:ui-monospace, "SFMono-Regular", Consolas, monospace;
  --rl:240px;
}
*{box-sizing:border-box}
body{margin:0;background:radial-gradient(ellipse at right top,#e1ede3,transparent 590px),var(--bg);color:var(--ink);font:15px/1.5 system-ui,sans-serif;
  padding:32px 20px 108px;min-height:100vh}
button,input,select,textarea{font:inherit;color:inherit}
button{cursor:pointer}
[hidden]{display:none!important}
:focus-visible{outline:2px solid var(--green);outline-offset:3px}
.wrap{max-width:1180px;margin:auto}
.panel{border:1px solid var(--line);border-radius:var(--radius-md);background:var(--panel)}
.nbtn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;
  padding:8px 14px;border:1px solid var(--line);border-radius:10px;background:var(--surface);box-shadow:var(--shadow-raised-sm);
  font-size:13px;font-weight:650}
.nbtn:hover{background:var(--green-soft)}
.nbtn.acc{background:linear-gradient(145deg,#408165,var(--green));border-color:var(--green);color:#fff}
.nbtn.acc:hover{background:#174b38}
.nbtn.ghost{background:transparent;box-shadow:none}

.top{display:flex;justify-content:space-between;align-items:center;gap:16px;padding-bottom:17px}
.brand{display:flex;align-items:center;gap:12px}
.brand-badge{display:grid;place-items:center;width:42px;height:42px;border-radius:var(--radius-md);
  background:linear-gradient(145deg,#48876a,var(--green));box-shadow:var(--shadow-raised-sm);
  color:#fff;font-size:22px}
.brand h1{margin:0;font-size:clamp(23px,4vw,34px);line-height:1.12;letter-spacing:-.045em}
.brand h1 em{font-style:normal;color:var(--green)}
.clock{text-align:right;padding:7px 11px;border-radius:var(--radius-md);
  background:var(--well);box-shadow:var(--shadow-inset)}
.clock b{font:650 18px var(--mono);white-space:nowrap}
.clock b i{font-style:normal}
.clock small{display:block;color:var(--mut);font-size:12px}
.clock .tz{font-size:10px;font-weight:750;color:var(--green);margin-left:5px}
.statline{display:flex;flex-wrap:wrap;gap:3px 24px;
  padding:9px 14px;margin:0 0 25px;border-radius:var(--radius-md);
  background:var(--surface);box-shadow:var(--shadow-raised-sm);color:var(--mut);font-size:13px}
.statline b{color:var(--ink)}

.view{display:none}
.view.active{display:block}
.tabbar{position:fixed;left:50%;bottom:calc(12px + env(safe-area-inset-bottom));z-index:50;
  transform:translateX(-50%);display:grid;grid-template-columns:repeat(4,minmax(0,1fr));
  width:min(430px,calc(100% - 20px));gap:5px;padding:6px;border-radius:var(--radius-lg);
  background:var(--surface);box-shadow:var(--shadow-raised)}
.tab{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;
  min-height:50px;padding:5px 2px;border:0;border-radius:var(--radius-md);background:transparent;color:var(--mut);font-size:12px;font-weight:650}
.tab svg{width:19px;height:19px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
.tab:hover,.tab.is-pressed{background:var(--green-soft);color:var(--green)}
.tab.is-pressed{box-shadow:var(--shadow-inset)}

.toolbar,.sched-head,.stage-controls{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 14px}
.stage-controls{flex-direction:column;align-items:flex-start;gap:12px}
#view-stages:has(#stageSchedule[hidden]) .stage-controls{max-width:840px;margin-inline:auto}
.stage-controls .toolbar{margin:0}
.chip{display:inline-flex;align-items:center;gap:6px;min-height:38px;padding:6px 12px;
  border:1px solid var(--line);border-radius:100px;background:var(--surface);box-shadow:var(--shadow-raised-sm);
  color:var(--ink2);font-size:13px;font-weight:650}
.chip:hover,.chip.is-pressed{background:var(--green-soft);color:var(--green)}
.chip.is-pressed{box-shadow:var(--shadow-inset)}
.chip .cdot,.lg i{width:8px;height:8px;border-radius:50%;background:var(--cc,var(--gray))}
.seg{display:inline-flex;gap:3px;padding:4px;border-radius:var(--radius-md);
  background:var(--well);box-shadow:var(--shadow-inset)}
.seg button{min-height:34px;padding:5px 12px;border:1px solid transparent;border-radius:9px;
  background:transparent;color:var(--mut);font-size:13px;font-weight:650}
.seg button.on{border-color:var(--line);background:var(--surface);box-shadow:var(--shadow-raised-sm);color:var(--ink)}

.tscroll{overflow:auto;max-height:63vh;min-height:210px;box-shadow:var(--shadow-raised-sm)}
table.grid{width:100%;min-width:1690px;border-spacing:0}
.grid th,.grid td{padding:8px;vertical-align:middle}
.grid th{position:sticky;top:0;z-index:3;background:#eef1ed;border-bottom:1px solid var(--line);
  text-align:left;color:var(--ink2);font-size:12px}
.grid td{border-bottom:1px solid #edf0ec}
.grid tbody tr:hover{background:#f6f9f6}
.grid .sortable{cursor:pointer}
.grid .sortable:hover{color:var(--green)}
.grid .arr{font-size:9px}
.grid .c-num{width:40px;min-width:40px;text-align:center;font:600 12px var(--mono);color:var(--mut)}
.grid .c-title{width:220px;min-width:220px}
.grid .c-num,.grid .c-title{position:sticky;left:0;z-index:2;background:var(--panel)}
.grid .c-title{left:40px;border-right:1px solid var(--line)}
.grid thead .c-num,.grid thead .c-title{z-index:4;background:#eef1ed}
.c-profile{min-width:155px}
.c-date{min-width:195px}
.ci{width:100%;min-height:34px;padding:5px 7px;border:1px solid var(--line);border-radius:8px;background:var(--panel);box-shadow:inset 1px 1px 3px #52705a17;font-size:13px}
.ci::placeholder{color:var(--mut)}
.subject-profile{border-inline-start:5px solid var(--subject-color)}
.datepair,.cell-name{display:flex;gap:5px;align-items:center}
.datepair .ci{min-width:0;font:12px var(--mono)}
.datepair .di{width:115px}
.datepair .ti{width:76px}
.round-endpoint+.round-endpoint{margin-top:5px}
.round-endpoint>span{display:block;margin-bottom:2px;color:var(--mut);font-size:11px}
.hint-unspec{display:block;color:var(--mut);font-size:11px;margin-top:3px}
.round-inactive{color:var(--mut);font-size:12px}
select.ci{width:auto}
select.round-format{display:block;width:100%;max-width:196px;margin-top:6px}
.del,.lvl-badge{flex:none;min-width:29px;min-height:29px;padding:3px 6px;border:1px solid var(--line);
  border-radius:6px;background:var(--panel);font-size:12px;font-weight:700}
.del:hover,.del.armed{background:#faeae7;color:var(--red)}
.lvl-badge{background:var(--green-soft);color:var(--green)}
.lvl-badge.l2{background:#edf4fb;color:#26577c}
.lvl-badge.l3{background:#fbf2de;color:#704d15}
.list-foot{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px}
#btnAdd{font-size:22px;padding:0 12px}
.dbbanner{margin-bottom:16px;padding:12px;border:1px solid #ebc0ba;border-radius:9px;background:#fff1ee;color:var(--red)}

.home-wrap{max-width:840px;margin-inline:auto;display:grid;gap:22px}
.card{
  --card-top:#f7faf5;--card-bottom:#e7eee7;--card-shadow:#536c5c35;
  --card-accent:var(--green);--card-rule:#5f796623;
  padding:18px 22px 16px;border-radius:var(--radius-lg);
  background:linear-gradient(145deg,var(--card-top),var(--card-bottom));
  box-shadow:8px 9px 19px var(--card-shadow),-7px -7px 16px var(--shine),inset 0 1px 2px #ffffffba
}
.card-h{display:flex;align-items:center;gap:10px;margin-bottom:12px;color:var(--card-accent)}
.card-h h2{margin:0;font-size:clamp(16px,2.8vw,20px);line-height:1.2;letter-spacing:-.025em}
.card-h .tag,.card-h .count{flex:none;border-radius:100px;font-size:11px;font-weight:750}
.card-h .tag{padding:4px 9px;color:var(--card-accent);background:var(--card-bottom)}
.card-h .count{min-width:32px;margin-left:auto;padding:4px 9px;text-align:center;font:700 12px var(--mono);
  background:var(--card-bottom);box-shadow:inset 2px 2px 5px var(--card-shadow),inset -2px -2px 4px #ffffffb5}
.card table{width:100%;border-collapse:collapse}
.card td,.card th{padding:10px 5px;border-top:1px solid var(--card-rule);vertical-align:middle;text-align:left}
.card tbody tr:hover{background:#ffffff45}
.card td:last-child{text-align:right}
.hn{width:27px;color:var(--mut);font:600 11px var(--mono)}
.hname{overflow-wrap:anywhere}
.hname b{font-size:14px;font-weight:750}
.hname em{color:var(--subj);font-style:normal;font-size:12px}
.hname small{display:block;color:var(--mut);font-size:12px}
.hname small.late{color:var(--red)}
.hdate{font:600 12px/1.4 var(--mono);color:var(--ink2)}
.hdate.cap{color:var(--mut);font:700 11px system-ui,sans-serif}
.tone-urgent{--card-top:#fdf2ee;--card-bottom:#f4ded9;--card-shadow:#b36a5c3c;
  --card-accent:var(--red);--card-rule:#aa55462b}
.tone-missed{--card-top:#f2f5f3;--card-bottom:#e1e7e4;--card-shadow:#6577703d;
  --card-accent:#435951;--card-rule:#596c6330}
.tone-soon{--card-top:#f1f8ef;--card-bottom:#deebdf;--card-shadow:#5f866139;
  --card-rule:#57865b30}
.empty{padding:14px 5px;color:var(--mut);font-size:13px}
.ck{display:inline-grid;place-items:center;width:32px;height:32px;cursor:pointer}
.ck input{appearance:none;display:grid;place-content:center;width:24px;height:24px;margin:0;cursor:pointer;
  border:1px solid color-mix(in srgb,var(--card-accent) 66%,var(--card-bottom));
  border-radius:8px;background:var(--card-bottom);
  box-shadow:inset 2px 2px 5px var(--card-shadow),inset -2px -2px 5px #ffffffbd}
.ck input::after{content:"";width:10px;height:6px;border:solid #fff;border-width:0 0 2px 2px;
  transform:translateY(-1px) rotate(-45deg);visibility:hidden}
.ck input:checked{background:linear-gradient(145deg,#448469,var(--green));border-color:var(--green);
  box-shadow:2px 3px 6px #294c3940,inset 0 1px 1px #ffffff70}
.ck input:checked::after{visibility:visible}
.ck input:disabled{opacity:.65;cursor:wait}
@media (forced-colors:active){
  .ck input,.ck input:checked{appearance:auto}
  .ck input::after{display:none}
}

.legend{display:flex;gap:8px;flex-wrap:wrap}
.lg{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;
  border-radius:100px;background:var(--well);box-shadow:var(--shadow-inset);
  color:var(--ink2);font-size:12px;font-weight:650}
.lg small{color:var(--mut);font:600 10px var(--mono)}
.gantt{--chart-base:#e9f0ea;--axis-size:54px;overflow:hidden;
  border-radius:var(--radius-lg);background:var(--chart-base);box-shadow:var(--shadow-raised)}
.gantt>.empty{padding:16px}
.g-scroll{overflow:auto;scrollbar-color:#a9beb1 var(--chart-base);padding:5px 0 7px}
.g-inner{position:relative;width:max-content;min-width:100%}
.g-inner.is-today::after{content:"";position:absolute;top:var(--axis-size);bottom:0;
  left:calc(var(--rl) + var(--today-x));width:2px;border-radius:2px;opacity:.8;
  background:#b9554a;box-shadow:0 0 7px #b9554a5c;pointer-events:none}
.g-headrow,.g-row{display:grid;grid-template-columns:var(--rl) var(--tw)}
.g-hlab,.g-label{position:sticky;left:0;z-index:4;display:flex;padding:4px 10px;background:var(--chart-base)}
.g-hlab span,.g-label-card{display:flex;align-items:center;gap:9px;width:100%;min-width:0;
  padding:8px 11px;border-radius:var(--radius-md);background:var(--surface);box-shadow:var(--shadow-raised-sm)}
.g-hlab span{color:var(--ink2);font-size:11px;font-weight:800;letter-spacing:.05em}
.g-htl{position:relative;height:var(--axis-size);border-radius:var(--radius-md) 0 0 var(--radius-md);
  background:var(--well);box-shadow:var(--shadow-inset)}
.g-month{position:absolute;top:6px;padding:1px 7px;border-left:2px solid #a6bcae;white-space:nowrap;
  color:var(--ink2);font-size:11px;font-weight:750;letter-spacing:.02em}
.g-tick{position:absolute;bottom:7px;transform:translateX(-50%);color:var(--mut);font:650 10px var(--mono)}
.g-label .dot{flex:none;width:9px;height:9px;border-radius:50%;background:var(--c);
  box-shadow:0 0 0 3px color-mix(in srgb,var(--c) 38%,transparent)}
.g-label b{display:block;font-size:12px;line-height:1.3;overflow-wrap:anywhere}
.g-label small{color:var(--mut);font-size:11px}
.g-label b small{color:var(--subject-ink)}
.g-track{position:relative;min-height:49px;margin-block:3px;border-radius:var(--radius-md) 0 0 var(--radius-md);
  background:repeating-linear-gradient(90deg,#537b641f 0 1px,transparent 1px var(--ppd)),var(--well);
  box-shadow:var(--shadow-inset)}
.g-bar,.g-stage{--event-fill:linear-gradient(145deg,color-mix(in srgb,var(--c) 77%,#fff),color-mix(in srgb,var(--c) 87%,#507360));
  background:var(--c) var(--event-fill);color:var(--ink);
  box-shadow:2px 3px 6px #32524338,inset 0 1px 1px #ffffff9c}
.g-bar{position:absolute;top:7px;height:35px;display:flex;flex-direction:column;justify-content:center;overflow:hidden;
  padding:0 8px;border-radius:9px;white-space:nowrap}
.g-bar b{font-size:11px}
.g-bar span{font:10px var(--mono)}
.g-bar.unspec{position:static;margin:7px 8px;background:var(--well);box-shadow:var(--shadow-inset);
  color:var(--mut);font-size:11px}
.g-stage{position:absolute;z-index:1;display:grid;place-items:center;min-width:23px;height:23px;padding:0;
  border:1px solid #33584935;border-radius:7px;font:750 12px var(--mono);cursor:pointer}
.g-stage.is-range{justify-items:start;padding-inline:7px;border-radius:8px}
.g-stage:focus-visible,.g-stage:hover{z-index:3}
.g-stage.is-remote,.lg-remote i{--remote-hatch:repeating-linear-gradient(-45deg,#17272039 0 2px,transparent 2px 6px)}
.g-stage.is-remote{background-image:var(--remote-hatch),var(--event-fill)}
.lg-remote i,.lg-proctored i{width:14px;height:14px;border-radius:4px}
.lg-remote i{border:1px solid #17272042;background:#e1ece6 var(--remote-hatch)}
.g-stage.is-proctored,.lg-proctored i{border:2px dashed #624184}
.lg-proctored i{background:#eee6f5}
#stageHint{min-height:22px;margin:6px 0;color:var(--mut);font-size:12px}

.dlg{width:min(720px,94vw);padding:18px;border:1px solid var(--line);border-radius:var(--radius-lg);background:var(--surface);
  box-shadow:var(--shadow-raised);color:var(--ink)}
.dlg::backdrop{background:#15261d70}
.dlg h3{margin:0}
.dlg p{font-size:13px;color:var(--mut)}
.dlg textarea{width:100%;resize:vertical;padding:10px;border:1px solid var(--line);border-radius:9px;background:var(--panel);box-shadow:var(--shadow-inset);font:12px var(--mono)}
.dlg .modes,.dlg-btns{display:flex;gap:9px 14px;flex-wrap:wrap;margin-top:12px;font-size:13px}
.dlg .modes input{accent-color:var(--green)}
.dlg-btns{justify-content:flex-end}
#toast{position:fixed;right:12px;bottom:calc(80px + env(safe-area-inset-bottom));z-index:80;
  max-width:calc(100% - 24px);padding:9px 12px;border-radius:8px;background:var(--ink);color:#fff;
  font-size:13px;opacity:0;pointer-events:none}
#toast.show{opacity:1}

@media (max-width:680px){
  :root{--rl:132px}
  body{padding:16px 10px 96px}
  .top{align-items:flex-start;gap:8px}
  .brand{gap:7px}
  .brand-badge{width:32px;height:32px;font-size:17px}
  .clock{min-width:0;padding:5px 7px}
  .clock b{font-size:13px}
  .clock small{font-size:10px}
  .statline{margin-bottom:15px;gap:2px 12px}
  .home-wrap{gap:16px}
  .card{padding:12px 10px}
  .card-h{gap:5px;flex-wrap:wrap;margin-bottom:8px}
  .card-h h2{font-size:15px}
  .card td,.card th{padding:6px 3px}
  .hdate{font-size:10px}
  .hn{display:none}
  .tab{font-size:11px}
  .stage-controls .toolbar{gap:5px}
  .stage-controls .chip{padding-inline:9px}
  .grid .c-num,.grid .c-title{position:static}
  .grid thead .c-num,.grid thead .c-title{position:sticky;left:auto}
  .g-label,.g-hlab{padding:4px 6px}
  .g-hlab span,.g-label-card{padding:7px;gap:6px}
}
</style>
</head>
<body>
<div class="wrap">

  <header class="top">
    <div class="brand">
      <div class="brand-badge">◈</div>
      <div>
        <h1>Олимп<em>·</em>Трекер</h1>

      </div>
    </div>
    <div class="clock">
      <b id="clockTime">--<i>:</i>--<i>:</i>--</b><span class="tz">МСК</span>
      <small id="clockDate"></small>
    </div>
  </header>

  <div class="statline" id="statline"></div>

  <?php if (!$dbOk): ?>
    <div class="dbbanner">⚠ Ошибка базы данных: <?= htmlspecialchars($dbErr) ?>. Проверьте подключение и миграции в папке <b>migrations</b>.</div>
  <?php endif; ?>

  <main>
    <!-- ДОМОЙ -->
    <section class="view active" id="view-home">
      <div class="home-wrap" id="homeWrap"></div>
    </section>

    <!-- РАСПИСАНИЕ -->
    <section class="view" id="view-sched">
      <div class="sched-head">
        <div class="legend" id="legend"></div>
        <span style="flex:1"></span>
        <div class="seg" id="schedSort">
          <button data-k="reg_end" class="on" aria-pressed="true">по закрытию ↑</button>
          <button data-k="reg_start" aria-pressed="false">по старту ↑</button>
        </div>
      </div>
      <div class="gantt panel" id="gantt"></div>
    </section>

    <section class="view" id="view-stages" aria-label="Этапы">
      <div class="stage-controls">
        <div class="seg" id="stageMode" role="group" aria-label="Вид этапов">
          <button class="on" data-mode="summary" aria-pressed="true">Сводка</button>
          <button data-mode="timeline" aria-pressed="false">Расписание</button>
        </div>
        <div class="toolbar" id="stageFilters" role="group" aria-label="Фильтр по турам">
          <button class="chip is-pressed" data-round="next" aria-pressed="true">Ближайший</button>
          <?php for ($round = 1; $round <= 4; $round++): ?>
            <button class="chip" data-round="<?= $round ?>" aria-pressed="false"><?= $round ?> тур</button>
          <?php endfor; ?>
          <button class="chip" data-round="all" aria-pressed="false" hidden>Все</button>
        </div>
      </div>
      <div class="home-wrap" id="stageSummary"></div>
      <div id="stageSchedule" hidden>
        <div class="sched-head legend" id="stageLegend"></div>
        <p id="stageHint" aria-live="polite">Нажмите на отметку тура, чтобы увидеть даты, время и формат.</p>
        <div class="gantt panel" id="stageGantt"></div>
      </div>
    </section>

    <!-- СПИСОК -->
    <section class="view" id="view-list">
      <div class="toolbar" id="chips"></div>
      <div class="tscroll panel">
        <table class="grid">
          <thead>
            <tr>
              <th class="c-num">№</th>
              <th class="c-title">Олимпиада</th>
              <th class="c-profile">Профиль</th>
              <th class="c-date sortable" data-sort="reg_start">Начало регистрации <span class="arr"></span></th>
              <th class="c-date sortable" data-sort="reg_end">Окончание регистрации <span class="arr"></span></th>
              <th>Этапов</th>
              <?php for ($round = 1; $round <= 4; $round++): ?>
                <th class="c-date sortable" data-sort="stage<?= $round ?>_date"><?= $round ?> тур <span class="arr"></span></th>
              <?php endfor; ?>
              <th>Уровень</th>
            </tr>
          </thead>
          <tbody id="listBody"></tbody>
        </table>
      </div>
      <div class="list-foot">
        <button class="nbtn acc" id="btnAdd" aria-label="Добавить строку">+</button>
        <button class="nbtn" id="btnImport">Вставить Markdown</button>
        <button class="nbtn" id="btnExport">Скопировать Markdown</button>
        <span style="color:var(--mut);font-size:12px;font-weight:600">Время — МСК. Если указана одна дата, тур считается однодневным. При уменьшении числа этапов данные лишних туров удаляются.</span>
      </div>
    </section>
  </main>
</div>

<!-- нижняя панель -->
<nav class="tabbar panel" aria-label="Основная навигация">
  <button class="tab is-pressed" data-view="home" aria-current="page" aria-label="Домой">
    <svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5"/></svg>
    <span>Домой</span>
  </button>
  <button class="tab" data-view="sched" aria-label="Расписание">
    <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M8 3v4M16 3v4M3 10.5h18M8 15h3"/></svg>
    <span>Расписание</span>
  </button>
  <button class="tab" data-view="stages" aria-label="Этапы">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16M6 15V9m6 6V5m6 10v-3"/><circle cx="6" cy="7" r="2"/><circle cx="12" cy="3" r="2"/><circle cx="18" cy="10" r="2"/></svg>
    <span>Этапы</span>
  </button>
  <button class="tab" data-view="list" aria-label="Список">
    <svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1.3" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1.3" fill="currentColor" stroke="none"/><circle cx="4" cy="18" r="1.3" fill="currentColor" stroke="none"/></svg>
    <span>Список</span>
  </button>
</nav>

<!-- диалог импорта -->
<dialog class="dlg" id="dlg">
  <h3>Импорт таблицы из Markdown</h3>
  <p>Укажите число этапов от 1 до 4. Для однодневного тура достаточно одной даты. Пример даты со временем по МСК — «2026-12-05 14:00». Таблицы старых форматов тоже поддерживаются.</p>
  <textarea id="mdIn" rows="8" aria-label="Таблица Markdown" placeholder="| № | Олимпиада | Профиль | Начало регистрации | Окончание регистрации | Начало 1 тура | Начало 2 тура | Начало 3 тура | Начало 4 тура | Конец 1 тура | Конец 2 тура | Конец 3 тура | Конец 4 тура | Формат 1 тура | Формат 2 тура | Формат 3 тура | Формат 4 тура | Кол-во этапов | Уровень |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Высшая проба | Математика | 2026-11-25 | 2026-12-01 | 2026-12-05 14:00 | 2027-02-05 |  |  | 2026-12-07 18:00 |  |  |  | Прокторинг | Очно |  |  | 2 | I |"></textarea>
  <div class="modes">
    <label><input type="radio" name="mdMode" value="append" checked> Дописать к списку</label>
    <label><input type="radio" name="mdMode" value="replace"> Заменить весь список</label>
  </div>
  <div class="dlg-btns">
    <button class="nbtn ghost" id="dlgClose">Отмена</button>
    <button class="nbtn acc" id="dlgGo">Импортировать</button>
  </div>
</dialog>

<div id="toast" role="status"></div>

<script>
/* ================= ДАННЫЕ ================= */
let DATA = <?= json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
const DB_OK = <?= $dbOk ? 'true' : 'false' ?>;

const MONTHS = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
const MONTHS_S = ['янв','фев','мар','апр','мая','июн','июл','авг','сен','окт','ноя','дек'];
const SUBJ = {
  'Математика':  {c:'#b7e06f', d:'#466a22'},
  'Физика':      {c:'#9ec9ea', d:'#315d84'},
  'Информатика': {c:'#93ddcd', d:'#24695c'},
  'Русский язык':{c:'#f2a9a0', d:'#94443b'},
  'Прочее':      {c:'#cfd5e0', d:'#566575'}
};
const state = {
  filter:null, sort:{key:'reg_start',dir:'asc'}, schedKey:'reg_end', stageMode:'summary', stageFilter:'next'
};
const roundNumbers = [1, 2, 3, 4];
const stageField = round => `stage${round}_date`;
const stageEndField = round => `stage${round}_end`;
const stageFormatField = round => `stage${round}_format`;
const dateFields = ['reg_start', 'reg_end', ...roundNumbers.map(stageField), ...roundNumbers.map(stageEndField), 'final_date'];

/* ================= УТИЛИТЫ ================= */
const $  = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

function mskParts(d = new Date()){
  const p = {};
  new Intl.DateTimeFormat('ru-RU',{timeZone:'Europe/Moscow',year:'numeric',month:'2-digit',day:'2-digit',
    hour:'2-digit',minute:'2-digit',second:'2-digit',hourCycle:'h23'}).formatToParts(d).forEach(x=>p[x.type]=x.value);
  return p;
}
const mskNowISO = () => { const p = mskParts(); return `${p.year}-${p.month}-${p.day}T${p.hour}:${p.minute}:${p.second}`; };
const mskToday  = (dt=new Date()) => { const p=mskParts(dt); return `${p.year}-${p.month}-${p.day}`; };
function addDaysISO(iso, n){ const [y,m,d]=iso.split('-').map(Number); const t=new Date(Date.UTC(y,m-1,d+n));
  return t.toISOString().slice(0,10); }
const dOf   = v => v ? v.slice(0,10) : null;
const tOf   = v => (v && v.slice(11,16) !== '23:59') ? v.slice(11,16) : '';
const mkUTC = d => { const [y,m,dd]=d.split('-').map(Number); return Date.UTC(y,m-1,dd); };
const dDays = (a,b) => Math.round((mkUTC(b)-mkUTC(a))/86400000);

function fmtDate(v){
  if(!v) return 'Уточняется на сайте';
  const [d,t]=v.split('T'); const [Y,M,D]=d.split('-').map(Number);
  const base = `${D} ${MONTHS[M-1]} ${Y}`;
  return (t && t!=='23:59:00') ? `${base} (${t.slice(0,5)} МСК)` : base;
}
function fmtShort(v){ if(!v) return '—'; const [d,t]=v.split('T'); const [Y,M,D]=d.split('-').map(Number);
  return `${D} ${MONTHS_S[M-1]}` + (t && t!=='23:59:00' ? ` ${t.slice(0,5)}` : ''); }

function subjectOf(name){
  const s = String(name).toLowerCase();
  if(/физик/.test(s) && !/физкуль|физ-?р/.test(s)) return 'Физика';
  if(/информ|искусст|робот|програм|компьют|кибер|\bит\b|\bit\b/.test(s)) return 'Информатика';
  if(/матем|мехмат/.test(s)) return 'Математика';
  if(/рус|литер|филолог|журнал/.test(s)) return 'Русский язык';
  return 'Прочее';
}
function splitProfile(name){
  const m = String(name).match(/^(.*?)\s*[（(]([^()（）]*)[)）]\s*$/);
  return m ? [m[1], m[2]] : [name, ''];
}
function leftLabel(endISO){
  const ms = Date.parse(endISO) - Date.parse(mskNowISO());
  const past = ms < 0, a = Math.abs(ms);
  const mins = Math.floor(a/60000), hrs = Math.floor(mins/60), days = Math.floor(hrs/24);
  const s = days>=1 ? `${days} дн ${hrs%24} ч` : hrs>=1 ? `${hrs} ч ${mins%60} мин` : `${mins} мин`;
  return past ? `закрылась ${s} назад` : `закроется через ${s}`;
}
function toast(msg){ const t=$('#toast'); t.textContent=msg; t.classList.add('show');
  clearTimeout(t._h); t._h=setTimeout(()=>t.classList.remove('show'),2600); }

async function api(action, data={}){
  const fd = new FormData(); fd.set('action', action);
  for(const k in data) fd.set(k, data[k]);
  const r = await fetch('api.php', {method:'POST', body:fd});
  const j = await r.json();
  if(!j.ok) throw new Error(j.error || 'ошибка сервера');
  return j;
}
function normRow(r){ dateFields.forEach(f=>{ if(r[f]) r[f]=r[f].replace(' ','T'); }); return r; }
function patchRow(row){ normRow(row); const i = DATA.findIndex(x=>x.id==row.id); if(i>-1) DATA[i]=row; }

/* ================= НАВИГАЦИЯ ================= */
$$('.tab').forEach(btn=>btn.addEventListener('click', ()=>{
  $$('.tab').forEach(b => {
    b.classList.toggle('is-pressed', b === btn);
    if (b === btn) b.setAttribute('aria-current', 'page');
    else b.removeAttribute('aria-current');
  });
  const v = btn.dataset.view;
  $$('.view').forEach(s=>s.classList.toggle('active', s.id==='view-'+v));
  if(v==='home') renderHome();
  if(v==='sched') renderSchedule();
  if(v==='list') renderList();
  if(v==='stages') renderStages();
}));

/* ================= ЧАСЫ И СТАТИСТИКА ================= */
function tickClock(){
  const p = mskParts();
  $('#clockTime').innerHTML = `${p.hour}<i>:</i>${p.minute}<i>:</i>${p.second}`;
  $('#clockDate').textContent = new Intl.DateTimeFormat('ru-RU',{timeZone:'Europe/Moscow',
    weekday:'long', day:'numeric', month:'long', year:'numeric'}).format(new Date());
}
function renderStats(){
  const now = mskNowISO();
  const withEnd = DATA.filter(r=>r.reg_end && r.reg_end > now).sort((a,b)=>a.reg_end<b.reg_end?-1:1);
  const nxt = withEnd[0];
  $('#statline').innerHTML =
    `<span>Олимпиад: <b>${DATA.length}</b></span>` +
    `<span>Регистрация отмечена: <b>${DATA.filter(r=>+r.registered).length}</b></span>` +
    (nxt ? `<span>Ближайший дедлайн: <b>${esc(splitProfile(nxt.name)[0])}</b> — ${fmtDate(nxt.reg_end)}</span>`
         : `<span>активных дедлайнов нет</span>`);
}
setInterval(tickClock, 1000);

/* ================= ДОМОЙ ================= */
function homeBuckets(){
  const ts = v => Date.parse(String(v).replace(' ', 'T'));
  const nowTs = ts(mskNowISO());
  const tomorrow20Ts = ts(addDaysISO(mskToday(), 1) + 'T20:00:00');
  const in7Ts = ts(addDaysISO(mskToday(), 7) + 'T23:59:59');
  const dayAgoTs = nowTs - 86400000;
  const byEnd = (a,b)=> a.reg_end < b.reg_end ? -1 : 1;

  // КРАСНЫЙ: дедлайн ещё впереди — закрытие сегодня или завтра строго до 20:00
  const urgent = DATA.filter(r => r.reg_end
      && ts(r.reg_end) > nowTs && ts(r.reg_end) < tomorrow20Ts).sort(byEnd);

  // СЕРЫЙ: закрылась в последние 24 часа и регистрация НЕ отмечена
  const missed = DATA.filter(r => r.reg_end && !+r.registered
      && ts(r.reg_end) <= nowTs && ts(r.reg_end) >= dayAgoTs).sort(byEnd);

  // все закрытые за сутки — для подсказки, почему серый блок может быть пуст
  const recent = DATA.filter(r => r.reg_end
      && ts(r.reg_end) <= nowTs && ts(r.reg_end) >= dayAgoTs);

  // БЛИЖАЙШЕЕ: закроется в ближайшие 7 дней (кроме уже попавших в красный блок)
  const uIds = new Set(urgent.map(r=>r.id));
  const soon = DATA.filter(r => r.reg_end
      && ts(r.reg_end) > nowTs && ts(r.reg_end) <= in7Ts && !uIds.has(r.id)).sort(byEnd);

  return {urgent, missed, soon, recent};
}
function ckBox(row, round = null) {
  const checked = round ? row.stage_attendance & (1 << (round - 1)) : Number(row.registered);
  const label = round ? `Участие · ${round} тур · ${row.name}` : `Регистрация · ${row.name}`;
  return `<label class="ck"><input type="checkbox" aria-label="${esc(label)}" data-id="${row.id}"
    ${round ? `data-round="${round}"` : ''} ${checked ? 'checked' : ''}></label>`;
}

function cardFrame(tone, tag, title, count, body) {
  return `<article class="card tone-${tone}">
    <header class="card-h"><h2>${title}</h2>${tag ? `<span class="tag">${tag}</span>` : ''}
      <span class="count">${count}</span></header>
    ${body}</article>`;
}

function hRow(r, i, dateCells, late){
  const [base, prof] = splitProfile(r.name);
  const subj = subjectOf(r.name), col = SUBJ[subj].d;
  return `<tr>
    <td class="hn">${i+1}</td>
    <td class="hname"><b>${esc(base)}</b> ${prof?`<em style="--subj:${col};color:${col}">(${esc(prof)})</em>`:''}
      <small class="${late?'late':''}">${leftLabel(r.reg_end)}</small></td>
    ${dateCells}
    <td style="text-align:right">${ckBox(r)}</td></tr>`;
}
function cardHTML(tone, tag, title, rows, mode, recent){
  let body = '';
  if(!rows.length){
    let e;
    if(mode==='urgent') e = 'Сегодня и завтра дедлайнов нет.';
    else if(mode==='missed') e = (recent && recent.length)
        ? `Все регистрации, закрывшиеся за последние сутки (${recent.length}), отмечены.`
        : 'За последние сутки регистрации не закрывались.';
    else e = 'Других дедлайнов в ближайшие 7 дней нет.';
    body = `<div class="empty">${e}</div>`;
  } else {
    const head = mode==='soon'
      ? `<tr><td></td><td class="hdate cap">Олимпиада (профиль)</td><td class="hdate cap">Начало регистрации</td><td class="hdate cap">Окончание регистрации</td><td></td></tr>`
      : `<tr><td></td><td class="hdate cap">Олимпиада (профиль)</td><td class="hdate cap">Регистрация закрывается</td><td></td></tr>`;
    const tr = rows.map((r,i)=> mode==='soon'
        ? hRow(r,i,`<td class="hdate">${fmtDate(r.reg_start)}</td><td class="hdate">${fmtDate(r.reg_end)}</td>`)
        : hRow(r,i,`<td class="hdate">${fmtDate(r.reg_end)}</td>`, mode==='missed')).join('');
    body = `<table><thead>${head}</thead><tbody>${tr}</tbody></table>`;
  }
  return cardFrame(tone, tag, title, rows.length, body);
}
function renderHome(){
  const {urgent, missed, soon, recent} = homeBuckets();
  const u = cardHTML('urgent','дедлайн ≤ завтра','Срочные регистрации', urgent, 'urgent');
  const m = cardHTML('missed','регистрация упущена','Пропустил', missed, 'missed', recent);
  const s = cardHTML('soon','горизонт 7 дней','Ближайшие регистрации', soon, 'soon');
  // «Пропустил» пустой → опускается в самый низ
  $('#homeWrap').innerHTML = missed.length ? u + m + s : u + s + m;
}
$('#homeWrap').addEventListener('change', async e=>{
  const cb = e.target.closest('input[data-id]'); if(!cb) return;
  try{
    const j = await api('toggle', {id: cb.dataset.id});
    const r = DATA.find(x=>x.id==cb.dataset.id); if(r) r.registered = j.registered;
    setTimeout(renderHome, 350); renderStats();
  }catch(err){ toast(err.message); cb.checked = !cb.checked; }
});
setInterval(() => {
  if ($('#view-home').classList.contains('active')) renderHome();
  if ($('#view-stages').classList.contains('active') && state.stageMode === 'summary'
      && !$('#stageSummary').contains(document.activeElement)) renderStageSummary();
  renderStats();
}, 60000);

/* ================= СПИСОК ================= */
const FILTERS = [null,'Русский язык','Математика','Физика','Информатика'];
function renderChips(){
  $('#chips').innerHTML = FILTERS.map(f=>{
    const on = state.filter===f ? ' is-pressed' : '';
    const dot = f ? `<span class="cdot" style="--cc:${SUBJ[f].c}"></span>` : '';
    return `<button class="chip${on}" data-f="${f??''}">${dot}${f??'Все'}</button>`;
  }).join('');
}
$('#chips').addEventListener('click', e=>{
  const b = e.target.closest('.chip'); if(!b) return;
  const f = b.dataset.f || null;
  state.filter = (state.filter===f) ? null : f;
  renderChips(); renderList();
});

function visibleRows(){
  let rows = state.filter ? DATA.filter(r=>subjectOf(r.name)===state.filter) : DATA.slice();
  const {key, dir} = state.sort;
  rows.sort((a,b)=>{
    const av=a[key], bv=b[key];
    if(av==null && bv==null) return a.id-b.id;
    if(av==null) return 1; if(bv==null) return -1;   // «Уточняется» всегда внизу
    const c = av<bv?-1:av>bv?1:0; return dir==='desc'?-c:c;
  });
  return rows;
}
function stageFormatSelect(row, round) {
  const field = stageFormatField(round);
  const format = row[field] || '';
  const options = [['', 'Формат уточняется'], ['Очно', 'Очно'], ['Дистанционно', 'Дистанционно'],
    ['Прокторинг', 'Прокторинг']];
  if (!options.some(([value]) => value === format)) options.push([format, format]);
  return `<select class="ci round-format" data-f="${field}" aria-label="Формат: ${round} тур">
    ${options.map(([value, label]) => `<option value="${esc(value)}" ${value === format ? 'selected' : ''}>${esc(label)}</option>`).join('')}
  </select>`;
}

function datePair(r, field, dateLabel, timeLabel, timeHint) {
  const value = r[field];
  return `<div class="datepair">
    <input type="date" class="ci di" data-f="${field}" aria-label="${dateLabel}" value="${dOf(value) ?? ''}">
    <input type="time" class="ci ti" data-f="${field}__t" aria-label="${timeLabel}" value="${tOf(value)}" title="${timeHint}">
  </div>`;
}

function dateCell(r, field) {
  const label = field === 'reg_start' ? 'Начало регистрации' : 'Окончание регистрации';
  return `<td class="c-date">
    ${datePair(r, field, `Дата: ${label}`, `Время: ${label}`, 'Пусто = 23:59')}
    <span class="hint-unspec" ${r[field] ? 'hidden' : ''}>Дата уточняется</span>
  </td>`;
}

function roundCell(r, round) {
  if (round > Number(r.stages_count)) {
    return '<td class="c-date round-inactive" title="Тур не проводится">—</td>';
  }
  return `<td class="c-date">
    <div class="round-endpoint"><span>Начало</span>
      ${datePair(r, stageField(round), `Дата начала: ${round} тур`, `Время начала: ${round} тур`,
        'Без времени: 00:00 для периода, 23:59 для одной даты')}
    </div>
    <div class="round-endpoint"><span>Окончание</span>
      ${datePair(r, stageEndField(round), `Дата окончания: ${round} тур`, `Время окончания: ${round} тур`, 'Пусто = 23:59')}
    </div>
    ${stageFormatSelect(r, round)}
  </td>`;
}

function rowTR(r, n) {
  const [title, prof] = splitProfile(r.name);
  const subject = subjectOf(r.name);
  const options = Number(r.stages_count) > 4 ? [...roundNumbers, Number(r.stages_count)] : roundNumbers;
  return `<tr data-id="${r.id}">
    <td class="c-num">${n}</td>
    <td class="c-title">
      <input class="ci" data-f="title" aria-label="Олимпиада" value="${esc(title)}" placeholder="Олимпиада">
    </td>
    <td class="c-profile"><div class="cell-name">
      <input class="ci subject-profile" data-f="profile" aria-label="Профиль" title="Предмет: ${subject}"
        style="--subject-color:${SUBJ[subject].c}" value="${esc(prof)}" placeholder="Профиль">
      <button class="del" aria-label="Удалить строку">✕</button></div></td>
    ${dateCell(r,'reg_start')}${dateCell(r,'reg_end')}
    <td><select class="ci" data-f="stages_count" aria-label="Количество этапов">${options.map(x=>`<option ${+r.stages_count===x?'selected':''}>${x}</option>`).join('')}</select></td>
    ${roundNumbers.map(round => roundCell(r, round)).join('')}
    <td style="text-align:center"><button class="lvl-badge l${+r.lvl}">У${+r.lvl}</button>
      <select class="ci" data-f="lvl" aria-label="Уровень" hidden>${[1,2,3].map(x=>`<option ${+r.lvl===x?'selected':''}>${x}</option>`).join('')}</select></td>
  </tr>`;
}
function renderList(){
  const rows = visibleRows();
  $('#listBody').innerHTML = rows.length
    ? rows.map((r,i)=>rowTR(r,i+1)).join('')
    : `<tr><td colspan="11"><div class="empty">Добавьте олимпиаду или вставьте таблицу Markdown.</div></td></tr>`;
  $$('.grid thead th.sortable').forEach(th=>{
    const on = th.dataset.sort===state.sort.key;
    th.querySelector('.arr').textContent = on ? (state.sort.dir==='asc'?'▲':'▼') : '';
  });
}
$$('.grid thead th.sortable').forEach(th=>th.addEventListener('click', ()=>{
  const k = th.dataset.sort;
  if(state.sort.key===k) state.sort.dir = state.sort.dir==='asc'?'desc':'asc';
  else state.sort = {key:k, dir:'asc'};
  renderList();
}));
// выбор уровня кликом по бейджу
$('#listBody').addEventListener('click', e=>{
  const bd = e.target.closest('.lvl-badge'); if(!bd) return;
  const sel = bd.parentElement.querySelector('select');
  sel.hidden = false; bd.hidden = true; sel.focus();
});
const pendingRowEdits = new Map();
$('#listBody').addEventListener('change', async e=>{
  const inp = e.target.closest('[data-f]'); if(!inp) return;
  const tr = inp.closest('tr'), id = tr.dataset.id;
  const original = DATA.find(r => r.id == id);
  let f = inp.dataset.f, value = inp.value;

  if(f.endsWith('__t')){
    const base = f.slice(0,-3);
    const d = tr.querySelector(`[data-f="${base}"]`).value;
    f = base; value = d ? `${d}T${inp.value||''}` : '';
  } else if (dateFields.includes(f)) {
    const time = tr.querySelector(`[data-f="${f}__t"]`).value;
    value = value && time ? `${value}T${time}` : value;
  } else if(f==='title' || f==='profile'){
    // два поля склеиваются в одно хранимое имя «Олимпиада (Профиль)»
    const t = tr.querySelector('[data-f="title"]').value.trim();
    const p = tr.querySelector('[data-f="profile"]').value.trim();
    f = 'name'; value = p ? `${t} (${p})` : t;
  }
  const previous = pendingRowEdits.get(id) ?? Promise.resolve();
  const request = previous.catch(() => {}).then(() => api('update', {id, field:f, value}));
  pendingRowEdits.set(id, request);
  if (f === 'stages_count') inp.disabled = true;
  try {
    const j = await request;
    patchRow(j.row);
    if (f === 'stages_count') {
      const cells = document.createElement('tr');
      cells.innerHTML = roundNumbers.map(round => roundCell(j.row, round)).join('');
      Array.from(cells.cells).forEach((cell, i) => tr.cells[i + 6].replaceWith(cell));
      inp.value = j.row.stages_count;
    } else if (f === 'name') {
      const subject = subjectOf(j.row.name);
      if (state.filter && state.filter !== subject) {
        renderList();
      } else {
        const profile = $(`#listBody tr[data-id="${id}"] .subject-profile`);
        if (profile) {
          profile.style.setProperty('--subject-color', SUBJ[subject].c);
          profile.title = `Предмет: ${subject}`;
        }
      }
    } else if (f === 'lvl') {
      const badge = tr.querySelector('.lvl-badge');
      badge.textContent = 'У' + j.row.lvl;
      badge.className = 'lvl-badge l' + j.row.lvl;
      badge.hidden = false;
      inp.hidden = true;
      badge.focus();
    } else if (dateFields.includes(f)) {
      const hint = inp.closest('td').querySelector('.hint-unspec');
      if (hint) hint.hidden = Boolean(j.row[f]);
      // Keep focus and any newer edit in the same pair.
      const pair = inp.closest('.datepair');
      if (!j.row[f] && !pair.querySelector('.di').value) pair.querySelector('.ti').value = '';
    }
    renderStats();
    if ($('#view-stages').classList.contains('active')) renderStages();
  } catch (err) {
    toast(err.message);
    if (pendingRowEdits.get(id) === request && original && tr.isConnected) {
      const current = DATA.find(r => r.id == id);
      tr.outerHTML = rowTR(current, tr.cells[0].textContent);
    }
  } finally {
    if (pendingRowEdits.get(id) === request) pendingRowEdits.delete(id);
    inp.disabled = false;
  }
});
// удаление: первый клик — «подтверждение», второй — удаление
$('#listBody').addEventListener('click', async e=>{
  const del = e.target.closest('.del'); if(!del) return;
  if(!del.classList.contains('armed')){
    del.classList.add('armed'); del.textContent='точно?';
    del._t = setTimeout(()=>{ del.classList.remove('armed'); del.textContent='✕'; }, 2500);
    return;
  }
  clearTimeout(del._t);
  const tr = del.closest('tr');
  try{
    await api('delete',{id: tr.dataset.id});
    DATA = DATA.filter(r=>r.id!=tr.dataset.id);
    renderList(); renderStats();
    toast('Строка удалена');
  }catch(err){ toast(err.message); }
});
$('#btnAdd').addEventListener('click', async ()=>{
  try{
    const j = await api('create'); normRow(j.row); DATA.push(j.row);
    renderList(); renderStats();
    const tr = $('#listBody').querySelector(`tr[data-id="${j.row.id}"]`);
    tr?.scrollIntoView({block:'center', behavior:'smooth'});
    tr?.querySelector('[data-f="title"]').focus();
  }catch(err){ toast(err.message); }
});

/* ---- markdown: импорт / экспорт ---- */
$('#btnImport').addEventListener('click', ()=>$('#dlg').showModal());
$('#dlgClose').addEventListener('click', ()=>$('#dlg').close());
$('#dlgGo').addEventListener('click', async ()=>{
  const md = $('#mdIn').value.trim();
  if(!md){ toast('Вставьте текст таблицы'); return; }
  const mode = document.querySelector('input[name=mdMode]:checked').value;
  try{
    const j = await api('import',{md, mode});
    $('#dlg').close(); $('#mdIn').value='';
    toast(`Импортировано строк: ${j.added}${j.skipped?` · пропущено: ${j.skipped}`:''}`);
    const l = await api('list'); DATA = l.rows.map(normRow);
    renderAll();
  }catch(err){ toast(err.message); }
});
$('#btnExport').addEventListener('click', async ()=>{
  try{
    const j = await api('export');
    await navigator.clipboard.writeText(j.md);
    toast('Markdown скопирован в буфер обмена');
  }catch(err){ toast('Не удалось скопировать: '+err.message); }
});

/* ================= ЭТАПЫ ================= */
const DAY_MS = 86400000;
const stageTime = date => Date.parse(date + 'Z');
const stageNow = () => stageTime(mskNowISO());

function roundFormat(value) {
  if (/проктор|proctor/iu.test(value)) return {mark: 'proctored', label: 'Прокторинг'};
  const remote = /дистанц|заочн|онлайн|online/iu.test(value);
  const onsite = /(?:^|[^а-яё])очн|офлайн|offline/iu.test(value);
  return {
    mark: remote && !onsite ? 'remote' : '',
    label: remote && onsite ? 'Смешанный формат' : remote ? 'Дистанционно' : onsite ? 'Очно' : ''
  };
}

function roundsFor(row) {
  return roundNumbers.filter(round => round <= Number(row.stages_count)).map(round => {
    const from = row[stageField(round)], to = row[stageEndField(round)];
    const start = from && to && !tOf(from) ? `${dOf(from)}T00:00:00` : (from || to);
    return {row, round, from, to, start, end: to || from,
      format: roundFormat(row[stageFormatField(round)] || ''),
      attended: Boolean(row.stage_attendance & (1 << (round - 1)))};
  });
}

function fmtRoundPeriod({from, to, start, end}) {
  if (!from || !to || from === to) return fmtDate(from || to);
  if (dOf(from) !== dOf(to)) return `${fmtDate(from)} — ${fmtDate(to)}`;
  return `${fmtDate(dOf(from))} · ${start.slice(11,16)}–${end.slice(11,16)} МСК`;
}

const byStageDate = (a, b) => a.start.localeCompare(b.start) || a.round - b.round;
const byStageEnd = (a, b) => a.end.localeCompare(b.end) || byStageDate(a, b);
function upcomingRound(rounds, now, compare = byStageDate) {
  return rounds.filter(r => r.end && stageTime(r.end) >= now).sort(compare)[0];
}

function toggleButton(button, on, className) {
  button.classList.toggle(className, on);
  button.setAttribute('aria-pressed', on);
}

$('#stageMode').addEventListener('click', e => {
  const mode = e.target.closest('button')?.dataset.mode;
  if (!mode || mode === state.stageMode) return;
  state.stageMode = mode;
  if (mode === 'summary' && state.stageFilter === 'all') state.stageFilter = 'next';
  renderStages();
});

$('#stageFilters').addEventListener('click', e => {
  const filter = e.target.closest('button')?.dataset.round;
  if (!filter || filter === state.stageFilter) return;
  state.stageFilter = filter;
  renderStages();
});

function renderStages() {
  const timeline = state.stageMode === 'timeline';
  $$('#stageMode button').forEach(b => toggleButton(b, b.dataset.mode === state.stageMode, 'on'));
  $$('#stageFilters button').forEach(b => {
    b.hidden = b.dataset.round === 'all' && !timeline;
    toggleButton(b, b.dataset.round === state.stageFilter, 'is-pressed');
  });
  $('#stageSummary').hidden = timeline;
  $('#stageSchedule').hidden = !timeline;
  if (timeline) renderStageSchedule();
  else renderStageSummary();
}

function stageCard(tone, title, entries, empty) {
  const now = stageNow();
  const rows = entries.map((event, i) => {
    const {row, round, start, end, format} = event;
    const ongoing = start && end && stageTime(start) <= now && stageTime(end) > now;
    const [base, profile] = splitProfile(row.name);
    const check = round ? ckBox(row, round) : '';
    return `<tr><td class="hn">${i + 1}</td>
      <td class="hname"><b>${esc(base)}</b> ${profile ? `<em style="--subj:${SUBJ[subjectOf(row.name)].d}">(${esc(profile)})</em>` : ''}
        <small>${round ? `${round} тур${format.label ? ` · ${esc(format.label)}` : ''}${ongoing ? ' · Идёт сейчас' : ''}` : `Всего этапов: ${row.stages_count}`}</small></td>
      <td class="hdate">${round ? fmtRoundPeriod(event) : 'Тур не проводится'}</td><td>${check}</td></tr>`;
  }).join('');
  const body = rows ? `<table aria-label="${title}"><tbody>${rows}</tbody></table>` : `<div class="empty">${empty}</div>`;
  return cardFrame(tone, '', title, entries.length, body);
}

function renderStageSummary() {
  const now = stageNow(), nearest = state.stageFilter === 'next';
  const urgent = [], missed = [], soon = [], later = [], attended = [], undated = [];
  for (const row of DATA) {
    const rounds = roundsFor(row);
    if (nearest) {
      const next = upcomingRound(rounds, now, byStageEnd);
      if (next) place(next);
      rounds.filter(r => r.end && stageTime(r.end) < now && stageTime(r.end) >= now - DAY_MS).forEach(place);
      if (!next && rounds.some(r => !r.start)) {
        undated.push(rounds.find(r => !r.start));
      }
    } else {
      const round = rounds.find(r => r.round === Number(state.stageFilter));
      if (round?.start) place(round);
      else undated.push(round ?? {row});
    }
  }

  function place(round) {
    const delta = stageTime(round.end) - now;
    if (delta < 0) (round.attended ? attended : missed).push(round);
    else if (delta <= DAY_MS) urgent.push(round);
    else if (delta <= 7 * DAY_MS) soon.push(round);
    else later.push(round);
  }

  [urgent, soon, later].forEach(rows => rows.sort(byStageEnd));
  [missed, attended].forEach(rows => rows.sort((a, b) => b.end.localeCompare(a.end)));
  const urgentCard = stageCard('urgent', 'Закроются в ближайшие 24 часа', urgent, 'За ближайшие 24 часа туры не закроются.');
  const missedCard = stageCard('missed', 'Пропустил', missed, 'Пропущенных туров нет.');
  const soonCard = stageCard('soon', 'Закроются в ближайшие 7 дней', soon, 'Других туров с окончанием в ближайшие 7 дней нет.');
  $('#stageSummary').innerHTML = urgentCard + (missed.length ? missedCard + soonCard : soonCard + missedCard)
    + (later.length ? stageCard('plain', 'Позже', later) : '')
    + (attended.length ? stageCard('plain', 'Участие отмечено', attended) : '')
    + (undated.length ? stageCard('plain', 'Без даты', undated) : '');
}

$('#stageSummary').addEventListener('change', async e => {
  const cb = e.target.closest('input[data-round]');
  if (!cb) return;
  cb.disabled = true;
  try {
    const j = await api('participation', {id: cb.dataset.id, round: cb.dataset.round, attended: Number(cb.checked)});
    const row = DATA.find(r => r.id == cb.dataset.id);
    if (row) row.stage_attendance = j.stage_attendance;
    renderStageSummary();
    $('#stageSummary').querySelector(`input[data-id="${cb.dataset.id}"][data-round="${cb.dataset.round}"]`)?.focus({preventScroll: true});
  } catch (err) {
    cb.checked = !cb.checked;
    toast(err.message);
  } finally {
    cb.disabled = false;
  }
});

const stageHint = 'Нажмите на отметку тура, чтобы увидеть даты, время и формат.';
$('#stageGantt').addEventListener('click', e => {
  const marker = e.target.closest('[data-stage-detail]');
  if (marker) $('#stageHint').textContent = marker.dataset.stageDetail;
});

function renderStageSchedule() {
  const filter = state.stageFilter, now = stageNow();
  const entries = DATA.map(row => {
    const rounds = roundsFor(row);
    const next = filter === 'next' ? upcomingRound(rounds, now) : null;
    const events = filter === 'next' ? (next ? [next] : [])
      : rounds.filter(r => r.start && (filter === 'all' || r.round === Number(filter))).sort(byStageDate);
    return {row, events};
  }).filter(entry => entry.events.length).sort((a, b) => byStageDate(a.events[0], b.events[0]));
  const legend = $('#stageLegend');
  renderLegend(legend, entries.map(entry => entry.row));
  const marks = new Set(entries.flatMap(({events}) => events.map(event => event.format.mark)));
  for (const [mark, label] of [['remote', 'Дистанционно'], ['proctored', 'Прокторинг']]) {
    if (marks.has(mark)) {
      legend.insertAdjacentHTML('beforeend', `<span class="lg lg-${mark}"><i aria-hidden="true"></i>${label}</span>`);
    }
  }
  $('#stageHint').textContent = stageHint;
  $('#stageHint').hidden = !entries.length;

  const dates = entries.flatMap(entry => entry.events.flatMap(event => [dOf(event.start), dOf(event.end)]));
  renderTimeline($('#stageGantt'), dates, ({min, ppd}) => entries.map(({row, events}) => {
    const lanes = [];
    const markers = events.map(event => {
      const {round, start, end, format} = event;
      const left = (dDays(min, dOf(start)) + 0.5) * ppd - 11.5;
      const span = dDays(dOf(start), dOf(end));
      const width = span * ppd + 23;
      // Tours on the same or overlapping days get separate lanes.
      const free = lanes.findIndex(right => left >= right + 2);
      const lane = free === -1 ? lanes.length : free;
      lanes[lane] = left + width;
      const detail = esc(`${row.name} · ${round} тур · ${fmtRoundPeriod(event)} · ${format.label || 'Формат уточняется'}`);
      return `<button class="g-stage${span ? ' is-range' : ''}${format.mark ? ` is-${format.mark}` : ''}"
        style="left:${left}px;top:${9 + lane * 27}px;width:${width}px"
        data-stage-detail="${detail}" aria-label="${detail}" title="${detail}">${round}</button>`;
    }).join('');
    return timelineRow(row, markers, '', Math.max(49, lanes.length * 27 + 17));
  }).join(''), 'Для выбранного тура нет дат.');
}

/* ================= РАСПИСАНИЕ ================= */
$('#schedSort').addEventListener('click', e => {
  const button = e.target.closest('button');
  if (!button) return;
  state.schedKey = button.dataset.k;
  $$('#schedSort button').forEach(x => toggleButton(x, x === button, 'on'));
  renderSchedule();
});

function renderLegend(container, rows) {
  const counts = {};
  rows.forEach(row => {
    const subject = subjectOf(row.name);
    counts[subject] = (counts[subject] || 0) + 1;
  });
  container.innerHTML = Object.entries(SUBJ).filter(([s]) => counts[s])
    .map(([s, v]) => `<span class="lg" style="--cc:${v.c}"><i></i>${s} <small>${counts[s]}</small></span>`).join('');
}

function timelineRow(row, bar, subtitle = '', height = 49) {
  const subj = SUBJ[subjectOf(row.name)];
  const [base, profile] = splitProfile(row.name);
  return `<div class="g-row" style="--c:${subj.c};--subject-ink:${subj.d}">
    <div class="g-label"><div class="g-label-card"><span class="dot"></span><span>
      <b>${esc(base)}${profile ? ` <small>(${esc(profile)})</small>` : ''}</b>${subtitle ? `<small>${subtitle}</small>` : ''}
    </span></div></div>
    <div class="g-track" style="min-height:${height}px">${bar}</div>
  </div>`;
}

function renderTimeline(container, dates, renderRows, emptyMessage) {
  if (!dates.length) {
    container.innerHTML = `<div class="empty">${emptyMessage}</div>`;
    return;
  }
  const min = addDaysISO(dates.reduce((a, b) => a < b ? a : b), -3);
  const max = addDaysISO(dates.reduce((a, b) => a > b ? a : b), 3);
  const days = dDays(min, max) + 1;
  const ppd = days <= 31 ? 46 : days <= 62 ? 30 : days <= 124 ? 18 : 12;
  const tw = days * ppd;
  const today = mskToday();
  const todayX = (dDays(min, today) + 0.5) * ppd;
  const isToday = today >= min && today <= max;

  const monthFormat = new Intl.DateTimeFormat('ru-RU', {month:'long', year:'numeric', timeZone:'UTC'});
  const tickStep = ppd >= 40 ? 1 : ppd >= 24 ? 2 : ppd >= 16 ? 5 : 7;
  let months = '', ticks = '';
  for (let i = 0; i < days; i++) {
    const date = addDaysISO(min, i);
    if (!i || date.endsWith('-01')) {
      const name = monthFormat.format(new Date(date));
      months += `<div class="g-month" style="left:${i * ppd}px">${name}</div>`;
    }
    if (i % tickStep === 0) ticks += `<span class="g-tick" style="left:${(i + 0.5) * ppd}px">${Number(date.slice(-2))}</span>`;
  }

  container.innerHTML = `<div class="g-scroll"><div class="g-inner${isToday ? ' is-today' : ''}"
    style="--tw:${tw}px;--ppd:${ppd}px;--today-x:${todayX}px"><div class="g-headrow">
    <div class="g-hlab"><span>Олимпиада</span></div><div class="g-htl">${months}${ticks}</div></div>
    ${renderRows({min, ppd})}</div></div>`;
  if (isToday) container.querySelector('.g-scroll').scrollLeft = Math.max(0, todayX - 140);
}

function renderSchedule() {
  const key = state.schedKey;
  const rows = DATA.slice().sort((a, b) => {
    if (!a[key] || !b[key]) return Number(!a[key]) - Number(!b[key]) || a.id - b.id;
    return a[key].localeCompare(b[key]);
  });
  renderLegend($('#legend'), rows);
  const dates = rows.flatMap(r => [r.reg_start, r.reg_end, r.final_date].filter(Boolean).map(dOf));
  renderTimeline($('#gantt'), dates, ({min, ppd}) => rows.map(row => {
    const {reg_start: start, reg_end: end} = row;
    const close = end ? `Закрытие: ${fmtShort(end)}` : 'Закрытие уточняется';
    if (!end) return timelineRow(row, '<div class="g-bar unspec">Даты уточняются на сайте</div>', close);
    const from = start ? dOf(start) : addDaysISO(dOf(end), -3);
    const left = dDays(min, from) * ppd;
    const width = Math.max((dDays(from, dOf(end)) + 1) * ppd - 4, 26);
    const label = start ? `${fmtShort(start)} → ${fmtShort(end)}` : `до ${fmtShort(end)}`;
    const detail = start ? `Регистрация: ${fmtDate(start)} → ${fmtDate(end)}` : `Закрытие регистрации: ${fmtDate(end)}`;
    const bar = `<div class="g-bar" style="left:${left}px;width:${width}px" title="${esc(`${row.name} · ${detail}`)}">
      <b>${esc(splitProfile(row.name)[0])}</b><span>${label}</span></div>`;
    return timelineRow(row, bar, close);
  }).join(''), 'Нет данных с датами.');
}

/* ================= СТАРТ ================= */
function renderAll(){ renderChips(); renderList(); renderHome(); renderStats(); }
tickClock();
renderAll();
if(!DB_OK) toast('Ошибка базы данных');
</script>
</body>
</html>