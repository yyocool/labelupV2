// qr-print-template.js 의 배치 계산이 규격을 그대로 따르는지 본다.
// 파일 전체는 관리자 화면 DOM 이 있어야 돌아가므로, 배치를 셈하는 토막만 떼어 돌린다.
const fs = require('fs');
const path = require('path');

const src = fs.readFileSync(
  path.join(__dirname, '..', 'public', 'js', 'qr-print-template.js'), 'utf8');

const begin = src.indexOf('  var PAGE_SIZES = {');
const end = src.indexOf('  function normalizePaper(');
if (begin < 0 || end < 0) throw new Error('떼어낼 토막을 찾지 못했다');

const slice = src.slice(begin, end);
const mod = new Function(slice + '\nreturn { pageSizeMm, mmOrNull, paperFromProduct, layoutFromSpec };')();

// /api/shop/editor-papers 가 A827 규격으로 내려보내는 모양 그대로.
const a827 = {
  id: 101, sku: 'A827', name: 'A827', shape: '',
  widthMm: 20, heightMm: 10,
  columnsCount: 8, rowsCount: 27,
  leftMarginMm: 14.5, topMarginMm: 13,
  hGapMm: 3, vGapMm: 0,
  labelsPerSheet: 216, paperSize: 'A4',
};

const got = mod.paperFromProduct(a827);
const want = {
  paperWidthMm: 210, paperHeightMm: 297,
  labelWidthMm: 20, labelHeightMm: 10,
  columns: 8, rows: 27,
  leftMarginMm: 14.5, topMarginMm: 13,
  hGapMm: 3, vGapMm: 0,
  labelsPerSheet: 216,
};

let bad = 0;
for (const [k, v] of Object.entries(want)) {
  const ok = Math.abs(Number(got[k]) - v) < 0.0005;
  if (!ok) bad++;
  console.log(`${ok ? 'OK  ' : 'FAIL'} ${k}: ${got[k]} (기대 ${v})`);
}

// 세로 칸 끝자리가 규격과 맞는지. 13 + 27*10 = 283mm.
const lastY = got.topMarginMm + (got.rows - 1) * (got.labelHeightMm + got.vGapMm) + got.labelHeightMm;
console.log(`${Math.abs(lastY - 283) < 0.0005 ? 'OK  ' : 'FAIL'} 마지막 행 끝: ${lastY}mm (기대 283)`);
if (Math.abs(lastY - 283) >= 0.0005) bad++;

// 여백이 비어 있는 옛 규격은 복판에 앉혀야 한다.
const noMargin = mod.paperFromProduct(
  Object.assign({}, a827, { leftMarginMm: null, topMarginMm: null }));
const midTop = (297 - 27 * 10) / 2;
const centered = Math.abs(noMargin.topMarginMm - midTop) < 0.0005;
console.log(`${centered ? 'OK  ' : 'FAIL'} 여백 없는 규격 복판 정렬: ${noMargin.topMarginMm}mm (기대 ${midTop})`);
if (!centered) bad++;

// 0mm 로 적힌 여백은 "값 없음"이 아니다. 0 그대로 나와야 한다.
const zeroTop = mod.paperFromProduct(Object.assign({}, a827, { topMarginMm: 0 }));
const keptZero = zeroTop.topMarginMm === 0;
console.log(`${keptZero ? 'OK  ' : 'FAIL'} 0mm 여백 보존: ${zeroTop.topMarginMm}mm (기대 0)`);
if (!keptZero) bad++;

console.log(bad === 0 ? '\n모두 통과' : `\n실패 ${bad}건`);
process.exit(bad === 0 ? 0 : 1);
