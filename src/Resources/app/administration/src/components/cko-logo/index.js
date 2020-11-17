import template from './logo-html.twig'

Shopware.Component.register('cko-logo', {
    template,

    props:{
        method:{
            type:String,
            required: true,
            default:''
        }
    }

});