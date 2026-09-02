UPDATE ai_example_prompts
SET label = '◆ 식품 문구', updated_at = NOW()
WHERE prompt_text = '식품 원산지·유통기한 라벨에 넣을 문구를 추천해줘.';

UPDATE ai_example_prompts
SET label = '◐ 화장품', updated_at = NOW()
WHERE prompt_text = '화장품 성분표 라벨을 만들고 싶어.';

UPDATE ai_example_prompts
SET label = '▣ 배송주의', updated_at = NOW()
WHERE prompt_text = '배송용 취급주의 라벨 디자인을 알려줘.';

UPDATE ai_example_prompts
SET label = '✿ 잎사귀', updated_at = NOW()
WHERE prompt_text = '로고 옆에 둘 심플한 잎사귀 일러스트를 그려줘.';
