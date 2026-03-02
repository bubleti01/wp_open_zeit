(function ($) {
    'use strict';

    function reindexRows($container, prefix) {
        $container.find('.igw-interval-row').each(function (index) {
            $(this).find('input[type="time"]').each(function () {
                var name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/\[(\d+)\](\[(start|end)\])$/, '[' + index + ']$2'));
            });
        });
    }

    $(document).on('click', '.igw-add-interval', function () {
        var day = $(this).data('day');
        var $container = $('.igw-intervals[data-day="' + day + '"]');
        var index = $container.find('.igw-interval-row').length;
        var base = day === 'exception' ? 'intervals' : 'igw_wp_open_zeit_data[weekly][' + day + '][intervals]';

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
})(jQuery);
