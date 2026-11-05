INSERT INTO faq_categories (name, slug, sort_order, is_active, created_at, updated_at)
SELECT '출력', 'print', 35, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM faq_categories WHERE slug = 'print');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '만든 라벨은 어떻게 출력하나요?',
'<p>편집기 오른쪽 위의 <b>미리보기/프린트</b>를 누르면 「편집기에서 출력」 창이 열립니다.</p><p>미리보기로 배치를 확인한 뒤 <b>출력</b>을 누르고, 프린터 인쇄 창에서 <b>배율 100%(실제 크기)</b>, <b>여백 없음</b>을 선택해 인쇄하세요.</p>',
10, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '만든 라벨은 어떻게 출력하나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '인쇄하면 라벨 칸과 위치가 어긋나요',
'<p>먼저 프린터 인쇄 창에서 아래 세 가지를 확인해 주세요.</p><ul><li>배율: <b>100% / 실제 크기</b> (「페이지에 맞춤」은 끄기)</li><li>여백: <b>없음</b>, 머리글·바닥글 끄기</li><li>용지 크기: <b>A4</b> (Letter로 되어 있으면 위치가 밀립니다)</li></ul><p>그래도 전체가 한쪽으로 밀리면 출력 창의 <b>인쇄 위치 조정 (mm)</b>에서 화살표로 1mm씩, 또는 칸에 숫자를 넣어 0.1mm 단위로 옮긴 뒤 다시 출력해 보세요.</p>',
20, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '인쇄하면 라벨 칸과 위치가 어긋나요');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '출력물이 전체적으로 작게(또는 크게) 나와요',
'<p>인쇄 창의 배율이 「페이지에 맞춤」이나 100%가 아닌 값으로 되어 있을 때 생깁니다.</p><p>배율을 <b>100%(실제 크기)</b>로, 여백을 <b>없음</b>으로 바꾸면 라벨지 규격 그대로 1:1로 인쇄됩니다. 브라우저나 프린터를 바꾸면 이 설정이 다시 돌아갈 수 있으니 인쇄할 때마다 확인해 주세요.</p>',
30, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '출력물이 전체적으로 작게(또는 크게) 나와요');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '위쪽은 맞는데 아래로 갈수록 조금씩 밀려요',
'<p>프린터는 종이를 끌어 보내면서 길이가 0.3~0.5% 정도 늘거나 줄 수 있습니다. 고장이 아니라 기계 특성이라, 출력 창의 <b>인쇄 배율 보정</b>으로 맞출 수 있습니다.</p><ol><li><b>칼선 인쇄(테스트용)</b>를 켜고 일반 A4에 한 장 출력합니다.</li><li>첫 칼선부터 마지막 칼선까지 가로·세로 길이를 자로 잽니다.</li><li>잰 값을 「가로 실측」「세로 실측」에 넣습니다.</li></ol><p>보정값은 지금 쓰는 브라우저에만 저장되고 프린터로 보낼 때만 적용됩니다. 처음으로 돌리려면 <b>보정 지우기</b>를 누르세요.</p>',
40, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '위쪽은 맞는데 아래로 갈수록 조금씩 밀려요');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '라벨지를 버리지 않고 위치를 미리 확인하는 방법이 있나요?',
'<p>출력 창 아래의 <b>칼선 인쇄(테스트용)</b>를 켜고 일반 A4 용지에 먼저 출력해 보세요. 라벨 외곽선이 함께 인쇄됩니다.</p><p>출력한 종이를 라벨지와 겹쳐 빛에 비춰 보면 위치가 맞는지 바로 알 수 있습니다. 위치를 맞춘 뒤 실제 라벨지에 인쇄할 때는 칼선 인쇄를 <b>꺼 주세요</b>. 미리보기의 빨간 칼선은 화면 확인용이라 인쇄되지 않습니다.</p>',
50, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '라벨지를 버리지 않고 위치를 미리 확인하는 방법이 있나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '여러 장 중 일부 페이지만 출력할 수 있나요?',
'<p>출력 창의 <b>출력</b> 버튼은 작업의 모든 페이지를 보냅니다. 제목줄의 <b>총 인쇄페이지수</b>로 몇 장이 나가는지 먼저 확인해 주세요. 창 안의 페이지 선택은 미리보기용입니다.</p><p>일부만 인쇄하려면 프린터 인쇄 창의 <b>페이지 범위</b>에 원하는 페이지(예: 2-3)를 입력하세요.</p>',
60, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '여러 장 중 일부 페이지만 출력할 수 있나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, 'PDF나 이미지 파일로 저장할 수 있나요?',
'<p>출력 창의 <b>PDF용 PNG 저장</b>을 누르면 라벨지 규격 그대로(1:1) 이미지 파일로 저장됩니다. 인쇄소에 맡기거나 다른 PC에서 인쇄할 때 쓰세요. 인쇄 배율 보정은 이 파일에는 적용되지 않습니다.</p><p>PDF가 필요하면 <b>출력</b>을 누른 뒤 프린터 대신 「PDF로 저장」을 고르면 됩니다. 파일을 인쇄할 때도 배율 100%, 여백 없음으로 인쇄해 주세요.</p>',
70, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = 'PDF나 이미지 파일로 저장할 수 있나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '라벨지 바탕색이나 디자인이 인쇄되지 않아요',
'<p>색이 있는 라벨지나 디자인이 미리 인쇄된 라벨지를 고르면, 바탕색과 디자인은 종이에 이미 있으므로 인쇄하지 않고 직접 넣은 글자·이미지만 인쇄합니다.</p><p>그래서 일반 흰 종이에 테스트하면 바탕이 보이지 않는 것이 정상입니다. 출력 창 위쪽 안내 문구에서도 확인할 수 있습니다.</p>',
80, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '라벨지 바탕색이나 디자인이 인쇄되지 않아요');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '라벨지는 프린터에 어떻게 넣어야 하나요?',
'<p>인쇄되는 면(라벨 면)의 방향은 프린터마다 다릅니다. 일반 종이 한쪽에 표시를 하고 한 장 출력해 보면 어느 면에 인쇄되는지 알 수 있습니다.</p><p>라벨지는 한 번에 많이 넣으면 밀리거나 걸리기 쉬우니 적은 양씩, 가능하면 수동 급지대에 한 장씩 넣어 주세요. 이미 일부 칸을 떼어 낸 라벨지는 걸림의 원인이 될 수 있습니다.</p>',
90, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '라벨지는 프린터에 어떻게 넣어야 하나요?');

INSERT INTO faqs (category_id, question, answer, sort_order, is_active, created_at, updated_at)
SELECT c.id, '잉크젯과 레이저 프린터 중 어떤 라벨지를 써야 하나요?',
'<p>투명 라벨처럼 프린터 종류에 따라 전용 제품이 나뉘는 라벨지가 있습니다. 맞지 않는 제품을 쓰면 잉크가 번지거나 마르지 않고, 레이저 프린터에서는 열에 의해 변형될 수 있습니다.</p><p>쇼핑몰에서 <b>잉크젯 투명 라벨</b>, <b>레이저 투명 라벨</b>처럼 내 프린터에 맞는 제품을 골라 주세요. 헷갈리면 라비의 <b>용지 추천</b>을 이용해 보세요.</p>',
100, 1, NOW(), NOW()
FROM faq_categories c
WHERE c.slug = 'print' AND NOT EXISTS (SELECT 1 FROM faqs WHERE question = '잉크젯과 레이저 프린터 중 어떤 라벨지를 써야 하나요?');
