<template>
    <el-form-item v-if="maybeHide">
        <elLabel slot="label" :label="listItem.label" :helpText="listItem.help_text"></elLabel>
        <el-input v-model="model" type="number"></el-input>
    </el-form-item>
</template>

<script>
import elLabel from '../../includes/el-label.vue'

export default {
    name: 'inputText',
    props: ['listItem', 'value', 'editItem'],
    components: {
        elLabel
    },
    watch: {
        model() {
            this.$emit('input', this.model);
        }
    },
    data() {
        return {
            model: this.value
        }
    },
    computed: {
        maybeHide(){
            const hasInventory =  this.editItem.settings.hasOwnProperty('inventory_type');
            if (!hasInventory) {
                return true;
            }
            const hasSimpleInventory = this.editItem.settings?.inventory_type == 'simple' && this.editItem.attributes.type == 'single';
            return hasSimpleInventory
        }
    },
}
</script>
