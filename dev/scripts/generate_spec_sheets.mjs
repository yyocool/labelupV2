/**
 * 상품 규격 안내 이미지를 상품마다 만든다.
 * 사용: node scripts/generate_spec_sheets.mjs [--sku A101,A307]
 */
import { createRequire } from 'module';
import { mkdir, writeFile } from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';

const require = createRequire(import.meta.url);
const { chromium } = require('../_tmp_pw/node_modules/playwright');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT_DIR = path.resolve(__dirname, '../public/assets/spec-sheets');
const PAPERS_URL = 'https://www.labelup.co.kr/api/shop/editor-papers';
const PRODUCT_URL = 'https://www.labelup.co.kr/api/shop/products/';

const INTROS = {
  '물류관리용': [
    '박스와 포장에 품목, 배송 및 출고 정보를 표시하는 라벨입니다.',
    '사무실부터 창고까지 다양한 물류 관리에 활용할 수 있습니다.',
  ],
  '주소용': [
    '수신인과 발신인의 이름, 주소 등 배송에 필요한 정보를 표시하는 라벨입니다.',
    '우편봉투와 소포의 주소 표기에 활용할 수 있습니다.',
  ],
  '바코드용': [
    '상품 식별과 재고 관리에 필요한 바코드 정보를 표시하는 라벨입니다.',
    '상품 포장과 보관함에 부착해 제품 관리에 활용할 수 있습니다.',
  ],
  '인덱스용': [
    '파일과 문서철, 수납함에 이름과 분류 정보를 표시하는 라벨입니다.',
    '필요한 문서와 물품을 쉽게 찾을 수 있도록 정리할 때 활용할 수 있습니다.',
  ],
  '정부문서화일 라벨': [
    '정부문서 파일과 문서철에 제목 및 분류 정보를 표시하는 라벨입니다.',
    '문서 정리와 보관에 활용할 수 있습니다.',
  ],
  '광택 라벨': [
    '광택이 있는 표면으로 디자인과 정보를 표현하는 라벨입니다.',
    '상품명과 브랜드 로고 등을 담아 제품 포장에 활용할 수 있습니다.',
  ],
  '방수 라벨': [
    '물기와 습기에 강한 백색 필름 소재의 라벨입니다.',
    '물기가 닿을 수 있는 용기와 포장 외부의 정보 표시에 활용할 수 있습니다.',
  ],
  '반투명 라벨': [
    '부착한 표면의 색상과 배경이 은은하게 비치는 반투명 라벨입니다.',
    '용기와 포장의 분위기를 살리면서 필요한 정보를 표시할 수 있습니다.',
  ],
  '잉크젯 투명 라벨': [
    '부착한 표면의 색상과 배경이 비치는 잉크젯 프린터용 투명 라벨입니다.',
    '용기와 포장 디자인을 살리면서 상품명과 브랜드 정보를 표시할 수 있습니다.',
  ],
  '레이저 투명 라벨': [
    '부착한 표면의 색상과 배경이 비치는 레이저 프린터용 투명 라벨입니다.',
    '용기와 포장 디자인을 살리면서 상품명과 브랜드 정보를 표시할 수 있습니다.',
  ],
  '보호용 필름': [
    '인쇄된 라벨 위에 덧붙여 사용하는 투명 보호용 필름입니다.',
    '라벨 표면을 덮어 오염과 손상으로부터 보호하는 데 활용할 수 있습니다.',
  ],
  '컬러 라벨(형광)': [
    '선명한 형광 색상으로 필요한 정보를 강조하는 라벨입니다.',
    '눈에 잘 띄어야 하는 안내 문구와 분류 표시에 활용할 수 있습니다.',
  ],
  '파스텔 컬러 라벨': [
    '부드러운 파스텔 색상으로 분류와 꾸미기에 활용하는 라벨입니다.',
    '문서와 수납함을 색상별로 구분하거나 포장에 포인트를 더할 수 있습니다.',
  ],
  '크라프트 라벨': [
    '자연스러운 크라프트 색상으로 포장에 따뜻한 분위기를 더하는 라벨입니다.',
    '상품명과 브랜드 로고를 담아 선물과 소품 포장에 활용할 수 있습니다.',
  ],
};

function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[ch]));
}

