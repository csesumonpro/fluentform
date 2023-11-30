<template>
    <div class="entry_info_box entry_submission_logs">
        <card>
            <card-head>
                <card-head-group class="justify-between">
                    <div class="entry_info_box_title">
                        {{$t('Submission Logs')}}
                    </div>
                    <div class="entry_info_box_actions">
                        <el-radio-group class="el-radio-group-info" v-model="log_type" size="medium">
                            <el-radio-button label="logs">{{$t('General')}}</el-radio-button>
                            <el-radio-button label="api_calls">{{$t('API Calls')}}</el-radio-button>
                        </el-radio-group>
                    </div>
                </card-head-group>
            </card-head>
            <card-body>
                <div class="entry_info_body">
                    <el-skeleton :loading="loading" animated :rows="4">
                        <div class="wpf_entry_details">
                            <template v-if="logs && logs.length">
                                <div v-for="(log, logKey) in logs" class="entry_submission_log wpf_each_entry" :key="logKey">
                                    <div class="wpf_entry_label">
                                        <span class="ff_tag" :class="'log_status_' + log.status">{{log.status}}</span>
                                        {{ $t('in') }}
                                        <span class="entry_submission_log_component">{{log.title}}</span>
                                        {{ $t('at') }}
                                        {{log.created_at}}
                                        <span class="wpf_entry_remove">
                                            <remove :plain="true" @on-confirm="removeLog(log.id)">
                                                <el-button
                                                    class="el-button--icon el-button--soft"
                                                    size="mini"
                                                    type="danger"
                                                    icon="el-icon-delete"
                                                />
                                            </remove>
                                        </span>
                                    </div>
                                    <div class="entry_submission_log_des" v-html="log.description"></div>
                                </div>
                            </template>
                            <p class="fs-17" v-else>{{$t('Sorry, No Logs found!')}}</p>
                        </div>
                    </el-skeleton>
                </div>
            </card-body>
        </card>
    </div>
</template>
<script type="text/babel">
    import remove from "@/admin/components/confirmRemove";
    import Card from '@/admin/components/Card/Card.vue';
    import CardBody from '@/admin/components/Card/CardBody.vue';
    import CardHead from '@/admin/components/Card/CardHead.vue';
    import CardHeadGroup from '@/admin/components/Card/CardHeadGroup.vue';

    export default {
        name: 'submission_logs',
        props: ['entry_id'],
        components:{
          remove,
                Card,
                CardHead,
                CardBody,
                CardHeadGroup,
        },
        data() {
            return {
                logs: [],
                loading: false,
                log_type: 'logs'
            }
        },
        watch: {
            entry_id() {
                this.fetchLogs();
            },
            log_type() {
                this.fetchLogs();
            }
        },
        methods: {
            fetchLogs() {
                this.loading = true;

                const url = FluentFormsGlobal.$rest.route('getSubmissionLogs', this.entry_id);

                FluentFormsGlobal.$rest.get(url, {
                    source_type: 'submission_item',
                    log_type: this.log_type
                })
                    .then(logs => {
                        this.logs = logs;
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },

            removeLog(logId) {
                const url = FluentFormsGlobal.$rest.route('deleteSubmissionLogs', this.entry_id);

                let data = {
                    log_ids: [logId],
                    log_type: this.log_type
                };

                FluentFormsGlobal.$rest.delete(url, data)
                    .then(response => {
                        this.$success(response.message);
                        this.fetchLogs();
                    })
                    .catch(error => {
                        console.log(error);
                    });
            },
        },
        mounted() {
            this.fetchLogs();
        }
    }
</script>
