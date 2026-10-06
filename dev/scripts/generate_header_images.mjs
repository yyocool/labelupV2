/**
 * 상품 상세 헤더 이미지를 만든다.
 * 가운데는 대표 목업, 아래 제목은 카테고리명, 설명은 카테고리별 고정 문구.
 * 사용: node scripts/generate_header_images.mjs [--sku A101,A102]
 */
import { createRequire } from 'module';
import { mkdir, readFile, readdir, writeFile } from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';

const require = createRequire(import.meta.url);
const { chromium } = require('../_tmp_pw/node_modules/playwright');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT_DIR = path.resolve(__dirname, '../public/assets/product-headers');
const MOCKUP_DIR = path.resolve(__dirname, '../public/assets/products/mockups');
const PAPERS_URL = 'https://www.labelup.co.kr/api/shop/editor-papers';
const CATALOG_URL = 'https://www.labelup.co.kr/api/shop/catalog?page=1';

const COPY = {
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

function heading(categoryName, parentName) {
  const name = String(categoryName || '').trim();
  const parent = String(parentName || '').trim();
  if (parent) return `${parent} (${name})`;
  return name;
}

function posterHtml(product) {
  const title = heading(product.categoryName, product.parentName);
  const lines = COPY[product.categoryName];
  return `<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #5A1124; }
  .poster {
    width: 980px;
    min-height: 1080px;
    padding: 58px 72px 64px;
    background-color: #5A1124;
    background-image: repeating-linear-gradient(
      -32deg,
      rgba(255,255,255,0.045) 0 1px,
      transparent 1px 12px
    );
    color: #fff;
    font-family: Pretendard, sans-serif;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .eyebrow {
    margin: 0;
    font-size: 15px;
    font-weight: 500;
    letter-spacing: 0.22em;
    color: rgba(255,255,255,0.88);
  }
  .logo {
    margin-top: 22px;
    font-size: 72px;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 0.95;
  }
  .logo-u { position: relative; display: inline-block; }
  .logo-u::before {
    content: "";
    position: absolute;
    left: 50%;
    top: 0.04em;
    width: 0.42em;
    height: 0.2em;
    transform: translateX(-50%);
    background: #fff;
    clip-path: polygon(0 100%, 14% 100%, 50% 22%, 86% 100%, 100% 100%, 50% 0);
  }
  .rule {
    width: 86px;
    height: 3px;
    margin: 16px auto 0;
    background: #fff;
    border-radius: 2px;
  }
  .headline {
    margin: 22px 0 0;
    font-size: 32px;
    font-weight: 700;
    letter-spacing: -0.03em;
    line-height: 1.25;
  }
  .lead {
    margin: 14px 0 0;
    max-width: 620px;
    font-size: 18px;
    font-weight: 400;
    line-height: 1.65;
    color: rgba(255,255,255,0.92);
  }
  .stage {
    position: relative;
    margin: 36px 0 8px;
    width: 520px;
    min-height: 420px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
  }
  .stage::after {
    content: "";
    position: absolute;
    left: 50%;
    bottom: 6px;
    width: 78%;
    height: 36px;
    transform: translateX(-50%);
    background: radial-gradient(ellipse, rgba(0,0,0,0.38) 0%, rgba(0,0,0,0) 72%);
  }
  .pack {
    position: relative;
    z-index: 1;
    width: 470px;
    max-height: 430px;
    object-fit: contain;
    filter: drop-shadow(0 22px 28px rgba(0,0,0,0.32));
  }
  .cat {
    margin: 28px 0 0;
    font-size: 48px;
    font-weight: 800;
    letter-spacing: -0.04em;
    line-height: 1.2;
  }
  .desc {
    margin: 16px 0 0;
    max-width: 680px;
    font-size: 18px;
    font-weight: 400;
    line-height: 1.7;
    color: rgba(255,255,255,0.92);
  }
</style>
</head>
<body>
  <article class="poster">
    <p class="eyebrow">ESTD. LABEL UP · AI LABEL PLATFORM</p>
    <div class="logo">LABEL<span class="logo-u">U</span>P</div>
    <div class="rule"></div>
    <h1 class="headline">라벨업AI로 쉽고 빠르게</h1>
    <p class="lead">디자인 생성부터 편집, 출력, 용지 구매까지<br>라벨 제작의 전 과정을 하나로 연결합니다.</p>
    <div class="stage"><img class="pack" src="${esc(product.mockupUrl)}" alt=""></div>
    <h2 class="cat">${esc(title)}</h2>
    <p class="desc">${esc(lines[0])}<br>${esc(lines[1])}</p>
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

async function mockupIndex() {
  const index = new Map();
  const names = await readdir(MOCKUP_DIR);
  for (const name of names) {
    const ext = path.extname(name).toLowerCase();
    if (!['.jpg', '.jpeg', '.png', '.webp', '.gif'].includes(ext)) continue;
    index.set(path.basename(name, path.extname(name)).toLowerCase(), path.join(MOCKUP_DIR, name));
  }
  return index;
}

function mimeFor(file) {
  const ext = path.extname(file).toLowerCase();
  if (ext === 'png') return 'image/png';
  if (ext === 'webp') return 'image/webp';
  if (ext === 'gif') return 'image/gif';
  return 'image/jpeg';
}

async function dataUrl(file) {
  const buf = await readFile(file);
  return `data:${mimeFor(file)};base64,${buf.toString('base64')}`;
}

function wantedSkus() {
  const idx = process.argv.indexOf('--sku');
  if (idx < 0) return null;
  return new Set(String(process.argv[idx + 1] || '').split(',').map((s) => s.trim()).filter(Boolean));
}

async function main() {
  const only = wantedSkus();
  const [papers, catalog] = await Promise.all([fetchJson(PAPERS_URL), fetchJson(CATALOG_URL)]);
  const parents = new Map();
  for (const cat of catalog.categories || []) {
    parents.set(String(cat.name || '').trim(), String(cat.parent_name || '').trim());
  }
  const mockups = await mockupIndex();
  let items = papers.items || [];
  if (only) items = items.filter((item) => only.has(item.sku));
  if (items.length === 0) throw new Error('생성할 상품이 없습니다.');

  const missingCopy = [...new Set(items.map((item) => String(item.categoryName || '').trim()).filter((name) => !COPY[name]))];
  if (missingCopy.length) {
    throw new Error(`설명 문구가 없는 카테고리: ${missingCopy.join(', ')}`);
  }

  const jobs = [];
  const skipped = [];
  for (const item of items) {
    const sku = String(item.sku || '').trim();
    const mockup = mockups.get(sku.toLowerCase());
    if (!mockup) {
      skipped.push(sku);
      continue;
    }
    const categoryName = String(item.categoryName || '').trim();
    jobs.push({
      sku,
      categoryName,
      parentName: parents.get(categoryName) || '',
      mockupUrl: await dataUrl(mockup),
    });
  }

  await mkdir(OUT_DIR, { recursive: true });
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1040, height: 1400 }, deviceScaleFactor: 1050 / 980 });
  const written = [];
  try {
    for (let i = 0; i < jobs.length; i += 1) {
      const product = jobs[i];
      const dest = path.join(OUT_DIR, `${product.sku}.jpg`);
      await page.setContent(posterHtml(product), { waitUntil: 'networkidle', timeout: 30000 });
      const fontReady = await page.evaluate(async () => {
        const faces = ['400 18px Pretendard', '500 15px Pretendard', '700 32px Pretendard', '800 72px Pretendard'];
        await Promise.all(faces.map((face) => document.fonts.load(face)));
        await document.fonts.ready;
        return faces.every((face) => document.fonts.check(face));
      });
      if (!fontReady) throw new Error('Pretendard 글꼴을 불러오지 못했습니다.');
      const loaded = await page.evaluate(() => {
        const img = document.querySelector('.pack');
        return !!(img && img.naturalWidth > 0);
      });
      if (!loaded) throw new Error(`${product.sku} 목업 이미지를 그리지 못했습니다.`);
      const poster = await page.$('.poster');
      await poster.screenshot({ path: dest, type: 'jpeg', quality: 92 });
      written.push({ sku: product.sku, category: heading(product.categoryName, product.parentName) });
      if ((i + 1) % 10 === 0 || i === jobs.length - 1) {
        console.log(`${i + 1}/${jobs.length} ${product.sku}`);
      }
    }
  } finally {
    await browser.close();
  }

  const manifest = path.resolve(__dirname, '../storage/imports/header_image_manifest.json');
  await mkdir(path.dirname(manifest), { recursive: true });
  await writeFile(manifest, JSON.stringify({ written, skipped }, null, 2), 'utf8');
  console.log(`완료 ${written.length}장, 목업 없음 ${skipped.length}개 → ${OUT_DIR}`);
  if (skipped.length) console.log('목업 없음:', skipped.join(', '));
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
