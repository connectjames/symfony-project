'use strict';

(function(window, $) {
    window.Checkout = function($wrapper) {
        this.$wrapper = $wrapper;

        this.$wrapper.find('.js-disabled').on(
            'click',
            this.handleDisabled.bind(this)
        );

        this.$wrapper.find('.js-submit').on(
            'click',
            this.handleSagePaySubmit.bind(this)
        );
    };

    $.extend(window.Checkout.prototype, {


        handleDisabled: function(e) {
            e.preventDefault();
        },

        handleSagePaySubmit: function(e) {
            e.preventDefault();

            var loader = $('#preloader-checkout');
            var cart = $('.cart');

            loader.removeClass('display-none');
            cart.addClass('display-none');

            var $myForm = $(e.currentTarget).closest('form');

            $myForm.find('.expiry').attr('required', 'required');
            $myForm.find('.expiry').attr('name', 'expiry');
            $myForm.find('.expiry').attr('title', 'expiry');
            $myForm.find('.expiry').attr('id', 'expiry');

            if (!$myForm[0].checkValidity()) {

                loader.addClass('display-none');
                cart.removeClass('display-none');

                $myForm.find(':invalid').each( function( index, node ) {

                    $('#' + node.id + '').addClass('field-error');
                });
            } else {

                var $urlMerchant = $(e.currentTarget).data('url-merchant');
                var $urlPlaceOrder = $(e.currentTarget).data('url-place-order');

                $.ajax({
                    url: $urlMerchant,
                    method: 'GET'
                }).done(function(data) {

                    var myCard = $('.card-js');

                    var expiry = "" + myCard.CardJs('expiryMonth') + myCard.CardJs('expiryYear');

                    sagepayOwnForm({ merchantSessionKey: data })
                        .tokeniseCardDetails({
                            cardDetails: {
                                cardholderName: myCard.CardJs('name'),
                                cardNumber: myCard.CardJs('cardNumber').replace(/ /g,''),
                                expiryDate: expiry,
                                securityCode: myCard.CardJs('cvc')
                            },
                            onTokenised : function(result) {
                                if (result.success) {
                                    window.location.href = $urlPlaceOrder + '?cardIdentifier=' + result.cardIdentifier;
                                } else {
                                    var i;
                                    for (i = 0; i < result.errors.length; ++i) {
                                        _toastr(result.errors[i].message, 'top-full-width', 'warning', false);
                                    }
                                }
                            }
                        })
                    ;
                });
            }
        }

    });

})(window, jQuery);