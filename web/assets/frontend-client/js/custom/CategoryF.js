'use strict';

(function(window, $) {
    window.CategoryF = function($wrapper) {
        this.$wrapper = $wrapper;

        this.$wrapper.find('.js-sort-products-link').on(
            'click',
            this.handleSortProductsLink.bind(this)
        );
    };

    $.extend(window.CategoryF.prototype, {

        handleSortProductsLink: function(e) {
            var nameLink = $(e.currentTarget).closest('div').find('.js-data a:eq(0)').attr('href');
            var priceLink = $(e.currentTarget).closest('div').find('.js-data a:eq(1)').attr('href');
            $(e.currentTarget).find('option:eq(0)').val(nameLink);
            $(e.currentTarget).find('option:eq(1)').val(priceLink);

        }
    });

})(window, jQuery);