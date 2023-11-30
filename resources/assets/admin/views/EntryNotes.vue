<template>
    <card class="entry_info_box entry_submission_activity">
        <card-head>
            <card-head-group class="justify-between">
                <div class="entry_info_box_title">
                    {{$t('Submission Notes')}}
                </div>
                <div class="entry_info_box_actions">
                    <el-button @click="add_note_box = !add_note_box" size="medium" type="primary" icon="el-icon-plus" class="el-button--soft">
                        {{ $t('Add Note') }}
                    </el-button>
                </div>
            </card-head-group>
        </card-head>
        <card-body>
            <div class="entry_info_body">
                <el-skeleton :loading="loading" animated :rows="4">
                    <div class="wpf_entry_details">
                        <div v-if="add_note_box" class="wpf_add_note_box">
                            <el-input
                                type="textarea"
                                :autosize="{ minRows: 3}"
                                :placeholder="$t('Please Provide Note Content')"
                                v-model="new_note.content">
                            </el-input>
                            <el-button :loading="isAddingNote" @click="addNewNote()" size="medium" type="info">
                                {{ $t('Submit Note') }}
                            </el-button>
                        </div>
                        <template v-if="notes && notes.length">
                            <div
                                v-for="activity in showingNotes"
                                :key="activity.id"
                                class="wpf_each_entry"
                            >
                                <div class="wpf_entry_label">
                                    {{activity.name}} - {{ activity.created_at }} <span v-show="api_log == 'yes'" class="ff_tag">{{activity.meta_key}}</span>
                                </div>
                                <div class="wpf_entry_value" v-html="activity.value"></div>
                            </div>

                            <el-button
                                class="mt-3 el-button--text-light"
                                v-if="notes.length > 5"
                                type="text"
                                @click="initial_limit =  !initial_limit"
                            >
                                <span v-if="initial_limit">{{ $t('Show More') }} <i class="el-icon-arrow-down"></i></span>
                                <span v-else>{{ $t('Show Less')}} <i class="el-icon-arrow-up"></i></span>
                            </el-button>

                        </template>
                        <p v-else class="fs-17"> {{$t('Sorry, No Notes found!')}}</p>
                    </div>
                </el-skeleton>
            </div>
        </card-body>
    </card>
</template>

<script type="text/babel">
    import chunk from 'lodash/chunk';
    import Card from '@/admin/components/Card/Card.vue';
    import CardHead from '@/admin/components/Card/CardHead.vue';
    import CardBody from '@/admin/components/Card/CardBody.vue';
    import CardHeadGroup from '@/admin/components/Card/CardHeadGroup.vue';

    export default {
        name: 'response_notes',
        props: ['entry_id', 'form_id'],
        components: {
            Card,
            CardHead,
            CardBody,
            CardHeadGroup
        },
        data() {
            return {
                doingAjax: false,
                notes: [],
                isAddingNote: false,
                add_note_box: false,
                initial_limit: true,
                api_log: 'yes',
                loading: false,
                new_note: {
                    content: '',
                    email_id: '',
                    email_subject: '',
                    status: 'info'
                }
            }
        },
        watch: {
            entry_id() {
                this.fetchNotes();
            },
            api_log() {
                this.fetchNotes();
            }
        },
        computed: {
            showingNotes() {
                if(this.initial_limit) {
                    return chunk(this.notes, 5)[0];
                }
                return this.notes;
            }
        },
        methods: {
            fetchNotes() {
                this.loading = true;

                const url = FluentFormsGlobal.$rest.route('getSubmissionNotes', this.entry_id);

                FluentFormsGlobal.$rest.get(url, {
                    api_log: this.api_log
                })
                    .then(notes => {
                        this.notes = notes;
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            addNewNote() {
                this.isAddingNote = true;

                const url = FluentFormsGlobal.$rest.route('storeSubmissionNote', this.entry_id);

                let data = {
                    note: this.new_note,
                    form_id: this.form_id
                };

                FluentFormsGlobal.$rest.post(url, data)
                    .then(response => {
                        this.$success(response.message);

                        this.notes.unshift(response.note);

                        this.new_note = {
                            content: '',
                            email_id: '',
                            email_subject: '',
                            status: 'info'
                        };
                    })
                    .catch(error => {

                    })
                    .finally(() =>{
                        this.isAddingNote = false;
                    })
            }
        },
        mounted(){
            this.fetchNotes();
        }
    }
</script>

