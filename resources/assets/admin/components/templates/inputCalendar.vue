<template>
    <withLabel :item="item">
        <div class="ff-el-calendar-wrapper">
            <img :src="plugin_url+'img/ff-calendar-placeholder.png'" alt="fluent-calendar-placeholder">
        </div>
    </withLabel>
</template>

<script type="text/babel">
    import withLabel from './withLabel.vue';

    export default {
        name: 'inputCalendar',
        props: ['item'],
        data() {
            return {
                plugin_url: window.FluentFormApp.plugin_public_url
            }
        },
        components: {
            withLabel
        }
    }
</script>
