-- Google OAuth branding 검수용: 개인정보 처리방침 전문 보강 + 사이트명 정합
UPDATE seo_settings
SET setting_value = 'labelup', updated_at = NOW()
WHERE setting_key = 'seo.org_name';

UPDATE seo_settings
SET setting_value = 'labelup — 라벨업', updated_at = NOW()
WHERE setting_key IN ('seo.site_name', 'seo.default_title');

UPDATE seo_settings
SET setting_value = ' — labelup', updated_at = NOW()
WHERE setting_key = 'seo.title_suffix';

UPDATE legal_documents
SET title = '개인정보 처리방침',
    content = '<h3>1. 총칙</h3>
<p>labelup(라벨업, 이하 \"회사\" 또는 \"labelup\")은 이용자의 개인정보를 중요시하며 「개인정보 보호법」 등 관련 법령을 준수합니다. 본 방침은 labelup이 제공하는 라벨 디자인·인쇄·쇼핑 서비스(웹사이트 https://www.labelup.co.kr , 편집기, 모바일 웹 포함)와 소셜 로그인(Google, Kakao, Naver 등)에 적용됩니다.</p>
<h3>2. 수집하는 개인정보 항목</h3>
<ul>
<li>필수(이메일 가입): 이메일, 비밀번호, 이름</li>
<li>선택: 연락처, 회사/상호, 배송지 정보</li>
<li><strong>Google 로그인</strong>: Google 계정에서 이용자가 동의한 범위의 정보 — 이름(또는 표시 이름), 이메일 주소, Google 고유 식별자(sub). labelup은 Google OAuth 범위로 <code>openid</code>, <code>email</code>, <code>profile</code> 을 요청합니다.</li>
<li>기타 소셜 로그인(카카오·네이버 등): 닉네임, 이메일(제공에 동의한 경우), 소셜 고유식별자</li>
<li>자동 수집: IP 주소, 접속 로그, 쿠키, 기기·브라우저 정보</li>
<li>서비스 이용: 주문·결제·배송 정보, 고객 문의, 디자인/작업 데이터</li>
</ul>
<h3>3. 수집 및 이용 목적</h3>
<ul>
<li>회원 식별, 로그인, 계정 생성·연동 및 서비스 제공</li>
<li>라벨 디자인·템플릿·AI 추천·편집기 기능 제공</li>
<li>주문·결제·배송·고객문의 처리</li>
<li>부정 이용 방지, 보안, 서비스 개선</li>
<li>법령상 의무 이행 및 분쟁 대응</li>
</ul>
<h3>4. Google API에서 받은 정보의 이용 제한</h3>
<p>labelup은 Google API로부터 받은 정보(이름, 이메일, 고유 식별자 등)를 <strong>이용자 인증·계정 연동·서비스 제공 목적에만</strong> 사용합니다. 해당 정보를 광고 목적으로 판매하지 않으며, Google API Services User Data Policy(제한적 사용 요건 포함)를 준수합니다.</p>
<h3>5. 보유 및 이용 기간</h3>
<p>원칙적으로 회원 탈퇴 시까지 보유·이용하며, 관련 법령에 따라 일정 기간 보관할 수 있습니다. Google 등 소셜 로그인으로 가입한 경우에도 동일하게 적용됩니다. 탈퇴 시 소셜 연동 정보도 함께 삭제하거나 분리 보관 후 파기합니다.</p>
<h3>6. 제3자 제공 및 처리 위탁</h3>
<p>회사는 원칙적으로 이용자 동의 없이 개인정보를 외부에 제공하지 않습니다. 결제·배송·클라우드·AI API 등 서비스 운영에 필요한 범위에서 처리 위탁이 있을 수 있으며, 위탁 시 관련 법령에 따라 관리·감독합니다. Google 로그인 과정에서 이용자는 Google의 개인정보처리방침도 함께 적용받을 수 있습니다.</p>
<h3>7. 이용자의 권리</h3>
<p>이용자는 개인정보 열람·정정·삭제·처리정지, 동의 철회를 요청할 수 있습니다. 회원 탈퇴 및 Google 계정 연동 해제는 마이페이지 또는 고객센터를 통해 요청할 수 있습니다.</p>
<h3>8. 개인정보 보호책임자</h3>
<p>개인정보 관련 문의는 사이트 푸터에 게시된 사업자 연락처(이메일·전화)로 접수할 수 있습니다.</p>
<h3>9. 방침 변경</h3>
<p>본 방침이 변경되는 경우 본 페이지(https://www.labelup.co.kr/privacy)를 통해 고지합니다.</p>
<p><em>시행일: 2026년 9월 17일 · 앱 이름: labelup (라벨업)</em></p>',
    version = version + 1,
    updated_at = NOW()
WHERE doc_key = 'privacy';
