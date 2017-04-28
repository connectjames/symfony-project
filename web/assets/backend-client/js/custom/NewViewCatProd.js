'use strict';

(function(window, $) {
    window.NewViewCatProd = function($wrapper) {
        this.$wrapper = $wrapper;
        this.helper = new Helper($wrapper);

        this.helper.productToBeCheckedOnPage();

        this.helper.allValuesOnPage();

        this.helper.collectCheckedBoxesOnPage();

        this.$wrapper.find('form input[name="metaTitle"]').on(
            'keyup',
            this.handleCharLimitTitleDescription.bind(this)
        );

        this.$wrapper.find('form textarea').on(
            'keyup',
            this.handleCharLimitMetaDescription.bind(this)
        );

        this.$wrapper.on(
            'click',
            '.js-table-checkbox input',
            this.handleToggleCheckboxes.bind(this)
        );

        this.$wrapper.find('#js-search form').on(
            'submit',
            this.handleSearchValue.bind(this)
        );

        this.$wrapper.on(
            'click',
            '#js-table #nav-pages a',
            this.handlePageChange.bind(this)
        );

        this.$wrapper.on(
            'click',
            '#js-products-list a',
            this.handleAllocatedProductsDelete.bind(this)
        );

        this.$wrapper.find('form[name="image-form"]').on(
            'submit',
            this.handleNewFormSubmit.bind(this)
        );

        this.$wrapper.find('#js-save-edit-product').on(
            'click',
            this.handleEditProductSave.bind(this)
        );

        this.$wrapper.find('#js-save-new-product').on(
            'click',
            this.handleNewProductSave.bind(this)
        );

        this.$wrapper.find('#js-save-edit-category').on(
            'click',
            this.handleEditCategorySave.bind(this)
        );

        this.$wrapper.find('#js-save-new-category').on(
            'click',
            this.handleNewCategorySave.bind(this)
        );
    };

    $.extend(window.NewViewCatProd.prototype, {

        handleCharLimitTitleDescription: function(e) {
            var metaTitleField = e.currentTarget;

            var len = metaTitleField.value.length;

            if (len >= 160) {

                metaTitleField.value = metaTitleField.value.substring(0, 55);
            } else {

                $('#charNumTitle').text('(' + (55 - len) + ' characters left)');
            }
        },

        handleCharLimitMetaDescription: function(e) {
            var metaDescriptionField = e.currentTarget;

            var len = metaDescriptionField.value.length;

            if (len >= 160) {

                metaDescriptionField.value = metaDescriptionField.value.substring(0, 160);
            } else {

                $('#charNumDesc').text('(' + (160 - len) + ' characters left)');
            }
        },

        handleToggleCheckboxes: function(e) {
            var checkboxes = document.getElementsByName('products');

            for (var i = 0, n = checkboxes.length; i < n; i++) {

                checkboxes[i].checked = e.currentTarget.checked;
            }
        },

        handleSearchValue: function(e) {
            e.preventDefault();

            this.helper.finalValueChecked();

            $.ajax({
                url: this.helper.pageUrlLink() + '?search=prod.name&searchValue=' + '%' + this.$wrapper.find('#js-search form').children('input').val() + '%' + '&sort=prod.id&page=1&checkboxes=' + localStorage.finalValue.split(',').sort(function(a, b){return a-b}).join(','),
                method: 'GET',
                dataType: 'html'
            }).done(function(data) {

                var result = $('<div />').append(data).find('#js-table').html();

                $('#js-table').html(result);

                if (localStorage.checkboxValues) {

                    localStorage.removeItem('checkboxValues');
                }

                window.Helper.prototype.productToBeCheckedOnPage();
                window.Helper.prototype.allValuesOnPage();
                window.Helper.prototype.collectCheckedBoxesOnPage();
                window.Helper.prototype.handleToggleCheckboxes();
            });
        },
        
        handlePageChange : function (e) {
            e.preventDefault();

            this.helper.finalValueChecked();

            var page = ($(e.currentTarget).attr('href')).split('=')[3];

            $.ajax({
                url: this.helper.pageUrlLink() + '?sort=prod.id&direction=asc&page=' + page + '&checkboxes=' + localStorage.finalValue.split(',').sort(function(a, b){return a-b}).join(','),
                method: 'GET',
                dataType: 'html'
            }).done(function(data) {

                var result = $('<div />').append(data).find('#js-table').html();

                $('#js-table').html(result);

                if (localStorage.checkboxValues) {

                    localStorage.removeItem('checkboxValues');
                }

                window.Helper.prototype.productToBeCheckedOnPage();
                window.Helper.prototype.allValuesOnPage();
                window.Helper.prototype.collectCheckedBoxesOnPage();

            });
        },

        handleAllocatedProductsDelete : function (e) {
            e.preventDefault();

            var finalResultCheckboxes = localStorage.finalValue;

            var $el = $(e.currentTarget).closest('.special-product');

            if (finalResultCheckboxes.indexOf(',') >= 0) {

                finalResultCheckboxes = ',' + finalResultCheckboxes + ',';

                finalResultCheckboxes = finalResultCheckboxes.replace(',' + $(e.currentTarget).attr('data-id') + ',', ',');

                if (finalResultCheckboxes.charAt(0) == ',') {
                    finalResultCheckboxes = finalResultCheckboxes.substring(1, finalResultCheckboxes.length)
                }

                if (finalResultCheckboxes.charAt(finalResultCheckboxes.length-1) == ',') {
                    finalResultCheckboxes = finalResultCheckboxes.substring(0, finalResultCheckboxes.length-1)
                }

            } else {

                finalResultCheckboxes = '';
            }

            localStorage.setItem("finalValue", finalResultCheckboxes);

            $(e.currentTarget).children('span').removeClass('fa-close')
                .addClass('fa-spinner')
                .addClass('fa-spin');

            $.ajax({
                url: this.helper.pageUrlLink() + '?checkboxes=' + finalResultCheckboxes,
                method: 'GET',
                dataType: 'html'
            }).done(function(data) {

                $el.fadeOut();

                var result = $('<div />').append(data).find('#js-table').html();

                $('#js-table').html(result);

                if (localStorage.checkboxValues) {

                    localStorage.removeItem("checkboxValues");
                }

                window.Helper.prototype.productToBeCheckedOnPage();
                window.Helper.prototype.allValuesOnPage();
                window.Helper.prototype.collectCheckedBoxesOnPage();

            });
        },

        handleNewFormSubmit: function(e) {
            e.preventDefault();

            var $form = $(e.currentTarget);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: new FormData(e.currentTarget),
                processData: false,
                contentType: false
            }).success(function (data) {
                var place = '#js-image-detail-preview';

                var result = $(data);

                $(place).html(result);
            });
        },

        handleEditProductSave : function (f) {
            f.preventDefault();

            this.helper.finalValueChecked();

            var newValues = [];

            Array.prototype.forEach.call($('input[name="categories"]:checked'), function (el) {
                newValues.push(el.value);
            });

            localStorage.setItem('categories', newValues);

            var $myForm = $('#product-form');

            if (!$myForm[0].checkValidity()) {

                $myForm.find(':invalid').each(function (index, node) {

                    $('#' + node.id + '').addClass('field-error');

                    _toastr('Please check the field "' + $("label[for='" + node.id + "']").text() + '" highlighted. Error: ' + node.validationMessage + '', 'top-full-width', 'warning', false);
                });
            } else {
                this.$wrapper.find('form[name="image-form"]').on('submit', function(e) {
                    e.preventDefault();

                    var $form = $(e.currentTarget);

                    $.ajax({
                        url: $form.data('url'),
                        type: 'POST',
                        data: new FormData(e.currentTarget),
                        processData: false,
                        contentType: false
                    }).success(function (data) {

                        var params = {
                            name: $('input[name="name"]').val(),
                            sku: $('input[name="sku"]').val(),
                            price: $('input[name="price"]').val(),
                            imageName: data,
                            weight: $('input[name="weight"]').val(),
                            url: $('input[name="url"]').val(),
                            metaTitle: $('input[name="metaTitle"]').val(),
                            metaKeywords: $('input[name="metaKeywords"]').val(),
                            metaDescription: $('textarea[name="metaDescription"]').val(),
                            shortDescription: $('#short-description').find('textarea').code(),
                            description: $('#description').find('textarea').code(),
                            dropshipper: $('select#dropshipper').val(),
                            checkboxes: localStorage.finalValue,
                            categories: localStorage.categories
                        };

                        var parameters = $.param(params);

                        window.location.href = $('form[name="product-form"]').attr('action')+ '?' + parameters;
                    });
                });

                this.$wrapper.find('form[name="image-form"]').submit();
            }
        },

        handleNewProductSave : function (f) {
            f.preventDefault();

            this.helper.finalValueChecked();

            var newValues = [];

            Array.prototype.forEach.call($('input[name="categories"]:checked'), function (el) {
                newValues.push(el.value);
            });

            localStorage.setItem('categories', newValues);

            var $myForm = $('#product-form');
            var $myImageForm = $('#image-form');

            if (!$myForm[0].checkValidity()) {

                $myForm.find(':invalid').each(function (index, node) {

                    $('#' + node.id + '').addClass('field-error');

                    _toastr('Please check the field "' + $("label[for='" + node.id + "']").text() + '" highlighted. Error: ' + node.validationMessage + '', 'top-full-width', 'warning', false);
                });
            } else if (!$myImageForm[0].checkValidity()) {
                $myImageForm.find(':invalid').each(function (index, node) {

                    $('#' + node.id + '').addClass('field-error');

                    _toastr('Please check the field "' + $("label[for='" + node.id + "']").text() + '" highlighted. Error: ' + node.validationMessage + '', 'top-full-width', 'warning', false);
                });
            } else {
                this.$wrapper.find('form[name="image-form"]').on('submit', function(e) {
                    e.preventDefault();

                    var $form = $(e.currentTarget);

                    var $url = $('input[name="url"]').val();

                    var $slug = $('input[name="name"]').val();

                    if ($url) {
                        $slug = $url;
                    }

                    $.ajax({
                        url: $form.data('url') + '?name=' + $slug,
                        type: 'POST',
                        data: new FormData(e.currentTarget),
                        processData: false,
                        contentType: false
                    }).success(function (data) {

                        var params = {
                            name: $('input[name="name"]').val(),
                            sku: $('input[name="sku"]').val(),
                            price: $('input[name="price"]').val(),
                            imageName: data,
                            weight: $('input[name="weight"]').val(),
                            url: $url,
                            metaTitle: $('input[name="metaTitle"]').val(),
                            metaKeywords: $('input[name="metaKeywords"]').val(),
                            metaDescription: $('textarea[name="metaDescription"]').val(),
                            shortDescription: $('#short-description').find('textarea').code(),
                            description: $('#description').find('textarea').code(),
                            dropshipper: $('select#dropshipper').val(),
                            checkboxes: localStorage.finalValue,
                            categories: localStorage.categories
                        };

                        var parameters = $.param(params);

                        window.location.href = $('form[name="product-form"]').attr('action')+ '?' + parameters;
                    });
                });

                this.$wrapper.find('form[name="image-form"]').submit();
            }
        },

        handleEditCategorySave : function (f) {
            f.preventDefault();

            this.helper.finalValueChecked();

            var $myForm = $('#category-form');

            if (!$myForm[0].checkValidity()) {

                $myForm.find(':invalid').each(function (index, node) {

                    $('#' + node.id + '').addClass('field-error');

                    _toastr('Please check the field "' + $("label[for='" + node.id + "']").text() + '" highlighted. Error: ' + node.validationMessage + '', 'top-full-width', 'warning', false);
                });
            } else {
                this.$wrapper.find('form[name="image-form"]').on('submit', function(e) {
                    e.preventDefault();

                    var $form = $(e.currentTarget);

                    $.ajax({
                        url: $form.data('url'),
                        type: 'POST',
                        data: new FormData(e.currentTarget),
                        processData: false,
                        contentType: false
                    }).success(function (data) {

                        var params = {
                            name: $('input[name="name"]').val(),
                            imageName: data,
                            description: $('#description').find('textarea').code(),
                            url: $('input[name="url"]').val(),
                            metaTitle: $('input[name="metaTitle"]').val(),
                            metaKeywords: $('input[name="metaKeywords"]').val(),
                            metaDescription: $('textarea[name="metaDescription"]').val(),
                            products: localStorage.finalValue
                        };

                        var parameters = $.param(params);

                        window.location.href = $('form[name="category-form"]').attr('action')+ '?' + parameters;
                    });
                });

                this.$wrapper.find('form[name="image-form"]').submit();
            }
        },

        handleNewCategorySave : function (f) {
            f.preventDefault();

            this.helper.finalValueChecked();

            var $myForm = $('#category-form');

            if (!$myForm[0].checkValidity()) {

                $myForm.find(':invalid').each(function (index, node) {

                    $('#' + node.id + '').addClass('field-error');

                    _toastr('Please check the field "' + $("label[for='" + node.id + "']").text() + '" highlighted. Error: ' + node.validationMessage + '', 'top-full-width', 'warning', false);
                });
            } else {
                this.$wrapper.find('form[name="image-form"]').on('submit', function(e) {
                    e.preventDefault();

                    var $form = $(e.currentTarget);

                    var $url = $('input[name="url"]').val();

                    var $slug = $('input[name="name"]').val();

                    if ($url) {
                        $slug = $url;
                    }

                    $.ajax({
                        url: $form.data('url') + '?name=' + $slug,
                        type: 'POST',
                        data: new FormData(e.currentTarget),
                        processData: false,
                        contentType: false
                    }).success(function (data) {

                        var params = {
                            name: $('input[name="name"]').val(),
                            imageName: data,
                            description: $('#description').find('textarea').code(),
                            url: $('input[name="url"]').val(),
                            metaTitle: $('input[name="metaTitle"]').val(),
                            metaKeywords: $('input[name="metaKeywords"]').val(),
                            metaDescription: $('textarea[name="metaDescription"]').val(),
                            products: localStorage.finalValue
                        };

                        var parameters = $.param(params);

                        window.location.href = $('form[name="category-form"]').attr('action')+ '?' + parameters;
                    });
                });

                this.$wrapper.find('form[name="image-form"]').submit();
            }
        }

    });

    window.Helper = function ($wrapper) {
        this.$wrapper = $wrapper;

        this.pageUrlLink.bind(this)
    };

    $.extend(Helper.prototype, {

        productToBeCheckedOnPage: function() {
            if (localStorage.finalValue) {

                var finalValue = localStorage.finalValue.split(',');

                $('input[name="products"]').each(function () {

                    for (var x = 0; x < finalValue.length; x++) {

                        $('input[name=products][value="' + finalValue[x] + '"').prop('checked', true);
                    }
                });
            }
        },

        collectCheckedBoxesOnPage: function() {
            var $table = $('#js-table');

            $table.find(':checkbox').on('change', function() {

                var checkboxes = document.querySelectorAll('input[name="products"]:checked'), newValues = [];

                Array.prototype.forEach.call(checkboxes, function (el) {

                    newValues.push(el.value);
                });

                localStorage.setItem('checkboxValues', newValues);
            });
        },

        allValuesOnPage: function() {
            if (document.querySelectorAll('input[name="products"]:checked')) {

                var checkboxes = document.querySelectorAll('input[name="products"]:checked'), valuesOnPage = [];

                Array.prototype.forEach.call(checkboxes, function (el) {

                    valuesOnPage.push(el.value);
                });

                localStorage.setItem('valuesOnPage', valuesOnPage);
            }
        },

        finalValueChecked: function() {
            if (localStorage.finalValue) {

                if (document.querySelectorAll('input[name="products"]:checked')) {

                    var checkboxes = document.querySelectorAll('input[name="products"]:checked'), valuesOnPageOnClick = [];

                    Array.prototype.forEach.call(checkboxes, function (el) {

                        valuesOnPageOnClick.push(el.value);
                    });
                }

                var relatedProducts = localStorage.finalValue.split(',');

                var valuesOnActualPage = localStorage.valuesOnPage.split(',');

                var relatedProductsLessValuesOnPage = [], found;

                for (var i = 0; i < relatedProducts.length; i++) {

                    found = false;
                    for (var j = 0; j < valuesOnActualPage.length; j++) {

                        if (relatedProducts[i] == valuesOnActualPage[j]) {

                            found = true;
                            break;
                        }
                    }
                    if (!found) {

                        relatedProductsLessValuesOnPage.push(relatedProducts[i]);
                    }
                }
                if (localStorage.checkboxValues) {

                    relatedProductsLessValuesOnPage.push(localStorage.checkboxValues.split(','));

                    localStorage.setItem('finalValue', relatedProductsLessValuesOnPage);

                } else if (valuesOnActualPage.length && !valuesOnPageOnClick.length) {

                    localStorage.setItem('finalValue', relatedProductsLessValuesOnPage);
                } else if (!relatedProductsLessValuesOnPage.length && !valuesOnActualPage.length) {

                    localStorage.setItem('finalValue', []);
                }
            } else if (localStorage.checkboxValues) {

                localStorage.setItem('finalValue', localStorage.checkboxValues);
            } else {

                localStorage.setItem('finalValue', []);
            }
        },

        pageUrlLink: function() {
            return this.$wrapper.find('form').data('url');
        }
    });
})(window, jQuery);