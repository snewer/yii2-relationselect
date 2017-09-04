function relationselectWidget(obj) {

    var
        $selectInput = jQuery('#' + obj.inputId),
        $pjax = jQuery('#' + obj.pjaxId),
        selectionInputSelector = '[name="' + obj.selectionInputName + '"]',
        multiple = $selectInput.prop('multiple');

    function getIds() {
        var value = $selectInput.val();
        if (typeof value == 'object') {
            return value.join(',');
        } else {
            return value;
        }
    }


    function updateSortUrl() {
        var $idsSortingLink = $pjax.find('a.ids-sorting');
        var url = $idsSortingLink.attr('href');
        url = url.replace(/sort=(-?)ids[\w%]*/, function (match, p1) {
            var ids = getIds();
            return 'sort=' + p1 + 'ids' + encodeURIComponent(',' + ids);
        });
        $idsSortingLink.attr('href', url);
    }

    function updateFilter() {
        var $filter = $pjax.find('[name="' + obj.idsFilterName + '"]');
        if ($filter) {
            var value = $selectInput.val();
            var ids;
            if (typeof value == 'object') {
                ids = value.join(',');
            } else {
                ids = value;
            }
            $filter.find('option:eq(1)').val(ids);
            $filter.find('option:eq(2)').val('!' + ids);
        }
    }

    $selectInput.on('change', function(){
        updateSortUrl();
    });

    $pjax.on('click', 'td:has(' + selectionInputSelector + ')', function (e) {
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
                $selectInput.append('<option value="' + id + '" selected>' + id + '</option>');
            } else {
                $selectInput.find('option[value=' + id + ']').remove();
            }
        } else {
            if (checked) {
                $selectInput.html('<option value="' + id + '" selected>' + id + '</option>');
            }
        }
        $selectInput.trigger('change');
        updateFilter();
    });

    // После обновления pjax контейнера, проходимся по всем input
    // и меняем их состояние в зависимости от выбранных значений.
    $pjax.on('pjax:success', function (e) {
        $pjax.find(selectionInputSelector).each(function () {
            var id = jQuery(this).val();
            var checked = multiple ? ($selectInput.val().indexOf(id + '') > -1) : (id == $selectInput.val());
            jQuery(this).prop('checked', checked);
        });
        updateFilter();
        updateSortUrl();
    });


}