/**
 * PostFinance Checkout Magento 1
 *
 * This Magento extension enables to process payments with PostFinance Checkout (https://postfinance.ch/en/business/products/e-commerce/postfinance-checkout-all-in-one.html/).
 *
 * @package PostFinanceCheckout_Payment
 * @author wallee AG (http://www.wallee.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */
MagePostFinanceCheckout.Checkout.Type.IWDCheckoutSuite = Class.create(
	MagePostFinanceCheckout.Checkout.Type, {
		initialize: function() {
			PaymentMethod.prototype.init = PaymentMethod.prototype.init.wrap(this.init.bind(this));

			PaymentMethod.prototype.applyResponse = PaymentMethod.prototype.applyResponse.wrap(this.applyResponse.bind(this));
			PaymentMethod.prototype.selectPaymentMethod = PaymentMethod.prototype.selectPaymentMethod.wrap(this.selectPaymentMethod.bind(this));

			OnePage.prototype.saveOrder = OnePage.prototype.saveOrder.wrap(this.saveOrder.bind(this));
		},

		init: function(callOriginal) {
			callOriginal();
			this.selectPaymentMethod(function() {});
		},

		applyResponse: function(callOriginal, methods) {
			callOriginal(methods);
			this.selectPaymentMethod(function() {});
		},

		selectPaymentMethod: function(callOriginal) {
			callOriginal();
			var paymentForm = $('payment_form_' + Singleton.get(PaymentMethod).getPaymentMethodCode());
			if (paymentForm) {
				paymentForm.show();
			}

			this.createHandler(
				Singleton.get(PaymentMethod).getPaymentMethodCode(),
				function() {},
				function(validationResult) {
					if (validationResult.success) {
						this.createOrder();
					}
				}.bind(this),
				function() {},
				function() {},
				function() {}
			);
		},

		saveOrder: function(callOriginal) {
			if (this.isSupportedPaymentMethod(Singleton.get(PaymentMethod).getPaymentMethodCode()) && this.getPaymentMethod(Singleton.get(PaymentMethod).getPaymentMethodCode()).handler) {
				Singleton.get(OnePage).validateCheckout(true);
				if (Singleton.get(OnePage).isCheckoutValid()) {
					this.getPaymentMethod(Singleton.get(PaymentMethod).getPaymentMethodCode()).handler.validate();
					return;
				}
			}

			callOriginal();
		},

		createOrder: function() {
			var checkout = Singleton.get(OnePage);
			var data = checkout.getSaveData();
			clearTimeout(checkout.validateTimeout);
			clearTimeout(checkout.blurTimeout);
			checkout.showLoader(checkout.sectionContainer);
			checkout.ajaxCall(checkout.saveUrl, data, this.onOrderCreated.bind(this));
		},

		onOrderCreated: function(result) {
			if (typeof(result.status) !== 'undefined') {
				if (result.status) {
					if (this.getPaymentMethod(Singleton.get(PaymentMethod).getPaymentMethodCode()).handler) {
						this.getPaymentMethod(Singleton.get(PaymentMethod).getPaymentMethodCode()).handler.submit();
					} else {
						location.href = MagePostFinanceCheckout.Checkout.paymentPageUrl + '&paymentMethodConfigurationId=' + this.getPaymentMethod(Singleton.get(PaymentMethod).getPaymentMethodCode()).configurationId;
					}
				} else {
					Singleton.get(OnePage).parseErrorResultSaveOrder(result);
				}
			}

			return false;
		}
	}
);
MagePostFinanceCheckout.Checkout.type = MagePostFinanceCheckout.Checkout.Type.IWDCheckoutSuite;