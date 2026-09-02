document.addEventListener('DOMContentLoaded', function () {
    var calCells = document.querySelectorAll('.schedule-cal-cell');
    calCells.forEach(function (cell) {
        cell.addEventListener('dblclick', function () {
            var dayLink = cell.querySelector('.schedule-cal-day a');
            if (dayLink && dayLink.href) {
                window.location.href = dayLink.href;
            }
        });
    });
});
