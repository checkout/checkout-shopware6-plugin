const ApiService = Shopware.Classes.ApiService;

class CkoRefundService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'cko') {
        super(httpClient, loginService, apiEndpoint);
    }

    refund(data = {payment_id: null, amount: null, currency: null}) {
        const headers = this.getBasicHeaders();

        if( data.amount === 0) {
            delete data.amount
            delete data.currency
        }

        return this.httpClient
            .post(
                `cko/refund`,
                JSON.stringify(data),
                {
                    headers: headers
                }
            )
            .then((response) => {
                return ApiService.handleResponse(response)
            })
            .catch(err => {
                console.log(err)
            });
    }
}

export default CkoRefundService;