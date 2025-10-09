(function() {
    'use strict';

    if (!window.wc || !window.wc.wcSettings || !window.wp) {
        return;
    }

    const parsigateGateways = window.parsigate_gateways || [];

    parsigateGateways.forEach(function(gatewayData) {
        try {
            const gatewaySettings = window.wc.wcSettings.getSetting(gatewayData.name + '_data', {});

            const gatewayLabel = window.wp.htmlEntities.decodeEntities(gatewayData.title)
                || window.wp.i18n.__(gatewayData.driver, 'parsigate');

            const gatewayContent = () => {
                return window.wp.htmlEntities.decodeEntities(gatewayData.description)
                    || window.wp.i18n.__('Secure payment gateway', 'parsigate');
            };

            const gatewayObject = {
                name: gatewayData.name,
                label: gatewayLabel,
                content: Object(window.wp.element.createElement)(gatewayContent, null),
                edit: Object(window.wp.element.createElement)(gatewayContent, null),
                canMakePayment: () => true,
                placeOrderButtonLabel: window.wp.i18n.__('Continue', 'parsigate'),
                ariaLabel: gatewayLabel,
                supports: {
                    features: gatewaySettings.supports || ['products'],
                },
            };

            if (gatewayData.icon) {
                gatewayObject.icons = [gatewayData.icon];
            }

            window.wc.wcBlocksRegistry.registerPaymentMethod(gatewayObject);

        } catch (error) {
        }
    });

})();