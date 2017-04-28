'use strict';

(function(window, $) {
    window.Basket = function($wrapper) {
        this.$wrapper = $wrapper;

        this.$wrapper.find('.js-qty input').on(
            'change paste keyup',
            this.handleUpdateBasket.bind(this)
        );

        this.$wrapper.find('.js-qty input').on(
            'click',
            this.handleUpdateBasket.bind(this)
        );

        this.$wrapper.find('.js-remove').on(
            'click',
            this.handleRemoveBasket.bind(this)
        );

        this.$wrapper.find('.js-disabled').on(
            'click',
            this.handleDisabled.bind(this)
        );

    };

    $.extend(window.Basket.prototype, {

        handleUpdateBasket: function(e) {
            e.preventDefault();

            var $quantity = $(e.currentTarget).val();
            var productIdAddToBasket = $(e.currentTarget).closest('div').data('url');

            if ($quantity < 0 || !$quantity) {

                $(e.currentTarget).addClass('field-error');

            } else if ($quantity > 0) {

                $(e.currentTarget).removeClass('field-error');

                $.ajax({
                    url: productIdAddToBasket + '?quantity=' + $quantity,
                    method: 'GET'
                }).done(function(data) {

                    var place = '.js-box-totals';

                    var result = $('<div />').append(data).find('.js-box-totals').html();

                    $(place).html(result);
                });

            }
        },

        handleRemoveBasket: function(e) {
            e.preventDefault();

            var productRemoveFromBasket = $(e.currentTarget).data('url');

            var $el = $(e.currentTarget).closest('.item');

            $(e.currentTarget).children('i').removeClass('fa-close')
                .addClass('fa-spinner')
                .addClass('fa-spin');

            $.ajax({
                url: productRemoveFromBasket,
                method: 'GET'
            }).done(function(data) {

                $el.fadeOut();

                var place = '.js-box-totals';

                var result = $('<div />').append(data).find('.js-box-totals').html();

                $(place).html(result);
            });
        },

        handleDisabled: function(e) {
            e.preventDefault();
        }
    });
})(window, jQuery);