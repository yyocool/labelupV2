<script>
(() => {
  if (window.__lbMobilePh) return;
  window.__lbMobilePh = true;
  const mq = window.matchMedia('(max-width: 768px)');
  const apply = () => {
    document.querySelectorAll('input[data-mobile-placeholder]').forEach((el) => {
      if (!el.dataset.desktopPlaceholder) el.dataset.desktopPlaceholder = el.placeholder;
      el.placeholder = mq.matches ? el.dataset.mobilePlaceholder : el.dataset.desktopPlaceholder;
    });
  };
  apply();
  document.addEventListener('DOMContentLoaded', apply);
  mq.addEventListener ? mq.addEventListener('change', apply) : mq.addListener(apply);
})();
</script>
