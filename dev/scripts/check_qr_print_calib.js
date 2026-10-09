// qr-label-print.js 의 인쇄 위치·배율 보정을 확인한다.
// 파일 전체는 관리자 화면 DOM 이 있어야 돌아가므로, 셈하는 토막만 떼어 돌린다.
const fs = require('fs');
const path = require('path');

const src = fs.readFileSync(
  path.join(__dirname, '..', 'public', 'js', 'qr-label-print.js'), 'utf8');

function slice(from, to) {
  const a = src.indexOf(from);
  const b = src.indexOf(to);
  if (a < 0 || b < 0) throw new Error(`떼어낼 토막을 찾지 못했다: ${from}`);
  return src.slice(a, b);
}

// localStorage 와 DOM 을 흉내 낸다. 보정값이 실제로 어떤 글자로 저장되는지 봐야 하므로
// 저장소는 진짜처럼 문자열만 담는다.
const store = {};
const localStorage = {
  getItem: (k) => (k in store ? store[k] : null),
  setItem: (k, v) => { store[k] = String(v); },
};
const document = { getElementById: () => null, querySelectorAll: () => [] };
const frame = { contentDocument: null };

const body =
  slice('  function slots(paper) {', '  function bindPayload(') +
  slice('  var CALIB_KEY =', '  function waitImages(');

const mod = new Function('localStorage', 'document', 'frame',
  body + '\nreturn { view, designSpan, pctFromMeasurement, clampPct, loadCalibration, saveCalibration, loadOffset, saveOffset, slots };'
)(localStorage, document, frame);

let bad = 0;
function check(name, got, want, tol = 0.0005) {
  const ok = typeof want === 'number' ? Math.abs(got - want) < tol : got === want;
  if (!ok) bad++;
  console.log(`${ok ? 'OK  ' : 'FAIL'} ${name}: ${got}${ok ? '' : ` (기대 ${want})`}`);
}

// A827: 8열 × 27행, 20×10mm, 좌 14.5 / 상 13, 가로간격 3, 세로간격 0.
const a827 = {
  columns: 8, rows: 27, labelWidthMm: 20, labelHeightMm: 10,
  leftMarginMm: 14.5, topMarginMm: 13, hGapMm: 3, vGapMm: 0,
};
const span = mod.designSpan(a827);
check('가로 칼선 구간', span.x, 181);
check('세로 칼선 구간', span.y, 270);

// 270mm 가 271mm 로 찍혔으면 270/271 = 99.631% 로 줄여 보내야 한다.
check('배율 계산 270→271', mod.pctFromMeasurement(270, 271), 99.63099630996311, 0.00001);
check('실측이 설계와 같으면 보정 없음', mod.pctFromMeasurement(270, 270), 100);
check('실측 0 은 무시', mod.pctFromMeasurement(270, 0), 100);
// 오타 울타리: 편집기와 같은 95~105%.
check('과한 값은 울타리에 걸림', mod.clampPct(50), 95);
check('과한 값은 울타리에 걸림(위)', mod.clampPct(200), 105);

// 편집기(PrintCalibration.cs)가 System.Text.Json 으로 읽는다. 글쇠는 X·Y 여야 한다.
mod.view.sx = 99.631;
mod.view.sy = 99.5;
mod.saveCalibration();
const raw = store['labelup.print.calibration.v1'];
check('편집기와 같은 저장 형식', raw, '{"X":99.631,"Y":99.5}');

// 편집기가 써 둔 값을 그대로 읽어 오는가.
store['labelup.print.calibration.v1'] = '{"X":100,"Y":99.631}';
mod.view.sx = 0; mod.view.sy = 0;
mod.loadCalibration();
check('편집기 값 읽기 가로', mod.view.sx, 100);
check('편집기 값 읽기 세로', mod.view.sy, 99.631);

// 위치는 템플릿마다 따로 남는다.
mod.view.templateKey = 'group-7';
mod.view.ox = 1.5; mod.view.oy = -2;
mod.saveOffset();
mod.view.templateKey = 'group-9';
mod.view.ox = 0; mod.view.oy = 0;
mod.saveOffset();
mod.loadOffset('group-7');
check('템플릿별 위치 보존 x', mod.view.ox, 1.5);
check('템플릿별 위치 보존 y', mod.view.oy, -2);
mod.loadOffset('group-404');
check('모르는 템플릿은 0 에서 시작', mod.view.ox, 0);

console.log(bad === 0 ? '\n모두 통과' : `\n실패 ${bad}건`);
process.exit(bad === 0 ? 0 : 1);
