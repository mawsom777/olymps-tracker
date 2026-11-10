<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
$rows = []; $dbOk = true; $dbErr = '';
try {
    $rows = db()->query('SELECT * FROM olympiads ORDER BY id')->fetchAll();
} catch (Throwable $e) {
    $dbOk = false; $dbErr = $e->getMessage();
}
function row_out(array $r): array {
    foreach (['reg_start','reg_end','stage1_date','final_date'] as $f)
        if ($r[$f]) $r[$f] = str_replace(' ', 'T', $r[$f]);
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@500;700;900&family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#e6eaf2; --panel:#e9edf5;
  --ink:#3a4258; --ink2:#5d6780; --mut:#8b94a8;
  --dark:#c3cbdd; --light:#ffffff;
  --lime:#b7e06f; --lime-d:#79a83c;
  --sky:#9ec9ea;  --sky-d:#5a8fbc;
  --rose:#f2a9a0; --rose-d:#c96b5e;
  --mint:#93ddcd; --mint-d:#47a592;
  --butter:#f4d98c; --lav:#c6b9ea; --gray:#cfd5e0;
  --rl:240px;
}
*{box-sizing:border-box; margin:0}
html{scroll-behavior:smooth}
body{
  background:var(--bg); color:var(--ink);
  font:15px/1.55 "Manrope",system-ui,sans-serif;
  padding:22px 16px 132px; min-height:100vh;
}
.bg-deco{position:fixed; inset:0; z-index:-1; pointer-events:none;
  background:
    radial-gradient(52rem 32rem at 88% -8%, rgba(158,201,234,.34), transparent 62%),
    radial-gradient(46rem 30rem at -10% 22%, rgba(183,224,111,.26), transparent 60%),
    radial-gradient(40rem 26rem at 108% 88%, rgba(242,169,160,.20), transparent 60%),
    radial-gradient(36rem 24rem at 6% 96%, rgba(147,221,205,.20), transparent 60%),
    radial-gradient(rgba(90,105,140,.06) 1px, transparent 1.4px);
  background-size:auto,auto,auto,auto,23px 23px;
}
.wrap{max-width:1180px; margin:0 auto}
::selection{background:rgba(183,224,111,.55)}
:focus-visible{outline:3px solid rgba(121,168,60,.45); outline-offset:2px; border-radius:8px}
::-webkit-scrollbar{height:10px;width:10px}
::-webkit-scrollbar-thumb{background:#c9d1e2;border-radius:99px;border:2px solid var(--bg)}
::-webkit-scrollbar-track{background:transparent}

/* ---- неоморфные поверхности ---- */
.neu{background:linear-gradient(148deg,#eef2f9,#dfe4ef);
  box-shadow:9px 9px 22px var(--dark), -9px -9px 22px var(--light);
  border-radius:20px}
.neu-in{box-shadow:inset 5px 5px 12px rgba(178,187,207,.8), inset -6px -6px 12px #fff;
  background:#e4e8f1}
.nbtn{display:inline-flex;align-items:center;gap:8px;justify-content:center;
  border:0;cursor:pointer;color:var(--ink);font:700 13.5px "Manrope";
  padding:11px 18px;border-radius:14px;background:linear-gradient(148deg,#eef2f9,#dfe4ef);
  box-shadow:6px 6px 14px var(--dark), -6px -6px 14px var(--light);
  transition:transform .16s, box-shadow .16s, color .16s}
.nbtn:hover{transform:translateY(-2px)}
.nbtn:active{box-shadow:inset 4px 4px 9px var(--dark), inset -5px -5px 10px #fff; transform:translateY(0)}
.nbtn.acc{background:linear-gradient(148deg,#cdeb97,#a9d163); color:#3c5417;
  box-shadow:6px 6px 14px rgba(121,168,60,.35), -6px -6px 14px #fff}
.nbtn.ghost{box-shadow:none;background:transparent;color:var(--mut)}
.nbtn.ghost:hover{color:var(--ink)}

/* ---- шапка ---- */
header.top{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;flex-wrap:wrap;margin-bottom:14px}
.brand{display:flex;align-items:center;gap:14px}
.brand-badge{width:56px;height:56px;border-radius:18px;display:grid;place-items:center;
  font:900 24px "Unbounded";color:#4c6a1f;background:linear-gradient(148deg,#d3ef9f,#aad45f);
  box-shadow:6px 6px 14px rgba(121,168,60,.35), -6px -6px 14px #fff}
.brand h1{font:900 clamp(22px,4vw,34px)/1 "Unbounded"; letter-spacing:.5px}
.brand h1 em{font-style:normal;color:var(--lime-d)}
.brand p{color:var(--mut);font-weight:600;font-size:13px;margin-top:5px;letter-spacing:.4px}
.clock{padding:10px 18px;border-radius:16px;text-align:right;min-width:172px}
.clock b{font:700 24px/1 "JetBrains Mono";letter-spacing:1px}
.clock b i{font-style:normal;animation:blink 1s steps(1) infinite}
.clock small{display:block;color:var(--mut);font-weight:700;font-size:12px;margin-top:3px;text-transform:capitalize}
.clock .tz{display:inline-block;margin-top:4px;font:700 10px "Manrope";letter-spacing:2px;color:#fff;
  background:var(--sky-d);padding:2px 8px;border-radius:99px}
@keyframes blink{50%{opacity:.25}}
.statline{margin:0 0 22px;padding:11px 18px;border-radius:14px;display:flex;gap:10px 22px;flex-wrap:wrap;
  font-size:13px;font-weight:600;color:var(--ink2)}
.statline b{font-family:"JetBrains Mono";color:var(--ink)}
.statline .dot{display:inline-block;width:8px;height:8px;border-radius:99px;background:var(--lime);margin-right:7px;vertical-align:1px}

/* ---- вкладки / навигация ---- */
.view{display:none}
.view.active{display:block}
.view.active > *{animation:rise .5s cubic-bezier(.22,.9,.3,1) both}
.view.active > *:nth-child(2){animation-delay:.07s}
.view.active > *:nth-child(3){animation-delay:.14s}
@keyframes rise{from{opacity:0;transform:translateY(16px)}}
.tabbar{position:fixed;left:50%;transform:translateX(-50%);
  bottom:calc(14px + env(safe-area-inset-bottom));z-index:50;
  display:flex;gap:8px;padding:9px;border-radius:24px;max-width:94vw}
.tab{position:relative;display:flex;flex-direction:column;align-items:center;gap:4px;justify-content:center;
  width:104px;height:64px;border:0;border-radius:17px;cursor:pointer;background:transparent;
  color:var(--mut);font:700 12px "Manrope";transition:all .2s}
.tab svg{width:23px;height:23px;stroke:currentColor;fill:none;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round;transition:transform .2s}
.tab:hover{color:var(--ink)}
.tab:hover svg{transform:translateY(-2px)}
.tab.is-pressed{color:var(--ink);background:#e3e8f1;
  box-shadow:inset 5px 5px 11px rgba(178,187,207,.85), inset -6px -6px 11px #fff}
.tab.is-pressed svg{stroke:var(--acc)}
.tab.is-pressed::after{content:"";position:absolute;bottom:8px;width:22px;height:4px;border-radius:99px;background:var(--acc)}

/* ---- фильтры-чипы ---- */
.toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:18px}
.chip{border:0;cursor:pointer;padding:10px 16px;border-radius:99px;font:700 13px "Manrope";color:var(--ink2);
  background:linear-gradient(148deg,#eef2f9,#dfe4ef);
  box-shadow:5px 5px 12px var(--dark), -5px -5px 12px #fff;transition:all .16s;display:inline-flex;gap:8px;align-items:center}
.chip .cdot{width:9px;height:9px;border-radius:99px;background:var(--cc,var(--gray))}
.chip:hover{transform:translateY(-2px)}
.chip.is-pressed{box-shadow:inset 4px 4px 9px rgba(178,187,207,.85), inset -5px -5px 9px #fff;color:var(--ink)}
.chip.is-pressed .cdot{box-shadow:0 0 0 3px color-mix(in srgb,var(--cc) 30%, transparent)}
.toolbar .spacer{flex:1}

/* ---- таблица «Список» ---- */
.tscroll{overflow:auto;max-height:calc(100vh - 300px);min-height:220px;border-radius:20px;padding:6px}
table.grid{border-collapse:separate;border-spacing:0;min-width:1720px;width:100%}
.grid th,.grid td{vertical-align:middle}
.grid thead th{position:sticky;top:0;z-index:6;background:#e9edf5;text-align:left;
  font:800 11px "Manrope";text-transform:uppercase;letter-spacing:.7px;color:var(--mut);
  padding:13px 10px;box-shadow:0 8px 12px -10px rgba(90,102,130,.45)}
.grid thead th.sortable{cursor:pointer;user-select:none}
.grid thead th.sortable:hover{color:var(--ink)}
.grid thead th .arr{font-size:10px;opacity:.85}
.grid tbody tr{transition:background .15s}
.grid tbody tr:hover{background:#edf1f8}
.grid td{padding:8px 8px;border-top:1px solid #dde3ee}
.c-num{width:46px;text-align:center;font:700 12px "JetBrains Mono";color:var(--mut);
  position:sticky;left:0;z-index:5;background:#e9edf5;box-shadow:6px 0 10px -8px rgba(90,102,130,.5)}
.c-title{width:230px;position:sticky;left:46px;z-index:5;background:#e9edf5;box-shadow:6px 0 10px -8px rgba(90,102,130,.5)}
.c-profile{width:170px;position:sticky;left:205px;z-index:5;background:#e9edf5;box-shadow:6px 0 10px -8px rgba(90,102,130,.5)}
.grid thead .c-num,.grid thead .c-title,.grid thead .c-profile{z-index:7}
.grid thead .c-num{left:0}
.grid thead .c-title{left:46px}
.grid thead .c-profile{left:210px}
tr.sub-Математика .c-profile{box-shadow:inset 4px 0 0 -1px var(--lime), 6px 0 10px -8px rgba(90,102,130,.5)}
tr.sub-Физика .c-profile{box-shadow:inset 4px 0 0 -1px var(--sky), 6px 0 10px -8px rgba(90,102,130,.5)}
tr.sub-Информатика .c-profile{box-shadow:inset 4px 0 0 -1px var(--mint), 6px 0 10px -8px rgba(90,102,130,.5)}
tr.sub-Русский .c-profile{box-shadow:inset 4px 0 0 -1px var(--rose), 6px 0 10px -8px rgba(90,102,130,.5)}
.c-date{min-width:196px}
.c-fmt{min-width:140px}
.ci{border:0;font:600 13px "Manrope";color:var(--ink);border-radius:10px;padding:8px 9px;width:100%;
  background:#e3e8f1;box-shadow:inset 3px 3px 7px rgba(178,187,207,.75), inset -4px -4px 8px #fff;transition:box-shadow .15s}
.ci:focus{outline:none;box-shadow:inset 3px 3px 7px rgba(178,187,207,.85), 0 0 0 2px rgba(121,168,60,.35)}
.ci::placeholder{color:#a9b1c3;font-weight:500}
input.ci[type=date],input.ci[type=time]{font-family:"JetBrains Mono";font-size:12px}
.datepair{display:flex;gap:6px}
.datepair .di{width:128px}.datepair .ti{width:90px}
.hint-unspec{display:block;font-size:10.5px;color:var(--mut);margin-top:4px;font-weight:600}
select.ci{appearance:none;cursor:pointer;width:auto}
.cell-name{display:flex;align-items:center;gap:8px}
.cell-name .ci{flex:1}
.del{flex:none;width:30px;height:30px;border:0;border-radius:10px;cursor:pointer;color:var(--mut);
  font-size:13px;font-weight:800;background:transparent;transition:all .15s;
  box-shadow:3px 3px 8px var(--dark), -3px -3px 8px #fff}
.del:hover{color:var(--rose-d)}
.del.armed{background:linear-gradient(148deg,#f6b1a8,#e88d81);color:#fff;box-shadow:none;
  width:auto;padding:0 10px;font-size:11px;animation:pulseDel 1s infinite}
@keyframes pulseDel{50%{filter:brightness(1.12)}}
.lvl-badge{display:inline-grid;place-items:center;width:34px;height:34px;border-radius:12px;
  font:800 12px "Manrope";color:#41531c;background:linear-gradient(148deg,#d7eeab,#b9dd78);
  box-shadow:3px 3px 8px var(--dark), -3px -3px 8px #fff}
.lvl-badge.l2{background:linear-gradient(148deg,#c4dcf2,#9cc4e4);color:#274b6b}
.lvl-badge.l3{background:linear-gradient(148deg,#f7e3ac,#eecf7d);color:#6b5316}
.list-foot{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-top:18px}
#btnAdd{width:56px;height:56px;border-radius:18px;font-size:26px;padding:0}
.dbbanner{margin-bottom:16px;padding:14px 18px;border-radius:16px;background:#fbe9e6;color:#8f3d31;
  font-weight:600;font-size:13.5px;box-shadow:inset 4px 4px 10px rgba(200,140,130,.35), inset -5px -5px 10px #fff}

/* ---- «Домой» ---- */
.home-wrap{max-width:780px;margin:0 auto;display:flex;flex-direction:column;gap:26px}
.card{padding:20px 22px 14px;border-radius:24px;transition:transform .2s}
.card:hover{transform:translateY(-2px)}
.card-h{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.card-h h2{font:800 clamp(15px,2.4vw,19px) "Unbounded";letter-spacing:.5px}
.card-h .tag{font:800 10px "Manrope";letter-spacing:1.6px;text-transform:uppercase;padding:4px 10px;border-radius:99px;color:#fff}
.card-h .count{margin-left:auto;font:700 13px "JetBrains Mono";padding:4px 12px;border-radius:99px;background:#e2e7f0;
  box-shadow:inset 3px 3px 6px rgba(178,187,207,.8), inset -3px -3px 6px #fff}
.card table{width:100%;border-collapse:collapse}
.card td{padding:10px 6px;border-top:1px solid #d9dfea;vertical-align:middle}
.card tr:first-child td{border-top:0}
.card tbody tr{transition:background .15s}
.card tbody tr:hover{background:rgba(255,255,255,.4)}
.hn{width:34px;font:700 12px "JetBrains Mono";color:var(--mut);text-align:center}
.hname b{font-weight:800;font-size:14.5px}
.hname em{font-style:normal;font-weight:700;font-size:12.5px;color:var(--subj)}
.hname small{display:block;color:var(--mut);font-weight:600;font-size:11.5px;margin-top:2px}
.hname small.late{color:var(--rose-d)}
.hdate{font:600 12px "JetBrains Mono";color:var(--ink2);white-space:nowrap}
.hdate.cap{color:var(--mut);text-transform:uppercase;font:800 10px "Manrope";letter-spacing:.7px;padding-bottom:4px}
.tone-urgent{background:linear-gradient(150deg,#fae6e2,#f2d5d0);animation:glowU 3s ease-in-out infinite}
.tone-urgent .card-h h2{color:#96423a}
.tone-urgent .tag{background:var(--rose-d)}
.tone-urgent .count{background:#f6ddd8;box-shadow:inset 3px 3px 6px rgba(201,107,94,.3), inset -3px -3px 6px #fff}
@keyframes glowU{0%,100%{box-shadow:9px 9px 22px rgba(201,107,94,.28), -9px -9px 22px #fff}
  50%{box-shadow:9px 9px 28px rgba(201,107,94,.45), -9px -9px 22px #fff}}
.tone-missed{background:linear-gradient(150deg,#e8eaee,#dde0e7)}
.tone-missed .card-h h2{color:#5d6478}
.tone-missed .tag{background:#8a91a4}
.tone-soon{background:linear-gradient(150deg,#e9f0f3,#e0e9ec)}
.tone-soon .card-h h2{color:#2f5d63}
.tone-soon .tag{background:var(--mint-d)}
.empty{padding:18px 10px;text-align:center;color:var(--mut);font-weight:600;font-size:13.5px}
.empty b{display:block;font-size:22px;margin-bottom:4px}
.ck{display:inline-grid;place-items:center;cursor:pointer}
.ck input{position:absolute;opacity:0;width:0}
.ck span{width:28px;height:28px;border-radius:10px;display:grid;place-items:center;color:transparent;
  background:#e3e8f1;box-shadow:inset 3px 3px 7px rgba(178,187,207,.8), inset -4px -4px 8px #fff;
  transition:all .18s;font-weight:900;font-size:14px}
.ck:hover span{box-shadow:inset 3px 3px 7px rgba(178,187,207,.9), 0 0 0 3px rgba(121,168,60,.18)}
.ck input:checked + span{background:linear-gradient(148deg,#cdeb97,#a9d163);color:#3c5417;
  box-shadow:3px 3px 8px rgba(121,168,60,.35), -3px -3px 8px #fff;animation:pop .3s cubic-bezier(.2,1.6,.4,1)}
@keyframes pop{from{transform:scale(.6)}}

/* ---- «Расписание» (гантт) ---- */
.sched-head{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
.legend{display:flex;gap:8px;flex-wrap:wrap}
.lg{display:inline-flex;align-items:center;gap:7px;font:700 12px "Manrope";color:var(--ink2);
  padding:7px 13px;border-radius:99px;background:#e7ebf3;
  box-shadow:inset 3px 3px 7px rgba(178,187,207,.7), inset -4px -4px 8px #fff}
.lg i{width:11px;height:11px;border-radius:99px;background:var(--cc)}
.lg small{color:var(--mut)}
.seg{display:inline-flex;padding:5px;border-radius:14px;background:#e2e7f0;
  box-shadow:inset 4px 4px 9px rgba(178,187,207,.8), inset -5px -5px 10px #fff}
.seg button{border:0;background:transparent;cursor:pointer;padding:7px 14px;border-radius:10px;
  font:700 12px "Manrope";color:var(--mut);transition:all .15s}
.seg button.on{background:linear-gradient(148deg,#eef2f9,#e0e5ef);color:var(--ink);
  box-shadow:3px 3px 7px var(--dark), -3px -3px 7px #fff}
.gantt{padding:10px 0 6px;overflow:hidden}
.g-scroll{overflow-x:auto;overflow-y:hidden}
.g-inner{position:relative;padding-bottom:8px}
.g-inner::after{content:"";position:absolute;top:64px;bottom:6px;left:calc(var(--rl) + var(--today-x));
  width:2px;border-radius:2px;background:var(--rose-d);opacity:.75;box-shadow:0 0 8px rgba(201,107,94,.6)}
.g-headrow{display:grid;grid-template-columns:var(--rl) var(--tw)}
.g-hlab{position:sticky;left:0;z-index:4;background:linear-gradient(148deg,#eef2f9,#e2e7f1);
  border-radius:12px;margin:2px 8px 2px 10px;display:flex;align-items:center;padding:0 12px;
  font:800 10px "Manrope";letter-spacing:1.2px;text-transform:uppercase;color:var(--mut)}
.g-htl{position:relative;height:60px;margin-right:10px}
.g-month{position:absolute;top:8px;height:24px;display:flex;align-items:center;padding:0 10px;border-left:2px solid #ccd4e4;
  font:800 11px "Manrope";letter-spacing:.8px;text-transform:uppercase;color:var(--ink2);white-space:nowrap}
.g-tick{position:absolute;bottom:6px;transform:translateX(-50%);font:600 10px "JetBrains Mono";color:var(--mut)}
.g-row{display:grid;grid-template-columns:var(--rl) var(--tw);align-items:center;animation:rise .45s both}
.g-label{position:sticky;left:0;z-index:3;margin:4px 8px 4px 10px;padding:8px 12px;border-radius:13px;
  background:linear-gradient(148deg,#eef2f9,#e3e8f2);box-shadow:4px 4px 10px rgba(178,187,207,.55), -4px -4px 10px #fff;
  display:flex;gap:10px;align-items:center;min-height:52px;transition:transform .15s}
.g-row:hover .g-label{transform:translateX(3px)}
.g-label .dot{flex:none;width:12px;height:12px;border-radius:99px;background:var(--c);box-shadow:0 0 0 4px color-mix(in srgb,var(--c) 25%, transparent)}
.g-label b{display:block;font-size:12.5px;font-weight:800;line-height:1.25}
.g-label small{color:var(--mut);font:600 10.5px "JetBrains Mono"}
.g-track{position:relative;height:52px;margin-right:10px;
  background:repeating-linear-gradient(90deg, rgba(140,152,180,.16) 0 1px, transparent 1px var(--ppd))}
.g-bar{position:absolute;top:7px;height:38px;border-radius:11px;display:flex;flex-direction:column;justify-content:center;
  padding:0 12px;overflow:hidden;white-space:nowrap;cursor:default;min-width:26px;
  background:linear-gradient(135deg, color-mix(in srgb,var(--c) 88%, #fff), color-mix(in srgb,var(--c) 72%, #46536e));
  box-shadow:4px 4px 10px rgba(90,102,130,.28), inset 0 1px 0 rgba(255,255,255,.5);
  transform-origin:left;animation:grow .6s cubic-bezier(.25,.9,.3,1) both;transition:transform .15s, box-shadow .15s}
.g-row:hover .g-bar{transform:translateY(-2px) scale(1.005);box-shadow:6px 8px 14px rgba(90,102,130,.35)}
.g-bar b{font-size:12px;font-weight:800;color:#333e55}
.g-bar span{font:600 10px "JetBrains Mono";color:rgba(51,62,85,.72)}
.g-bar.unspec{position:static;width:auto;margin:7px 12px 7px 0;border:2px dashed #b6bfd2;background:transparent;box-shadow:none;
  display:flex;flex-direction:row;align-items:center;gap:8px;color:var(--mut)}
@keyframes grow{from{transform:scaleX(0);opacity:.3}}

/* ---- диалог импорта / тост ---- */
dialog.dlg{border:0;border-radius:24px;padding:26px;width:min(720px,92vw);background:#e9edf5;
  box-shadow:14px 14px 34px rgba(140,152,180,.6), -10px -10px 26px #fff}
dialog.dlg::backdrop{background:rgba(70,80,105,.35);backdrop-filter:blur(2px)}
.dlg h3{font:800 17px "Unbounded";margin-bottom:8px}
.dlg p{color:var(--mut);font-size:12.5px;font-weight:600;margin-bottom:14px}
.dlg textarea{width:100%;resize:vertical;border:0;border-radius:14px;padding:12px;
  font:500 12px "JetBrains Mono";color:var(--ink);background:#e2e7f0;
  box-shadow:inset 5px 5px 12px rgba(178,187,207,.8), inset -6px -6px 12px #fff}
.dlg textarea:focus{outline:none;box-shadow:inset 5px 5px 12px rgba(178,187,207,.9), 0 0 0 2px rgba(121,168,60,.35)}
.dlg .modes{display:flex;gap:16px;margin:14px 0 18px;font-weight:700;font-size:13px;color:var(--ink2)}
.dlg .modes label{display:flex;gap:7px;align-items:center;cursor:pointer}
.dlg .modes input{accent-color:var(--lime-d)}
.dlg-btns{display:flex;justify-content:flex-end;gap:10px}
#toast{position:fixed;right:20px;bottom:calc(100px + env(safe-area-inset-bottom));z-index:80;
  padding:12px 20px;border-radius:14px;font-weight:700;font-size:13.5px;opacity:0;transform:translateY(14px);
  background:#3d465c;color:#fff;box-shadow:8px 8px 20px rgba(50,60,85,.4);transition:all .3s;pointer-events:none}
#toast.show{opacity:1;transform:translateY(0)}

@media (max-width:680px){
  body{padding:14px 10px 128px}
  :root{--rl:170px}
  header.top{align-items:stretch}
  .clock{flex:1;text-align:left;min-width:0}
  .tab{width:86px;height:58px}
  .card{padding:16px 12px 10px}
  .hdate{font-size:10.5px}
  .tscroll{max-height:calc(100vh - 320px)}
  #btnAdd{width:50px;height:50px}
  .c-title{width:170px}
  .c-profile{width:130px;left:216px}
  .grid thead .c-profile{left:216px}
  table.grid{min-width:1560px}
}
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important}
}
</style>
</head>
<body>
<div class="bg-deco"></div>
<div class="wrap">

  <header class="top">
    <div class="brand">
      <div class="brand-badge">◈</div>
      <div>
        <h1>ОЛИМП<em>·</em>ТРЕКЕР</h1>
        <p>реестр олимпиад · дедлайны регистраций · МСК</p>
      </div>
    </div>
    <div class="clock neu-in">
      <b class="mono" id="clockTime">--<i>:</i>--<i>:</i>--</b>
      <small id="clockDate">…</small>
      <span class="tz">МСК</span>
    </div>
  </header>

  <div class="statline neu" id="statline"></div>

  <?php if (!$dbOk): ?>
    <div class="dbbanner">⚠ Нет соединения с базой: <?= htmlspecialchars($dbErr) ?>. Проверьте <b>config.php</b> и импортируйте <b>schema.sql</b>.</div>
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
          <button data-k="reg_end" class="on">по закрытию ↑</button>
          <button data-k="reg_start">по старту ↑</button>
        </div>
      </div>
      <div class="gantt neu" id="gantt"></div>
    </section>

    <!-- СПИСОК -->
    <section class="view" id="view-list">
      <div class="toolbar" id="chips"></div>
      <div class="tscroll neu">
        <table class="grid">
          <thead>
            <tr>
              <th class="c-num">№</th>
              <th class="c-title">Олимпиада</th>
              <th class="c-profile">Профиль</th>
              <th class="c-date sortable" data-sort="reg_start">Начало регистрации <span class="arr"></span></th>
              <th class="c-date sortable" data-sort="reg_end">Окончание регистрации <span class="arr"></span></th>
              <th class="c-date sortable" data-sort="stage1_date">Дата первого этапа <span class="arr"></span></th>
              <th class="c-fmt">Формат первого этапа</th>
              <th class="c-date sortable" data-sort="final_date">Дата финального этапа <span class="arr"></span></th>
              <th class="c-fmt">Формат заключительного этапа</th>
              <th>Этапов</th>
              <th>Уровень</th>
            </tr>
          </thead>
          <tbody id="listBody"></tbody>
        </table>
      </div>
      <div class="list-foot">
        <button class="nbtn acc" id="btnAdd" title="Добавить строку">+</button>
        <button class="nbtn" id="btnImport">⧉ Вставить Markdown</button>
        <button class="nbtn" id="btnExport">⤓ Скопировать Markdown</button>
        <span style="color:var(--mut);font-size:12px;font-weight:600">пустая дата = «Уточняется на сайте» · пустое время = 23:59</span>
      </div>
    </section>
  </main>
</div>

<!-- нижняя панель -->
<nav class="tabbar neu" aria-label="Основная навигация">
  <button class="tab is-pressed" data-view="home" style="--acc:#cf6f63" aria-label="Домой">
    <svg viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5"/></svg>
    <span>Домой</span>
  </button>
  <button class="tab" data-view="sched" style="--acc:#5a8fbc" aria-label="Расписание">
    <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M8 3v4M16 3v4M3 10.5h18M8 15h3"/></svg>
    <span>Расписание</span>
  </button>
  <button class="tab" data-view="list" style="--acc:#79a83c" aria-label="Список">
    <svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1.3" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1.3" fill="currentColor" stroke="none"/><circle cx="4" cy="18" r="1.3" fill="currentColor" stroke="none"/></svg>
    <span>Список</span>
  </button>
</nav>

<!-- диалог импорта -->
<dialog class="dlg" id="dlg">
  <h3>Импорт таблицы из Markdown</h3>
  <p>11 столбцов: № · Олимпиада · Профиль · Начало регистрации · Окончание регистрации · Дата первого этапа · Формат 1-го этапа · Дата финала · Формат финала · Кол-во этапов · Уровень. Старый формат из 10 столбцов (профиль в скобках названия) тоже понимается. Даты: «21 сентября 2026 (14:00 МСК)», «21.09.2026», «2026-09-21», «Уточняется на сайте».</p>
  <textarea id="mdIn" rows="8" placeholder="| № | Олимпиада | Профиль | Начало регистрации | Окончание регистрации | Дата первого этапа | Формат первого этапа | Дата финального этапа | Формат заключительного этапа | Кол-во этапов | Уровень |
|---|---|---|---|---|---|---|---|---|---|---|
| 6 | Высшая проба | Математика | 20 августа 2026 | 21 сентября 2026 (14:00 МСК) | 25 сентября 2026 | Заочный | 5 февраля 2027 | Очный | 2 | I |"></textarea>
  <div class="modes">
    <label><input type="radio" name="mdMode" value="append" checked> Дописать к списку</label>
    <label><input type="radio" name="mdMode" value="replace"> Заменить весь список</label>
  </div>
  <div class="dlg-btns">
    <button class="nbtn ghost" id="dlgClose">Отмена</button>
    <button class="nbtn acc" id="dlgGo">Импортировать</button>
  </div>
</dialog>

<div id="toast"></div>
<datalist id="fmtList">
  <option value="Очно"></option><option value="Дистанционно"></option><option value="Очно-дистанционно"></option>
</datalist>

<script>
/* ================= ДАННЫЕ ================= */
let DATA = <?= json_encode($rows, JSON_UNESCAPED_UNICODE) ?>;
const DB_OK = <?= $dbOk ? 'true' : 'false' ?>;

const MONTHS = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
const MONTHS_S = ['янв','фев','мар','апр','мая','июн','июл','авг','сен','окт','ноя','дек'];
const SUBJ = {
  'Математика':  {c:'#b7e06f', d:'#79a83c'},
  'Физика':      {c:'#9ec9ea', d:'#5a8fbc'},
  'Информатика': {c:'#93ddcd', d:'#47a592'},
  'Русский язык':{c:'#f2a9a0', d:'#c96b5e'},
  'Прочее':      {c:'#cfd5e0', d:'#8a91a4'}
};
const state = { filter:null, sort:{key:'reg_start',dir:'asc'}, schedKey:'reg_end' };

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
function normRow(r){ ['reg_start','reg_end','stage1_date','final_date'].forEach(f=>{ if(r[f]) r[f]=r[f].replace(' ','T'); }); return r; }
function patchRow(row){ normRow(row); const i = DATA.findIndex(x=>x.id==row.id); if(i>-1) DATA[i]=row; }

/* ================= НАВИГАЦИЯ ================= */
$$('.tab').forEach(btn=>btn.addEventListener('click', ()=>{
  $$('.tab').forEach(b=>b.classList.toggle('is-pressed', b===btn));
  const v = btn.dataset.view;
  $$('.view').forEach(s=>s.classList.toggle('active', s.id==='view-'+v));
  if(v==='home') renderHome();
  if(v==='sched') renderSchedule();
  if(v==='list') renderList();
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
    `<span><span class="dot"></span>в списке: <b>${DATA.length}</b></span>` +
    `<span>с регистрацией: <b>${DATA.filter(r=>+r.registered).length}</b></span>` +
    (nxt ? `<span>ближайший дедлайн: <b>${esc(splitProfile(nxt.name)[0])}</b> — ${fmtDate(nxt.reg_end)}</span>`
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

  // диагностика (можно потом удалить): что закрылось за сутки и почему не в «Пропустил»
  console.table(recent.map(r => ({
    id: r.id, name: r.name, reg_end: r.reg_end,
    registered: r.registered, 'попадёт в серый': !+r.registered
  })));

  return {urgent, missed, soon, recent};
}
function ckBox(r){
  return `<label class="ck" title="Отметить регистрацию"><input type="checkbox" data-id="${r.id}" ${+r.registered?'checked':''}><span>✓</span></label>`;
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
    if(mode==='urgent') e = '<b>🌿</b>Сегодня и завтра дедлайнов нет — можно выдохнуть.';
    else if(mode==='missed') e = (recent && recent.length)
        ? `<b>🤔</b>За последние сутки закрывалось регистраций: ${recent.length}, но все они отмечены чек-боксом как выполненные — поэтому блок пуст. Снимите отметку, и строка появится здесь.`
        : '<b>✅</b>Пропусков нет: за последние сутки регистраций не закрывалось.';
    else e = '<b>🔭</b>В ближайшие 7 дней регистраций не закрывается.';
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
  return `<article class="card tone-${tone}">
    <header class="card-h"><span class="tag">${tag}</span><h2>${title}</h2><span class="count">${rows.length}</span></header>
    ${body}</article>`;
}
function renderHome(){
  const {urgent, missed, soon, recent} = homeBuckets();
  const u = cardHTML('urgent','дедлайн ≤ завтра','ОБЯЗАТЕЛЬНО СЕГОДНЯ', urgent, 'urgent');
  const m = cardHTML('missed','регистрация упущена','ПРОПУСТИЛ', missed, 'missed', recent);
  const s = cardHTML('soon','горизонт 7 дней','БЛИЖАЙШЕЕ', soon, 'soon');
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
setInterval(()=>{ if($('#view-home').classList.contains('active')) renderHome(); renderStats(); }, 60000);

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
function dateCell(r, f){
  const v = r[f] || '';
  return `<td class="c-date"><div class="datepair">
      <input type="date" class="ci di" data-f="${f}" value="${dOf(v)??''}">
      <input type="time" class="ci ti" data-f="${f}__t" value="${tOf(v)}" title="Пусто = 23:59">
    </div>${!v?'<span class="hint-unspec">уточняется на сайте</span>':''}</td>`;
}
function rowTR(r, n){
  const subj = subjectOf(r.name);
  const [title, prof] = splitProfile(r.name);
  return `<tr class="sub-${subj}" data-id="${r.id}">
    <td class="c-num rn">${n}</td>
    <td class="c-title"><input class="ci" data-f="title" value="${esc(title)}" placeholder="Олимпиада"></td>
    <td class="c-profile"><div class="cell-name">
      <input class="ci" data-f="profile" value="${esc(prof)}" placeholder="Профиль">
      <button class="del" title="Удалить строку">✕</button></div></td>
    ${dateCell(r,'reg_start')}${dateCell(r,'reg_end')}${dateCell(r,'stage1_date')}
    <td class="c-fmt"><input class="ci" data-f="stage1_format" value="${esc(r.stage1_format)}" list="fmtList" placeholder="Формат"></td>
    ${dateCell(r,'final_date')}
    <td class="c-fmt"><input class="ci" data-f="final_format" value="${esc(r.final_format)}" list="fmtList" placeholder="Формат"></td>
    <td><select class="ci" data-f="stages_count">${[1,2,3,4].map(x=>`<option ${+r.stages_count===x?'selected':''}>${x}</option>`).join('')}</select></td>
    <td style="text-align:center"><span class="lvl-badge l${+r.lvl}">У${+r.lvl}</span>
      <select class="ci" data-f="lvl" style="display:none">${[1,2,3].map(x=>`<option ${+r.lvl===x?'selected':''}>${x}</option>`).join('')}</select></td>
  </tr>`;
}
function renderList(){
  const rows = visibleRows();
  $('#listBody').innerHTML = rows.length
    ? rows.map((r,i)=>rowTR(r,i+1)).join('')
    : `<tr><td colspan="11"><div class="empty"><b>📭</b>Здесь пока пусто. Нажмите «+» внизу или импортируйте Markdown.</div></td></tr>`;
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
  sel.style.display='inline-block'; bd.style.display='none'; sel.focus();
});
$('#listBody').addEventListener('change', async e=>{
  const inp = e.target.closest('[data-f]'); if(!inp) return;
  const tr = inp.closest('tr'), id = tr.dataset.id;
  let f = inp.dataset.f, value = inp.value;

  if(f.endsWith('__t')){
    const base = f.slice(0,-3);
    const d = tr.querySelector(`[data-f="${base}"]`).value;
    f = base; value = d ? `${d}T${inp.value||''}` : '';
  } else if(f==='title' || f==='profile'){
    // два поля склеиваются в одно хранимое имя «Олимпиада (Профиль)»
    const t = tr.querySelector('[data-f="title"]').value.trim();
    const p = tr.querySelector('[data-f="profile"]').value.trim();
    f = 'name'; value = p ? `${t} (${p})` : t;
  }
  try{
    const j = await api('update',{id, field:f, value});
    patchRow(j.row); renderStats();
    if(['reg_start','reg_end'].includes(f)) renderHome();
    if(f==='name'){ tr.className='sub-'+subjectOf(j.row.name); renderHome(); renderSchedule(); }
    if(f==='lvl'){ const b=tr.querySelector('.lvl-badge'); b.textContent='У'+j.row.lvl; b.className='lvl-badge l'+j.row.lvl;
      b.style.display='inline-block'; e.target.style.display='none'; }
    if(['reg_start','reg_end','stage1_date','final_date'].includes(f)){
      const old = inp.closest('td'); const tmp = document.createElement('tr');
      tmp.innerHTML = dateCell(j.row, f); const fresh = tmp.querySelector('td');
      old.replaceWith(fresh);
    }
  }catch(err){ toast(err.message); }
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
    tr.style.opacity=0; tr.style.transition='opacity .25s';
    setTimeout(()=>{ renderList(); renderStats(); renderHome(); }, 240);
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

/* ================= РАСПИСАНИЕ ================= */
$('#schedSort').addEventListener('click', e=>{
  const b = e.target.closest('button'); if(!b) return;
  state.schedKey = b.dataset.k;
  $$('#schedSort button').forEach(x=>x.classList.toggle('on', x===b));
  renderSchedule();
});
function renderLegend(){
  const cnt = {};
  DATA.forEach(r=>{ const s=subjectOf(r.name); cnt[s]=(cnt[s]||0)+1; });
  $('#legend').innerHTML = Object.entries(SUBJ).filter(([s])=>cnt[s])
    .map(([s,v])=>`<span class="lg" style="--cc:${v.c}"><i></i>${s} <small>${cnt[s]}</small></span>`).join('');
}
function renderSchedule(){
  renderLegend();
  const rows = DATA.slice();
  const k = state.schedKey;
  rows.sort((a,b)=>{ const av=a[k],bv=b[k];
    if(av==null&&bv==null) return a.id-b.id; if(av==null) return 1; if(bv==null) return -1;
    return av<bv?-1:av>bv?1:0; });

  const dates = [];
  rows.forEach(r=>['reg_start','reg_end','final_date'].forEach(f=>{ if(r[f]) dates.push(dOf(r[f])); }));
  if(!dates.length){ $('#gantt').innerHTML = '<div class="empty"><b>🗓</b>Нет данных с датами.</div>'; return; }
  const min = addDaysISO(dates.reduce((a,b)=>a<b?a:b), -3);
  const max = addDaysISO(dates.reduce((a,b)=>a>b?a:b), 3);
  const days = dDays(min, max) + 1;
  const ppd = days<=31 ? 46 : days<=62 ? 30 : days<=124 ? 18 : 12;
  const tw = days*ppd;
  const rl = window.matchMedia('(max-width:680px)').matches ? 170 : 240;
  const today = mskToday();

  let months = [], cur = null;
  for(let i=0;i<days;i++){
    const d = addDaysISO(min,i); const [y,m] = d.split('-');
    const key = y+m;
    if(!cur || cur.key!==key){ cur = {key, y, m, start:i, len:1}; months.push(cur); } else cur.len++;
  }
  const monthHTML = months.map(s=>{
    const name = new Intl.DateTimeFormat('ru-RU',{month:'long'}).format(new Date(+s.y, +s.m-1, 1));
    return `<div class="g-month" style="left:${s.start*ppd}px;width:${s.len*ppd}px">${name} ${s.y}</div>`;
  }).join('');
  const tickK = ppd>=40?1:ppd>=24?2:ppd>=16?5:7;
  let ticks = '';
  for(let i=0;i<days;i+=tickK){ const d=addDaysISO(min,i); ticks += `<span class="g-tick" style="left:${i*ppd+ppd/2}px">${+d.slice(8,10)}</span>`; }

  const rowsHTML = rows.map((r,idx)=>{
    const subj = subjectOf(r.name), c = SUBJ[subj].c;
    const [base, prof] = splitProfile(r.name);
    let bar = '';
    if(r.reg_start && r.reg_end){
      const left = dDays(min, dOf(r.reg_start))*ppd;
      const w = Math.max((dDays(dOf(r.reg_start), dOf(r.reg_end))+1)*ppd - 6, 26);
      bar = `<div class="g-bar" style="left:${left}px;width:${w}px;--c:${c};animation-delay:${idx*45}ms"
        title="${esc(r.name)}\nРегистрация: ${fmtDate(r.reg_start)} → ${fmtDate(r.reg_end)}">
        <b>${esc(base)}</b><span>${fmtShort(r.reg_start)} → ${fmtShort(r.reg_end)}</span></div>`;
    } else if(r.reg_end){
      const left = dDays(min, addDaysISO(dOf(r.reg_end),-3))*ppd;
      const w = Math.max(4*ppd-6, 26);
      bar = `<div class="g-bar" style="left:${left}px;width:${w}px;--c:${c};animation-delay:${idx*45}ms"
        title="${esc(r.name)}\nСтарт регистрации уточняется, закрытие: ${fmtDate(r.reg_end)}">
        <b>${esc(base)}</b><span>до ${fmtShort(r.reg_end)}</span></div>`;
    } else {
      bar = `<div class="g-bar unspec">◌ ${esc(base)} — даты уточняются на сайте</div>`;
    }
    const close = r.reg_end ? `закрытие ${fmtShort(r.reg_end)}${tOf(r.reg_end)?' · '+tOf(r.reg_end):''}` : 'закрытие уточняется';
    return `<div class="g-row" style="--c:${c}">
      <div class="g-label"><span class="dot"></span><span><b>${esc(base)}${prof?` <small style="color:${SUBJ[subj].d}">(${esc(prof)})</small>`:''}</b><small>${close}</small></span></div>
      <div class="g-track">${bar}</div></div>`;
  }).join('');

  const todayX = (dDays(min, today))*ppd + ppd/2;
  const g = $('#gantt');
  g.innerHTML = `<div class="g-scroll"><div class="g-inner" style="--rl:${rl}px;--tw:${tw}px;--ppd:${ppd}px;--today-x:${todayX}px">
      <div class="g-headrow"><div class="g-hlab">Олимпиада</div><div class="g-htl">${monthHTML}${ticks}</div></div>
      ${rowsHTML}</div></div>`;
  const tLine = g.querySelector('.g-inner');
  if(today < min || today > max) tLine.style.setProperty('--today-x','-100px');
  const sc = g.querySelector('.g-scroll');
  sc.scrollLeft = Math.max(0, todayX - 140);
}

/* ================= СТАРТ ================= */
function renderAll(){ renderChips(); renderList(); renderHome(); renderSchedule(); renderStats(); }
tickClock();
renderAll();
if(!DB_OK) toast('Нет соединения с базой данных');
</script>
</body>
</html>