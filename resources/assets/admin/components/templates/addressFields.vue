<template>
<div>
    <label v-if="item.settings.label" class="label-block" :class="item.settings.required ? 'is-required' : ''" v-html="item.settings.label"></label>

    <el-row :gutter="20">
        <template v-for="(field, i) in getOrerderFields">
            <el-col :key="i" :md="is_conversion_form ? 24 : 12" v-if="getField(field).settings.visible"  class="address-field-wrapper">
                <component :is="guessElTemplate(getField(field))" :item="getField(field)"></component>
            </el-col>
        </template>
    </el-row>
</div>
</template>

<script>
import inputText from './inputText.vue'
import select from './select.vue'
import selectCountry from './selectCountry.vue'

export default {
    name: 'address_fields',
    props: ['item'],
    components: {
        'ff_inputText': inputText,
        'ff_select': select,
        'ff_selectCountry': selectCountry,
    },
    methods:{
        getField(field){
            if(this.item.settings.field_order){
                return this.item.fields[field.value];
            }
            return  field;
        }
       
    },
    computed: {
        getOrerderFields(){
            return this.item.settings.field_order ? this.item.settings.field_order : this.item.fields;
        }
    }
}
</script>
