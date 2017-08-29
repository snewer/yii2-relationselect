function relationselectWidget(obj) {

    var
        $selectInput = jQuery('#' + obj.inputId),
        $pjax = jQuery('#' + obj.pjaxId),
        selectionInputSelector = '[name="' + obj.selectionInputName + '"]',
        multiple = $selectInput.prop('multiple');

    $pjax.on('click', 'td:has(' + selectionInputSelector + ')', function(e) {
        var input = $(this).find(selectionInputSelector);
        if (e.target != input[0]) {
            input.click();
        }
    });

    $pjax.on('change', selectionInputSelector, function () {
        var id = jQuery(this).val();
        var checked = jQuery(this).prop('checked');
        if (multiple) {
            if (checked) {
                $selectInput.append('<option value="' + id + '" selected>' + id + '</option>').trigger('change');
            } else {
                $selectInput.find('option[value=' + id + ']').remove().trigger('change');
            }
        } else {
            if (checked) {
                $selectInput.html('<option value="' + id + '" selected>' + id + '</option>').trigger('change');
            }
        }
    });

    $pjax.on('pjax:success', function () {
        $pjax.find(selectionInputSelector).each(function () {
            var id = jQuery(this).val();
            var checked = multiple ? ($selectInput.val().indexOf(id + '') > -1) : (id == $selectInput.val());
            jQuery(this).prop('checked', checked);
        });
    });

}