function formatMm(value) {
  const n = Number(value);
  if (!Number.isFinite(n) || n <= 0) return '';
  const rounded = Math.round(n * 1000) / 1000;
  return String(rounded).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');
}

function resolveGrid(product) {
  const n = Math.max(1, Number(product.labelsPerSheet) || 1);
  let cols = Number(product.columnsCount) || 0;
  let rows = Number(product.rowsCount) || 0;
  if (cols > 0 && rows > 0 && cols * rows === n) return { cols, rows, n };
  if (cols > 0 && n % cols === 0) return { cols, rows: n / cols, n };
  if (rows > 0 && n % rows === 0) return { cols: n / rows, rows, n };

  const lw = Number(product.widthMm) || 1;
  const lh = Number(product.heightMm) || 1;
  let bestCols = 1;
  let bestScore = Infinity;
  for (let c = 1; c <= n; c += 1) {
    if (n % c !== 0) continue;
    const r = n / c;
    const aspect = (c * lw) / (r * lh);
    const score = Math.abs(Math.log(aspect / 0.72));
    if (score < bestScore) {
      bestScore = score;
      bestCols = c;
    }
  }
  return { cols: bestCols, rows: n / bestCols, n };
}

function sheetCountSvg(cols, rows, count, lw, lh) {
  const totalW = cols * lw;
  const totalH = rows * lh;
  const maxW = 176;
  const maxH = 236;
  const scale = Math.min(maxW / totalW, maxH / totalH);
  const drawW = totalW * scale;
  const drawH = totalH * scale;
  const cellW = lw * scale;
  const cellH = lh * scale;
  const pad = 2;
  const x0 = pad;
  const y0 = pad;
  const ink = '#1a1a1a';
  const digits = String(count).length;
  const numSize = Math.max(22, Math.min(drawW / (digits * 0.68), drawH * 0.42, 84));
  const plateW = digits * numSize * 0.66;
  const plateH = numSize * 0.78;
  const cx = x0 + drawW / 2;
  const cy = y0 + drawH / 2;

  const lines = [];
  for (let c = 1; c < cols; c += 1) {
    const x = x0 + c * cellW;
    lines.push(`<line x1="${x.toFixed(2)}" y1="${y0.toFixed(2)}" x2="${x.toFixed(2)}" y2="${(y0 + drawH).toFixed(2)}" stroke="${ink}" stroke-width="1.05"/>`);
  }
  for (let r = 1; r < rows; r += 1) {
    const y = y0 + r * cellH;
    lines.push(`<line x1="${x0.toFixed(2)}" y1="${y.toFixed(2)}" x2="${(x0 + drawW).toFixed(2)}" y2="${y.toFixed(2)}" stroke="${ink}" stroke-width="1.05"/>`);
  }

  const vbW = pad * 2 + drawW;
  const vbH = pad * 2 + drawH;
  return `<svg class="sheet-count" viewBox="0 0 ${vbW.toFixed(1)} ${vbH.toFixed(1)}" width="${Math.round(vbW)}" height="${Math.round(vbH)}" role="img" aria-label="${count}칸">
    <rect x="${x0.toFixed(2)}" y="${y0.toFixed(2)}" width="${drawW.toFixed(2)}" height="${drawH.toFixed(2)}" fill="#fff" stroke="${ink}" stroke-width="1.7"/>
    ${lines.join('')}
    <rect x="${(cx - plateW / 2).toFixed(2)}" y="${(cy - plateH / 2).toFixed(2)}" width="${plateW.toFixed(2)}" height="${plateH.toFixed(2)}" fill="#fff"/>
    <text x="${cx.toFixed(2)}" y="${cy.toFixed(2)}" text-anchor="middle" dominant-baseline="central" fill="${ink}" font-family="Pretendard, sans-serif" font-size="${numSize.toFixed(1)}" font-weight="900">${count}</text>
  </svg>`;
}

