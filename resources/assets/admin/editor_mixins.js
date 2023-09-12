import notifier from './notifier';

export default {
    methods: {
        /**
         * Translate a string
         * @param {String}
         * @return {String}
         */
        $t(str) {
            let transString = window.FluentFormApp.form_editor_str[str];
            if(transString) {
                return transString;
            }
            return str;
        },

        /**
         * Toggle vertically among editor sidebar sections
         * @param section
         */
        toggleFieldsSection(section) {
            if (this.optionFieldsSection == section) {
                this.optionFieldsSection = '';
            } else {
                this.optionFieldsSection = section;
            }
        },

        /**
         * Find appropriate vue component dynamically
         * it looks form `template` property in the item object
         * @param item
         * @return {string}
         */
        guessElTemplate(item) {
            let template = 'ff_';
            template += item.template || item.editor_options.template;
            return template;
        },

        // recursive for map through elements
        /**
         * Recursively map through elements
         * triggers a callback function
         * @param allElements
         * @param callback
         */
        mapElements(allElements, callback) {
            _ff.map(allElements, (existingItem) => {
                if (existingItem.element != 'container') {
                    callback(existingItem);
                }
                if (existingItem.element == 'container') {
                    _ff.map(existingItem.columns, (column) => {
                        this.mapElements(column.fields, callback);
                    });
                }
            });
        },

        /**
         * generates an unique key and assign it to element
         * @param item
         */
        uniqElKey(item) {
            item.uniqElKey = 'el_' + Date.now() + Math.random();
        },

        /**
         * Helper method of `makeUniqueNameAttr`
         * @param existingAttrNames {Array}
         * @param field
         * @return {string} new unique name
         */
        getUniqueNameAttr(existingAttrNames, field) {
            if(!field.attributes.name) {
                return '';
            }
            let nameWithSuffix = field.attributes.name.match(/([0-9a-zA-Z-_]+)(?:_(\d+))/);

            if (existingAttrNames.includes(field.attributes.name)) {
                let baseName = nameWithSuffix ? nameWithSuffix[1] : field.attributes.name;
                let siblingsOfNew = existingAttrNames.filter((name) => {
                    if (name.includes(baseName)) {
                        return true;
                    }
                }).sort(function (a, b) {
                    var x = a.match(/(?!_)\d+/);
                    x = x && parseInt(x[0]);

                    var y = b.match(/(?!_)\d+/);
                    y = y && parseInt(y[0]);

                    return y - x;
                });

                let suffix = siblingsOfNew[0].match(/(?!_)\d+/);

                if (suffix && parseInt(suffix[0])) {
                    return siblingsOfNew[0].replace(/(?!_)\d+/, parseInt(suffix[0]) + 1);
                } else {
                    return field.attributes.name + '_1';
                }
            }
            return field.attributes.name;
        },

        /**
         * helper method
         * which helps `insertItemOnClick/handleDrop` to perform its work
         * @param allElements
         * @param newItem
         */
        makeUniqueNameAttr(allElements, newItem) {
            // generate unique key for each element
            this.uniqElKey(newItem);

            if (newItem.attributes.name || newItem.element == 'container') {
                let existingAttrNames = [];

                this.mapElements(allElements, (existingItem) => {
                    if(existingItem.attributes.name) {
                        existingAttrNames.push(existingItem.attributes.name);
                    }
                });

                if (newItem.element == 'container') {
                    _ff.map(newItem.columns, (column) => {
                        _ff.map(column.fields, (field) => {
                            let name = this.getUniqueNameAttr(existingAttrNames, field);
                            this.uniqElKey(field);
                            field.attributes.name = name;
                            existingAttrNames.push(name);
                        });
                    });
                } else {
                    let name = this.getUniqueNameAttr(existingAttrNames, newItem);
                    console.log(name);
                    newItem.attributes.name = name;
                }
            }
        },

        ...notifier
    },
    filters: {
        ucFirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
        _startCase(string) {
            return _ff.startCase(string);
        }
    },
    data() {
        return {
            is_conversion_form: !!window.FluentFormApp.is_conversion_form
        }
    }
};
