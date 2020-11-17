import { Component } from 'src/core/shopware';
import template from './sw-order-detail-base.html.twig';
import enGB from '../../snippets/en-GB.json'
import deDE from '../../snippets/de-DE.json'

Shopware.Component.override('sw-order-detail-base',{
    template,
    inject: [
        //
    ],
    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    data() {
        return {
        //
        };
    },

    props: {
        //
    }

});