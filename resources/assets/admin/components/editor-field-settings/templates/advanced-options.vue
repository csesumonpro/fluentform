<template>
    <el-form-item>
        <div class="clearfix">
            <div class="pull-right top-check-action">
                <el-checkbox v-model="valuesVisible">{{ $t('Show Values') }}</el-checkbox>
                <el-checkbox v-if="hasCalValue" v-model="editItem.settings.calc_value_status">{{ $t('Calc Values') }}</el-checkbox>
                <template v-if="has_pro">
                    <el-checkbox v-if="hasImageSupport" v-model="editItem.settings.enable_image_input">{{ $t('Photo') }}</el-checkbox>
                </template>
                <template v-else-if="hasImageSupport">
                    <el-checkbox v-model="pro_mock" @change="showProMessage()">{{ $t('Photo') }}</el-checkbox>
                </template>
            </div>
            <elLabel slot="label" :label="listItem.label" :helpText="listItem.help_text"></elLabel>
        </div>

        <vddl-list :drop="handleDrop" v-if="optionsToRender.length" class="vddl-list__handle ff_advnced_options_wrap" :list="editItem.settings.advanced_options" :horizontal="false">
            <vddl-draggable :moved="handleMoved" class="optionsToRender" v-for="(option, index) in editItem.settings.advanced_options" :key="option.id"
                            :draggable="option"
                            :index="index"
                            :wrapper="editItem.settings.advanced_options"
                            effect-allowed="move">
                <vddl-nodrag class="nodrag">
                    <div class="checkbox">
                        <input ref="defaultOptions"
                               class="form-control"
                               :type="optionsType"
                               name="fluentform__default-option"
                               :value="option.value"
                               :checked="isChecked(option.value)"
                               @change="updateDefaultOption(option)"
                        >
                    </div>

                    <vddl-handle
                            :handle-left="20"
                            :handle-top="20"
                            class="handle">
                    </vddl-handle>

                    <div
                            style="max-width: 64px; max-height: 32px; overflow: hidden;"
                            v-if="editItem.settings.enable_image_input && hasImageSupport"
                    >
                        <photo-widget enable_clear="yes" v-model="option.image" :for_advanced_option="true" />
                    </div>

                    <div>
                        <el-input
                                :placeholder="$t('label')"
                                v-model="option.label"
                                @input="updateValue(option)"
                        ></el-input>
                    </div>

                    <div v-if="valuesVisible">
                        <el-input :placeholder="$t('value')" v-model="option.value"></el-input>
                    </div>

                    <div v-if="editItem.settings.calc_value_status">
                        <el-input
                                :placeholder="$t('calc value')"
                                type="number"
                                step="any"
                                v-model="option.calc_value"
                        ></el-input>
                    </div>

                    <action-btn>
                        <action-btn-add @click="increase(index)" size="mini"></action-btn-add>
                        <action-btn-remove @click="decrease(index)" size="mini"></action-btn-remove>
                    </action-btn>
                </vddl-nodrag>
            </vddl-draggable>
        </vddl-list>

        <el-button
                type="warning"
                size="mini"
                :disabled="!editItem.attributes.value"
                @click.prevent="clear"
        >{{ $t('Clear Selection') }}</el-button>

        <el-button
                size="mini"
                @click="initBulkEdit()"
                v-if="!editItem.settings.calc_value_status && !editItem.settings.enable_image_input"
        >{{ $t('Bulk Edit / Predefined Data Sets') }} </el-button>

        <el-dialog
                :append-to-body="true"
                class="ff_backdrop"
                :visible.sync="bulkEditVisible"
                width="60%"
        >
            <div slot="title">
                <h4 class="mb-2">{{$t('Edit your options')}}</h4>
                <p>{{ $t('Please provide the value as LABEL:VALUE as each line or select from predefined data sets') }}</p>
            </div>
            <div v-if="bulkEditVisible" class="bulk_editor_wrapper mt-4">
                <el-row :gutter="20">
                    <el-col :span="24">
                        <ul class="ff_bulk_option_groups mb-3">
                            <li
                                    @click="setOptions(options)"
                                    v-for="(options, optionGroup) in editor_options"
                                    :key="optionGroup"
                                    :class="{ 'active': options === activeClass}"
                            >{{optionGroup}}</li>
                        </ul>
                    </el-col>
                    <el-col :span="24">
                        <el-input type="textarea" :rows="5" v-model="value_key_pair_text"></el-input>
                        <p class="mt-2">{{ $t('You can simply give value only the system will convert the label as value') }}</p>
                    </el-col>
                </el-row>
            </div>
            <div slot="footer" class="dialog-footer text-left mt-4">
                <el-button type="primary" @click="confirmBulkEdit()">{{ $t('Yes, Confirm!') }}</el-button>
                <el-button @click="bulkEditVisible = false" type="info" class="el-button--soft">{{ $t('Cancel') }}</el-button>
            </div>
        </el-dialog>

    </el-form-item>
</template>

