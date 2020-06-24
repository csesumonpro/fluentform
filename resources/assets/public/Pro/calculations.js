export default function ($, $theForm) {
    var calculationFields = $theForm.find('.ff_has_formula');

    if (!calculationFields.length) {
        return;
    }

    let repeaterTriggerCache = {};

    mexp.addToken([
        {
            type: 8,
            token: "round",
            show: "round",
            value: function (value, decimals) {
                if (!decimals && decimals !== 0) {
                    decimals = 2;
                }
                value = parseFloat(value).toFixed(decimals);
                return parseFloat(value);
            }
        },
        {
            type: 8,
            token: "max",
            show: "max",
            value: function (a, b) {
                if (a > b)
                    return a;
                return b;
            }
        }
    ]);

    // polyfill for matchAll
    function findAll(regexPattern, sourceString) {
        let output = []
        let match
        // make sure the pattern has the global flag
        let regexPatternWithGlobal = RegExp(regexPattern, "g")
        while (match = regexPatternWithGlobal.exec(sourceString)) {
            // get rid of the string copy
            delete match.input
            // store the match data
            output.push(match)
        }
        return output
    }

    var doCalculation = function () {
        jQuery.each(calculationFields, (index, field) => {
            var $field = jQuery(field);
            var formula = $field.data('calculation_formula');
            let regEx = /{(.*?)}/g;
            // let matches = [...formula.matchAll(regEx)];
            let matches = findAll(regEx, formula);
            let replaces = {};

            jQuery.each(matches, (index, match) => {
                let itemKey = match[0];
                if (itemKey.indexOf('{input.') != -1) {
                    let inputName = itemKey.replace(/{input.|}/g, '');
                    replaces[itemKey] = $theForm.find('input[name=' + inputName + ']').val() || 0;
                } else if (itemKey.indexOf('{select.') != -1) { // select Field
                    let inputName = itemKey.replace(/{select.|}/g, '');
                    let itemValue = getDataCalcValue('select[data-name=' + inputName + '] option:selected');
                    $theForm.find('select[data-name=' + inputName + ']').attr('data-calc_value', itemValue);
                    replaces[itemKey] = itemValue;
                } else if (itemKey.indexOf('{checkbox.') != -1) { // checkboxes Field
                    let inputName = itemKey.replace(/{checkbox.|}/g, '');
                    replaces[itemKey] = getDataCalcValue('input[data-name=' + inputName + ']:checked');
                } else if (itemKey.indexOf('{radio.') != -1) { // Radio Fields
                    let inputName = itemKey.replace(/{radio.|}/g, '');
                    replaces[itemKey] = $theForm.find('input[name=' + inputName + ']:checked').attr('data-calc_value') || 0;
                } else if (itemKey.indexOf('{repeat.') != -1) { // Radio Fields
                    let tableName = itemKey.replace(/{repeat.|}/g, '');
                    let $targetTable = $theForm.find('table[data-root_name=' + tableName + ']');

                    if (!repeaterTriggerCache[tableName]) {
                        repeaterTriggerCache[tableName] = true;
                        $targetTable.on('repeat_change', () => {
                            doCalculation();
                        });
                    }

                    replaces[itemKey] = $targetTable.find('tbody tr').length;
                }
            });

            jQuery.each(replaces, (key, value) => {
                if (!value) {
                    value = 0;
                }
                formula = formula.split(key).join(value);
            });
            let calculatedValue = '';
            try {
                calculatedValue = mexp.eval(formula);
                if (isNaN(calculatedValue)) {
                    calculatedValue = '';
                }

                if(typeof formula == 'string' && formula.indexOf('round') === 0) {
                    let decimal = parseInt(formula.substr(-2, 1));
                    if(decimal && Number.isInteger(decimal)) {
                        calculatedValue = parseFloat(calculatedValue).toFixed(2);
                    }
                }

            } catch (error) {
                console.log(error);
            }

            if ($field[0].type == 'text') {
                $($field).val(calculatedValue)
                    .prop('defaultValue', calculatedValue)
                    .trigger('change');
            } else {
                $field.text(calculatedValue);
            }
        });
    };

    function getDataCalcValue(selector) {
        let itemValue = 0;
        let selectedItems = $theForm.find(selector);
        $.each(selectedItems, (indexItem, item) => {
            let eachItemValue = $(item).attr('data-calc_value');
            if (eachItemValue && !isNaN(eachItemValue)) {
                itemValue += Number(eachItemValue);
            }
        });
        return itemValue;
    }

    /**
     * Init Calculation input number fild
     */
    var initNumberCalculations = function () {
        $theForm.find(
            'input[type=number],input[data-calc_value],select[data-calc_value]'
        ).on('change keyup', doCalculation).trigger('change');
        doCalculation();
    };

    initNumberCalculations();
}