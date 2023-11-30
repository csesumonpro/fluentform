<template>
    <div class="chained-select-settings">
        <el-form-item>
            <div slot="label">
                {{ listItem.label }}
                <el-tooltip popper-class="ff_tooltip_wrap" :content="listItem.help_text" placement="top">
                    <i class="tooltip-icon el-icon-info"></i>
                </el-tooltip>
            </div>
            <el-radio-group v-model="dataSourceType">
              <el-radio label="file">{{ $t('File Upload') }}</el-radio>
              <el-radio label="url">{{ $t('Remote URL') }}</el-radio>
            </el-radio-group>

            <div class="uploader" v-if="dataSourceType === 'file'">
                <el-upload
                    drag
                    :data="dataSourceInfo"
                    accept=".csv"
                    class="dragndrop"
                    :limit="1"
                    :multiple="false"
                    :action="uploadAction"
                    :file-list="fileList"
                    :on-success="onSuccess"
                    :on-remove="onRemove"
                >
                    <i class="el-icon-upload"></i>
                    <div class="el-upload__text">{{ $t('Drop file here or') }} <em>{{ $t(' click to upload') }}</em></div>
                </el-upload>
            </div>

            <div v-else style="margin-top: 10px;">
                <el-input class="chain-select-upload-button" :placeholder="$t('Please input a remote csv url...')" v-model="dataSourceUrl">
                    <template slot="append">
                        <el-button
                            size="medium"
                            type="primary"
                            icon="el-icon-download"
                            @click="fetchRemoteFile"
                            :loading="fetching"
                        >{{ $t('Fetch') }}
                        </el-button>
                    </template>
                </el-input>

                <el-button
                    type="text"
                    class="pull-right btn-danger"
                    :loading="removing"
                    @click="deleteDataSource"
                >{{ $t('Clear Data Source') }}
                </el-button>
            </div>
        </el-form-item>
        <p>
            <small><a :href="sample_csv_url" target="_blank">{{ $t('Download Sample CSV') }}</a></small>
        </p>
    </div>
</template>

<script>
    export default {
        name: 'chainSelectDataSource',
        props: ['listItem', 'value'],
        data() {
            return {
                sample_csv_url: window.FluentFormApp.plugin_public_url + 'img/sample.csv',
                editItem: null,
                fetching: false,
                removing: false,
                uploadAction: `${window.ajaxurl}?action=fluentform_chained_select_file_upload`
            };
        },
        methods: {
            fetchRemoteFile() {
                if (!this.dataSourceUrl) return;

                this.fetching = true;

                FluentFormsGlobal.$post({
                    action: 'fluentform_chained_select_file_upload',
                    ...this.dataSourceInfo,
                    url: this.dataSourceUrl,
                }).then(response => {
                    if (response.data.headers) {
                        this.editItem.settings.data_source = response.data;
                    }
                }).fail(response => {
                    this.$notify.error({
                        offset: 32,
                        title: 'Error',
                        message: response.responseJSON.data.message
                    });
                }).always(() => {
                    this.fetching = false;
                });
            },
            onSuccess(response, file, fileList) {
                this.editItem.settings.data_source = response.data;
            },
            onRemove(file, fileList) {
                this.deleteDataSource();
            },
            deleteDataSource() {
                if (this.dataSourceType === 'url' && !this.dataSourceUrl) return;

                this.removing = true;

                const data = Object.assign(
                    {}, 
                    this.dataSourceInfo, 
                    {action: 'fluentform_chained_select_remove_ds'}
                )

                FluentFormsGlobal.$post(data).then(response => {
                    this.editItem.settings.data_source.url = '';
                    this.editItem.settings.data_source.name = '';
                    this.editItem.settings.data_source.meta_key = null;
                    this.editItem.settings.data_source.headers = response.data.headers;
                    this.$notify.success({
                        offset: 32,
                        title: 'Success',
                        message: response.data.message
                    });
                }).fail(response => {
                    // ...
                }).always(() => {
                    this.removing = false;
                });
            }
        },
        computed: {
            dataSourceInfo() {
                return {
                    type: this.dataSourceType,
                    meta_key: this.fieldMetaKey,
                    name: this.editItem.attributes.name,
                    form_id: window.FluentFormApp.form.id
                };
            },
            fileList() {
                let fileList = [];

                if (this.editItem.settings.data_source.name) {
                    fileList = [{
                        url: this.editItem.settings.data_source.url,
                        name: this.editItem.settings.data_source.name
                    }];
                }

                return fileList;
            },
            dataSourceType: {
                get() {
                    return this.editItem.settings.data_source.type;
                },
                set(type) {
                    this.editItem.settings.data_source.type = type;
                }
            },
            dataSourceUrl: {
                get() {
                    return this.editItem.settings.data_source.url;
                },
                set(url) {
                    this.editItem.settings.data_source.url = url;
                }
            },
            fieldMetaKey: {
                get() {
                    return `chained_select_${this.editItem.attributes.name}`;
                },
                set(val) {
                    // ...
                }
            }
        },
        created() {
            this.editItem = this.$attrs.editItem;
        }
    };
</script>
