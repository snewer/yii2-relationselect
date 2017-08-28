function relationselectWidget(selectInputId, pjaxContainerId)
{
    var selectInput = jQuery('#' + selectInputId);
    var pjax = jQuery('#' + pjaxContainerId);

    var multiple = selectInput.prop('multiple');

    pjax.on('pjax:success', function(){
        jQuery(this).find('[data-action=link]').each(function(){
            var id = jQuery(this).data('id');
            if (multiple) {
                if (selectInput.val().indexOf(id + '') > -1) {
                    jQuery(this).hide();
                } else {
                    jQuery(this).show();
                }
            } else {
                if (id == selectInput.val()) {
                    jQuery(this).hide();
                } else {
                    jQuery(this).show();
                }
            }
        });
        jQuery(this).find('[data-action=unlink]').each(function(){
            var id = jQuery(this).data('id');
            if (multiple) {
                if (selectInput.val().indexOf(id + '') > -1) {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                }
            } else {
                if (id == selectInput.val()) {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                }
            }
        });
    });

    pjax.on('click', '[data-action=link]', function(){
        var id = jQuery(this).data('id');
        if (multiple) {
            selectInput.append('<option value="' + id + '" selected>' + id + '</option>');
            jQuery(this).hide().siblings('[data-action=unlink]').show();
        } else {
            selectInput.html('<option value="' + id + '" selected>' + id + '</option>');
            pjax.find('[data-action=unlink]').hide();
            pjax.find('[data-action=link]').show();
            jQuery(this).hide().siblings('[data-action=unlink]').show();
        }
    });

    pjax.on('click', '[data-action=unlink]', function(){
        var id = jQuery(this).data('id');
        if (multiple) {
            selectInput.find('option[value=' + id + ']').remove();
            jQuery(this).hide().siblings('[data-action=link]').show();
        } else {
            selectInput.html('');
            pjax.find('[data-action=unlink]').hide();
            pjax.find('[data-action=link]').show();
        }
    });

}