function cellSpecSvg(lw, lh) {
  const widthLabel = `${formatMm(lw)}mm`;
  const heightLabel = `${formatMm(lh)}mm`;
  const maxW = 188;
  const maxH = 118;
  const scale = Math.min(maxW / lw, maxH / lh);
  const w = lw * scale;
  const h = lh * scale;
  const font = 15;
  const charW = font * 0.58;
  const tick = 5;
  const gap = 9;
  const ink = '#1a1a1a';
  const widthTextW = widthLabel.length * charW;
  const heightTextH = heightLabel.length * charW;
  const padL = Math.max(4, (widthTextW - w) / 2 + 2);
  const padR = 28;
  const padT = Math.max(4, (heightTextH - h) / 2 + 2);
  const padB = gap + 22;
  const x = padL;
  const y = padT;
  const dimY = y + h + gap;
  const vx = x + w + gap;
  const midY = y + h / 2;
  const labelX = vx + 14;
  const vbW = vx + padR;
  const vbH = dimY + 18;

  return `<svg class="cell-spec" viewBox="0 0 ${vbW.toFixed(1)} ${vbH.toFixed(1)}" width="${Math.round(vbW)}" height="${Math.round(vbH)}" role="img" aria-label="한 칸 ${esc(widthLabel)} × ${esc(heightLabel)}">
    <rect x="${x.toFixed(2)}" y="${y.toFixed(2)}" width="${w.toFixed(2)}" height="${h.toFixed(2)}" fill="#fff" stroke="${ink}" stroke-width="1.7"/>
    <line x1="${x.toFixed(2)}" y1="${dimY.toFixed(2)}" x2="${(x + w).toFixed(2)}" y2="${dimY.toFixed(2)}" stroke="${ink}" stroke-width="1"/>
    <line x1="${x.toFixed(2)}" y1="${(dimY - tick).toFixed(2)}" x2="${x.toFixed(2)}" y2="${(dimY + tick).toFixed(2)}" stroke="${ink}" stroke-width="1"/>
    <line x1="${(x + w).toFixed(2)}" y1="${(dimY - tick).toFixed(2)}" x2="${(x + w).toFixed(2)}" y2="${(dimY + tick).toFixed(2)}" stroke="${ink}" stroke-width="1"/>
    <text x="${(x + w / 2).toFixed(2)}" y="${(dimY + 16).toFixed(2)}" text-anchor="middle" fill="${ink}" font-family="Pretendard, sans-serif" font-size="${font}" font-weight="500">${esc(widthLabel)}</text>
    <line x1="${vx.toFixed(2)}" y1="${y.toFixed(2)}" x2="${vx.toFixed(2)}" y2="${(y + h).toFixed(2)}" stroke="${ink}" stroke-width="1"/>
    <line x1="${(vx - tick).toFixed(2)}" y1="${y.toFixed(2)}" x2="${(vx + tick).toFixed(2)}" y2="${y.toFixed(2)}" stroke="${ink}" stroke-width="1"/>
    <line x1="${(vx - tick).toFixed(2)}" y1="${(y + h).toFixed(2)}" x2="${(vx + tick).toFixed(2)}" y2="${(y + h).toFixed(2)}" stroke="${ink}" stroke-width="1"/>
    <text x="${labelX.toFixed(2)}" y="${midY.toFixed(2)}" text-anchor="middle" dominant-baseline="central" transform="rotate(-90 ${labelX.toFixed(2)} ${midY.toFixed(2)})" fill="${ink}" font-family="Pretendard, sans-serif" font-size="${font}" font-weight="500">${esc(heightLabel)}</text>
  </svg>`;
}

function diagramSvg(product) {
  const { cols, rows, n } = resolveGrid(product);
  const lw = Number(product.widthMm) || 1;
  const lh = Number(product.heightMm) || 1;
  return `<div class="diagram">${sheetCountSvg(cols, rows, n, lw, lh)}${cellSpecSvg(lw, lh)}</div>`;
}

function optionCards(options) {
  const cards = (options || []).map((option) => {
    const name = String(option.name || '');
    const matched = name.match(/(\d+)/);
    const number = matched ? matched[1] : name;
    return `<div class="opt"><div class="k">OPTION</div><div class="n">${esc(number)}</div><div class="u">매입</div></div>`;
  });
  if (cards.length === 0) return '';
  return `<div class="opts opts--${Math.min(cards.length, 6)}">${cards.join('')}</div>`;
}

