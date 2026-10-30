<?php
/** @var array<string, mixed> $product */
/** @var array<string, mixed> $reviewSummary */
/** @var array<string, mixed>|null $myReview */
$reviewDist = is_array($reviewSummary['dist'] ?? null) ? $reviewSummary['dist'] : [];
$reviewAvg = (float) ($reviewSummary['avg'] ?? 0);
$reviewTotal = (int) ($reviewSummary['count'] ?? 0);
$reviewConfig = [
    'productId' => (int) $product['id'],
    'loggedIn' => !empty($authUser),
    'approval' => ($reviewApproval ?? 'auto') === 'manual' ? 'manual' : 'auto',
    'myReview' => $myReview ?? null,
    'counts' => [
        'all' => $reviewTotal,
        'photo' => (int) ($reviewSummary['photo'] ?? 0),
        'text' => (int) ($reviewSummary['text'] ?? 0),
    ],
];
?>
<section class="shop-reviews" id="reviews" aria-label="상품 리뷰">
  <div class="shop-reviews__head">
    <h2>상품 리뷰 <span class="js-review-total"><?= number_format($reviewTotal) ?></span></h2>
    <button type="button" class="shop-reviews__write js-review-write">
      <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
      <span class="js-review-write-label"><?= !empty($myReview) ? '내 리뷰 수정' : '리뷰 쓰기' ?></span>
    </button>
  </div>

  <div class="shop-reviews__summary">
    <div class="shop-reviews__score">
      <strong><?= e(number_format($reviewAvg, 1)) ?></strong>
      <span class="shop-stars shop-stars--lg" style="--rate:<?= e((string) $reviewAvg) ?>" aria-label="평점 <?= e(number_format($reviewAvg, 1)) ?>점"></span>
      <small><?= $reviewTotal > 0 ? number_format($reviewTotal) . '개의 리뷰' : '아직 리뷰가 없습니다' ?></small>
    </div>
    <ul class="shop-reviews__dist" aria-label="별점 분포">
      <?php for ($star = 5; $star >= 1; $star--): ?>
      <?php $cnt = (int) ($reviewDist[$star] ?? 0); $pct = $reviewTotal > 0 ? round($cnt / $reviewTotal * 100) : 0; ?>
      <li>
        <span><?= $star ?>점</span>
        <i><b style="width:<?= (int) $pct ?>%"></b></i>
        <em><?= number_format($cnt) ?></em>
      </li>
      <?php endfor; ?>
    </ul>
  </div>

  <div class="shop-reviews__tabs" role="tablist">
    <button type="button" class="is-active" role="tab" data-review-tab="all">전체 <span><?= number_format($reviewConfig['counts']['all']) ?></span></button>
    <button type="button" role="tab" data-review-tab="photo">포토리뷰 <span><?= number_format($reviewConfig['counts']['photo']) ?></span></button>
    <button type="button" role="tab" data-review-tab="text">일반리뷰 <span><?= number_format($reviewConfig['counts']['text']) ?></span></button>
  </div>

  <ul class="shop-review-list js-review-list" aria-live="polite"></ul>
  <p class="shop-reviews__empty js-review-empty" hidden>등록된 리뷰가 없습니다. 첫 리뷰를 남겨 주세요.</p>
  <button type="button" class="shop-reviews__more js-review-more" hidden>리뷰 더보기</button>
</section>

<div class="shop-review-modal" id="shopReviewModal" hidden>
  <div class="shop-review-modal__backdrop" data-review-close></div>
  <div class="shop-review-modal__panel" role="dialog" aria-modal="true" aria-labelledby="shopReviewModalTitle">
    <div class="shop-review-modal__head">
      <h3 id="shopReviewModalTitle">리뷰 쓰기</h3>
      <button type="button" class="shop-review-modal__x" data-review-close aria-label="닫기">×</button>
    </div>
    <div class="shop-review-modal__body">
      <p class="shop-review-modal__product"><?= e((string) $product['name']) ?></p>
      <div class="shop-review-rate">
        <span class="shop-review-rate__label">별점</span>
        <div class="shop-review-rate__stars js-review-stars" role="radiogroup" aria-label="별점 선택">
          <?php for ($star = 1; $star <= 5; $star++): ?>
          <button type="button" role="radio" aria-checked="false" data-star="<?= $star ?>" aria-label="<?= $star ?>점">★</button>
          <?php endfor; ?>
        </div>
        <em class="js-review-rate-text">별점을 선택해 주세요</em>
      </div>
      <textarea class="js-review-editor" aria-label="리뷰 내용"></textarea>
      <p class="shop-review-modal__hint">사진을 넣으면 <b>포토리뷰</b>, 글만 쓰면 <b>일반리뷰</b>로 등록됩니다. 사진은 최대 10장까지 넣을 수 있습니다.</p>
      <?php if ($reviewConfig['approval'] === 'manual'): ?>
      <p class="shop-review-modal__notice">작성한 리뷰는 관리자 확인 후 노출됩니다.</p>
      <?php endif; ?>
    </div>
    <div class="shop-review-modal__foot">
      <button type="button" class="shop-review-modal__delete js-review-delete" hidden>삭제</button>
      <button type="button" class="shop-review-modal__cancel" data-review-close>취소</button>
      <button type="button" class="shop-review-modal__submit js-review-submit">등록하기</button>
    </div>
  </div>
</div>
<script>window.LABELUP_REVIEWS = <?= json_encode($reviewConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="<?= js('shop-reviews.js') ?>" defer></script>
