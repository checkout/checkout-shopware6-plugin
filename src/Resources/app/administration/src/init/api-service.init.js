import CkoRefundService from '../core/service/api/cko-refund-service'
import CkoVoidService from '../core/service/api/cko-void-service'
import CkoCaptureService from '../core/service/api/cko-capture-service'

const { Application } = Shopware;

Application.addServiceProvider('CkoVoidService', (container) => {
    const initContainer = Application.getContainer('init')

    return new CkoVoidService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('CkoCaptureService', (container) => {
    const initContainer = Application.getContainer('init')

    return new CkoCaptureService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('CkoRefundService', (container) => {
    const initContainer = Application.getContainer('init')

    return new CkoRefundService(initContainer.httpClient, container.loginService);
});