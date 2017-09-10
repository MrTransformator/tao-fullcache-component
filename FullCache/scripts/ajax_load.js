$(function () {
    var $ajax_load_blocks = $('.ajax-load-insertions'),
        insertions = [];

    if (!$ajax_load_blocks.length) {
        return;
    }

    $ajax_load_blocks.each(function () {
        insertions.push($(this).data('insertion-str'));
    });

    $.ajax({
        url: '/ajax-load-insertions/',
        data: {
            ajax_load_insertions: JSON.stringify(insertions)
        },
        dataType: 'json',
        type: 'post',
        success: function (data) {
            if (!data.length) {
                return;
            }

            $.each(data, function (key, insertion) {
                $ajax_load_blocks.filter('[data-insertion-str="' + insertion.name + '"]').replaceWith(insertion.value);
            });
        }
    });
});