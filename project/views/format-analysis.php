<div class="page-header">
    <div class="page-header-row">
        <div>
            <h1>타사 라벨 포맷 분석</h1>
            <p>Formtec 등 타사 라벨 소프트웨어 파일을 파싱하고, 분석 결과를 축적·갱신합니다.</p>
        </div>
        <div class="btn-group">
            <a href="<?= url('format-analysis.php?tab=analyses') ?>" class="btn btn-sm <?= $tab === 'analyses' ? 'btn-primary' : 'btn-secondary' ?>">분석 기록</a>
            <a href="<?= url('format-analysis.php?tab=profiles') ?>" class="btn btn-sm <?= $tab === 'profiles' ? 'btn-primary' : 'btn-secondary' ?>">포맷 프로필</a>
        </div>
    </div>
</div>

<?php if ($tab === 'analyses'): ?>

<div class="fa-layout">
    <section class="card fa-upload-card">
        <div class="card-header"><h3>파일 업로드 · 분석</h3></div>
        <form method="post" enctype="multipart/form-data" class="fa-upload-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload">
            <div class="fa-dropzone">
                <input type="file" name="format_file" id="faFile" accept=".dgz,.dgf,.zip,.xls,.xlsx,.lbl,.nlbl,.btw" required>
                <label for="faFile">
                    <strong>.dgz / .dgf</strong> 등 라벨 포맷 파일을 선택하세요
                    <span>최대 20MB · 이미지 오브젝트는 JPG/PNG로 자동 추출·저장됩니다</span>
                </label>
            </div>
            <div class="form-group">
                <label>분석 메모 (선택)</label>
                <textarea name="analyst_notes" class="form-control" rows="2" placeholder="예: 주소용 라벨 3105 샘플, 가변필드 3열"></textarea>
            </div>
            <label class="fa-check">
                <input type="checkbox" name="merge_profile" value="1" checked>
                분석 결과를 연결된 포맷 프로필에 반영 (지속 업데이트)
            </label>
            <div class="btn-group" style="margin-top:12px">
                <button type="submit" class="btn btn-primary">분석 실행</button>
            </div>
        </form>
    </section>

    <?php if ($detail): ?>
    <section class="card fa-detail-card">
        <div class="card-header">
            <h3>분석 상세 #<?= (int) $detail['id'] ?></h3>
            <div class="btn-group">
                <a href="<?= url('format-analysis.php') ?>" class="btn btn-secondary btn-sm">목록</a>
            </div>
        </div>

        <div class="fa-meta-grid">
            <div><dt>원본 파일</dt><dd><?= e($detail['original_name']) ?></dd></div>
            <div><dt>크기</dt><dd><?= number_format((int) $detail['file_size']) ?> B</dd></div>
            <div><dt>벤더</dt><dd><?= e($detail['detected_vendor'] ? $detail['detected_vendor'] : '—') ?></dd></div>
            <div><dt>포맷</dt><dd><code><?= e($detail['detected_format']) ?></code></dd></div>
            <div><dt>버전</dt><dd><?= e($detail['detected_version'] ? $detail['detected_version'] : '—') ?></dd></div>
            <div><dt>신뢰도</dt><dd><span class="fa-conf"><?= (int) $detail['confidence'] ?>%</span></dd></div>
            <div><dt>SKU</dt><dd><?= e($detail['product_sku'] ? $detail['product_sku'] : '—') ?></dd></div>
            <div><dt>제품명</dt><dd><?= e($detail['product_name'] ? $detail['product_name'] : '—') ?></dd></div>
            <div><dt>용지</dt><dd><?= e($detail['paper'] ? $detail['paper'] : '—') ?></dd></div>
            <div><dt>카테고리</dt><dd><?= e($detail['category'] ? $detail['category'] : '—') ?></dd></div>
            <div><dt>프로필</dt><dd><?= e(!empty($detail['format_name']) ? $detail['format_name'] : '미연결') ?></dd></div>
            <div><dt>해시</dt><dd class="fa-hash"><?= e($detail['file_hash']) ?></dd></div>
        </div>

        <?php
        $extractedImages = array();
        if (!empty($summary['extracted_images']) && is_array($summary['extracted_images'])) {
            // JSON 객체 키 등으로 순서가 깨지지 않게 재인덱스
            $extractedImages = array_values($summary['extracted_images']);
        }
        ?>
        <div class="fa-images-panel">
            <div class="fa-images-panel-head">
                <h4>이미지 오브젝트 <?= !empty($extractedImages) ? '(' . count($extractedImages) . ')' : '' ?></h4>
                <?php if (empty($sourceMissing)): ?>
                <form method="post" style="margin:0">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reextract_images">
                    <input type="hidden" name="analysis_id" value="<?= (int) $detail['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-sm">이미지 재추출 · 저장</button>
                </form>
                <?php endif; ?>
            </div>
            <?php if (!empty($extractedImages)): ?>
            <div class="fa-image-grid">
                <?php foreach ($extractedImages as $img):
                    $imgSrc = FormatAnalysisService::imageSrc((int) $detail['id'], $img);
                ?>
                <figure class="fa-image-card">
                    <?php if ($imgSrc !== ''): ?>
                    <a href="<?= e($imgSrc) ?>" target="_blank" rel="noopener">
                        <img src="<?= e($imgSrc) ?>"
                             alt="extracted <?= e(isset($img['filename']) ? $img['filename'] : 'image') ?>"
                             loading="lazy">
                    </a>
                    <?php else: ?>
                    <div class="fa-image-missing">미리보기 없음</div>
                    <?php endif; ?>
                    <figcaption>
                        <strong><?= e(isset($img['filename']) ? $img['filename'] : 'image') ?></strong>
                        <span>
                            <?= strtoupper(e(isset($img['ext']) ? $img['ext'] : '')) ?> · <?= number_format((int) (isset($img['size']) ? $img['size'] : 0)) ?> B
                            <?php if (!empty($img['offset'])): ?> · offset <?= (int) $img['offset'] ?><?php endif; ?>
                        </span>
                        <?php if (!empty($img['kind']) && $img['kind'] === 'clipart_object'): ?>
                        <span class="fa-img-badge">클립아트 (WMF→PNG)</span>
                        <?php elseif (!empty($img['object_type']) || (!empty($img['kind']) && ($img['kind'] === 'image_object' || $img['kind'] === 'ole_image_object'))): ?>
                        <span class="fa-img-badge"><?= !empty($img['object_type_name']) ? e($img['object_type_name']) : '이미지 오브젝트' ?></span>
                        <?php endif; ?>
                        <?php if (!empty($img['source_entry'])): ?>
                        <span class="fa-sub"><?= e($img['source_entry']) ?></span>
                        <?php endif; ?>
                    </figcaption>
                </figure>
                <?php endforeach; ?>
            </div>
            <?php
            $hasClipartImg = false;
            foreach ($extractedImages as $eimg) {
                if (!empty($eimg['kind']) && $eimg['kind'] === 'clipart_object') {
                    $hasClipartImg = true;
                    break;
                }
            }
            if (!$hasClipartImg):
            ?>
            <p class="fa-sub" style="color:#b45309;margin-top:10px">
                클립아트(WMF)가 추출되지 않았습니다. 원본 .dgz를 다시 첨부하거나
                <strong>이미지 재추출</strong>을 눌러 주세요.
            </p>
            <?php endif; ?>
            <?php elseif (!empty($sourceMissing)): ?>
            <div class="fa-source-missing">
                <p class="fa-sub" style="color:#b45309;margin-bottom:10px">
                    원본 파일이 서버에 없어 클립아트·이미지를 다시 추출할 수 없습니다.
                    같은 파일(<strong><?= e($detail['original_name']) ?></strong>)을 다시 첨부해 주세요.
                </p>
                <form method="post" enctype="multipart/form-data" class="fa-attach-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="attach_source">
                    <input type="hidden" name="analysis_id" value="<?= (int) $detail['id'] ?>">
                    <input type="file" name="source_file" accept=".dgz,.dgf,.zip,.xls,.xlsx,.lbl,.nlbl,.btw" required>
                    <button type="submit" class="btn btn-primary btn-sm">원본 첨부 후 이미지 추출</button>
                </form>
            </div>
            <?php else: ?>
            <p class="fa-sub">이미지 오브젝트가 없거나 아직 추출되지 않았습니다.</p>
            <form method="post" enctype="multipart/form-data" class="fa-attach-form" style="margin-top:8px">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="attach_source">
                <input type="hidden" name="analysis_id" value="<?= (int) $detail['id'] ?>">
                <input type="file" name="source_file" accept=".dgz,.dgf,.zip,.xls,.xlsx,.lbl,.nlbl,.btw" required>
                <button type="submit" class="btn btn-primary btn-sm">원본 다시 첨부 · 이미지 추출</button>
            </form>
            <?php endif; ?>
        </div>

        <?php
        $layout = (!empty($summary['layout']) && is_array($summary['layout'])) ? $summary['layout'] : null;
        $canvasImages = array();
        if (!empty($extractedImages)) {
            foreach ($extractedImages as $ei => $eimg) {
                $src = FormatAnalysisService::imageSrc((int) $detail['id'], $eimg);
                if ($src !== '') {
                    $canvasImages[(int) $ei] = $src;
                }
            }
        }
        // layout image_index 재연결: 클립아트에는 절대 사진 JPEG를 넣지 않음
        if ($layout && !empty($layout['objects']) && !empty($extractedImages)) {
            $clipIdx = null;
            $photoIdx = null;
            $usedIdx = array();
            foreach ($extractedImages as $ei => $eimg) {
                $kind = isset($eimg['kind']) ? $eimg['kind'] : '';
                if ($kind === 'clipart_object' || (!empty($eimg['source_format']) && $eimg['source_format'] === 'wmf')
                    || (!empty($eimg['ext']) && strtolower($eimg['ext']) === 'png' && $kind !== 'image_object')) {
                    if ($clipIdx === null) {
                        $clipIdx = (int) $ei;
                    }
                } elseif ($photoIdx === null) {
                    $photoIdx = (int) $ei;
                }
            }
            // png 이면서 jpeg와 md5/크기가 다르면 클립아트로 간주
            if ($clipIdx === null) {
                foreach ($extractedImages as $ei => $eimg) {
                    $ext = isset($eimg['ext']) ? strtolower($eimg['ext']) : '';
                    if ($ext === 'png' || (!empty($eimg['mime']) && $eimg['mime'] === 'image/png')) {
                        $clipIdx = (int) $ei;
                        break;
                    }
                }
            }
            foreach ($layout['objects'] as &$layObj) {
                if (empty($layObj['kind']) || $layObj['kind'] !== 'image') {
                    continue;
                }
                $isClip = (!empty($layObj['label']) && (strpos($layObj['label'], '클립') !== false || strpos($layObj['label'], 'clip') !== false));
                $idx = isset($layObj['image_index']) ? $layObj['image_index'] : null;

                if ($isClip) {
                    if ($clipIdx !== null) {
                        $layObj['image_index'] = $clipIdx;
                    } else {
                        // 클립아트 추출 실패 시 사진을 넣지 않음 (같은 이미지 2장 방지)
                        $layObj['image_index'] = null;
                    }
                    continue;
                }

                // 사진
                if ($idx !== null && isset($canvasImages[(int) $idx]) && (int) $idx !== $clipIdx) {
                    $usedIdx[(int) $idx] = true;
                    continue;
                }
                if ($photoIdx !== null) {
                    $layObj['image_index'] = $photoIdx;
                } elseif ($idx !== null && (int) $idx === $clipIdx) {
                    $layObj['image_index'] = null;
                }
            }
            unset($layObj);

            // 두 이미지 오브젝트가 같은 index 를 쓰면 한쪽을 클립/빈칸으로 분리
            $seen = array();
            foreach ($layout['objects'] as &$layObj) {
                if (empty($layObj['kind']) || $layObj['kind'] !== 'image') {
                    continue;
                }
                if (!isset($layObj['image_index']) || $layObj['image_index'] === null) {
                    continue;
                }
                $i = (int) $layObj['image_index'];
                if (isset($seen[$i])) {
                    $isClip = (!empty($layObj['label']) && strpos($layObj['label'], '클립') !== false);
                    if ($isClip && $clipIdx !== null && $clipIdx !== $i) {
                        $layObj['image_index'] = $clipIdx;
                    } else {
                        $layObj['image_index'] = null;
                    }
                } else {
                    $seen[$i] = true;
                }
            }
            unset($layObj);
        }
        ?>
        <?php if ($layout): ?>
        <div class="fa-canvas-panel">
            <div class="fa-canvas-panel-head">
                <h4>디자인 캔버스 (Formtec 편집화면)</h4>
                <div class="fa-canvas-legend">
                    <span class="fa-leg fa-leg-field">텍스트 필드</span>
                    <span class="fa-leg fa-leg-table">표</span>
                    <span class="fa-leg fa-leg-shape">도형</span>
                    <span class="fa-leg fa-leg-image">이미지</span>
                    <span class="fa-leg fa-leg-barcode">바코드</span>
                </div>
            </div>
            <p class="fa-sub">
                주소용 라벨 <?= e(isset($layout['sku_hint']) ? $layout['sku_hint'] : '') ?>
                · <?= e($layout['paper']) ?>
                · 라벨 <?= e($layout['label_width_mm']) ?> × <?= e($layout['label_height_mm']) ?> mm
                <?php if (!empty($layout['label_grid'])): ?>
                · <?= (int) $layout['label_grid']['rows'] ?>×<?= (int) $layout['label_grid']['cols'] ?> = <?= (int) $layout['labels_per_sheet'] ?>개
                <?php endif; ?>
                · 오브젝트 <?= (int) $layout['object_count'] ?>개
            </p>
            <div class="fa-canvas-wrap">
                <canvas id="faDesignCanvas" width="900" height="560" aria-label="라벨 디자인 캔버스"></canvas>
            </div>
            <?php if (!empty($layout['excel']['columns'])): ?>
            <div class="fa-data-window">
                <p class="fa-label">데이터 창 <?= !empty($layout['excel']['path']) ? '(' . e($layout['excel']['path']) . ')' : '' ?></p>
                <div class="table-wrap">
                    <table class="fa-table fa-data-table">
                        <thead>
                            <tr>
                                <?php foreach ($layout['excel']['columns'] as $col): ?>
                                <th><?= e($col) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ((isset($layout['excel']['rows']) ? $layout['excel']['rows'] : array()) as $ri => $row): ?>
                            <tr class="<?= $ri === 0 ? 'is-active' : '' ?>">
                                <?php foreach ($layout['excel']['columns'] as $ci => $col): ?>
                                <td><?= e(isset($row[$ci]) ? $row[$ci] : '') ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <div class="fa-canvas-objects">
                <p class="fa-label">오브젝트 목록</p>
                <ul class="fa-obj-list">
                    <?php foreach ($layout['objects'] as $obj): ?>
                    <li>
                        <span class="fa-obj-kind fa-obj-kind-<?= e($obj['kind']) ?>"><?= e($obj['kind']) ?></span>
                        <strong><?= e($obj['label'] !== '' ? $obj['label'] : $obj['id']) ?></strong>
                        <span class="fa-sub">
                            <?= e($obj['x_mm']) ?>,<?= e($obj['y_mm']) ?>mm
                            · <?= e($obj['w_mm']) ?>×<?= e($obj['h_mm']) ?>mm
                            <?php if (!empty($obj['excel_column'])): ?> · Excel:<?= e($obj['excel_column']) ?><?php endif; ?>
                            <?php if (!empty($obj['sample_value'])): ?> · "<?= e($obj['sample_value']) ?>"<?php endif; ?>
                            <?php if (!empty($obj['table_cols'])): ?> · <?= (int) $obj['table_cols'] ?>×<?= (int) $obj['table_rows'] ?><?php endif; ?>
                            <?php if (!empty($obj['barcode_orientation'])): ?> · <?= $obj['barcode_orientation'] === 'vertical' ? '세로형' : '가로형' ?><?php endif; ?>
                            <?php if (!empty($obj['shape_filled']) && !empty($obj['fill_color'])): ?> · 채우기 <?= e($obj['fill_color']) ?><?php endif; ?>
                            <?php if ($obj['kind'] === 'image' && (!isset($obj['image_index']) || $obj['image_index'] === null)): ?> · 이미지 미추출<?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <script>
            (function () {
                var layout = <?= json_encode($layout, JSON_UNESCAPED_UNICODE) ?>;
                var images = <?= json_encode($canvasImages, JSON_UNESCAPED_UNICODE) ?>;
                var canvas = document.getElementById('faDesignCanvas');
                if (!canvas || !layout) return;
                var ctx = canvas.getContext('2d');
                var labelW = layout.label_width_mm || 63.5;
                var labelH = layout.label_height_mm || 38.1;
                var pad = 48;
                var scale = Math.min((canvas.width - pad * 2) / labelW, (canvas.height - pad * 2 - 24) / labelH);

                function mx(mm) { return pad + mm * scale; }
                function my(mm) { return pad + mm * scale; }
                function ms(mm) { return mm * scale; }

                function roundRect(x, y, w, h, r) {
                    r = Math.min(r, w / 2, h / 2);
                    ctx.beginPath();
                    ctx.moveTo(x + r, y);
                    ctx.arcTo(x + w, y, x + w, y + h, r);
                    ctx.arcTo(x + w, y + h, x, y + h, r);
                    ctx.arcTo(x, y + h, x, y, r);
                    ctx.arcTo(x, y, x + w, y, r);
                    ctx.closePath();
                }

                function drawRuler() {
                    ctx.fillStyle = '#cbd5e1';
                    ctx.fillRect(0, 0, canvas.width, pad - 8);
                    ctx.fillRect(0, 0, pad - 8, canvas.height);
                    ctx.fillStyle = '#475569';
                    ctx.font = '10px sans-serif';
                    for (var i = 0; i <= Math.ceil(labelW); i += 5) {
                        var x = mx(i);
                        ctx.beginPath();
                        ctx.moveTo(x, pad - 8);
                        ctx.lineTo(x, pad - (i % 10 === 0 ? 18 : 12));
                        ctx.strokeStyle = '#64748b';
                        ctx.stroke();
                        if (i % 10 === 0) ctx.fillText(String(i), x + 2, 14);
                    }
                    for (var j = 0; j <= Math.ceil(labelH); j += 5) {
                        var y = my(j);
                        ctx.beginPath();
                        ctx.moveTo(pad - 8, y);
                        ctx.lineTo(pad - (j % 10 === 0 ? 18 : 12), y);
                        ctx.stroke();
                        if (j % 10 === 0) ctx.fillText(String(j), 4, y - 2);
                    }
                }

                function drawLabelBg() {
                    ctx.fillStyle = '#94a3b8';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    drawRuler();
                    // shadow
                    ctx.fillStyle = 'rgba(15,23,42,.18)';
                    roundRect(mx(1.2), my(1.5), ms(labelW), ms(labelH), 10);
                    ctx.fill();
                    // label
                    ctx.fillStyle = '#ffffff';
                    ctx.strokeStyle = '#334155';
                    ctx.lineWidth = 1.25;
                    roundRect(mx(0), my(0), ms(labelW), ms(labelH), 10);
                    ctx.fill();
                    ctx.stroke();
                    ctx.fillStyle = '#334155';
                    ctx.font = '12px sans-serif';
                    ctx.fillText(
                        '라벨 ' + labelW.toFixed(2) + ' × ' + labelH.toFixed(2) + ' mm',
                        mx(0),
                        my(labelH) + 22
                    );
                }

                function drawGridTable(obj) {
                    var x = mx(obj.x_mm), y = my(obj.y_mm), w = ms(obj.w_mm), h = ms(obj.h_mm);
                    var cols = obj.table_cols || 3;
                    var rows = obj.table_rows || 3;
                    ctx.strokeStyle = '#0f172a';
                    ctx.lineWidth = 1.5;
                    ctx.strokeRect(x, y, w, h);
                    ctx.beginPath();
                    for (var c = 1; c < cols; c++) {
                        var cx = x + (w * c / cols);
                        ctx.moveTo(cx, y);
                        ctx.lineTo(cx, y + h);
                    }
                    for (var r = 1; r < rows; r++) {
                        var ry = y + (h * r / rows);
                        ctx.moveTo(x, ry);
                        ctx.lineTo(x + w, ry);
                    }
                    ctx.stroke();
                }

                function drawBarcode(obj) {
                    var x = mx(obj.x_mm), y = my(obj.y_mm), w = ms(obj.w_mm), h = ms(obj.h_mm);
                    var value = String(obj.barcode_value || obj.sample_value || '0000000000000');
                    var orient = obj.barcode_orientation || 'horizontal';
                    var patterns = {
                        '0': '11011001100', '1': '11001101100', '2': '11001100110', '3': '10010011000',
                        '4': '10010001100', '5': '10001001100', '6': '10011001000', '7': '10011000100',
                        '8': '10001100100', '9': '11001001000'
                    };
                    var modules = ['11010010000'];
                    for (var i = 0; i < value.length; i++) {
                        modules.push(patterns[value.charAt(i)] || patterns['0']);
                    }
                    modules.push('1100011101011');
                    var bits = modules.join('');

                    function paint(bw, bh) {
                        var textH = Math.max(9, Math.min(12, bh * 0.22));
                        var barH = Math.max(8, bh - textH - 3);
                        var moduleW = bw / bits.length;
                        ctx.fillStyle = '#0f172a';
                        for (var bi = 0; bi < bits.length; bi++) {
                            if (bits.charAt(bi) === '1') {
                                ctx.fillRect(-bw / 2 + bi * moduleW, -bh / 2, Math.max(1, moduleW), barH);
                            }
                        }
                        ctx.font = textH + 'px "Consolas", "Courier New", monospace';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'top';
                        ctx.fillText(value, 0, -bh / 2 + barH + 1);
                    }

                    ctx.save();
                    ctx.translate(x + w / 2, y + h / 2);
                    if (orient === 'vertical') {
                        // 세로형: 90° 회전 (막대 가로)
                        ctx.rotate(-Math.PI / 2);
                        paint(h, w);
                    } else {
                        // 가로형: 막대 세로, 숫자 아래 (Formtec 기본)
                        paint(w, h);
                    }
                    ctx.restore();
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'alphabetic';
                }

                function drawObject(obj, imgMap) {
                    var x = mx(obj.x_mm), y = my(obj.y_mm), w = ms(obj.w_mm), h = ms(obj.h_mm);
                    var kind = obj.kind || 'shape';

                    if (kind === 'image') {
                        var idx = obj.image_index;
                        if (idx != null && imgMap[idx] && imgMap[idx].complete && imgMap[idx].naturalWidth) {
                            var im = imgMap[idx];
                            var ir = Math.min(w / im.width, h / im.height);
                            var iw = im.width * ir, ih = im.height * ir;
                            ctx.drawImage(im, x + (w - iw) / 2, y + (h - ih) / 2, iw, ih);
                        } else {
                            ctx.strokeStyle = '#64748b';
                            ctx.strokeRect(x, y, w, h);
                            ctx.fillStyle = '#64748b';
                            ctx.font = '11px sans-serif';
                            ctx.fillText('IMAGE', x + 6, y + h / 2);
                        }
                        return;
                    }

                    if (kind === 'barcode') {
                        drawBarcode(obj);
                        return;
                    }

                    if (kind === 'table') {
                        drawGridTable(obj);
                        return;
                    }

                    if (kind === 'shape') {
                        var fill = obj.fill_color || '';
                        var stroke = obj.stroke_color || '#0f172a';
                        var filled = !!obj.shape_filled;
                        var lw = obj.line_width ? Number(obj.line_width) : 2;
                        ctx.lineWidth = Math.max(1, lw);
                        ctx.strokeStyle = stroke;
                        ctx.fillStyle = fill || '#FFFFFF';

                        function strokeOrFillShape() {
                            if (filled && fill) {
                                ctx.fill();
                            }
                            ctx.stroke();
                        }

                        if ((obj.shape_type || 'rect') === 'ellipse') {
                            ctx.beginPath();
                            if (typeof ctx.ellipse === 'function') {
                                ctx.ellipse(x + w / 2, y + h / 2, Math.max(1, w / 2), Math.max(1, h / 2), 0, 0, Math.PI * 2);
                            } else {
                                ctx.save();
                                ctx.translate(x + w / 2, y + h / 2);
                                ctx.scale(Math.max(0.01, w / 2), Math.max(0.01, h / 2));
                                ctx.arc(0, 0, 1, 0, Math.PI * 2);
                                ctx.restore();
                            }
                            strokeOrFillShape();
                        } else {
                            if (filled && fill) {
                                ctx.fillRect(x, y, w, h);
                            }
                            ctx.strokeRect(x, y, w, h);
                        }
                        return;
                    }

                    // field / text — Formtec처럼 테두리 없이 텍스트만
                    ctx.fillStyle = '#0f172a';
                    var fs = Math.max(11, Math.min(18, h * 0.72));
                    ctx.font = fs + 'px "Malgun Gothic", "Apple SD Gothic Neo", sans-serif';
                    var text = obj.sample_value || obj.label || obj.excel_column || '';
                    ctx.fillText(String(text), x, y + h * 0.78);
                }

                function render(imgMap) {
                    drawLabelBg();
                    (layout.objects || []).forEach(function (obj) {
                        drawObject(obj, imgMap || {});
                    });
                }

                var pending = 0;
                var imgMap = {};
                Object.keys(images || {}).forEach(function (k) {
                    pending++;
                    var im = new Image();
                    im.onload = im.onerror = function () {
                        pending--;
                        if (pending <= 0) render(imgMap);
                    };
                    im.src = images[k];
                    imgMap[k] = im;
                    imgMap[String(k)] = im;
                    if (!isNaN(Number(k))) {
                        imgMap[Number(k)] = im;
                    }
                });
                if (pending === 0) render(imgMap);
            })();
            </script>
        </div>
        <?php endif; ?>

        <?php if (!empty($summary)): ?>
        <div class="fa-summary">
            <h4>파싱 요약</h4>
            <?php if (!empty($summary['entries']) && is_array($summary['entries'])): ?>
            <p class="fa-label">ZIP 엔트리</p>
            <ul class="fa-list">
                <?php foreach ($summary['entries'] as $ent): ?>
                <li><code><?= e($ent['path']) ?></code> · <?= number_format((int) $ent['size']) ?> B</li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <?php if (!empty($summary['header_fields']) && is_array($summary['header_fields'])): ?>
            <p class="fa-label">헤더 필드</p>
            <table class="fa-table">
                <?php foreach ($summary['header_fields'] as $k => $v): ?>
                <tr><th><?= e($k) ?></th><td><?= e(is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE)) ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>

            <?php if (!empty($summary['fonts'])): ?>
            <p class="fa-label">폰트</p>
            <p><?= e(implode(', ', $summary['fonts'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($summary['dimension_hints_inch']) && is_array($summary['dimension_hints_inch'])): ?>
            <p class="fa-label">치수 힌트 (inch → mm)</p>
            <div class="fa-chips">
                <?php foreach (array_slice($summary['dimension_hints_inch'], 0, 12) as $d): ?>
                <span class="fa-chip"><?= e($d['offset']) ?> · <?= e($d['inch']) ?>in / <?= e($d['mm']) ?>mm</span>
                <?php endforeach; ?>
                <?php if (count($summary['dimension_hints_inch']) > 12): ?>
                <span class="fa-chip fa-chip--more">+<?= count($summary['dimension_hints_inch']) - 12 ?>개</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($summary['data_file']) && is_array($summary['data_file'])): ?>
            <p class="fa-label">가변 데이터 파일</p>
            <p class="fa-break"><code><?= e($summary['data_file']['path']) ?></code> · <?= e($summary['data_file']['type']) ?> · <?= number_format((int) $summary['data_file']['size']) ?> B</p>
            <?php if (!empty($summary['data_file']['candidate_strings'])): ?>
            <div class="fa-chips">
                <?php foreach (array_slice($summary['data_file']['candidate_strings'], 0, 16) as $s): ?>
                <span class="fa-chip"><?= e(strlen($s) > 48 ? substr($s, 0, 48) . '…' : $s) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($summary['data_path'])): ?>
            <p class="fa-label">데이터 경로</p>
            <p class="fa-mono fa-break"><?= e($summary['data_path']) ?></p>
            <?php endif; ?>

            <?php if (!empty($summary['sheet_ref'])): ?>
            <p class="fa-label">시트 참조</p>
            <p><code><?= e($summary['sheet_ref']) ?></code></p>
            <?php endif; ?>

            <?php if (!empty($summary['trailer_code'])): ?>
            <p class="fa-label">트레일러 코드</p>
            <p><code><?= e($summary['trailer_code']) ?></code></p>
            <?php endif; ?>

            <details class="fa-raw">
                <summary>원본 JSON</summary>
                <?php
                $summaryForJson = $summary;
                if (!empty($summaryForJson['extracted_images']) && is_array($summaryForJson['extracted_images'])) {
                    foreach ($summaryForJson['extracted_images'] as &$ji) {
                        if (!empty($ji['data_base64'])) {
                            $ji['data_base64'] = '[base64 ' . strlen($ji['data_base64']) . ' chars]';
                        }
                    }
                    unset($ji);
                }
                ?>
                <pre><?= e(json_encode($summaryForJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
            </details>
        </div>
        <?php endif; ?>

        <form method="post" class="fa-notes-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_notes">
            <input type="hidden" name="analysis_id" value="<?= (int) $detail['id'] ?>">
            <div class="form-group">
                <label>분석 메모</label>
                <textarea name="analyst_notes" class="form-control" rows="3"><?= e(isset($detail['analyst_notes']) ? $detail['analyst_notes'] : '') ?></textarea>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary btn-sm">메모 저장</button>
            </div>
        </form>
        <?php if (!empty($detail['profile_id'])): ?>
        <form method="post" class="fa-notes-form" style="margin-top:0;border-top:none;padding-top:0">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="merge_profile">
            <input type="hidden" name="analysis_id" value="<?= (int) $detail['id'] ?>">
            <button type="submit" class="btn btn-secondary btn-sm">프로필에 반영</button>
        </form>
        <?php endif; ?>
        <form method="post" class="fa-notes-form" onsubmit="return confirm('이 분석 기록을 삭제할까요?');" style="margin-top:8px">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_analysis">
            <input type="hidden" name="analysis_id" value="<?= (int) $detail['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">기록 삭제</button>
        </form>
    </section>
    <?php endif; ?>

    <section class="card">
        <div class="card-header">
            <h3>분석 기록 (<?= count($analyses) ?>)</h3>
            <?php if (!empty($analyses)): ?>
            <div class="btn-group">
                <button type="submit" form="faBulkDelete" class="btn btn-danger btn-sm" onclick="return confirm('선택한 분석 기록을 삭제할까요?');">선택 삭제</button>
            </div>
            <?php endif; ?>
        </div>
        <?php if (empty($analyses)): ?>
        <p class="fa-empty">아직 분석 기록이 없습니다. 위에서 .dgz 파일을 업로드해 보세요.</p>
        <?php else: ?>
        <form method="post" id="faBulkDelete">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_analyses">
            <div class="table-wrap">
                <table class="data-table fa-table-list">
                    <thead>
                        <tr>
                            <th style="width:36px">
                                <input type="checkbox" id="faCheckAll" title="전체 선택" aria-label="전체 선택">
                            </th>
                            <th>#</th>
                            <th>미리보기</th>
                            <th>파일</th>
                            <th>벤더/포맷</th>
                            <th>SKU · 제품</th>
                            <th>신뢰도</th>
                            <th>일시</th>
                            <th style="width:72px">삭제</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analyses as $row):
                            $rowSummary = json_decode(isset($row['summary_json']) ? $row['summary_json'] : '{}', true);
                            $thumb = null;
                            if (!empty($rowSummary['extracted_images'][0]['filename'])) {
                                $thumb = $rowSummary['extracted_images'][0]['filename'];
                            }
                        ?>
                        <tr class="<?= ($detail && (int) $detail['id'] === (int) $row['id']) ? 'is-active' : '' ?>">
                            <td>
                                <input type="checkbox" name="analysis_ids[]" value="<?= (int) $row['id'] ?>" class="fa-row-check">
                            </td>
                            <td><a href="<?= url('format-analysis.php?id=' . (int) $row['id']) ?>"><?= (int) $row['id'] ?></a></td>
                            <td>
                                <?php if ($thumb):
                                    $thumbSrc = FormatAnalysisService::imageSrc((int) $row['id'], $rowSummary['extracted_images'][0]);
                                ?>
                                <a href="<?= url('format-analysis.php?id=' . (int) $row['id']) ?>" class="fa-list-thumb">
                                    <?php if ($thumbSrc !== ''): ?>
                                    <img src="<?= e($thumbSrc) ?>" alt="">
                                    <?php else: ?>
                                    <span>—</span>
                                    <?php endif; ?>
                                </a>
                                <?php else: ?>
                                <span class="fa-list-thumb fa-list-thumb--empty">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= url('format-analysis.php?id=' . (int) $row['id']) ?>"><?= e($row['original_name']) ?></a>
                                <div class="fa-sub"><?= number_format((int) $row['file_size']) ?> B
                                    <?php if (!empty($rowSummary['extracted_images'])): ?>
                                    · 이미지 <?= count($rowSummary['extracted_images']) ?>개
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?= e($row['detected_vendor'] ? $row['detected_vendor'] : '—') ?>
                                <div class="fa-sub"><code><?= e($row['detected_format']) ?></code> <?= e($row['detected_version']) ?></div>
                            </td>
                            <td>
                                <?= e($row['product_sku'] ? $row['product_sku'] : '—') ?>
                                <div class="fa-sub"><?= e($row['product_name']) ?></div>
                            </td>
                            <td><?= (int) $row['confidence'] ?>%</td>
                            <td><?= e(isset($row['created_at']) ? $row['created_at'] : '') ?></td>
                            <td>
                                <button type="submit"
                                        form="faDeleteOne_<?= (int) $row['id'] ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('#<?= (int) $row['id'] ?> 기록을 삭제할까요?');">
                                    삭제
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        <?php foreach ($analyses as $row): ?>
        <form method="post" id="faDeleteOne_<?= (int) $row['id'] ?>" class="fa-hidden-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_analysis">
            <input type="hidden" name="analysis_id" value="<?= (int) $row['id'] ?>">
        </form>
        <?php endforeach; ?>
        <script>
        (function () {
            var all = document.getElementById('faCheckAll');
            if (!all) return;
            all.addEventListener('change', function () {
                document.querySelectorAll('.fa-row-check').forEach(function (el) {
                    el.checked = all.checked;
                });
            });
        })();
        </script>
        <?php endif; ?>
    </section>
</div>

<?php else: /* profiles tab */ ?>

<div class="fa-layout">
    <section class="card">
        <div class="card-header">
            <h3><?= $editProfile ? '포맷 프로필 수정' : '포맷 프로필 등록' ?></h3>
            <?php if ($editProfile): ?>
            <a href="<?= url('format-analysis.php?tab=profiles') ?>" class="btn btn-secondary btn-sm">새로 작성</a>
            <?php endif; ?>
        </div>
        <form method="post" class="fa-profile-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_profile">
            <input type="hidden" name="profile_id" value="<?= $editProfile ? (int) $editProfile['id'] : 0 ?>">
            <div class="fa-form-grid">
                <div class="form-group">
                    <label>벤더</label>
                    <select name="vendor" class="form-control">
                        <?php foreach (FormatAnalysisService::getVendors() as $vk => $vl): ?>
                        <option value="<?= e($vk) ?>" <?= ($editProfile && $editProfile['vendor'] === $vk) ? 'selected' : '' ?>><?= e($vl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>format_key *</label>
                    <input type="text" name="format_key" class="form-control" required
                           value="<?= e($editProfile ? $editProfile['format_key'] : '') ?>"
                           placeholder="formtec_dgz" <?= $editProfile ? 'readonly' : '' ?>>
                </div>
                <div class="form-group">
                    <label>포맷명 *</label>
                    <input type="text" name="format_name" class="form-control" required
                           value="<?= e($editProfile ? $editProfile['format_name'] : '') ?>"
                           placeholder="Formtec Design Pro 패키지 (.dgz)">
                </div>
                <div class="form-group">
                    <label>확장자</label>
                    <input type="text" name="extensions" class="form-control"
                           value="<?= e($editProfile ? $editProfile['extensions'] : '') ?>"
                           placeholder=".dgz, .dgf, .xls">
                </div>
                <div class="form-group">
                    <label>매직 시그니처</label>
                    <input type="text" name="magic_signature" class="form-control"
                           value="<?= e($editProfile ? $editProfile['magic_signature'] : '') ?>">
                </div>
                <div class="form-group">
                    <label>컨테이너</label>
                    <input type="text" name="container_type" class="form-control"
                           value="<?= e($editProfile ? $editProfile['container_type'] : '') ?>"
                           placeholder="zip">
                </div>
                <div class="form-group">
                    <label>상태</label>
                    <select name="status" class="form-control">
                        <?php foreach (array('active' => '활성', 'draft' => '초안', 'archived' => '보관') as $sk => $sl): ?>
                        <option value="<?= e($sk) ?>" <?= ($editProfile && $editProfile['status'] === $sk) ? 'selected' : '' ?>><?= e($sl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>구조 노트</label>
                <textarea name="structure_notes" class="form-control" rows="6"><?= e($editProfile ? $editProfile['structure_notes'] : '') ?></textarea>
            </div>
            <div class="form-group">
                <label>field_schema (JSON)</label>
                <textarea name="field_schema" class="form-control fa-mono-input" rows="8"><?php
                    if ($editProfile && $editProfile['field_schema']) {
                        $js = json_decode($editProfile['field_schema'], true);
                        echo e($js ? json_encode($js, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $editProfile['field_schema']);
                    }
                ?></textarea>
            </div>
            <div class="form-group">
                <label>운영 메모 (지속 업데이트 로그)</label>
                <textarea name="notes" class="form-control" rows="4"><?= e($editProfile ? $editProfile['notes'] : '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?= $editProfile ? '프로필 저장' : '프로필 등록' ?></button>
        </form>
    </section>

    <section class="card">
        <div class="card-header"><h3>등록된 포맷 프로필 (<?= count($profiles) ?>)</h3></div>
        <?php if (empty($profiles)): ?>
        <p class="fa-empty">프로필이 없습니다.</p>
        <?php else: ?>
        <div class="fa-profile-list">
            <?php foreach ($profiles as $p): ?>
            <article class="fa-profile-item">
                <div class="fa-profile-head">
                    <div>
                        <strong><?= e($p['format_name']) ?></strong>
                        <div class="fa-sub"><?= e($p['vendor']) ?> · <code><?= e($p['format_key']) ?></code> · <?= e($p['extensions']) ?></div>
                    </div>
                    <div class="btn-group">
                        <a href="<?= url('format-analysis.php?tab=profiles&edit_profile=' . (int) $p['id']) ?>" class="btn btn-secondary btn-sm">편집</a>
                        <form method="post" onsubmit="return confirm('프로필을 삭제할까요?');" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete_profile">
                            <input type="hidden" name="profile_id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">삭제</button>
                        </form>
                    </div>
                </div>
                <?php if (!empty($p['structure_notes'])): ?>
                <pre class="fa-notes-pre"><?= e($p['structure_notes']) ?></pre>
                <?php endif; ?>
                <?php if (!empty($p['notes'])): ?>
                <p class="fa-sub" style="white-space:pre-wrap;margin-top:8px"><?= e($p['notes']) ?></p>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php endif; ?>

<style>
/* 레이아웃이 긴 문자열에 의해 가로로 밀리지 않도록 */
.main-content { min-width: 0; max-width: 100%; overflow-x: hidden; }
.fa-layout { display: flex; flex-direction: column; gap: 20px; max-width: 100%; overflow-x: hidden; }
.fa-detail-card, .fa-upload-card, .fa-layout > .card { max-width: 100%; overflow-x: hidden; min-width: 0; }
.fa-upload-card .fa-dropzone { position: relative; border: 2px dashed var(--border); border-radius: 12px; padding: 28px 16px; text-align: center; background: var(--bg-subtle); margin-bottom: 14px; }
.fa-dropzone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.fa-dropzone label { display: flex; flex-direction: column; gap: 6px; color: var(--text-secondary); pointer-events: none; }
.fa-dropzone label strong { color: var(--text); font-size: 15px; }
.fa-dropzone label span { font-size: 12px; }
.fa-check { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); }
.fa-meta-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 18px; max-width: 100%; }
.fa-meta-grid > div { min-width: 0; }
.fa-meta-grid dt { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.fa-meta-grid dd { margin: 2px 0 0; font-size: 13px; font-weight: 500; overflow-wrap: anywhere; word-break: break-word; }
.fa-hash { font-family: ui-monospace, monospace; font-size: 11px; color: var(--text-muted); overflow-wrap: anywhere; word-break: break-all; }
.fa-conf { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #dcfce7; color: #166534; font-weight: 700; font-size: 12px; }
.fa-summary { border-top: 1px solid var(--border-light); padding-top: 14px; max-width: 100%; overflow-x: hidden; }
.fa-summary h4 { margin: 0 0 10px; font-size: 14px; }
.fa-label { margin: 12px 0 4px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
.fa-list { margin: 0; padding-left: 18px; font-size: 13px; max-width: 100%; }
.fa-list li { overflow-wrap: anywhere; word-break: break-word; }
.fa-table { width: 100%; max-width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 8px; table-layout: fixed; }
.fa-table th, .fa-table td { border: 1px solid var(--border-light); padding: 6px 10px; text-align: left; vertical-align: top; overflow-wrap: anywhere; word-break: break-word; }
.fa-table th { width: 140px; background: var(--bg-subtle); font-weight: 600; }
.fa-chips { display: flex; flex-wrap: wrap; gap: 6px; max-width: 100%; }
.fa-chip { display: inline-block; max-width: 100%; padding: 4px 8px; border-radius: 8px; background: #f1f5f9; border: 1px solid var(--border-light); font-size: 11px; font-family: ui-monospace, monospace; overflow-wrap: anywhere; word-break: break-all; white-space: normal; }
.fa-chip--more { background: #e2e8f0; color: var(--text-muted); }
.fa-mono, .fa-mono-input { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; }
.fa-break, .fa-break code { overflow-wrap: anywhere; word-break: break-all; max-width: 100%; display: inline-block; }
.fa-raw { margin-top: 14px; max-width: 100%; }
.fa-raw pre { max-height: 320px; overflow: auto; background: #0f172a; color: #e2e8f0; padding: 12px; border-radius: 8px; font-size: 11px; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-all; max-width: 100%; }
.fa-empty { color: var(--text-muted); padding: 16px; }
.fa-sub { font-size: 11px; color: var(--text-muted); margin-top: 2px; overflow-wrap: anywhere; word-break: break-word; }
.fa-table-list { table-layout: auto; }
.fa-table-list tr.is-active { background: var(--primary-light); }
.table-wrap { max-width: 100%; overflow-x: auto; }
.fa-form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
.fa-profile-list { display: flex; flex-direction: column; gap: 14px; }
.fa-profile-item { border: 1px solid var(--border-light); border-radius: 10px; padding: 14px; background: var(--bg-subtle); max-width: 100%; overflow-wrap: anywhere; }
.fa-profile-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; flex-wrap: wrap; }
.fa-notes-pre { white-space: pre-wrap; word-break: break-word; font-size: 12px; background: #fff; border: 1px solid var(--border-light); border-radius: 8px; padding: 10px; margin: 10px 0 0; color: var(--text-secondary); }
.fa-notes-form { border-top: 1px solid var(--border-light); padding-top: 14px; margin-top: 14px; }
.fa-images-panel { border: 1px solid var(--border-light); border-radius: 12px; padding: 14px; background: var(--bg-subtle); margin: 4px 0 18px; max-width: 100%; }
.fa-images-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 10px; }
.fa-images-panel-head h4 { margin: 0; font-size: 14px; }
.fa-image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; margin: 8px 0 4px; max-width: 100%; }
.fa-attach-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.fa-attach-form input[type="file"] { max-width: 100%; }
.fa-source-missing { padding: 4px 0 2px; }
.fa-image-card { margin: 0; border: 1px solid var(--border-light); border-radius: 10px; overflow: hidden; background: #fff; min-width: 0; }
.fa-image-card a { display: block; background: #f1f5f9; min-height: 140px; }
.fa-image-card img { display: block; width: 100%; height: 180px; object-fit: contain; background: repeating-conic-gradient(#e2e8f0 0% 25%, #f8fafc 0% 50%) 50% / 16px 16px; }
.fa-image-missing { display:flex; align-items:center; justify-content:center; min-height:140px; color:var(--text-muted); font-size:12px; background:#f1f5f9; }
.fa-image-card figcaption { padding: 8px 10px 10px; display: flex; flex-direction: column; gap: 2px; }
.fa-image-card figcaption strong { font-size: 12px; }
.fa-image-card figcaption span { font-size: 11px; color: var(--text-muted); overflow-wrap: anywhere; }
.fa-img-badge { display: inline-block !important; width: fit-content; margin-top: 4px; padding: 2px 7px; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: 10px !important; font-weight: 700; }
.fa-list-thumb { display: inline-flex; width: 48px; height: 48px; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-light); background: #f1f5f9; align-items: center; justify-content: center; }
.fa-list-thumb img { width: 100%; height: 100%; object-fit: cover; }
.fa-list-thumb--empty { color: var(--text-muted); font-size: 12px; }
.fa-hidden-form { display: none; }
.fa-canvas-panel { border: 1px solid var(--border-light); border-radius: 12px; padding: 14px; background: var(--bg-subtle); margin: 0 0 18px; max-width: 100%; overflow: hidden; }
.fa-canvas-panel-head { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:6px; }
.fa-canvas-panel-head h4 { margin:0; font-size:14px; }
.fa-canvas-legend { display:flex; flex-wrap:wrap; gap:6px; }
.fa-leg { font-size:11px; padding:2px 8px; border-radius:999px; border:1px solid var(--border-light); background:#fff; }
.fa-leg-field { color:#1d4ed8; background:#dbeafe; border-color:#bfdbfe; }
.fa-leg-image { color:#1e40af; background:#eff6ff; border-color:#bfdbfe; }
.fa-leg-shape { color:#475569; background:#f1f5f9; }
.fa-leg-table { color:#92400e; background:#fef3c7; border-color:#fde68a; }
.fa-leg-barcode { color:#065f46; background:#ecfdf5; border-color:#a7f3d0; }
.fa-canvas-wrap { width:100%; overflow:auto; background:#e2e8f0; border-radius:10px; border:1px solid var(--border-light); }
#faDesignCanvas { display:block; width:100%; max-width:900px; height:auto; margin:0 auto; background:#94a3b8; }
.fa-data-window { margin-top:14px; }
.fa-data-table th { background:#e2e8f0; }
.fa-data-table tr.is-active td { background:#dbeafe; font-weight:600; }
.fa-canvas-objects { margin-top:12px; max-width:100%; }
.fa-obj-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:6px; }
.fa-obj-list li { display:flex; flex-wrap:wrap; gap:8px; align-items:baseline; padding:8px 10px; background:#fff; border:1px solid var(--border-light); border-radius:8px; overflow-wrap:anywhere; }
.fa-obj-kind { font-size:10px; font-weight:700; text-transform:uppercase; padding:2px 6px; border-radius:6px; background:#f1f5f9; }
.fa-obj-kind-field { background:#dbeafe; color:#1d4ed8; }
.fa-obj-kind-text { background:#dcfce7; color:#166534; }
.fa-obj-kind-image { background:#eff6ff; color:#1e40af; }
.fa-obj-kind-shape { background:#e2e8f0; color:#475569; }
.fa-obj-kind-table { background:#fef3c7; color:#92400e; }
.fa-obj-kind-barcode { background:#ecfdf5; color:#065f46; }
</style>
