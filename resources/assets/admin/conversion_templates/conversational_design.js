import '../helpers';
import Vue from 'vue';

import {
    ColorPicker,
    Form,
    Input,
    Row,
    Col,
    FormItem,
    Select,
    OptionGroup,
    Option,
    Slider,
    Button,
    Loading,
    Message,
    Switch,
    Notification,
} from 'element-ui';

Vue.use(Form);
Vue.use(Input);
Vue.use(Row);
Vue.use(Col);
Vue.use(FormItem);
Vue.use(ColorPicker);
Vue.use(Select);
Vue.use(Option);
Vue.use(OptionGroup);
Vue.use(Slider);
Vue.use(Switch);
Vue.use(Button);

Vue.use(Loading.directive)
Vue.prototype.$loading = Loading.service
Vue.prototype.$notify = Notification
Vue.prototype.$message = Message;

import lang from 'element-ui/lib/locale/lang/en'
import locale from 'element-ui/lib/locale'
// configure language
locale.use(lang);

import DesignSkeleton from './Parts/Skeleton.vue';
import notifier from '@/admin/notifier'
import globalSearch from '../global_search';

Vue.mixin({
    methods: {
        $t(str) {
            let transString = window.fluent_forms_global_var.admin_i18n[str];
            if(transString) {
                return transString;
            }
            return str;
        },
        
        ...notifier
    },
    filters: {
        ucFirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
        _startCase(string) {
            return _ff.startCase(string);
        }
    }
});

new Vue({
    el: '#ff_conversation_form_design_app',
    data: {},
    components: {
        DesignSkeleton: DesignSkeleton,
        globalSearch
    },
    beforeCreate() {
        this.$on('change-title', (module) => {
            jQuery('title').text(`${module} - FluentForm`);
        });
        this.$emit('change-title', 'Conversational Form Design');
    },
    mounted() {
        (new ClipboardJS('.copy')).on('success', (e) => {
            this.$copy();
        });
    }
});
