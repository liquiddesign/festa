$.fn.editable.defaults.mode = 'inline';

$(document).ready(function () {

    $('input.selector').change(function () {
        if ($(this).is(":checked"))
            $(this).parents('tr').addClass('bg-warning');
        else {
            $(this).parents('tr').removeClass('bg-warning');
            $('input.selector-all').prop('checked', false);
        }

        $('.selector-sum').html($('input.selector:checked').length);

        if ($('input.selector:checked').length > 0) {
            $('.table-controls').css('display', 'inline-block');
        } else {

            $('.table-controls').css('display', 'none');
        }
    });


    $('input.lang-selector').change(function () {
        var lang = $(this).val();
        $("div[data-lang], tr[data-lang]").addClass('d-none');
        $("div[data-lang='" + lang + "'], tr[data-lang='" + lang + "']").removeClass('d-none');
    });

    $('input.selector-all').change(function () {
        if ($(this).is(":checked")) {
            $('.selector-sum').html($('.selector-total').html());
            $('input.selector').parents('tr').addClass('bg-warning');
            $('input.selector').prop('checked', true);
            $('.table-controls').css('display', 'inline-block');
        } else {
            $('.selector-sum').html(0);
            $('input.selector').parents('tr').removeClass('bg-warning');
            $('input.selector').prop('checked', false);
            $('.table-controls').css('display', 'none');
        }
    });

    $('table tr td.clickable').click(function () {

        var tr = $(this).parent('tr');
        window.location.href = tr.attr('data-href');
    });


    $('.form-editable').editable();
});

(function () {
    Nette.toggle = function (id, visible) {
        var el = $(id);
        if (el) {
            let display = visible ? 'table-row' : 'none';
            el.css('display', display);
        }
    };

})();
