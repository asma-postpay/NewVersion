define([
	'jquery',
	'Magento_Catalog/js/price-utils',
	'underscore',
	'mage/template',
	'postpay-js',
	'jquery/ui'
], function ($, utils, _, mageTemplate,postpay)
{
	'use strict';

	return function (widget)
	{
		$.widget('mage.priceBox', widget,
		{
			reloadPrice: function reDrawPrices() {
			{
			  var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                            priceTemplate = mageTemplate(this.options.priceTemplate);

                        _.each(this.cache.displayPrices, function (price, priceCode) {
                            price.final = _.reduce(price.adjustments, function (memo, amount) {
                                return memo + amount;
                            }, price.amount);

                            price.formatted = utils.formatPrice(price.final, priceFormat);

                            $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate({
                                data: price
                            }));
                            /*update Postpay widget based on the change in Product Price
                             * Note : data-amount calculation is in ref Postpay\Serializers\Decimal.php */
                            $('.postpay-widget').attr('data-amount', Math.round(price.final * Math.pow(10, 2)));
                            postpay.ui.refresh();
                        }, this);
			}
		}});

		return $.mage.priceBox;
	}
});
