<template>
    <div>
        <el-form-item v-if="isInventoryEnabled">
            <elLabel slot="label" :label="listItem.label" :helpText="listItem.help_text"></elLabel>

            <div v-if="optionsToRender.length" class="ff_advnced_options_wrap">
                <div class="vddl-list__handle optionsToRender"
                     v-for="(option, index) in editItem.settings[getOptionsKey]" :key="option.id">

                    <div class="vddl-nodrag">
                        <el-input :min="1" type="number" style="width: 30%"
                                  v-model.number="editItem.settings[getOptionsKey][index].quantity"></el-input>
                        <div>&nbsp;</div>
                        <div>
                            {{ option.label }}
                        </div>
                    </div>

                </div>
            </div>
        </el-form-item>

    </div>
</template>

<script>
    import elLabel from '../../includes/el-label.vue'

    export default {
        name: 'inventoryStock',
        props: ['listItem', 'value', 'editItem'],
        data() {
            return {
                optionsToRender: [],
                bulkEditVisible: false,
                value_key_pair_text: '',
                has_pro: !!window.FluentFormApp.hasPro,
            }
        },
        components: {
            elLabel
        },
        methods: {
            createOptionsToRender() {
                this.optionsToRender = this.editItem.settings[this.getOptionsKey];
            },

        },
        computed: {
            getOptionsKey () {
                if (this.editItem.element == 'multi_payment_component') {
                    return 'pricing_options';
                } else {
                    return 'advanced_options';
                }
            },
            isSingleInput(){
                if (this.editItem.element == 'multi_payment_component' && this.editItem.attribute.type == 'single'){
                    return true;
                }
                return  false;
            },
            isInventoryEnabled(){
                if ( this.editItem.settings.inventory_type == 'simple' ){
                    return true;
                }
                return  false;
            }
        },
        mounted() {
            let items = this.editItem.settings[this.getOptionsKey];
            items.forEach((item, i) => {
                if (item.quantity === undefined) {
                    this.$set(this.editItem.settings[this.getOptionsKey][i], 'quantity', 1)
                }
                this.editItem.settings[this.getOptionsKey][i].quantity = parseInt(item.quantity)
            })
            this.createOptionsToRender();

        }

    };
</script>