<script type="text/babel">
    import elLabel from '../../includes/el-label.vue';
    import each from 'lodash/each';
    import PhotoWidget from '@/common/PhotoUploader';
    import ActionBtn from '@/admin/components/ActionBtn/ActionBtn.vue';
    import ActionBtnAdd from '@/admin/components/ActionBtn/ActionBtnAdd.vue';
    import ActionBtnRemove from '@/admin/components/ActionBtn/ActionBtnRemove.vue';

    export default {
        name: 'advanced-options',
        props: {
            editItem: {
                type: Object
            },
            listItem: {
                type: Object
            },
            hasCalValue: {
                default() {
                    return true;
                }
            }
        },
        components: {
            elLabel,
            PhotoWidget,
            ActionBtn,
            ActionBtnAdd,
            ActionBtnRemove
        },
        data() {
            return {
                optionsToRender: [],
                bulkEditVisible: false,
                value_key_pair_text: '',
                has_pro: !!window.FluentFormApp.hasPro,
                pro_mock: false,
                editor_options: JSON.parse(window.FluentFormApp.bulk_options_json),
                activeClass: null,
            }
        },
        computed: {
            optionsType() {
                let determiner = this.editItem.attributes.type || (this.editItem.attributes.multiple && 'multiselect') || this.editItem.element;

                switch (determiner) {
                    case 'multiselect':
                    case 'checkbox':
                        return 'checkbox'
                        break;
                    case 'select':
                    case 'radio':
                        return 'radio'
                        break;
                    default:
                        return 'radio'
                }
            },
            hasImageSupport() {
                return this.editItem.element != 'select';
            },
            valuesVisible:{
                get() {
                    return this.editItem.settings.values_visible||false;
                },
                set(val) {
                    this.$set(this.editItem.settings, 'values_visible', val);
                }
            },
        },
        methods: {
            handleDrop(data) {
                const { index, list, item } = data;
                item.id = new Date().getTime();
                list.splice(index, 0, item);
            },
            handleMoved(item) {
                const { index, list } = item;
                list.splice(index, 1);
            },

            updateValue(currentOption) {
                if(!this.valuesVisible) {
                    currentOption.value = event.target.value;
                }
            },

            initBulkEdit() {
                let astext = '';
                each(this.editItem.settings.advanced_options, (item) => {
                    astext += item.label;
                    if (item.label && item.label != item.value) {
                        astext += ':' + item.value;
                    }
                    astext += String.fromCharCode(13, 10);
                });
                this.value_key_pair_text = astext;
                this.bulkEditVisible = true
            },

            confirmBulkEdit() {
                let lines = this.value_key_pair_text.split('\n');
                let values = [];
                each(lines, (line) => {
                    let lineItem = line.split(':');
                    let label = lineItem[0];
                    let value = lineItem[1];
                    if (!value) {
                        value = label;
                    }
                    if (label && value) {
                        values.push({
                            label: label,
                            value: value
                        });
                    }
                });

                this.editItem.settings.advanced_options = values;
                this.bulkEditVisible = false
            },

            isChecked(optVal) {
                if (typeof this.editItem.attributes.value != 'number') {
                    return this.editItem.attributes.value.includes(optVal);
                }
            },

            increase(index) {
                let options = this.editItem.settings.advanced_options;

                let keys = options.map(opt => {
                    let value = opt.value;
                    value = value.toString();
                    let nums = value.match(/\d+/g);
                    return nums && Number(nums.pop());
                });
                let key = Math.max(...keys.filter(i => i != 'undefined')) + 1;
                let optionStr = `Item ${key}`;
                let optionKey = optionStr.toLowerCase().replace(/\s/g, '_');

                let newOpt = {
                    label: optionStr,
                    value: optionKey,
                    calc_value: '',
                    image: ''
                };

                options.splice(index + 1, 0, newOpt);
            },

            decrease(index) {
                let options = this.editItem.settings.advanced_options;
                if (options.length > 1) {
                    options.splice(index, 1);
                } else {
                    this.$notify.error({
                        message: 'You have to have at least one option.',
                        offset: 30
                    });
                }
            },

            setOptions(options) {
                this.value_key_pair_text = options.join('\n');
                this.activeClass = options;
            },

            clear() {
                let attributes = this.editItem.attributes;

                if (attributes.type == 'checkbox' || attributes.multiple) {
                    attributes.value = [];
                } else {
                    attributes.value = '';
                }
                this.$refs.defaultOptions.map(el => el.checked = false);
            },

            updateDefaultOption(option) {
                let attributes = this.editItem.attributes;
                if (attributes.type == 'checkbox' || attributes.multiple) {
                    if (event.target.checked) {
                        attributes.value.push(option.value);
                    } else {
                        attributes.value.splice(attributes.value.indexOf(option.value), 1);
                    }
                } else {
                    if (event.target.checked) {
                        attributes.value = option.value;
                    } else {
                        attributes.value = '';
                    }
                }
            },

            createOptionsToRender() {
                this.optionsToRender = this.editItem.settings.advanced_options;
            },
            showProMessage() {
                this.$notify.error('Image type fields only available on pro version');
                this.pro_mock = false;
            }
        },
        mounted() {
            this.createOptionsToRender();
            this.editItem.settings.advanced_options.forEach((item,i)=>{
                item.id = i;
            })
        }
    }
</script>
