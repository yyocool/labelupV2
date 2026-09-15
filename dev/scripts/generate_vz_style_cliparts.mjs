#!/usr/bin/env node
/**
 * Vecteezy 인기 테마를 참고한 원본 클립아트 생성.
 * VECTORS / PNGs / SVGs 카테고리별 20개.
 * Vecteezy 저작물은 사용하지 않는다.
 */
import fs from 'fs';
import path from 'path';
import zlib from 'zlib';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'public', 'assets', 'cliparts');
const MANIFEST = path.join(ROOT, 'storage', 'imports', 'clipart_vz_manifest.json');
const SIZE = 512;

const ITEMS = [
  { slug: 'coffee', title: '커피 컵', ko: '커피', tags: '#커피 #카페 #음료 #라벨' },
  { slug: 'flower', title: '플라워', ko: '꽃', tags: '#꽃 #플라워 #자연 #장식' },
  { slug: 'heart', title: '하트', ko: '하트', tags: '#하트 #사랑 #선물' },
  { slug: 'frame', title: '오너먼트 프레임', ko: '프레임', tags: '#프레임 #테두리 #배지' },
  { slug: 'arrow', title: '화살표', ko: '화살표', tags: '#화살표 #아이콘 #방향' },
  { slug: 'star', title: '스타', ko: '별', tags: '#별 #스타 #포인트' },
  { slug: 'leaf', title: '리프', ko: '잎사귀', tags: '#잎 #친환경 #자연' },
  { slug: 'tree', title: '트리', ko: '나무', tags: '#나무 #식물 #자연' },
  { slug: 'sun', title: '선샤인', ko: '태양', tags: '#태양 #날씨 #해' },
  { slug: 'cloud', title: '클라우드', ko: '구름', tags: '#구름 #날씨 #하늘' },
  { slug: 'gift', title: '기프트 박스', ko: '선물상자', tags: '#선물 #박스 #리본' },
  { slug: 'cat', title: '고양이', ko: '고양이', tags: '#고양이 #동물 #펫' },
  { slug: 'dog', title: '강아지', ko: '강아지', tags: '#강아지 #동물 #펫' },
  { slug: 'apple', title: '애플', ko: '사과', tags: '#사과 #푸드 #과일' },
  { slug: 'banner', title: '리본 배너', ko: '배너', tags: '#배너 #리본 #라벨' },
  { slug: 'check', title: '체크 아이콘', ko: '체크', tags: '#체크 #아이콘 #확인' },
  { slug: 'pin', title: '로케이션 핀', ko: '위치핀', tags: '#핀 #위치 #맵' },
  { slug: 'ribbon', title: '보우 리본', ko: '리본', tags: '#리본 #선물 #장식' },
  { slug: 'snow', title: '스노우플레이크', ko: '눈꽃', tags: '#눈꽃 #겨울 #시즌' },
  { slug: 'butterfly', title: '버터플라이', ko: '나비', tags: '#나비 #봄 #자연' },
];

const C = {
  burgundy: '#7B2840',
  rose: '#C45A78',
  blush: '#F0BECD',
  teal: '#1A8A8A',
  mint: '#7ED4C8',
  gold: '#E2B14A',
};
C.navy = '#1E4A7A';
C.sky = '#6EB6E6';
C.leaf = '#2F8F4E';
C.lime = '#8FCB5A';
C.orange = '#E07A2F';
C.cream = '#FFF6EC';
C.ink = '#2A2426';
C.white = '#FFFFFF';

