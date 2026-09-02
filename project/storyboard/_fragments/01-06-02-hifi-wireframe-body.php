<?php
/**
 * 마이페이지 — 주문·배송 하이파이 와이어프레임 바디 (01-06-02)
 */
?>
<div class="sb-hifi-mypage">
    <?php $mpActive = 'orders'; include __DIR__ . '/01-06-sub-shell.php'; ?>
        <div class="sb-hifi-mypage__card">
            <div class="sb-hifi-mypage__section-head">
                <h3>주문 내역</h3>
            </div>
            <div class="sb-hifi-mypage__filter-tabs sb-wf-zone" data-zone-id="M-02">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-02</span>
                <span class="sb-hifi-mypage__filter-tab is-active">전체</span>
                <span class="sb-hifi-mypage__filter-tab">배송중</span>
                <span class="sb-hifi-mypage__filter-tab">완료</span>
                <span class="sb-hifi-mypage__filter-tab">취소</span>
            </div>
            <div class="sb-hifi-mypage__table-wrap sb-wf-zone" data-zone-id="M-03">
                <span class="sb-wf-zone-label sb-wf-zone-label--purple">M-03</span>
                <table class="sb-hifi-mypage__table">
                    <thead>
                        <tr><th>주문번호</th><th>상품</th><th>금액</th><th>상태</th><th>주문일</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ORD-20250612-01</td>
                            <td>유광 화이트 라벨 · 70×37mm 5매</td>
                            <td>18,000원</td>
                            <td><span class="sb-hifi-mypage__status sb-hifi-mypage__status--ship">배송중</span></td>
                            <td>2025.06.12</td>
                        </tr>
                        <tr>
                            <td>ORD-20250608-04</td>
                            <td>투명 PET 라벨 · Ø50mm 100매</td>
                            <td>32,500원</td>
                            <td><span class="sb-hifi-mypage__status sb-hifi-mypage__status--done">배송완료</span></td>
                            <td>2025.06.08</td>
                        </tr>
                        <tr>
                            <td>ORD-20250601-02</td>
                            <td>바코드 라벨 롤 · 40×30mm 500매</td>
                            <td>54,000원</td>
                            <td><span class="sb-hifi-mypage__status sb-hifi-mypage__status--done">배송완료</span></td>
                            <td>2025.06.01</td>
                        </tr>
                        <tr>
                            <td>ORD-20250522-07</td>
                            <td>크래프트 라벨 · 50×30mm 200매</td>
                            <td>21,000원</td>
                            <td><span class="sb-hifi-mypage__status sb-hifi-mypage__status--cancel">주문취소</span></td>
                            <td>2025.05.22</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sb-hifi-mypage__card sb-hifi-mypage__mini-panel sb-wf-zone" data-zone-id="M-04">
            <span class="sb-wf-zone-label">M-04</span>
            <h4>배송지 관리</h4>
            <div class="sb-hifi-mypage__addr-box">
                <strong>기본 배송지</strong>
                김라벨 · 010-1234-5678<br>
                서울시 강남구 테헤란로 123<br>
                라벨업빌딩 5층
            </div>
            <span class="sb-hifi-mypage__add-btn">＋ 배송지 추가</span>
        </div>
    </div><!-- /.sb-hifi-mypage__content -->
    </div><!-- /.sb-hifi-mypage__center -->
</div>
