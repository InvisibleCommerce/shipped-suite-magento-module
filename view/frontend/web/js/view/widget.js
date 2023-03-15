define(
    [
        'ko',
        'jquery',
        'uiComponent',
        './get-totals',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/quote'
    ],
    function(
        ko,
        $,
        Component,
        getTotalsAction,
        customerData,
        totals,
        quote
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'InvisibleCommerce_ShippedSuite/widget'
            },
            initialize: function() {
                var checkoutConfig = window.checkoutConfig;
                var config = checkoutConfig.shippedSuite;

                if (typeof shippedConfig !== 'undefined') {
                    // variable is undefined
                    var localShippedConfig = Object.assign(config.shippedConfig, shippedConfig || {});
                } else {
                    var localShippedConfig = config.shippedConfig;
                }

                this._super();

                var callback = (response) => {
                    var items = response.items;
                    items.forEach((item) => {
                        if (item.name === config.shieldName) {
                            window.checkoutConfig.imageData[parseInt(item.item_id)] = config.shieldImageData;
                        }
                        if (item.name === config.greenName) {
                            window.checkoutConfig.imageData[parseInt(item.item_id)] = config.greenImageData;
                        }
                    });

                    var newTotals = quote.totals();
                    newTotals.items_qty = 45;
                    quote.setTotals(newTotals);

                    return true;
                }

                require([
                    'jquery',
                    'Magento_Ui/js/lib/view/utils/dom-observer',
                    config.jsUrl
                ], function ($, $do) {
                    $(document).ready(function() {
                        $do.get('.shipped-widget', function() {
                            const shippedWidget = new Shipped.Widget(localShippedConfig);

                            let subtotal = 0;
                            customerData.get('cart')().items.map((item) => {
                                subtotal += item.product_price_value * item.qty;
                            });

                            shippedWidget.updateOrderValue(subtotal);
                            shippedWidget.onChange((details) => {
                                let path
                                if (details.isSelected) {
                                    path = '/shippedsuite/widget/add';
                                } else {
                                    path = '/shippedsuite/widget/remove';
                                }

                                fetch(path, {
                                    method: 'POST',
                                    mode: 'same-origin',
                                    credentials: 'same-origin',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    }
                                }).then((response) => {
                                    response.json();
                                }).then(() => {
                                    getTotalsAction([callback], $.Deferred());
                                });
                            });
                        });
                    });
                });

                return this;
            }
        });
    }
);
