const ApiService = Shopware.Classes.ApiService;

class CkoVoidService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'cko') {
        super(httpClient, loginService, apiEndpoint);
    }

    void(data = {payment_id: null, payment_method: null}) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `cko/void`,
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

export default CkoVoidService;