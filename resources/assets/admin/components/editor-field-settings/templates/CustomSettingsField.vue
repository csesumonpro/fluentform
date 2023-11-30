<template>
    <div>
        <component  v-if="componentReady" :listItem="listItem" :form_items="form_items" :editItem="editItem" :is="customComponent"></component>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'CustomSettingsField',
    props: ['listItem', 'editItem', 'form_items'],
    data() {
        return {
            componentReady: false
        }
    },
    computed: {
        customComponent() {
            return window.ffEditorOptionsCustomComponents[this.listItem.componentName] || null;
        }
    },
    beforeMount() {
        if(window.ffEditorOptionsCustomComponents[this.listItem.componentName]) {
            this.componentReady = true;
        }
    },
    created() {
        console.log(this);
    }
}
</script>
