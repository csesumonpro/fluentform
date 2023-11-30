import Vue from 'vue';
import globalSearch from './global_search';
import {
    Loading
} from 'element-ui';


Vue.use(Loading);

var app = new Vue({
    el: '#ff_documentation_app',
    components: {
        globalSearch
    }
});