function sheetHtml(product) {
  const category = String(product.categoryName || '');
  const intro = INTROS[category] || ['용도에 맞춰 정보를 표시하는 라벨입니다.', '필요한 규격을 선택해 사용할 수 있습니다.'];
  const badges = (Array.isArray(product.hashtags) ? product.hashtags : [])
    .map((tag) => String(tag).trim())
    .filter(Boolean);
  const size = `${formatMm(product.widthMm)} × ${formatMm(product.heightMm)} mm`;
  const count = Math.max(0, Number(product.labelsPerSheet) || 0);
  const material = String(product.material || '').trim() || '-';
  const rows = [
    ['제품명', product.name || '-'],
    ['제품번호', product.sku || '-'],
    ['규격', size],
    ['칸수', count > 0 ? `${count}칸` : '-'],
    ['프린터', '레이저잉크젯 공용'],
    ['재질', material],
  ];

  return `<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #f7f3ee; }
  .sheet {
    width: 720px;
    background: #f7f3ee;
    padding: 54px 72px 46px;
    font-family: Pretendard, sans-serif;
    color: #2a2623;
  }
  .eyebrow {
    margin: 0;
    text-align: center;
    color: #8d3b46;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.16em;
  }
  h1 {
    margin: 14px 0 0;
    text-align: center;
    font-family: Pretendard, sans-serif;
    font-size: 34px;
    font-weight: 700;
    letter-spacing: -0.03em;
    line-height: 1.25;
  }
  .lead {
    margin: 16px auto 0;
    max-width: 520px;
    text-align: center;
    color: #5f5954;
    font-size: 14.5px;
    line-height: 1.7;
    font-weight: 400;
  }
  .diagram-wrap { display: flex; justify-content: center; margin: 26px 0 4px; }
  .diagram { display: flex; align-items: center; justify-content: center; gap: 36px; }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 18px;
  }
  tr { border-top: 1px solid #ece6df; }
  tr:first-child { border-top: 1.5px solid #6d3942; }
  td { padding: 13px 2px; font-size: 15px; vertical-align: middle; }
  td.k { color: #8a847e; font-weight: 400; width: 28%; }
  td.v { text-align: right; font-weight: 600; color: #241f1c; line-height: 1.45; }
  .opts {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 14px;
    margin-top: 28px;
  }
  .opt {
    width: 132px;
    background: #fff;
    border: 1px solid #e5ddd6;
    border-radius: 18px;
    padding: 16px 8px 14px;
    text-align: center;
  }
  .opts--1 .opt, .opts--2 .opt { width: 148px; }
  .opts--4 .opt { width: 118px; }
  .opts--5 .opt, .opts--6 .opt { width: 108px; }
  .opt .k { color: #9a938c; font-size: 11px; font-weight: 600; letter-spacing: 0.14em; }
  .opt .n { margin-top: 6px; font-size: 30px; font-weight: 700; letter-spacing: -0.03em; color: #2a2623; line-height: 1; }
  .opt .u { margin-top: 6px; color: #8d8680; font-size: 13px; }
  .why { margin-top: 54px; text-align: center; }
  .why h2 {
    margin: 12px 0 0;
    font-family: Pretendard, sans-serif;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -0.03em;
    line-height: 1.35;
  }
  .why p { margin: 16px auto 0; max-width: 460px; color: #5f5954; font-size: 14.5px; line-height: 1.7; }
  .badges { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 22px; }
  .badge {
    background: #fff;
    border: 1px solid #e4ddd6;
    border-radius: 999px;
    padding: 7px 14px;
    color: #4e4944;
    font-size: 13.5px;
    line-height: 1.2;
  }
</style>
</head>
<body>
  <article class="sheet">
    <p class="eyebrow">PRODUCT INFO</p>
    <h1>제품 정보</h1>
    <p class="lead">${esc(intro[0])}<br>${esc(intro[1])}</p>
    <div class="diagram-wrap">${diagramSvg(product)}</div>
    <table>
      ${rows.map(([k, v]) => `<tr><td class="k">${esc(k)}</td><td class="v">${esc(v)}</td></tr>`).join('')}
    </table>
    ${optionCards(product.options)}
    <section class="why">
      <p class="eyebrow">WHY THIS LABEL</p>
      <h2>물류부터 문서까지,<br>한 장이면 충분합니다</h2>
      <p>물류정보 표기부터 주소, 바코드, 문서 정리까지<br>필요한 용도에 맞춰 규격을 선택해 사용할 수 있습니다.</p>
      ${badges.length ? `<div class="badges">${badges.map((b) => `<span class="badge">${esc(b)}</span>`).join('')}</div>` : ''}
    </section>
  </article>
</body>
</html>`;
}

