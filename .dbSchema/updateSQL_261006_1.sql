-- 261006_1: label_specs.shape 에 '맞춤 도넛' 추가
--
-- 형태가 '맞춤'인 용지는 custom_path_svg 가 칼선 그림이고, 그 안의 닫힌 도형이 편집 칸이 된다.
-- 칼선이 겹쳐 그려진(바깥 외곽 + 안쪽 외곽) 용지를 두 가지로 나눈다.
--   custom       = 맞춤 일반 : 안쪽 칼선 안만 편집 칸. 칼선 사이의 고리는 편집할 수 없다.
--   custom_donut = 맞춤 도넛 : 바깥·안쪽 칼선 사이의 고리가 편집 칸. 안쪽은 오려 낸다.
--
-- 기존 'custom' 행은 그대로 '맞춤 일반'이 되므로 자료를 옮길 필요가 없다.

ALTER TABLE label_specs
  MODIFY shape ENUM('rect','round','custom','custom_donut') NOT NULL DEFAULT 'rect';
