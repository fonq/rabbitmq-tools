$(document).ready(function(){
    function multifield_input(prefix, suffix, type) {
        if (type === 'hidden' ) {
            return '<input type="hidden" id="' + prefix + '_mf' + suffix +
                '" name="' + prefix + '_mf' + suffix + '" value="string"/>';
        }
        else if (type === 'text' ) {
            return '<input type="text" id="' + prefix + '_mf' + suffix +
                '" name="' + prefix + '_mf' + suffix + '" value=""/>';
        }
        else if (type === 'select' ) {
            return '<select id="' + prefix + '_mf' + suffix + '" name="' + prefix +
                '_mf' + suffix + '">' +
                '<option value="string">String</option>' +
                '<option value="number">Number</option>' +
                '<option value="boolean">Boolean</option>' +
                '<option value="list">List</option>' +
                '</select>';
        }
    }
    function update_multifield(multifield, dict) {
        let largest_id = 0;
        let empty_found = false;
        let name = multifield.attr('id');
        let type_inputs = $('#' + name + ' *[name$="_mftype"]');
        type_inputs.each(function(index) {
            let re = new RegExp(name + '_([0-9]*)_mftype');
            let match = $(this).attr('name').match(re);
            if (!match) return;
            let id = parseInt(match[1]);
            largest_id = Math.max(id, largest_id);
            let prefix = name + '_' + id;
            let type = $(this).val();
            let input = $('#' + prefix + '_mfvalue');
            if (type === 'list') {
                if (input.size() === 1) {
                    input.replaceWith('<div class="multifield-sub" id="' + prefix +
                        '"></div>');
                }
                update_multifield($('#' + prefix), false);
            }
            else {
                if (input.size() === 1) {
                    let key = dict ? $('#' + prefix + '_mfkey').val() : '';
                    let value = input.val();
                    if (key === '' && value === '') {
                        if (index === type_inputs.length - 1) {
                            empty_found = true;
                        }
                        else {
                            $(this).parents('.mf').first().remove();
                        }
                    }
                }
                else {
                    $('#' + prefix).replaceWith(multifield_input(prefix, 'value',
                        'text'));
                }
            }
        });
        if (!empty_found) {
            let prefix = name + '_' + (largest_id + 1);
            let t = multifield.hasClass('string-only') ? 'hidden' : 'select';
            let val_type = multifield_input(prefix, 'value', 'text') + ' ' +
                multifield_input(prefix, 'type', t);

            if (dict) {
                multifield.append('<table class="mf"><tr><td>' +
                    multifield_input(prefix, 'key', 'text') +
                    '</td><td class="equals"> = </td><td>' +
                    val_type + '</td></tr></table>');
            }
            else {
                multifield.append('<div class="mf">' + val_type + '</div>');
            }
        }
    }
    function update_multifields() {
        $('div.multifield').each(function(index) {
            update_multifield($(this), true);
        });
    }
    $('#generate_messages_button').click(function (e) {
        e.preventDefault();
        $('#fld_do').val('PublishMessages');
        $('#generate_messages_form').trigger('submit');
    });
    $('#move_messases_btn').click(function (e) {
        e.preventDefault();
        $('#fld_do').val('MoveMessages');
        $('#move_message_form').trigger('submit');
    });
    $('.autosubmit').change(function(e){
        $(this).closest('form').trigger('submit');

    });
    $('#close_dialog').click(function (e) {
        e.preventDefault();
        $(this).parent().remove();
    });
    $('.form-popup-warn span').click(function (e) {
        alert('close');
        $('.form-popup-warn').remove();
    });

    $('.argument-link').click(function() {
        let field = $(this).attr('field');
        let row = $('#' + field).find('.mf tr').last();

        let key = row.find('input').first();
        let value = row.find('input').last();
        let type = row.find('select').last();
        key.val($(this).attr('key'));
        value.val($(this).attr('value'));
        type.val($(this).attr('type'));
        update_multifields();
    });


    $('.delete_button, .requeue_button, .dead_letter_button').click(function (e)
    {
        e.preventDefault();
        let iScrollTop = $(document).scrollTop();
        let formId = '#' + $(this).data('forform');
        let formElement = $(formId);
        let _do = '';

        if($(this).hasClass('delete_button'))
        {
            _do = 'DeleteMessage';
        }
        else if($(this).hasClass('requeue_button'))
        {
            _do = 'Requeue';
        }
        else if($(this).hasClass('dead_letter_button'))
        {
            _do = 'DeadLetter';
        }
        else
        {
            alert('That button is not supported yet.');
        }

        $('.fld_do', formElement).val(_do);

        $('.fld_scrollPos', formElement).val(iScrollTop);
        formElement.trigger('submit');
    });

    let scrollToField = $('#fld_scroll_to');

    if(scrollToField.length === 1)
    {
        $(document).scrollTop(scrollToField.val());
    }

    setTimeout(function () {
        $('.autohide').fadeOut(200);
    }, 2000)

});
