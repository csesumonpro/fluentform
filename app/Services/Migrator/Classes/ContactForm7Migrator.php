<?php

namespace FluentForm\App\Services\Migrator\Classes;

use FluentForm\App\Modules\Form\Form;

use FluentForm\Framework\Helpers\ArrayHelper;

class ContactForm7Migrator extends BaseMigrator
{
    public function __construct()
    {
        $this->key = 'contactform7';
        $this->title = 'Contact Form 7';
        $this->shortcode = 'contact_form_7';
    }

    public function exist()
    {
        return !!defined('WPCF7_PLUGIN');
    }

    protected function getForms()
    {
        $forms = [];
        $postItems = get_posts(['post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1]);
        foreach ($postItems as $form) {
            $forms[] = [
                'ID'   => $form->ID,
                'name' => $form->post_title,
            ];
        }
        return $forms;
    }

    public function getFields($form)
    {
        $formPostMeta = get_post_meta($form['ID'], '_form', true);
        $formMetaDataArray = preg_split('/\r\n|\r|\n/', $formPostMeta);
        $submitBtn = [];
        $formattedArray = [];
        $fluentFields = [];

        foreach ($formMetaDataArray as $formMetaString) {
            if (!empty($formMetaString)) {
                if (strpos($formMetaString, '<label>') !== false || strpos($formMetaString, '</label>') !== false) {
                    $formMetaString = trim(str_replace(['<label>', '</label>'], '', $formMetaString));
                }
                $formattedArray[] = $formMetaString;
            }
        }

        foreach ($formattedArray as $formattedKey => $formattedValue) {
            preg_match_all('/\[[^\]]*\]/', $formattedValue, $fieldStringArray);
            $fieldStringArray = isset($fieldStringArray[0]) ?? $fieldStringArray[0];

            if (count($fieldStringArray) > 1) {
                $desiredKey = 0;
                foreach ($fieldStringArray as $stringKey => $fieldString) {
                    $desiredKey = $formattedKey + $stringKey;

                    if (preg_match('/\[.*?\].*?\[.*?\]/', $fieldString, $matches)) {
                        $fieldString = isset($matches[0]) ?? $matches[0];
                    }
                    array_splice($formattedArray, $desiredKey, 0, $fieldString);
                }
                unset($formattedArray[$desiredKey + 1]);
            }
        }

        $superFormatted = [];
        foreach ($formattedArray as $key => &$formattedString) {
            $fieldStringArray = [];

            if ($formattedString['0'] !== '[' && $formattedString[strlen($formattedString) - 1] !== ']') {
                if (
                    isset($formattedArray[$key + 1]) &&
                    $formattedArray[$key + 1]['0'] === '[' &&
                    $formattedArray[$key + 1][strlen($formattedArray[$key + 1]) - 1] === ']'
                ) {
                    $superFormatted[] = $formattedString . $formattedArray[$key + 1];
                    unset($formattedArray[$key + 1]);
                }
            } else {
                $superFormatted[] = $formattedString;
                unset($formattedArray[$key]);
            }
        }

        $fields = [];
        foreach ($superFormatted as $superKey => &$superValue) {
            $fieldLabel = '';
            $fieldElement = '';
            $fieldName = '';
            $fieldRequired = false;
            $fieldPlaceholder = '';
            $fieldValue = '';
            $fieldMinLength = '';
            $fieldMaxLength = '';
            $fieldSize = '';
            $fieldStep = '';
            $fieldMultipleValues = [];
            $fieldMin = '';
            $fieldMax = '';
            $fieldDefault = '';
            $fieldMultiple = false;
            $fieldFileTypes = '';
            $fieldMaxFileSize = '';
            $fieldMaxFileSizeUnit = 'KB';
            $withoutSquareBrackets = '';

            $fieldString = '';

            if (preg_match('/^(.*?)\[/', $superValue, $matches)) {
                $fieldLabel = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/\[([^]]+)\]/', $superValue, $matches)) {
                $fieldString = isset($matches[1]) ? $matches[1] : '';
            }

            $words = preg_split('/\s+/', $fieldString);
            $fieldRequired = isset($words[0]) && strpos($words[0], '*') !== false ?? true;
            $fieldElement = isset($words[0]) ? trim($words[0], '*') : '';
            $fieldName = $fieldElement . '-' . rand(100, 999);

            if ($fieldElement === 'quiz') {
                continue;
            }

            if (preg_match('/\[.*?\].*?\[.*?\]/', $superValue, $matches)) {
                preg_match('/\[(.*?)\](.*?)\[/', $matches[0], $withoutBracketMatches);
                if ($fieldElement === 'acceptance' || $fieldElement === 'textarea') {
                    $withoutSquareBrackets = isset($withoutBracketMatches[2]) ? $withoutBracketMatches[2] : '';
                }
            }

            if ($fieldElement === 'textarea' && preg_match('/\[.*?\].*?\[.*?\]/', $fieldString, $matches)) {
                $withoutSquareBrackets = isset($matches[0]) ? preg_replace('/\[.*?\]/', '', $fieldString) : '';
            }

            if ($fieldElement === 'submit') {
                preg_match_all('/(["\'])(.*?)\1/', $fieldString, $matches);
                $submitBtn = $this->getSubmitBtn([
                    'uniqElKey' => $fieldElement . '-' . time(),
                    'label'     => isset($matches[2][0]) ? $matches[2][0] : 'Submit'
                ]);
                continue;
            }

            if ($fieldElement === 'select' && strpos($fieldString, 'multiple') !== false) {
                $fieldMultiple = true;
            }

            if (preg_match('/min:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldMin = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/max:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldMax = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/minlength:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldMinLength = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/maxlength:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldMaxLength = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/size:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldSize = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/step:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldStep = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/(?:placeholder|watermark) "([a-zA-Z0-9]+)"/', $fieldString, $matches)) {
                $fieldPlaceholder = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/filetypes:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldFileTypes = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match_all('/(["\'])(.*?)\1/', $fieldString, $matches)) {
                error_log(print_r($matches, true));
                if (isset($matches[2])) {
                    if (count($matches[2]) > 1) {
                        $fieldMultipleValues = $matches[2];
                    } else {
                        if (isset($matches[2]) && count($matches[2]) === 1) {
                            $fieldValue = isset($matches[2][0]) ? $matches[2][0] : '';
                        }
                    }
                }
            }

            if (preg_match('/default:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldDefault = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/limit:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldMaxFileSize = isset($matches[1]) ? $matches[1] : '';

                if (strpos($fieldMaxFileSize, 'mb') !== false) {
                    $fieldMaxFileSizeUnit = 'MB';
                }

                $fieldMaxFileSize = str_replace(['mb', 'kb'], '', $fieldMaxFileSize);
            }

            if (preg_match('/autocomplete:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldValue = isset($matches[1]) ? $matches[1] : '';
            }

            if (!$fieldValue) {
                $fieldValue = $withoutSquareBrackets;
            }

            if (!$fieldLabel) {
                $fieldLabel = $fieldElement;
            }

            $fieldType = ArrayHelper::get($this->fieldTypeMap(), $fieldElement);

            $args = [
                'uniqElKey'          => $fieldElement . '-' . time(),
                'type'               => $fieldType,
                'index'              => $superKey,
                'required'           => $fieldRequired,
                'label'              => $fieldLabel,
                'name'               => $fieldName,
                'placeholder'        => $fieldPlaceholder,
                'class'              => '',
                'value'              => $fieldValue,
                'help_message'       => '',
                'container_class'    => '',
                'min'                => $fieldMin,
                'max'                => $fieldMax,
                'minlength'          => $fieldMinLength,
                'maxlength'          => $fieldMaxLength,
                'size'               => $fieldSize,
                'step'               => $fieldStep,
                'choices'            => $fieldMultipleValues,
                'default'            => $fieldDefault,
                'multiple'           => $fieldMultiple,
                'allowed_file_types' => $fieldFileTypes,
                'max_file_size'      => $fieldMaxFileSize,
                'max_size_unit'      => $fieldMaxFileSizeUnit,
                'tnc_html'           => $withoutSquareBrackets
            ];

            $fields = $this->formatFieldData($args, $fieldType);

            if ($fieldData = $this->getFluentClassicField($fieldType, $fields)) {
                $fluentFields['fields'][$args['index']] = $fieldData;
            }
        }

        $fluentFields['submitButton'] = $submitBtn;

        return $fluentFields;
    }

    public function getSubmitBtn($args)
    {
        return [
            'uniqElKey'      => 'submit-' . time(),
            'element'        => 'button',
            'attributes'     => [
                'type'  => 'submit',
                'class' => '',
                'id'    => ''
            ],
            'settings'       => [
                'container_class'  => '',
                'align'            => 'left',
                'button_style'     => 'default',
                'button_size'      => 'md',
                'color'            => '#ffffff',
                'background_color' => '#409EFF',
                'button_ui'        => [
                    'type' => 'default',
                    'text' => $args['label'],
                ],
                'normal_styles'    => [],
                'hover_styles'     => [],
                'current_state'    => "normal_styles"
            ],
            'editor_options' => [
                'title' => 'Submit Button',
            ],

        ];
    }

    private function fieldTypeMap()
    {
        return [
            'email'      => 'email',
            'text'       => 'input_text',
            'url'        => 'input_url',
            'tel'        => 'phone',
            'textarea'   => 'input_textarea',
            'number'     => 'input_number',
            'range'      => 'rangeslider',
            'date'       => 'input_date',
            'checkbox'   => 'input_checkbox',
            'radio'      => 'input_radio',
            'select'     => 'select',
            'file'       => 'input_file',
            'acceptance' => 'terms_and_condition'
        ];
    }

    protected function formatFieldData($args, $type)
    {
        switch ($type) {
            case 'input_number':
                $args['min'] = ArrayHelper::get($args, 'min');
                $args['max'] = ArrayHelper::get($args, 'max');
                break;
            case 'rangeslider':
                $args['min'] = ArrayHelper::get($args, 'min');
                $args['max'] = ArrayHelper::get($args, 'max');
                $args['step'] = ArrayHelper::get($args, 'step');
                break;
            case 'input_date':
                $args['format'] = "Y-m-d H:i";
                break;
            case 'select':
            case 'input_radio':
            case 'input_checkbox':
                list($options, $defaultVal) = $this->getOptions(ArrayHelper::get($args, 'choices', []),
                    ArrayHelper::get($args, 'default', '')
                );;
                $args['options'] = $options;
                if ($type == 'select') {
                    $isMulti = ArrayHelper::isTrue($args, 'multiple');
                    if ($isMulti) {
                        $args['type'] = 'multi-select';
                        $args['multiple'] = true;
                        $args['value'] = $defaultVal;
                    } else {
                        $args['value'] = array_shift($defaultVal) ?: "";
                    }
                } elseif ($type == 'input_checkbox') {
                    $args['value'] = $defaultVal;
                } elseif ($type == 'input_radio') {
                    $args['value'] = array_shift($defaultVal) ?: "";
                }
                break;
            case 'input_file':
                $args['allowed_file_types'] = $this->getFileTypes($args, 'allowed_file_types');
                $args['max_size_unit'] = ArrayHelper::get($args, 'max_size_unit');
                $max_size = ArrayHelper::get($args, 'max_file_size') ?: 1;
                if ($args['max_size_unit'] === 'MB') {
                    $args['max_file_size'] = ceil($max_size * 1048576); // 1MB = 1048576 Bytes
                }
                $args['max_file_count'] = '1';
                $args['upload_btn_text'] = 'File Upload';
                break;
            case 'terms_and_condition':
                $args['tnc_html'] = ArrayHelper::get($args, 'tnc_html',
                    'I have read and agree to the Terms and Conditions and Privacy Policy.'
                );
                break;
            default :
                break;
        }
        return $args;
    }

    protected function getOptions($options, $default)
    {
        $formattedOptions = [];
        $defaults = [];
        foreach ($options as $key => $option) {
            $formattedOption = [
                'label'      => $option,
                'value'      => $option,
                'image'      => '',
                'calc_value' => '',
                'id'         => $key + 1,
            ];
            if (strpos($default, '_') !== false) {
                $defaults = explode('_', $default);
                foreach ($defaults as $defaultValue) {
                    if ($formattedOption['id'] == $defaultValue) {
                        $defaults[] = $formattedOption['value'];
                    }
                }
            } else {
                $defaults = $default;
            }
            $formattedOptions[] = $formattedOption;
        }
        return [$formattedOptions, $defaults];
    }

    protected function getFileTypes($field, $arg)
    {
        // All Supported File Types in Fluent Forms
        $allFileTypes = [
            "image/*|jpg|jpeg|gif|png|bmp",
            "audio/*|mp3|wav|ogg|oga|wma|mka|m4a|ra|mid|midi|mpga",
            "video/*|avi|divx|flv|mov|ogv|mkv|mp4|m4v|mpg|mpeg|mpe|video/quicktime|qt",
            "application/pdf|pdf",
            "text/*|doc|ppt|pps|xls|mdb|docx|xlsx|pptx|odt|odp|ods|odg|odc|odb|odf|rtf|txt",
            "zip|gz|gzip|rar|7z",
            "exe",
            "csv"
        ];

        $formattedTypes = explode('|', ArrayHelper::get($field, $arg, ''));
        $fileTypeOptions = [];

        foreach ($formattedTypes as $format) {
            foreach ($allFileTypes as $fileTypes) {
                if (!empty($format) && (strpos($fileTypes, $format) !== false)) {
                    if (strpos($format, '/*')) {
                        $parts = explode('|', $fileTypes);
                        $afterFirstPipe = isset($parts[1]) ? implode('|', array_slice($parts, 1)) : '';
                        $fileTypeOptions[] = $afterFirstPipe;
                    }
                }
            }
        }

        return array_unique($fileTypeOptions);
    }

    protected function getFormName($form)
    {
        return $form['name'];
    }

    protected function getFormMetas($form)
    {
        $formObject = new Form(wpFluentForm());
        return $formObject->getFormsDefaultSettings();
    }

    protected function getFormId($form)
    {
        return $form['ID'];
    }

    public function getFormsFormatted()
    {
        $forms = [];
        $items = $this->getForms();
        foreach ($items as $item) {
            $forms[] = [
                'name'           => $item['name'],
                'id'             => $item['ID'],
                'imported_ff_id' => $this->isAlreadyImported($item),
            ];
        }
        return $forms;
    }
}