async function fetchJson(url) {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`${url} → ${res.status}`);
  const body = await res.json();
  if (!body.success) throw new Error(`${url} → ${body.message || 'failed'}`);
  return body.data;
}

async function mapPool(items, limit, worker) {
  const out = new Array(items.length);
  let cursor = 0;
  async function run() {
    while (cursor < items.length) {
      const index = cursor;
      cursor += 1;
      out[index] = await worker(items[index], index);
    }
  }
  await Promise.all(Array.from({ length: Math.min(limit, items.length) }, () => run()));
  return out;
}

function wantedSkus() {
  const idx = process.argv.indexOf('--sku');
  if (idx < 0) return null;
  return new Set(String(process.argv[idx + 1] || '').split(',').map((s) => s.trim()).filter(Boolean));
}

async function main() {
  const only = wantedSkus();
  const papers = await fetchJson(PAPERS_URL);
  let items = papers.items || [];
  if (only) items = items.filter((item) => only.has(item.sku));
  if (items.length === 0) throw new Error('생성할 상품이 없습니다.');

  const detailed = await mapPool(items, 8, async (item) => {
    const detail = await fetchJson(PRODUCT_URL + item.id);
    return {
      ...item,
      name: detail.name || item.name,
      sku: detail.sku || item.sku,
      categoryName: detail.category || item.categoryName,
      hashtags: Array.isArray(detail.hashtags) ? detail.hashtags : null,
      material: detail.material || item.material,
      widthMm: detail.width_mm || item.widthMm,
      heightMm: detail.height_mm || item.heightMm,
      labelsPerSheet: detail.labels_per_sheet || item.labelsPerSheet,
      options: Array.isArray(detail.options) ? detail.options : [],
    };
  });

  const withoutTags = detailed.filter((p) => !Array.isArray(p.hashtags));
  if (withoutTags.length) {
    throw new Error(`해시태그를 받지 못한 상품: ${withoutTags.map((p) => p.sku).join(', ')}`);
  }

  const missing = [...new Set(detailed.map((p) => p.categoryName).filter((name) => !INTROS[name]))];
  if (missing.length) {
    console.warn('소개 문구가 없는 카테고리:', missing.join(', '));
  }

  await mkdir(OUT_DIR, { recursive: true });
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 760, height: 1200 }, deviceScaleFactor: 1050 / 720 });
  const written = [];
  try {
    for (let i = 0; i < detailed.length; i += 1) {
      const product = detailed[i];
      const file = `${product.sku}.jpg`;
      const dest = path.join(OUT_DIR, file);
      await page.setContent(sheetHtml(product), { waitUntil: 'networkidle', timeout: 30000 });
      const fontReady = await page.evaluate(async () => {
        const faces = ['400 15px Pretendard', '500 15px Pretendard', '600 15px Pretendard', '700 34px Pretendard', '900 80px Pretendard'];
        await Promise.all(faces.map((face) => document.fonts.load(face)));
        await document.fonts.ready;
        return faces.every((face) => document.fonts.check(face));
      });
      if (!fontReady) throw new Error('Pretendard 글꼴을 불러오지 못했습니다.');
      const sheet = await page.$('.sheet');
      await sheet.screenshot({ path: dest, type: 'jpeg', quality: 92 });
      written.push({ sku: product.sku, file, options: product.options.length, category: product.categoryName });
      if ((i + 1) % 10 === 0 || i === detailed.length - 1) {
        console.log(`${i + 1}/${detailed.length} ${product.sku}`);
      }
    }
  } finally {
    await browser.close();
  }

  const manifest = path.resolve(__dirname, '../storage/imports/spec_sheet_manifest.json');
  await mkdir(path.dirname(manifest), { recursive: true });
  await writeFile(manifest, JSON.stringify(written, null, 2), 'utf8');
  console.log(`완료 ${written.length}장 → ${OUT_DIR}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