function svgWrap(body, bg = 'none') {
  return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512" fill="none">
  ${bg !== 'none' ? `<rect width="512" height="512" fill="${bg}"/>` : ''}
  ${body}
</svg>
`;
}

function vectorSvg(slug) {
  const m = {
    coffee: `
      <ellipse cx="256" cy="430" rx="90" ry="16" fill="${C.blush}"/>
      <path d="M168 168h176c18 0 28 16 26 34l-18 176c-3 28-28 46-56 46H216c-28 0-53-18-56-46l-18-176c-2-18 8-34 26-34Z" fill="${C.burgundy}"/>
      <path d="M188 168h136v28H188Z" fill="${C.rose}"/>
      <path d="M344 210c46 0 70 28 70 62s-24 62-70 62" stroke="${C.gold}" stroke-width="22" stroke-linecap="round"/>
      <path d="M210 120c8-28 22-40 34-40 14 0 18 16 30 16 10 0 16-14 28-14 16 0 28 20 20 44" stroke="${C.navy}" stroke-width="10" stroke-linecap="round" fill="none"/>
      <circle cx="230" cy="300" r="10" fill="${C.blush}" opacity=".5"/>`,
    flower: `
      ${[0, 60, 120, 180, 240, 300].map((a) => {
        const r = a * Math.PI / 180;
        const x = 256 + Math.cos(r) * 88;
        const y = 256 + Math.sin(r) * 88;
        return `<ellipse cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" rx="52" ry="70" transform="rotate(${a} ${x.toFixed(1)} ${y.toFixed(1)})" fill="${C.rose}"/>`;
      }).join('\n')}
      <circle cx="256" cy="256" r="48" fill="${C.gold}"/>
      <circle cx="256" cy="256" r="22" fill="${C.cream}"/>`,
    heart: `
      <path d="M256 430C120 330 86 236 86 176c0-52 40-90 90-90 36 0 62 18 80 46 18-28 44-46 80-46 50 0 90 38 90 90 0 60-34 154-170 254Z" fill="${C.burgundy}"/>
      <path d="M176 168c12-22 30-34 52-34" stroke="${C.blush}" stroke-width="16" stroke-linecap="round"/>`,
    frame: `
      <rect x="64" y="64" width="384" height="384" rx="28" fill="${C.cream}" stroke="${C.gold}" stroke-width="28"/>
      <rect x="96" y="96" width="320" height="320" rx="12" stroke="${C.burgundy}" stroke-width="8"/>
      <circle cx="64" cy="64" r="18" fill="${C.rose}"/>
      <circle cx="448" cy="64" r="18" fill="${C.rose}"/>
      <circle cx="64" cy="448" r="18" fill="${C.rose}"/>
      <circle cx="448" cy="448" r="18" fill="${C.rose}"/>`,
    arrow: `
      <path d="M80 256h250" stroke="${C.navy}" stroke-width="44" stroke-linecap="round"/>
      <path d="M290 140l150 116L290 372Z" fill="${C.teal}"/>`,
    star: `
      <path d="M256 56l52 148h156l-126 92 48 148-130-94-130 94 48-148-126-92h156Z" fill="${C.gold}"/>
      <path d="M256 150l24 70h74l-60 44 22 70-60-44-60 44 22-70-60-44h74Z" fill="${C.cream}"/>`,
    leaf: `
      <path d="M256 60c130 70 170 190 130 310-70 40-170 50-230-20C96 250 150 110 256 60Z" fill="${C.leaf}"/>
      <path d="M250 90c8 90 10 180 6 300" stroke="${C.lime}" stroke-width="12" stroke-linecap="round"/>
      <path d="M252 180c40-10 78 10 96 40M250 250c46 0 86 24 100 56M248 320c40 8 70 30 80 56" stroke="${C.lime}" stroke-width="8" stroke-linecap="round"/>`,
    tree: `
      <rect x="228" y="340" width="56" height="100" rx="10" fill="#8B5A2B"/>
      <path d="M256 70l130 160H126Z" fill="${C.leaf}"/>
      <path d="M256 150l140 170H116Z" fill="${C.lime}"/>
      <circle cx="220" cy="200" r="8" fill="${C.gold}"/>
      <circle cx="300" cy="260" r="8" fill="${C.rose}"/>`,
    sun: `
      ${[...Array(12)].map((_, i) => {
        const a = i * 30 * Math.PI / 180;
        const x1 = 256 + Math.cos(a) * 120;
        const y1 = 256 + Math.sin(a) * 120;
        const x2 = 256 + Math.cos(a) * 175;
        const y2 = 256 + Math.sin(a) * 175;
        return `<line x1="${x1.toFixed(1)}" y1="${y1.toFixed(1)}" x2="${x2.toFixed(1)}" y2="${y2.toFixed(1)}" stroke="${C.orange}" stroke-width="16" stroke-linecap="round"/>`;
      }).join('\n')}
      <circle cx="256" cy="256" r="92" fill="${C.gold}"/>
      <circle cx="256" cy="256" r="70" fill="#F6D56B"/>`,
    cloud: `
      <ellipse cx="200" cy="270" rx="90" ry="70" fill="${C.sky}"/>
      <ellipse cx="300" cy="250" rx="110" ry="86" fill="${C.sky}"/>
      <ellipse cx="370" cy="286" rx="74" ry="56" fill="${C.navy}"/>
      <ellipse cx="256" cy="300" rx="150" ry="70" fill="${C.sky}"/>`,
    gift: `
      <rect x="120" y="210" width="272" height="200" rx="18" fill="${C.burgundy}"/>
      <path d="M120 210l136-70 136 70v36H120Z" fill="${C.rose}"/>
      <rect x="236" y="140" width="40" height="270" fill="${C.gold}"/>
      <path d="M176 120c0-36 28-56 56-36 10 8 24 28 24 28s14-20 24-28c28-20 56 0 56 36 0 28-40 50-80 70-40-20-80-42-80-70Z" fill="${C.gold}"/>`,
    cat: `
      <ellipse cx="256" cy="300" rx="130" ry="120" fill="#F0C27A"/>
      <path d="M150 210l-20-110 90 70Z" fill="#F0C27A"/>
      <path d="M362 210l20-110-90 70Z" fill="#F0C27A"/>
      <path d="M150 210l-10-80 70 56Z" fill="#E8A45A"/>
      <circle cx="210" cy="286" r="16" fill="${C.ink}"/>
      <circle cx="302" cy="286" r="16" fill="${C.ink}"/>
      <path d="M256 310l-16 22h32Z" fill="${C.rose}"/>
      <path d="M200 360c20 20 56 24 56 24s36-4 56-24" stroke="${C.ink}" stroke-width="8" stroke-linecap="round"/>`,
    dog: `
      <ellipse cx="256" cy="310" rx="128" ry="118" fill="#C8894A"/>
      <ellipse cx="150" cy="250" rx="46" ry="72" fill="#A86B32" transform="rotate(-20 150 250)"/>
      <ellipse cx="362" cy="250" rx="46" ry="72" fill="#A86B32" transform="rotate(20 362 250)"/>
      <ellipse cx="256" cy="340" rx="64" ry="46" fill="#E8C39A"/>
      <circle cx="214" cy="286" r="15" fill="${C.ink}"/>
      <circle cx="298" cy="286" r="15" fill="${C.ink}"/>
      <ellipse cx="256" cy="326" rx="18" ry="12" fill="${C.ink}"/>
      <path d="M210 370c22 16 46 18 46 18s24-2 46-18" stroke="${C.ink}" stroke-width="8" stroke-linecap="round"/>`,
    apple: `
      <path d="M256 140c70-90 150-20 150 70 0 130-70 220-150 220S106 340 106 210c0-90 80-160 150-70Z" fill="#D64545"/>
      <path d="M256 140c-20 50-10 90 20 120" stroke="#8B2E2E" stroke-width="8"/>
      <path d="M256 128c10-40 40-58 70-50-6 36-28 56-70 50Z" fill="${C.leaf}"/>
      <circle cx="200" cy="210" r="16" fill="${C.blush}" opacity=".6"/>`,
    banner: `
      <path d="M70 230h372l-36 50 36 50H70l36-50Z" fill="${C.burgundy}"/>
      <path d="M70 230l36 50-36 50v-100Z" fill="${C.rose}"/>
      <path d="M442 230l-36 50 36 50v-100Z" fill="${C.rose}"/>
      <rect x="150" y="248" width="212" height="36" rx="8" fill="${C.gold}"/>`,
    check: `
      <circle cx="256" cy="256" r="170" fill="${C.teal}"/>
      <path d="M160 262l62 62 130-140" stroke="${C.white}" stroke-width="36" stroke-linecap="round" stroke-linejoin="round"/>`,
    pin: `
      <path d="M256 70c86 0 150 70 150 156 0 120-150 230-150 230S106 346 106 226C106 140 170 70 256 70Z" fill="${C.burgundy}"/>
      <circle cx="256" cy="214" r="58" fill="${C.cream}"/>
      <circle cx="256" cy="214" r="28" fill="${C.rose}"/>`,
    ribbon: `
      <path d="M120 150c0-40 70-70 136 10 0 0 20-50 80-50 70 0 90 70 40 120L256 310 160 230c-40-36-40-50-40-80Z" fill="${C.rose}"/>
      <path d="M392 150c0-40-70-70-136 10 0 0-20-50-80-50-70 0-90 70-40 120l120 80 96-80c40-36 40-50 40-80Z" fill="${C.burgundy}"/>
      <path d="M200 300l56 130 56-130-56 36Z" fill="${C.gold}"/>`,
    snow: `
      <g stroke="${C.sky}" stroke-width="16" stroke-linecap="round">
        <line x1="256" y1="80" x2="256" y2="432"/>
        <line x1="80" y1="256" x2="432" y2="256"/>
        <line x1="130" y1="130" x2="382" y2="382"/>
        <line x1="382" y1="130" x2="130" y2="382"/>
      </g>
      ${[0, 60, 120, 180, 240, 300].map((a) => {
        const r = a * Math.PI / 180;
        const x = 256 + Math.cos(r) * 150;
        const y = 256 + Math.sin(r) * 150;
        return `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="14" fill="${C.navy}"/>`;
      }).join('\n')}
      <circle cx="256" cy="256" r="22" fill="${C.navy}"/>`,
    butterfly: `
      <ellipse cx="170" cy="200" rx="110" ry="80" fill="${C.rose}" transform="rotate(-20 170 200)"/>
      <ellipse cx="342" cy="200" rx="110" ry="80" fill="${C.burgundy}" transform="rotate(20 342 200)"/>
      <ellipse cx="180" cy="320" rx="80" ry="60" fill="${C.gold}" transform="rotate(18 180 320)"/>
      <ellipse cx="332" cy="320" rx="80" ry="60" fill="${C.gold}" transform="rotate(-18 332 320)"/>
      <rect x="246" y="150" width="20" height="230" rx="10" fill="${C.ink}"/>
      <circle cx="256" cy="150" r="16" fill="${C.ink}"/>`,
  };
  return svgWrap(m[slug] || m.star);
}

function iconSvg(slug) {
  const stroke = C.ink;
  const fill = C.burgundy;
  const m = {
    coffee: `<path d="M170 190h150c12 0 20 10 18 22l-14 150c-2 20-20 32-40 32H206c-20 0-38-12-40-32l-14-150c-2-12 6-22 18-22Z" fill="${fill}"/><path d="M338 230c36 0 52 22 52 46s-16 46-52 46" stroke="${stroke}" stroke-width="16" fill="none" stroke-linecap="round"/><path d="M214 150c6-22 18-32 28-32 10 0 14 14 24 14s12-12 22-12" stroke="${stroke}" stroke-width="10" fill="none" stroke-linecap="round"/>`,
    flower: `<circle cx="256" cy="256" r="36" fill="${C.gold}"/>
      ${[0, 72, 144, 216, 288].map((a) => {
        const r = a * Math.PI / 180;
        const x = 256 + Math.cos(r) * 78;
        const y = 256 + Math.sin(r) * 78;
        return `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="38" fill="${fill}"/>`;
      }).join('')}`,
    heart: `<path d="M256 392C150 314 118 248 118 198c0-40 32-70 70-70 28 0 48 14 68 40 20-26 40-40 68-40 38 0 70 30 70 70 0 50-32 116-138 194Z" fill="${fill}"/>`,
    frame: `<rect x="110" y="110" width="292" height="292" rx="20" stroke="${fill}" stroke-width="22"/><rect x="150" y="150" width="212" height="212" rx="8" stroke="${stroke}" stroke-width="8"/>`,
    arrow: `<path d="M120 256h210" stroke="${fill}" stroke-width="28" stroke-linecap="round"/><path d="M300 168l120 88-120 88Z" fill="${fill}"/>`,
    star: `<path d="M256 96l40 116h122l-98 72 38 116-102-74-102 74 38-116-98-72h122Z" fill="${C.gold}"/>`,
    leaf: `<path d="M256 120c90 50 120 140 90 230-50 28-120 36-164-14-40-70 0-170 74-216Z" fill="${C.leaf}"/><path d="M250 150c6 70 8 140 4 220" stroke="${C.lime}" stroke-width="10" stroke-linecap="round"/>`,
    tree: `<rect x="236" y="330" width="40" height="70" rx="6" fill="#8B5A2B"/><path d="M256 120l90 120H166Z" fill="${C.leaf}"/><path d="M256 190l100 130H156Z" fill="${C.lime}"/>`,
    sun: `${[...Array(8)].map((_, i) => {
      const a = i * 45 * Math.PI / 180;
      return `<line x1="${(256 + Math.cos(a) * 92).toFixed(1)}" y1="${(256 + Math.sin(a) * 92).toFixed(1)}" x2="${(256 + Math.cos(a) * 132).toFixed(1)}" y2="${(256 + Math.sin(a) * 132).toFixed(1)}" stroke="${C.orange}" stroke-width="14" stroke-linecap="round"/>`;
    }).join('')}<circle cx="256" cy="256" r="70" fill="${C.gold}"/>`,
    cloud: `<path d="M160 300c0-50 40-86 90-80 12-40 56-66 98-52 46 16 70 62 62 108H168c-8 0-8-8-8 24Z" fill="${C.sky}"/>`,
    gift: `<rect x="150" y="230" width="212" height="150" rx="12" fill="${fill}"/><path d="M150 230l106-52 106 52v24H150Z" fill="${C.rose}"/><rect x="240" y="178" width="32" height="202" fill="${C.gold}"/>`,
    cat: `<circle cx="256" cy="286" r="96" fill="#E8B86D"/><path d="M180 230l-8-86 70 56Z" fill="#E8B86D"/><path d="M332 230l8-86-70 56Z" fill="#E8B86D"/><circle cx="222" cy="280" r="10" fill="${C.ink}"/><circle cx="290" cy="280" r="10" fill="${C.ink}"/>`,
    dog: `<circle cx="256" cy="290" r="96" fill="#C8894A"/><ellipse cx="168" cy="250" rx="32" ry="52" fill="#A86B32"/><ellipse cx="344" cy="250" rx="32" ry="52" fill="#A86B32"/><circle cx="224" cy="278" r="10" fill="${C.ink}"/><circle cx="288" cy="278" r="10" fill="${C.ink}"/>`,
    apple: `<path d="M256 168c50-70 110-10 110 56 0 96-50 160-110 160S146 320 146 224c0-66 60-126 110-56Z" fill="#D64545"/><path d="M256 160c8-28 30-40 52-34-4 24-20 38-52 34Z" fill="${C.leaf}"/>`,
    banner: `<path d="M96 230h320l-28 40 28 40H96l28-40Z" fill="${fill}"/>`,
    check: `<circle cx="256" cy="256" r="120" fill="${C.teal}"/><path d="M186 262l46 46 94-104" stroke="#fff" stroke-width="24" stroke-linecap="round" stroke-linejoin="round"/>`,
    pin: `<path d="M256 120c60 0 104 48 104 108 0 84-104 164-104 164S152 312 152 228c0-60 44-108 104-108Z" fill="${fill}"/><circle cx="256" cy="220" r="36" fill="${C.cream}"/>`,
    ribbon: `<path d="M160 200c0-36 70-50 96 16 26-66 96-52 96 16 0 30-40 50-96 80-56-30-96-50-96-80Z" fill="${fill}"/>`,
    snow: `<g stroke="${C.navy}" stroke-width="14" stroke-linecap="round"><line x1="256" y1="140" x2="256" y2="372"/><line x1="140" y1="256" x2="372" y2="256"/><line x1="170" y1="170" x2="342" y2="342"/><line x1="342" y1="170" x2="170" y2="342"/></g>`,
    butterfly: `<ellipse cx="190" cy="230" rx="80" ry="56" fill="${C.rose}"/><ellipse cx="322" cy="230" rx="80" ry="56" fill="${fill}"/><rect x="248" y="190" width="16" height="140" rx="8" fill="${C.ink}"/>`,
  };
  return svgWrap(m[slug] || m.star);
}

/* ---------- PNG raster ---------- */
function crc32(buf) {
  let c = ~0;
  for (let i = 0; i < buf.length; i++) {
    c ^= buf[i];
    for (let k = 0; k < 8; k++) c = (c >>> 1) ^ (0xedb88320 & -(c & 1));
  }
  return ~c >>> 0;
}
function chunk(type, data) {
  const len = Buffer.alloc(4);
  len.writeUInt32BE(data.length);
  const t = Buffer.from(type);
  const crc = Buffer.alloc(4);
  crc.writeUInt32BE(crc32(Buffer.concat([t, data])));
  return Buffer.concat([len, t, data, crc]);
}
function encodePng(rgba, w, h) {
  const raw = Buffer.alloc((w * 4 + 1) * h);
  for (let y = 0; y < h; y++) {
    raw[y * (w * 4 + 1)] = 0;
    rgba.copy(raw, y * (w * 4 + 1) + 1, y * w * 4, (y + 1) * w * 4);
  }
  const ihdr = Buffer.alloc(13);
  ihdr.writeUInt32BE(w, 0);
  ihdr.writeUInt32BE(h, 4);
  ihdr[8] = 8;
  ihdr[9] = 6;
  const sig = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);
  return Buffer.concat([
    sig,
    chunk('IHDR', ihdr),
    chunk('IDAT', zlib.deflateSync(raw)),
    chunk('IEND', Buffer.alloc(0)),
  ]);
}

function hexToRgba(hex, a = 255) {
  const h = hex.replace('#', '');
  return [parseInt(h.slice(0, 2), 16), parseInt(h.slice(2, 4), 16), parseInt(h.slice(4, 6), 16), a];
}
function createCanvas() {
  return Buffer.alloc(SIZE * SIZE * 4);
}
function setPx(buf, x, y, rgba) {
  x = x | 0;
  y = y | 0;
  if (x < 0 || y < 0 || x >= SIZE || y >= SIZE) return;
  const i = (y * SIZE + x) * 4;
  const sa = rgba[3] / 255;
  if (sa >= 1) {
    buf[i] = rgba[0]; buf[i + 1] = rgba[1]; buf[i + 2] = rgba[2]; buf[i + 3] = 255;
    return;
  }
  const da = buf[i + 3] / 255;
  const outA = sa + da * (1 - sa);
  if (outA <= 0) return;
  buf[i] = Math.round((rgba[0] * sa + buf[i] * da * (1 - sa)) / outA);
  buf[i + 1] = Math.round((rgba[1] * sa + buf[i + 1] * da * (1 - sa)) / outA);
  buf[i + 2] = Math.round((rgba[2] * sa + buf[i + 2] * da * (1 - sa)) / outA);
  buf[i + 3] = Math.round(outA * 255);
}
function fillCircle(buf, cx, cy, r, rgba) {
  const rr = r * r;
  const x0 = Math.max(0, Math.floor(cx - r));
  const x1 = Math.min(SIZE - 1, Math.ceil(cx + r));
  const y0 = Math.max(0, Math.floor(cy - r));
  const y1 = Math.min(SIZE - 1, Math.ceil(cy + r));
  for (let y = y0; y <= y1; y++) {
    for (let x = x0; x <= x1; x++) {
      if ((x - cx) * (x - cx) + (y - cy) * (y - cy) <= rr) setPx(buf, x, y, rgba);
    }
  }
}
function fillPoly(buf, pts, rgba) {
  if (pts.length < 3) return;
  let minY = SIZE, maxY = 0;
  for (const p of pts) { minY = Math.min(minY, p[1]); maxY = Math.max(maxY, p[1]); }
  minY = Math.max(0, Math.floor(minY));
  maxY = Math.min(SIZE - 1, Math.ceil(maxY));
  for (let y = minY; y <= maxY; y++) {
    const xs = [];
    for (let i = 0, n = pts.length; i < n; i++) {
      const [x1, y1] = pts[i];
      const [x2, y2] = pts[(i + 1) % n];
      if ((y1 <= y && y2 > y) || (y2 <= y && y1 > y)) {
        xs.push(x1 + ((y - y1) * (x2 - x1)) / (y2 - y1));
      }
    }
    xs.sort((a, b) => a - b);
    for (let i = 0; i < xs.length; i += 2) {
      const a = Math.max(0, Math.floor(xs[i]));
      const b = Math.min(SIZE - 1, Math.ceil(xs[i + 1] || xs[i]));
      for (let x = a; x <= b; x++) setPx(buf, x, y, rgba);
    }
  }
}
function fillRect(buf, x, y, w, h, rgba, r = 0) {
  if (r <= 0) {
    for (let yy = y; yy < y + h; yy++) for (let xx = x; xx < x + w; xx++) setPx(buf, xx, yy, rgba);
    return;
  }
  fillRect(buf, x + r, y, w - 2 * r, h, rgba);
  fillRect(buf, x, y + r, w, h - 2 * r, rgba);
  fillCircle(buf, x + r, y + r, r, rgba);
  fillCircle(buf, x + w - r, y + r, r, rgba);
  fillCircle(buf, x + r, y + h - r, r, rgba);
  fillCircle(buf, x + w - r, y + h - r, r, rgba);
}
function starPts(cx, cy, r, n = 5, inner = 0.42) {
  const pts = [];
  for (let i = 0; i < n * 2; i++) {
    const a = -Math.PI / 2 + (i * Math.PI) / n;
    const rad = i % 2 === 0 ? r : r * inner;
    pts.push([cx + rad * Math.cos(a), cy + rad * Math.sin(a)]);
  }
  return pts;
}
function heartPts(cx, cy, s) {
  const pts = [];
  for (let t = 0; t < 360; t += 4) {
    const a = (t * Math.PI) / 180;
    const x = 16 * Math.sin(a) ** 3;
    const y = 13 * Math.cos(a) - 5 * Math.cos(2 * a) - 2 * Math.cos(3 * a) - Math.cos(4 * a);
    pts.push([cx + (x * s) / 16, cy - (y * s) / 16]);
  }
  return pts;
}

function paintPng(slug) {
  const buf = createCanvas();
  const burgundy = hexToRgba(C.burgundy);
  const rose = hexToRgba(C.rose);
  const gold = hexToRgba(C.gold);
  const teal = hexToRgba(C.teal);
  const leaf = hexToRgba(C.leaf);
  const lime = hexToRgba(C.lime);
  const navy = hexToRgba(C.navy);
  const sky = hexToRgba(C.sky);
  const orange = hexToRgba(C.orange);
  const cream = hexToRgba(C.cream);
  const ink = hexToRgba(C.ink);
  const white = hexToRgba(C.white);
  const blush = hexToRgba(C.blush);

  // white sticker outline first for PNG look
  const outline = (fn) => {
    const tmp = createCanvas();
    fn(tmp);
    for (let y = 0; y < SIZE; y++) {
      for (let x = 0; x < SIZE; x++) {
        const i = (y * SIZE + x) * 4;
        if (tmp[i + 3] < 20) continue;
        for (let oy = -8; oy <= 8; oy++) {
          for (let ox = -8; ox <= 8; ox++) {
            if (ox * ox + oy * oy <= 72) setPx(buf, x + ox, y + oy, white);
          }
        }
      }
    }
    for (let i = 0; i < tmp.length; i += 4) {
      if (tmp[i + 3] > 10) setPx(buf, (i / 4) % SIZE, Math.floor(i / 4 / SIZE), [tmp[i], tmp[i + 1], tmp[i + 2], tmp[i + 3]]);
    }
  };

  const draw = (b) => {
    if (slug === 'coffee') {
      fillCircle(b, 256, 430, 70, blush);
      fillRect(b, 170, 170, 172, 230, burgundy, 24);
      fillRect(b, 188, 170, 136, 28, rose, 6);
      fillRect(b, 342, 210, 70, 100, gold, 30);
    } else if (slug === 'flower') {
      for (let i = 0; i < 6; i++) {
        const a = (i * 60 * Math.PI) / 180;
        fillCircle(b, 256 + Math.cos(a) * 88, 256 + Math.sin(a) * 88, 52, rose);
      }
      fillCircle(b, 256, 256, 48, gold);
      fillCircle(b, 256, 256, 20, cream);
    } else if (slug === 'heart') {
      fillPoly(b, heartPts(256, 250, 150), burgundy);
    } else if (slug === 'frame') {
      fillRect(b, 70, 70, 372, 372, gold, 24);
      fillRect(b, 108, 108, 296, 296, cream, 12);
      fillRect(b, 130, 130, 252, 252, burgundy, 8);
      fillRect(b, 150, 150, 212, 212, cream, 4);
    } else if (slug === 'arrow') {
      fillRect(b, 80, 232, 250, 48, navy, 20);
      fillPoly(b, [[290, 150], [450, 256], [290, 362]], teal);
    } else if (slug === 'star') {
      fillPoly(b, starPts(256, 256, 180), gold);
      fillPoly(b, starPts(256, 256, 80), cream);
    } else if (slug === 'leaf') {
      fillCircle(b, 250, 250, 150, leaf);
      fillRect(b, 246, 90, 12, 300, lime, 6);
    } else if (slug === 'tree') {
      fillRect(b, 230, 340, 52, 100, hexToRgba('#8B5A2B'), 8);
      fillPoly(b, [[256, 70], [386, 230], [126, 230]], leaf);
      fillPoly(b, [[256, 150], [396, 340], [116, 340]], lime);
    } else if (slug === 'sun') {
      for (let i = 0; i < 12; i++) {
        const a = (i * 30 * Math.PI) / 180;
        fillRect(b, 256 + Math.cos(a) * 130 - 8, 256 + Math.sin(a) * 130 - 8, 16, 50, orange, 8);
      }
      fillCircle(b, 256, 256, 92, gold);
      fillCircle(b, 256, 256, 68, hexToRgba('#F6D56B'));
    } else if (slug === 'cloud') {
      fillCircle(b, 190, 270, 80, sky);
      fillCircle(b, 300, 250, 100, sky);
      fillCircle(b, 370, 290, 70, navy);
      fillCircle(b, 256, 310, 110, sky);
    } else if (slug === 'gift') {
      fillRect(b, 120, 210, 272, 200, burgundy, 16);
      fillPoly(b, [[120, 210], [256, 140], [392, 210], [392, 246], [120, 246]], rose);
      fillRect(b, 236, 140, 40, 270, gold, 4);
    } else if (slug === 'cat') {
      fillCircle(b, 256, 300, 120, hexToRgba('#F0C27A'));
      fillPoly(b, [[150, 210], [130, 100], [220, 190]], hexToRgba('#F0C27A'));
      fillPoly(b, [[362, 210], [382, 100], [292, 190]], hexToRgba('#F0C27A'));
      fillCircle(b, 210, 286, 16, ink);
      fillCircle(b, 302, 286, 16, ink);
      fillCircle(b, 256, 322, 12, rose);
    } else if (slug === 'dog') {
      fillCircle(b, 256, 310, 118, hexToRgba('#C8894A'));
      fillCircle(b, 150, 250, 48, hexToRgba('#A86B32'));
      fillCircle(b, 362, 250, 48, hexToRgba('#A86B32'));
      fillCircle(b, 214, 286, 14, ink);
      fillCircle(b, 298, 286, 14, ink);
      fillCircle(b, 256, 328, 16, ink);
    } else if (slug === 'apple') {
      fillCircle(b, 256, 270, 140, hexToRgba('#D64545'));
      fillCircle(b, 300, 160, 36, leaf);
      fillCircle(b, 200, 220, 16, blush);
    } else if (slug === 'banner') {
      fillPoly(b, [[70, 230], [442, 230], [406, 280], [442, 330], [70, 330], [106, 280]], burgundy);
      fillRect(b, 150, 252, 212, 36, gold, 8);
    } else if (slug === 'check') {
      fillCircle(b, 256, 256, 170, teal);
      fillPoly(b, [[160, 262], [222, 324], [352, 184], [330, 164], [222, 286], [180, 244]], white);
    } else if (slug === 'pin') {
      fillCircle(b, 256, 200, 130, burgundy);
      fillPoly(b, [[126, 230], [256, 450], [386, 230]], burgundy);
      fillCircle(b, 256, 214, 58, cream);
      fillCircle(b, 256, 214, 28, rose);
    } else if (slug === 'ribbon') {
      fillCircle(b, 190, 200, 80, rose);
      fillCircle(b, 322, 200, 80, burgundy);
      fillPoly(b, [[200, 300], [256, 430], [312, 300], [256, 336]], gold);
    } else if (slug === 'snow') {
      fillRect(b, 248, 80, 16, 352, sky, 8);
      fillRect(b, 80, 248, 352, 16, sky, 8);
      fillCircle(b, 256, 256, 22, navy);
      for (let i = 0; i < 6; i++) {
        const a = (i * 60 * Math.PI) / 180;
        fillCircle(b, 256 + Math.cos(a) * 150, 256 + Math.sin(a) * 150, 14, navy);
      }
    } else if (slug === 'butterfly') {
      fillCircle(b, 170, 200, 90, rose);
      fillCircle(b, 342, 200, 90, burgundy);
      fillCircle(b, 180, 320, 70, gold);
      fillCircle(b, 332, 320, 70, gold);
      fillRect(b, 246, 150, 20, 230, ink, 10);
    }
  };

  outline(draw);
  return encodePng(buf, SIZE, SIZE);
}

function main() {
  fs.mkdirSync(OUT, { recursive: true });
  const items = [];
  let sort = 1;
  for (const [kind, slugCat, ext, builder] of [
    ['vector', 'vectors', 'svg', (it) => vectorSvg(it.slug)],
    ['png', 'pngs', 'png', null],
    ['svg', 'svgs', 'svg', (it) => iconSvg(it.slug)],
  ]) {
    ITEMS.forEach((it, i) => {
      const file = `vz_${kind}_${String(i + 1).padStart(2, '0')}_${it.slug}.${ext}`;
      const full = path.join(OUT, file);
      if (ext === 'png') fs.writeFileSync(full, paintPng(it.slug));
      else fs.writeFileSync(full, builder(it), 'utf8');
      items.push({
        title: `${it.title} · ${kind.toUpperCase()}`,
        category_slug: slugCat,
        image_path: `/assets/cliparts/${file}`,
        hashtags: `${it.tags} #${kind} #vecteezy스타일 #클립아트 #${it.ko}`,
        description: `Vecteezy 인기 테마를 참고한 원본 ${it.title} 클립아트 (${kind.toUpperCase()})`,
        sort_order: sort++,
      });
    });
  }
  fs.writeFileSync(MANIFEST, JSON.stringify({ count: items.length, items }, null, 2));
  console.log(`wrote ${items.length} cliparts -> ${OUT}`);
  console.log(`manifest ${MANIFEST}`);
}

main();
