<template>
    <div class="calculation_item">
        <el-form-item>
            <elLabel slot="label" :label="listItem.label" :helpText="listItem.status_tips"></elLabel>
            <el-checkbox v-model="value.status">{{listItem.status_label}}</el-checkbox>
        </el-form-item>
        <el-form-item v-if="value.status">
            <elLabel slot="label" :label="listItem.formula_label" :helpText="listItem.formula_tips"></elLabel>
            <inputPopover
                class="calc_pop_over"
                fieldType="textarea"
                v-model="value.formula"
                :data="editorShortcodes"
                placement="bottom"
                :attr-name="editItem.attributes.name">
            </inputPopover>
            <p><a target="_blank" href="https://wpmanageninja.com/docs/fluent-form/field-types/calculate-numeric-entities-in-wp-fluent-forms/" rel="nofollow">{{
                    $t('View Calculation Documentation')
                }}</a></p>
        </el-form-item>
    </div>
</template>

<script type="text/babel">
    import elLabel from '../../includes/el-label.vue'
    import inputPopover from '../../input-popover.vue'
    import each from 'lodash/each'


    export default {
        name: 'inputText',
        props: ['listItem', 'value', 'form_items', 'editItem'],
        components: {
            elLabel,
            inputPopover
        },
        watch: {
            model() {
                this.$emit('input', this.model);
            }
        },
        computed: {
            editorShortcodes() {
                let shortcodes = {};
                each(this.form_items, (item) => {
                    if(item.element == 'container') {
                        each(item.columns, (eachColumn) => {
                            each(eachColumn.fields, (columnItem) => {
                                if (this.isCalculative(columnItem) && this.editItem.attributes.name != columnItem.attributes.name) {
                                    shortcodes[this.getItemCode(columnItem)] = columnItem.settings.label || columnItem.settings.admin_field_label || columnItem.attributes.name;
                                }
                            });
                        });
                    } else if (this.isCalculative(item) && this.editItem.attributes.name != item.attributes.name) {
                        shortcodes[this.getItemCode(item)] = item.settings.label || item.settings.admin_field_label || item.attributes.name;
                    }
                });

                return [
                    {
                        'shortcodes': shortcodes
                    }
                ];
            }
        },
        data() {
            return {
                model: this.value
            }
        },
        methods: {
            getItemCode(item) {
                if (item.element == 'input_number' || item.element == 'rangeslider' || (item.element == 'multi_payment_component' && item.attributes.type =='single')) {
                    return '{input.' + item.attributes.name + '}';
                } else if(item.element == 'select') {
                    return '{select.' + item.attributes.name + '}';
                } else if(item.element == 'input_checkbox') {
                    return '{checkbox.' + item.attributes.name + '}';
                } else if(item.element == 'input_radio' || item.element == 'net_promoter_score') {
                    return '{radio.' + item.attributes.name + '}';
                } else if(item.element == 'repeater_field') {
                    return '{repeat.'+item.attributes.name+'}';
                } else if(item.element == 'multi_payment_component' && item.attributes.type !=='single') {
                    return '{payment.'+item.attributes.name+'}';
                }  else if(item.element == 'custom_payment_component') {
                    return '{payment.'+item.attributes.name+'}';
                }
            },
            isCalculative(item) {
                const paymentElements = [
                    'multi_payment_component',
                    'input_number',
                    'repeater_field',
                    'net_promoter_score',
                    'rangeslider',
                    'custom_payment_component'
                ];

                if (paymentElements.indexOf(item.element) != -1) {
                    return true;
                }

                return (
                    item.element == 'select' ||
                    item.element == 'input_checkbox' ||
                    item.element == 'input_radio'
                ) && item.settings.calc_value_status
            }
        }
    }
</script>