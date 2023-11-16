<?php

namespace FluentForm\App\Services\Form;

use FluentForm\App\Services\ConditionAssesor;
use FluentForm\Framework\Support\Arr;

class FormValidationParser
{
    private $form;

    private $userInput = [];

    private $flattedInputs = [];

    private $formFields = [];

    public function __construct($form, $userInput = [], $formFields = [])
    {
        $this->form = $form;
        $this->userInput = $userInput;
        $this->flattedInputs = $this->flattenArrayForHtmlNameArr($userInput);

        if (!$formFields) {
            $this->formFields = json_decode($form->form_fields, true);
        }

    }

    public function getRequiredFields()
    {
        $fields = Arr::get($this->formFields, 'fields', []);

        $requiredRules = $this->extractRequiredFields($fields);

        dd($requiredRules);
    }


    private function extractRequiredFields($formFields, $parentName = '')
    {
        $requiredFields = [];

        foreach ($formFields as $field) {
            // Concatenate parent name with current field name
            $currentFieldName = $parentName;
            if (isset($field['attributes']['name'])) {
                $currentFieldName .= ($parentName ? '.' : '') . $field['attributes']['name'];
            }

            // Check for nested fields within the 'fields' key
            if (isset($field['fields']) && is_array($field['fields'])) {
                $nestedFields = $this->extractRequiredFields($field['fields'], $currentFieldName);
                $requiredFields = array_merge($requiredFields, $nestedFields);
            } // Check for nested fields within the 'columns' key
            elseif (isset($field['columns']) && is_array($field['columns'])) {

                // we have to check if the field has conditional logics

                $field['conditionals'] = Arr::get($field, 'settings.conditional_logics', []);
                $matched = ConditionAssesor::evaluate($field, $this->flattedInputs);
                if (!$matched) {
                    continue;
                }

                foreach ($field['columns'] as $column) {
                    if (isset($column['fields']) && is_array($column['fields'])) {
                        $columnFields = $this->extractRequiredFields($column['fields'], $currentFieldName);
                        $requiredFields = array_merge($requiredFields, $columnFields);
                    }
                }
            } else {
                if (!empty($currentFieldName)) {
                    $isRequired = false;

                    // Check if the field is required
                    if (isset($field['settings']['validation_rules']['required'])) {
                        $field['conditionals'] = Arr::get($field, 'settings.conditional_logics', []);
                        $matched = ConditionAssesor::evaluate($field, $this->flattedInputs);
                        if (!$matched) {
                            continue;
                        }
                        $isRequired = (bool)$field['settings']['validation_rules']['required']['value'];
                    }

                    $requiredFields[$currentFieldName] = $isRequired;
                }
            }
        }

        return $requiredFields;
    }

    private function flattenArrayForHtmlNameArr($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            // Create the new key format
            $new_key = empty($prefix) ? $key : "{$prefix}[{$key}]";

            if (is_array($value)) {
                // Recursively call the function for nested arrays
                $result = array_merge($result, $this->flattenArrayForHtmlNameArr($value, $new_key));
            } else {
                // Assign the value to the new key
                $result[$new_key] = $value;
            }
        }
        return $result;
    }
}
