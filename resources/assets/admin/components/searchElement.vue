<template>
    <div class="search-element">
        <div class="ff-input-wrap">
            <span class="el-icon el-icon-search"></span>
            <el-input :class="[searchElementStr.length > 0 ? 'active' : '']" v-model="searchElementStr" type="text" :placeholder="placeholder" />
        </div>

        <div class="search-element-result" v-show="searchResult.length" style="margin-top: 15px;">
            <div v-for="(itemMockList, i) in searchResult" :key="i" class="v-row mb15">
                <div class="v-col--50" v-for="(itemMock, i) in itemMockList" :key="i">
                    <vddl-draggable
                        class="btn-element"
                        :draggable="itemMock"
                        :selected="insertItemOnClick"
                        :index="i"
                        :wrapper="itemMockList"
                        :disable-if="isDisabled(itemMock)"
                        :moved="moved"
                        effectAllowed="copy">
                        <i :class="itemMock.editor_options.icon_class"></i>
                        <span>{{ itemMock.editor_options.title }}</span>
                    </vddl-draggable>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'search-element',
    props: {
        list: {
            type: Array,
            required: true
        },
        insertItemOnClick: {
            type: Function,
            required: true
        },
        isDisabled: {
            type: Function,
            required: true
        },
        moved: {
            type: Function,
            required: true
        },
        placeholder: {
            type: String
        },
        isSidebarSearch: Boolean
    },
    data() {
        return {
            searchElementStr: '',
            searchResult: [],
            tags: window.FluentFormApp.element_search_tags
        }
    },
    watch: {
        searchElementStr() {
            const searchElementStr = this.searchElementStr.trim().toLowerCase();
            const tags = this.tags;
            let searchResult = [];

            if (searchElementStr) {
                searchResult = this.list.filter((item) => {
                    if (tags[item.element]) {
                        let search = this.makeSearchString(item);
                        search += tags[item.element].toString();
                        return search.toLowerCase().includes(searchElementStr);
                    }
					return false;
                });
                this.$emit('update:isSidebarSearch', true);
            } else {
                this.$emit('update:isSidebarSearch', false);
            }
            this.searchResult = _ff.chunk( searchResult, 2 );
        }
    },
    methods: {
        makeSearchString(field) {
            let searchStr = '';
            const { name, type } = field.attributes || {};
            if (name) searchStr += name;
            if (type) searchStr += type;
            if (field.element) searchStr += field.element;
            if (field.settings?.label) searchStr += field.settings.label;
            if (field.editor_options?.title) searchStr += field.editor_options.title;

            if (field.fields && typeof field.fields === 'object') {
                for (const item in field.fields) {
                    searchStr += this.makeSearchString(field.fields[item]);
                }
            }
            return searchStr.toString();
        }
    }
}
</script>


