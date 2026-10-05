UPDATE faq_categories
SET name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    updated_at = NOW()
WHERE name LIKE '%크레딧%';

UPDATE faqs
SET question = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(question, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    answer = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(answer, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    updated_at = NOW()
WHERE question LIKE '%크레딧%' OR answer LIKE '%크레딧%';

UPDATE event_popups
SET title = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    content = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(content, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    updated_at = NOW()
WHERE title LIKE '%크레딧%' OR content LIKE '%크레딧%';

UPDATE credit_reward_rules
SET name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    updated_at = NOW()
WHERE name LIKE '%크레딧%' OR description LIKE '%크레딧%';

UPDATE purchase_credit_products
SET name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    updated_at = NOW()
WHERE name LIKE '%크레딧%' OR description LIKE '%크레딧%';

UPDATE credit_transactions
SET description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크')
WHERE description LIKE '%크레딧%';

UPDATE user_notifications
SET title = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    body = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(body, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크')
WHERE title LIKE '%크레딧%' OR body LIKE '%크레딧%';

UPDATE legal_documents
SET title = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(title, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    content = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(content, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    updated_at = NOW()
WHERE title LIKE '%크레딧%' OR content LIKE '%크레딧%';

UPDATE ai_credit_costs
SET label = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(label, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크')
WHERE label LIKE '%크레딧%' OR description LIKE '%크레딧%';

UPDATE qr_print_templates
SET name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    objects_json = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(objects_json, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    settings_json = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(settings_json, '“크레딧”이란', '“잉크”란'), '"크레딧"이란', '"잉크"란'), '크레딧으로', '잉크로'), '크레딧과', '잉크와'), '크레딧은', '잉크는'), '크레딧이', '잉크가'), '크레딧을', '잉크를'), '크레딧', '잉크'),
    updated_at = NOW()
WHERE name LIKE '%크레딧%' OR objects_json LIKE '%크레딧%' OR settings_json LIKE '%크레딧%';
