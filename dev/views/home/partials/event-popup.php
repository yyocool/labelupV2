<?php
/** @var array<int, array<string, mixed>> $eventPopups */
$eventPopups = $eventPopups ?? [];
if ($eventPopups === []) {
    return;
}
?>
<div id="eventPopupRoot" class="event-popup-root" hidden></div>
<script>
window.LABELUP_EVENT_POPUPS = <?= json_encode(array_values(array_map(static function (array $p): array {
    return [
        'id' => (int) ($p['id'] ?? 0),
        'title' => (string) ($p['title'] ?? ''),
        'image' => (string) ($p['image_src'] ?? ''),
        'link' => (string) ($p['link_url'] ?? ''),
        'content' => (string) ($p['content'] ?? ''),
        'hide_days' => (int) ($p['hide_days'] ?? 1),
    ];
}, $eventPopups)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= js('event-popup.js') ?>"></script>
