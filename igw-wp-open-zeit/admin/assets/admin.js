(function ($) {
    'use strict';

    function reindexRows($container) {
        $container.find('.igw-interval-row').each(function (index) {
            $(this).find('input[type="time"]').each(function () {
                var name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/\[(\d+)\](\[(start|end)\])$/, '[' + index + ']$2'));
            });
        });
    }

    function toMinutes(timeString) {
        var parts = (timeString || '').split(':');
        if (parts.length !== 2) return null;
        var h = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10);
        if (isNaN(h) || isNaN(m)) return null;
        return (h * 60) + m;
    }

    function validateIntervals($form) {
        var isValid = true;

        $form.find('.igw-interval-row').each(function () {
            if (!isValid) return;

            var $row = $(this);
            var $start = $row.find('input[type="time"][name$="[start]"]');
            var $end = $row.find('input[type="time"][name$="[end]"]');

            if ($start.length === 0 || $end.length === 0) {
                return;
            }

            if ($start.prop('disabled') || $end.prop('disabled')) {
                return;
            }

            var startVal = $start.val();
            var endVal = $end.val();

            if (!startVal || !endVal) {
                return;
            }

            var startMinutes = toMinutes(startVal);
            var endMinutes = toMinutes(endVal);

            if (startMinutes === null || endMinutes === null || endMinutes <= startMinutes) {
                alert('Endzeit muss größer als Startzeit sein.');
                isValid = false;
            }
        });

        return isValid;
    }

    $(document).on('click', '.igw-add-interval', function () {
        var day = $(this).data('day');
        var $container = $('.igw-intervals[data-day="' + day + '"]');
        var index = $container.find('.igw-interval-row').length;
        var base = 'igw_wp_open_zeit_data[weekly][' + day + '][intervals]';

        var row = '<div class="igw-interval-row">' +
            '<input type="time" name="' + base + '[' + index + '][start]" /> ' +
            '<input type="time" name="' + base + '[' + index + '][end]" /> ' +
            '<button type="button" class="button igw-remove-interval">Zeitraum löschen</button>' +
            '</div>';

        $container.append(row);
    });

    $(document).on('click', '.igw-remove-interval', function () {
        var $container = $(this).closest('.igw-intervals');
        $(this).closest('.igw-interval-row').remove();
        reindexRows($container);
    });

    $(document).on('change', '.igw-closed-toggle', function () {
        var $scope = $(this).closest('td, form');
        var disabled = $(this).is(':checked');
        $scope.find('.igw-intervals input[type="time"], .igw-add-interval, .igw-remove-interval').prop('disabled', disabled);
    }).trigger('change');

    $(document).on('submit', 'form', function () {
        return validateIntervals($(this));
    });
})(jQuery);
