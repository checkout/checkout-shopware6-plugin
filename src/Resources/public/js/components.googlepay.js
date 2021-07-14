/**
 * Checkout Google Pay Class.
 *
 * @class      CheckoutcomGooglePay (name)
 * @param      {<type>}    $form   The form
 * @return     {Function}  { description_of_the_return_value }
 */
function CheckoutcomGooglePay() {
	const $buttonArea = document.getElementById('cko-google-pay-area');
	const $publicKey = document.getElementById('cko_pk').value;
	const $googlePayEnv = document.getElementById('gpay_env').value;
	const $merchantId = document.getElementById('gpay_merchant_id').value;
	const $ckoPaymentMethodId = document.getElementById('cko_payment_methodId').value;
	const $defaultPaymentMethod = document.querySelectorAll('input[name="paymentMethodId"]:checked');
	const $selectedPaymentMethodId = $defaultPaymentMethod[0].value
	const swVersion = document.getElementById("sw_version").value

	/**
	 * Constants
	 */
	const baseRequest = {
		apiVersion: 2,
		apiVersionMinor: 0
	},
		tokenizationSpecification = {
			type: 'PAYMENT_GATEWAY',
			parameters: {
			'gateway': 'checkoutltd',
			'gatewayMerchantId': $publicKey
			}
	},
		allowedPaymentMethods = ['CARD', 'TOKENIZED_CARD'],
		allowedCardNetworks = getAllowedCardNetworks(),
		allowedCardAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"],
		baseCardPaymentMethod = {
			type: 'CARD',
			parameters: {
				allowedAuthMethods: allowedCardAuthMethods,
				allowedCardNetworks: allowedCardNetworks
			}
	},
		cardPaymentMethod = Object.assign({tokenizationSpecification: tokenizationSpecification}, baseCardPaymentMethod),
		paymentsClient = new window.google.payments.api.PaymentsClient({environment:  $googlePayEnv}),
		isReadyToPayRequest = Object.assign({}, baseRequest),
		$input = document.getElementById('checkoutcom-google-token');
	var self = this;

	isReadyToPayRequest.allowedPaymentMethods = [baseCardPaymentMethod];

	/**
	 * Init payments client.
	 */
	paymentsClient.isReadyToPay(isReadyToPayRequest).then(function(response) {
		if (response.result) {
			// Add Google Pay button to the page
			insertButton();
			prefetchData();
		} else {
			self.hide(response);
		}

	}).catch(function(err) {
		self.hide(err);
	});


	/**
	 * Protected methods
	 */

	/**
	 * Gets the allowed card networks.
	 *
	 */
	function getAllowedCardNetworks() {
		return ["AMEX", "DISCOVER", "JCB", "MASTERCARD", "VISA"];
	}

	/**
	 * Create Google Pay Button.
	 */
	function insertButton() {
		const button = paymentsClient.createButton({
			buttonColor: window.gpay_button_color,
			buttonSizeMode: 'fill',
			onClick: handleClick
		});
		button.id = 'checkoutcom-google-pay';

		if($selectedPaymentMethodId === $ckoPaymentMethodId) {
			$buttonArea.append(button);
		} else {
			$buttonArea.remove(button);
		}
	}

	/**
	 * Generate reques
	 *
	 */
	function getRequest() {
		const paymentDataRequest = Object.assign({}, baseRequest);
		paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];

		paymentDataRequest.transactionInfo = {
			totalPriceStatus: 'FINAL',
			totalPrice: window.display_total,
			currencyCode: window.display_currency,
			countryCode: window.display_cuntry
		};

		paymentDataRequest.merchantInfo = {
			merchantName: window.location.hostname,
			merchantId: $merchantId
		};

		return paymentDataRequest;
	}

	/**
	 * Prefetch Data for performance.
	 */
	function prefetchData() {
		paymentsClient.prefetchPaymentData(getRequest());
	}

	/**
	 * Hanle Google Pay click.
	 */
	function handleClick() {
		paymentsClient.loadPaymentData(getRequest()).then(function(paymentData){
			const $data = paymentData.paymentMethodData.tokenizationData.token
			gpaytoken($data);

		}).catch(function(err){
			self.hide(err);
		});

	}

	/**
	 * Generate cko token based on google pay data
	 * 
	 */
	const gpaytoken =  async ($data) => {
		const fetchUrl = getTokenUrl($publicKey);

		const requestBody = {
			type : "googlepay",
			token_data : JSON.parse($data)
		}

		const response = await fetch(fetchUrl, { 
			method: 'POST',
			headers: {
				"Authorization" : $publicKey,
				"Content-Type": "application/json" 
			},
			body: JSON.stringify(requestBody)
		});

		const json = await response.json();
		const gpaytoken = json.token
		const submitForms = await submitForm(gpaytoken);

		return submitForms;
	}

	/**
	 * Save token in customer custom field
	 * Trigger default submit order button
	 *
	 */
	const submitForm =  async (token) => { 
		const fetchUrl = '/cko/components/store-card-token/'
			+ document.getElementById('customer_id').value
			+ '/' + token
			+ '/' + document.getElementById('cko_context_id').value
			+ '/' + 'new_card'
			+ '/' + window.isSaveCardCheck

		
		fetch(fetchUrl, { headers: { "Content-Type": "application/json; charset=utf-8" }})
			.then(res => res.json())
			.then(response => {
				if(response.success) {
					swVersion.startsWith("6.4") ? document.querySelector("#confirmOrderForm").submit() : document.querySelector('#changePaymentForm').submit();
					document.getElementsByClassName('gpay-card-info-container')[0].disabled = true
				} else {
					write('Error while saving card token');
				}
			})
			.catch(err => {
				write('Error while saving card token ');
			});
	}

	/**
	 * Get Cko generate token url
	 */
	function getTokenUrl($key) {
		let url = 'https://api.checkout.com/tokens'

		if($key.includes('_test_')){
			url = 'https://api.sandbox.checkout.com/tokens'
		}

		return url;
	}

	/**
	 * Write to console.
	 *
	 */
	function write(reason) {
		console.log('checkoutcom-google-pay', reason);
	}

	/**
	 * Hide Google Pay option from the DOM.
	 *
	 */
	this.hide = function(reason) {
		console.log("hide google pay option", reason);
		write(reason);
	};
	
}
