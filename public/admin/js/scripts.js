$(function () {

    $.nette.init();

    $('[data-toggle="tooltip"]').tooltip();

    $('.datepicker').flatpickr({
        locale: "cs",
        enableTime: true,
        time_24hr: true,
        format: "d.m.Y H:i",
        altFormat: "d.m.Y H:i",
        altInput: true
    });

    // jQuery('.datepick').datetimepicker({
    //     timepicker: true,
    //     autoclose: true,
    // });

    lightbox.option({
        'resizeDuration': 300,
        'fadeDuration': 300,
        'imageFadeDuration': 300,
        'albumLabel': '%1/%2'
    });

    /* Zmena umistneni paginace (manualni) */
    $('table.lqd-table').after('<div class="table-controls d-flex justify-content-between align-items-center mb-2"></div><div class="table-pagination"></div> <div class="table-buttons"></div>');
    $('.table-pagination').appendTo('.table-controls');
    $('.table-buttons').appendTo('.table-controls');
    $('input[name="foot[saveall]"]').appendTo('.table-buttons');
    $('input[name="foot[deleteSelected]"]').appendTo('.table-buttons');
    $('.pagination').appendTo('.table-pagination');
    /*********************************************/


    $('a[data-fakeclick]').bind("click", function (ev) {

        ev.preventDefault();
        $($(this).attr('data-fakeclick')).click();
    });


    // $(".js-select2").select2();
    // $(".js-select2-tags").select2({tags: true});

    // $('.inlinebar').sparkline('html', {type: 'bar', barColor: 'red'} );


    $('a.edit-email').click(function () {


        // call ajax handleGetemailinfo	payload
        var uuid = $(this).data('uuid');
        $('#editEmail').modal('show');

        // defined in da template
        $.ajax(getemailinfo + '&emails-uuid=' + uuid).done(function (data) {

            $('input[name=name]').val(data.name);
            $('input[name=email]').val(data.email);
            $('input[name=phone]').val(data.phone);
            $('input[name=note]').val(data.note);
            $('input[name=externalid]').val(data.externalid);

            $('input[name=surname]').val(data.surname);
            $('input[name=birthdate]').val(data.birthdate);
            $('input[name=uuid]').val(data.uuid);
            $('input[name=name_5]').val(data.name_5);
            $('input[name=surname_5]').val(data.surname_5);
            $('select[name=gender]').val(data.gender);


        });

    });

    $('input[name=name]').change(function () {

        var value = $(this).val();

        // defined in da template
        if (value != '' && $('input[name=name_5]').val() == '')
            $.ajax(getname5 + '&emails-value=' + value).done(function (data) {

                $('input[name=name_5]').val(data.value);
            });
    });

    $('select[name=template]').change(function () {
        var templates = $(this).data('templates');
        var template = templates[$(this).val()];
        console.log(template);
        $.each(template, function (index, val) {
            if ($('input[name=' + index + ']').length) {
                $('input[name=' + index + ']').val(val);
            }
        });
    });

    $('input[name=surname]').change(function () {

        var value = $('input[name=surname]').val();
        var gender = $('input[name=gender]').val();

        // defined in da template
        if (value != '' && $('input[name=surname_5]').val() == '')
            $.ajax(getsurname5 + '&emails-value=' + value + '&gender=' + gender).done(function (data) {

                $('input[name=surname_5]').val(data.value);
            });
    });

    $('#frm-step1Form select[name=directory]').change(function () {

        $('#frm-step1Form-tags').empty();

        if ($(this).val())
            $.ajax(url_getalltags + '&uuid=' + $(this).val()).done(function (data) {
                $('#frm-step1Form-tags').append('<option value="-" selected="selected">bez štítku</option>');
                $.each(data.items, function (index, value) {
                    $('#frm-step1Form-tags').append('<option value="' + index + '" selected="selected">' + value + '</option>');
                });
                $('#frm-step1Form-tags').trigger("change");
            });
    });

    $('#frm-step1Form input, select#frm-step1Form-tags').change(function () {

        var values = $('#frm-step1Form').serializeArray();
        values.pop();

        $('#selected-emails').html('<i class="fa fa-spinner fa-pulse fa-spin"></i>');

        // load animation
        $.ajax(url_recalculate_targets + '&' + $.param(values)).done(function (data) {

            $('#selected-emails').html(data.total);

            $("#frm-step1Form input[type=submit]").prop("disabled", parseInt(data.total) == 0);
        });


    });

    $('#insertHtmlIntoTemplate').click(function () {
        $('#iframe').contents().find("html body").html($("#insertHtml textarea[name=html]").val());
    });

    // handle fill URL
    $("input.fillseo, input[name^='name']").blur(function () {
        var lang = $(this).data('lang') ? $(this).data('lang') : '';

        if ($(this).val() != "") {
            // var form = $(this).parents('form');


            // Replace
            if ($("input.seo_url[name=url" + lang +"]").val()) {
                let urlInput = $("input.seo_url[name=url" + lang +"]");
                let inputValue = urlInput.val();

                if (inputValue.indexOf('<title>') >= 0) {
                    urlInput.val(inputValue.replace('<title>', solve_seo_link($(this).val())));
                }
            }

            if ($("input.seo_url[name='page[url" + lang +"]']").val()) {
                let urlInput = $("input.seo_url[name='page[url" + lang +"]']");
                let inputValue = urlInput.val();

                if (inputValue.indexOf('<title>') >= 0) {
                    urlInput.val(inputValue.replace('<title>', solve_seo_link($(this).val())));
                }
            }

            if ($("input.seo_url[name='page[url][" + lang + "]']").val()) {
                let urlInput = $("input.seo_url[name='page[url][" + lang + "]']");
                let inputValue = urlInput.val();

                if (inputValue.indexOf('<title>') >= 0) {
                    urlInput.val(inputValue.replace('<title>', solve_seo_link($(this).val())));
                }
            }


            if (!$("input.seo_url[name=url" + lang +"]").val()) $("input.seo_url[name=url" + lang +"]").val(solve_seo_link($(this).val()));
            if (!$("input.seo_url[name='page[url" + lang +"]']").val()) $("input.seo_url[name='page[url" + lang +"]']").val(solve_seo_link($(this).val()));

            if (!$("input.seo_url[name='page[url" + lang + "]").val()) $("input.seo_url[name='page[url" + lang + "]").val(solve_seo_link($(this).val()));
            if (!$("input.seo_url[name='page[url][" + lang + "]']").val()) $("input.seo_url[name='page[url][" + lang + "]']").val(solve_seo_link($(this).val()));

            if (!$("input.seo_title[name=title" + lang + "]").val()) $("input.seo_title[name=title" + lang + "]").val($(this).val());
            if (!$("input.seo_title[name='page[title][" + lang + "]']").val()) $("input.seo_title[name='page[title][" + lang + "]']").val($(this).val());
        }
    });

    // handle fill URL
    $("input.seo_url").blur(function () {
        $(this).val(solve_seo_link($(this).val()));
    });

    // Sticky rows
    $('.admin-table').stickyRows({rows: ['.admin-head']});

    $(".sortable-body").sortable({
        appendTo:"tbody",
        helper:"clone",
        update: function(ev, ui) {
            let previousItem = ui.item[0].previousElementSibling;
            let nextItem = ui.item[0].nextElementSibling;
            let thisItem = ui.item[0];
            let newValue = null;
            let thisValue = $(thisItem).attr('data-sortableValue');
            let thisUuid = $(thisItem).attr('data-uuid');
            let thisProperty = $(thisItem).attr('data-property');
            let className = $(thisItem).attr('data-model');


            if (!nextItem && previousItem) {
                newValue = $(previousItem).attr('data-sortablevalue');

                $(previousItem).find('input[name^="priority"]').val(thisValue);
                $(thisItem).find('input[name^="priority"]').val(newValue);

                $.when(
                    $.ajax({
                        type: 'POST',
                        url: changeEntityValueLink,
                        data : {
                            'class' : className,
                            'uuid' : thisUuid,
                            'property' : thisProperty,
                            'value' : newValue
                        }
                    }),
                    $.ajax({
                        type: 'POST',
                        url: changeEntityValueLink,
                        data : {
                            'class' : className,
                            'uuid' :  $(previousItem).attr('data-uuid'),
                            'property' : thisProperty,
                            'value' : thisValue
                        }
                    })
                );
            } else if (nextItem && previousItem) {
                newValue = parseInt($(previousItem).attr('data-sortablevalue')) + 1;
                $(thisItem).find('input[name^="priority"]').val(newValue);
                $(nextItem).find('input[name^="priority"]').val(newValue + 1);

                $.when(
                    $.ajax({
                        type: 'POST',
                        url: changeEntityValueLink,
                        data : {
                            'class' : className,
                            'uuid' : thisUuid,
                            'property' : thisProperty,
                            'value' : newValue
                        }
                    }),
                    $.ajax({
                        type: 'POST',
                        url: changeEntityValueLink,
                        data : {
                            'class' : className,
                            'uuid' :  $(nextItem).attr('data-uuid'),
                            'property' : thisProperty,
                            'value' : newValue + 1
                        }
                    })
                );
            } else {
                newValue = parseInt($(nextItem).attr('data-sortablevalue'));
                $(thisItem).find('input[name^="priority"]').val(newValue);
                $(nextItem).find('input[name^="priority"]').val(newValue + 1);

                $.when(
                    $.ajax({
                        type: 'POST',
                        url: changeEntityValueLink,
                        data : {
                            'class' : className,
                            'uuid' : thisUuid,
                            'property' : thisProperty,
                            'value' : newValue
                        }
                    }),
                    $.ajax({
                        type: 'POST',
                        url: changeEntityValueLink,
                        data : {
                            'class' : className,
                            'uuid' :  $(nextItem).attr('data-uuid'),
                            'property' : thisProperty,
                            'value' : newValue + 1
                        }
                    })
                );
            }

        }
    });
});

function solve_seo_link(newstr) {
    newstr = newstr.toLowerCase();
    newstr = translate_chars(newstr, 'ÁČĎÉĚÍŇÓŘŠŤÚŮÝŽ', 'acdeeinorstuuyz');
    newstr = translate_chars(newstr, 'áčďéěíňóřšťúůýž', 'acdeeinorstuuyz');

    //nahradit nepovolene znaky pomlckama
    newstr = newstr.replace(/[^a-z0-9_/-]+/g, '-');
    newstr = newstr.replace(/-+/g, '-');
    newstr = newstr.replace(/^-/g, '');
    newstr = newstr.replace(/-$/g, '');

    return newstr;
}

function translate_chars(str, originals, translations) {
    if (originals.length != translations.length) {
        alert('Originals have not the same length as translations');
    }

    for (var i = 0; i < str.length; i++) {
        for (var j = 0; j < originals.length; j++) {
            if (str.charAt(i) == originals.charAt(j)) {
                str = "".concat(str.substring(0, i), translations.charAt(j), str.substring(i + 1));
                break;
            }
        }
    }

    return str;
}
