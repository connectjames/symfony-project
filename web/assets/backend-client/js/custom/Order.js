'use strict';

(function(window, $) {
    window.Order = function($wrapper) {
        this.$wrapper = $wrapper;

        this.$wrapper.on(
            'click',
            '.js-table-checkbox input',
            this.handleToggleCheckboxes.bind(this)
        );

        this.$wrapper.find('#js-search-client form').on(
            'submit',
            this.handleSearchValue.bind(this)
        );

        this.$wrapper.find('.js-status select[name="status-change"]').on(
            'change',
            this.handleStatusChange.bind(this)
        );

        this.$wrapper.find('#js-statuses').on(
            'change',
            this.handleStatusesChange.bind(this)
        );

        this.$wrapper.find('#print-invoice').on(
            'click',
            this.printInvoice.bind(this)
        );

        this.$wrapper.find('#print-dispatch-note').on(
            'click',
            this.printDispatchNote.bind(this)
        );

        this.$wrapper.find('#print-all').on(
            'click',
            this.printInvoiceAndDispatchNote.bind(this)
        );

    };

    $.extend(window.Order.prototype, {

        handleToggleCheckboxes: function(e) {
            var checkboxes = document.getElementsByName('orders');

            for(var i=0, n=checkboxes.length;i<n;i++) {

                checkboxes[i].checked = e.currentTarget.checked;
            }
        },

        handleSearchValue: function(e) {
            e.preventDefault();

            window.location.href = this.$wrapper.data('url') +  '?' + 'search=ord.lastName&searchValue=%' + this.$wrapper.find('#js-search-client form').children('input').val() + '%';
        },

        handleStatusChange: function (e) {
            $.ajax({
                url: e.currentTarget.value,
                method: 'GET'
            }).done(function() {

                _toastr('Status of this order updated','top-full-width','success',false);
            });
        },

        handleStatusesChange: function (e) {
            var checkboxes = document.querySelectorAll('input[name="orders"]:checked'), values = [];

            var date1 = $.datepicker.formatDate('yy-mm-dd', new Date());

            var date2 = $.datepicker.formatDate('dd/mm/yy', new Date());

            var newStatus = '';

            var status = this.$wrapper.find('select#status').val();

            Array.prototype.forEach.call(checkboxes, function (el) {

                values.push(el.value);

                switch (status) {

                    case '1':
                        newStatus = 'Approved ' + date2;
                        break;

                    case '2':
                        newStatus = 'Payment Released ' + date2;
                        break;

                    case '3':
                        newStatus = 'Pending';
                        break;

                    case '4':
                        newStatus = 'Blocked';
                        break;

                    case '5':
                        newStatus = 'Cancelled';
                        break;
                }

                $("#status-selected-" + el.value).text(newStatus);
            });

            if (values.length) {

                $.ajaxSetup({
                    data: { ordersId: values.toString(), statusId: status, date: date1 }
                });

                $.ajax({
                    url: this.$wrapper.find('#js-statuses').data('url'),
                    method: 'GET'
                }).done(function() {

                    _toastr('Statuses of orders selected updated','top-full-width','success',false);
                });

                $(this).val(0)
            } else {

                _toastr('Please check at least one checkbox','top-full-width','warning',false);

                $(this).val(0)
            }
        },

        printInvoice: function (e) {
            var checkboxes = document.querySelectorAll('input[name="orders"]:checked'), values = [];

            Array.prototype.forEach.call(checkboxes, function (el) {

                values.push(el.value);
            });

            if (values.length) {

                window.location.href = this.$wrapper.find('#print-invoice').data('url') + '?ordersId=' + values.toString();
            } else {

                _toastr('Please check at least one checkbox','top-full-width','warning',false);
            }
        },

        printDispatchNote: function (e) {
            var checkboxes = document.querySelectorAll('input[name="orders"]:checked'), values = [];

            Array.prototype.forEach.call(checkboxes, function (el) {

                values.push(el.value);
            });

            if (values.length) {

                window.location.href = this.$wrapper.find('#print-dispatch-note').data('url') + '?ordersId=' + values.toString();
            } else {

                _toastr('Please check at least one checkbox','top-full-width','warning',false);
            }
        },

        printInvoiceAndDispatchNote: function (e) {
            var checkboxes = document.querySelectorAll('input[name="orders"]:checked'), values = [];

            Array.prototype.forEach.call(checkboxes, function (el) {

                values.push(el.value);
            });

            if (values.length) {

                window.location.href = this.$wrapper.find('#print-all').data('url') + '?ordersId=' + values.toString();
            } else {

                _toastr('Please check at least one checkbox','top-full-width','warning',false);
            }
        }
    });
})(window, jQuery);