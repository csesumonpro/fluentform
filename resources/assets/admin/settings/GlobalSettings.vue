<template>
    <div class="ff_global_setting_options_wrap">
        <el-skeleton :loading="loading" animated :rows="10" :class="loading ? 'ff_card' : ''">
            <!--Different form settings section-->
            <template v-if="app_ready">
                <layout
                    :email_report="email_report"
                    :integration_failure_notification="integration_failure_notification"
                    :data="formSettings"
                    :default_message_setting_fields="default_message_setting_fields"
                    :file_upload_optoins="file_upload_optoins"
                    :captcha_status="captcha_status"
                />
            </template>

            <!--Save settings-->
            <div class="mt-4">
                <el-button type="primary" icon="el-icon-success" @click="save">
                    {{ $t('Save Settings') }}
                </el-button>
            </div>
        </el-skeleton>
    </div>
</template>

<script type="text/babel">
    import Layout from '../components/settings/FormSettings/Layout.vue';

    export default {
        name: "GlobalSettings",
        components: {
            Layout
        },
        data() {
            return {
                loading: true,
                app_ready: false,
                formSettings: {
                    layout: {
                        labelPlacement: 'top',
                        helpMessagePlacement: 'with_label',
                        errorMessagePlacement: 'inline'
                    },
                    misc: {
                        isIpLogingDisabled: false,
                        file_upload_locations: '',
                    },
	                default_messages : {}
                },
	            default_message_setting_fields: {},
                email_report: {},
                integration_failure_notification: {},
                file_upload_optoins: [],
                captcha_status: {
                    'hcaptcha': false,
                    'recaptcha': false,
                    'turnstile': false
                },
            }
        },
        methods: {
            fetch() {
                this.loading = true;
                const url = FluentFormsGlobal.$rest.route('getGlobalSettings');

                FluentFormsGlobal.$rest.get(url, {
                    key: [
                        '_fluentform_global_form_settings',
                        '_fluentform_email_report_summary',
                        '_fluentform_failed_integration_notification',
                        '_fluentform_reCaptcha_keys_status',
                        '_fluentform_hCaptcha_keys_status',
	                    '_fluentform_global_default_message_setting_fields',
                        '_fluentform_turnstile_keys_status'
                    ]
                })
                    .then(response => {
                        this.loading = false;
                        let settings = response._fluentform_global_form_settings || {};
                        if (!settings.layout) {
                            settings.layout = {
                                labelPlacement: 'top',
                                helpMessagePlacement: 'with_label',
                                errorMessagePlacement: 'inline'
                            };
                        }
	                    this.default_message_setting_fields = response._fluentform_global_default_message_setting_fields || {};
                        this.formSettings = Object.assign(this.formSettings, settings);
                        let emailReport = response._fluentform_email_report_summary;
                        if (!emailReport) {
                            emailReport = {
                                status: 'yes',
                                send_to_type: 'admin_email',
                                custom_recipients: '',
                                sending_day: 'Mon'
                            };
                        }
                        this.email_report = emailReport;
                        let failedNotification = response._fluentform_failed_integration_notification;
                        if (!failedNotification) {
                            failedNotification = {
                                status: 'no',
                                send_to_type: 'admin_email',
                                custom_recipients: '',
                            };
                        }
                        this.integration_failure_notification = failedNotification;
                        this.file_upload_optoins = response.file_upload_optoins;
                        this.captcha_status = {
                            hcaptcha: response._fluentform_hCaptcha_keys_status,
                            recaptcha: response._fluentform_reCaptcha_keys_status,
                            turnstile: response._fluentform_turnstile_keys_status
                        }
                    })
                    .catch(e => {
                        this.$fail(e.message);
                    })
                    .finally(() => {
                        this.loading = false;
                        this.app_ready = true;
                    });
            },

            save() {
                this.loading = true;
                const url = FluentFormsGlobal.$rest.route('storeGlobalSettings');

                let data = {
                    key: ['SaveGlobalLayoutSettings', 'EmailSummarySettings'],
                    form_settings: JSON.stringify(this.formSettings),
                    email_report: JSON.stringify(this.email_report),
                    integration_failure_notification: JSON.stringify(this.integration_failure_notification)
                };

                FluentFormsGlobal.$rest.post(url, data)
                    .then(response => {
                        if (response) {
                            this.loading = false;
                            response.map(res => {
                                if (res?.message){
                                    this.$success(res.message)
                                    return;
                                }
                            });
                        }
                    })
                    .catch(e => {
                        this.$fail(this.$t('Something Went Wrong Please Try Again!'))
                    })
                    .finally(() => {
                        this.loading = false;
                    });
                this.saveFailedIntegrationNotification();
            },
            saveFailedIntegrationNotification() {
                const url = FluentFormsGlobal.$rest.route('storeGlobalSettings');

                let data = {
                    key: 'failedIntegrationNotification',
                    value: JSON.stringify(this.integration_failure_notification)
                };

                FluentFormsGlobal.$rest.post(url, data)
                    .then(response => {
                    })
                    .catch(e => {
                    });
            }
        },
        mounted() {
            this.fetch();
        }
    };
</script>
