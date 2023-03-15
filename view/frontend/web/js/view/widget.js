define(
    ['ko', 'jquery', 'uiComponent', 'Magento_Checkout/js/action/get-totals', 'Magento_Customer/js/customer-data'],
    function(
        ko,
        $,
        Component,
        getTotalsAction,
        customerData
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'InvisibleCommerce_ShippedSuite/widget'
            },
            initialize: function() {
                var config = window.checkoutConfig.shippedSuite;
                if (typeof shippedConfig !== 'undefined') {
                    // variable is undefined
                    console.log(shippedConfig || {});
                    console.log(config.shippedConfig);
                    var localShippedConfig = Object.assign(config.shippedConfig, shippedConfig || {});
                } else {
                    var localShippedConfig = config.shippedConfig;
                }

                console.log(localShippedConfig);
                this._super();

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
                                    getTotalsAction([], $.Deferred());
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
