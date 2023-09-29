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

        //remove all label and empty line
        foreach ($formMetaDataArray as $formMetaString) {
            if (!empty($formMetaString)) {
                if (strpos($formMetaString, '<label>') !== false || strpos($formMetaString, '</label>') !== false) {
                    $formMetaString = trim(str_replace(['<label>', '</label>'], '', $formMetaString));
                }
                $formattedArray[] = $formMetaString;
            }
        }

        //format array with field label and remove quiz field
        $fieldStringArray = [];
        foreach ($formattedArray as $formattedKey => &$formattedValue) {
            preg_match_all('/\[[^\]]*\]/', $formattedValue, $fieldStringMatches);
            $fieldString = isset($fieldStringMatches[0][0]) ? $fieldStringMatches[0][0] : '';

            if (preg_match('/\[(.*?)\](.*?)\[.*?\]/', $formattedValue, $withoutBracketMatches)) {
                $withoutBracketString = isset($withoutBracketMatches[2]) ? trim($withoutBracketMatches[2]) : '';
                $fieldString = str_replace(']',' "'. $withoutBracketString . '"]', $fieldString);
            }

            if (strpos($formattedValue,'[quiz') !== 0) {
                if (strpos($formattedValue, '[') === false) {
                    if (
                        isset($formattedArray[$formattedKey + 1]) &&
                        strpos($formattedArray[$formattedKey + 1], '[') !== false
                    ) {
                        $fieldStringArray[] = $formattedValue . $formattedArray[$formattedKey + 1];
                        unset($formattedArray[$formattedKey + 1]);
                    }
                } else {
                    $fieldStringArray[] = $fieldString;
                    unset($formattedArray[$formattedKey]);
                }
            }
        }

        // format fields as fluent forms field
        foreach ($fieldStringArray as $superKey => &$superValue) {
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
            $fieldFileSizeUnit = 'KB';
            $tncHtml = '';

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

            if ($fieldElement === 'submit') {
                preg_match_all('/(["\'])(.*?)\1/', $fieldString, $matches);
                $submitBtn = $this->getSubmitBttn([
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

            if (preg_match('/filetypes:([a-zA-Z0-9|]+)/', $fieldString, $matches)) {
                $fieldFileTypes = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match_all('/(["\'])(.*?)\1/', $fieldString, $matches)) {
                if (isset($matches[2])) {
                    if (count($matches[2]) > 1) {
                        $fieldMultipleValues = $matches[2];
                    } else {
                        if (count($matches[2]) === 1) {
                            $fieldValue = isset($matches[2][0]) ? $matches[2][0] : '';
                        }
                    }
                }
            }

            if (preg_match('/default:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldDefault = isset($matches[1]) ? $matches[1] : '';
            }

            if (preg_match('/limit:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldMaxFileSize = isset($matches[1]) ? $matches[1] : '1mb';

                if (strpos($fieldMaxFileSize, 'mb') !== false) {
                    $fieldFileSizeUnit = 'MB';
                }

                $fieldMaxFileSize = str_replace(['mb', 'kb'], '', $fieldMaxFileSize);
            }

            if (preg_match('/autocomplete:([a-zA-Z0-9]+)/', $fieldString, $matches)) {
                $fieldValue = isset($matches[1]) ? $matches[1] : '';
            }

            if ($fieldElement === 'acceptance') {
                $tncHtml = $fieldValue;
            }

            if (!$fieldLabel) {
                $fieldLabel = $fieldElement;
            }

            $fieldType = ArrayHelper::get($this->fieldTypeMap(), $fieldElement);

            $fieldName = $fieldType . '_' . $superKey;

            $args = [
                'uniqElKey'          => 'el_'. $superKey . time(),
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
                'prefix'             => '',
                'suffix'             => '',
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
                'max_size_unit'      => $fieldFileSizeUnit,
                'tnc_html'           => $tncHtml
            ];

            $fields = $this->formatFieldData($args, $fieldType);

            if ($fieldMultiple) {
                $fieldType = 'multi_select';
            }

            if ($fieldData = $this->getFluentClassicField($fieldType, $fields)) {
                $fluentFields['fields'][$args['index']] = $fieldData;
            }
        }

        $fluentFields['submitButton'] = $submitBtn;

        return $fluentFields;
    }

    public function getSubmitBttn($args)
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
                'align'            => 'left',
                'button_style'     => 'default',
                'container_class'  => '',
                'help_message'     => '',
                'background_color' => '#1a7efb',
                'button_size'      => 'md',
                'color'            => '#ffffff',
                'button_ui'        => [
                    'type'    => 'default',
                    'text'    => $args['label'],
                    'img_url' => ''
                ],
                'normal_styles'    => [
                    'backgroundColor' => '#1a7efb',
                    'borderColor'     => '#1a7efb',
                    'color'           => '#ffffff',
                    'borderRadius'    => '',
                    'minWidth'        => ''
                ],
                'hover_styles'     => [
                    'backgroundColor' => '#1a7efb',
                    'borderColor'     => '#1a7efb',
                    'color'           => '#ffffff',
                    'borderRadius'    => '',
                    'minWidth'        => ''
                ],
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
            'email'        => 'email',
            'text'         => 'input_text',
            'url'          => 'input_url',
            'tel'          => 'phone',
            'textarea'     => 'input_textarea',
            'number'       => 'input_number',
            'range'        => 'rangeslider',
            'date'         => 'input_date',
            'checkbox'     => 'input_checkbox',
            'radio'        => 'input_radio',
            'select'       => 'select',
            'multi_select' => 'multi_select',
            'file'         => 'input_file',
            'acceptance'   => 'terms_and_condition'
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
                        $args['multiple'] = true;
                        $args['value'] = $defaultVal;
                    } else {
                        $args['value'] = is_array($defaultVal) ? array_shift($defaultVal) : "";
                    }
                } elseif ($type == 'input_checkbox') {
                    $args['value'] = $defaultVal;
                } elseif ($type == 'input_radio') {
                    $args['value'] = is_array($defaultVal) ? array_shift($defaultVal) : "";
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
                if (ArrayHelper::get($args, 'tnc_html') !== '') {
                    $args['tnc_html'] = ArrayHelper::get($args, 'tnc_html',
                        'I have read and agree to the Terms and Conditions and Privacy Policy.'
                    );
                    $args['required'] = true;
                }
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
            "pdf",
            "text/*|doc|ppt|pps|xls|mdb|docx|xlsx|pptx|odt|odp|ods|odg|odc|odb|odf|rtf|txt",
            "zip|gz|gzip|rar|7z",
            "exe",
            "csv"
        ];

        $formattedTypes = explode('|', ArrayHelper::get($field, $arg, ''));
        $fileTypeOptions = [];

        foreach ($formattedTypes as $format) {
            foreach ($allFileTypes as $fileTypes) {
                if (!empty($format) && strpos($fileTypes, $format) !== false) {
                    if (strpos($fileTypes, '/*|') !== false) {
                        $fileTypes = explode('/*|', $fileTypes)[1];
                    }
                    $fileTypeOptions[] = $fileTypes;
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
        $defaults = $formObject->getFormsDefaultSettings();

        return [
            'formSettings'                 => [
                'confirmation' => ArrayHelper::get($defaults, 'confirmation'),
                'restrictions' => ArrayHelper::get($defaults, 'restrictions'),
                'layout'       => ArrayHelper::get($defaults, 'layout'),
            ],
            'advancedValidationSettings'   => $this->getAdvancedValidation(),
            'delete_entry_on_submission'   => 'no',
            'notifications'                => $this->getNotifications(),
            'step_data_persistency_status' => 'no',
            'form_save_state_status'       => 'no'
        ];
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

    private function getNotifications()
    {
        return [
            'name'      => __('Admin Notification Email', 'fluentform'),
            'sendTo'    => [
                'type'    => 'email',
                'email'   => '{wp.admin_email}',
                'field'   => '',
                'routing' => [],
            ],
            'fromName'  => '',
            'fromEmail' => '',
            'replyTo'   => '',
            'bcc'       => '',
            'subject'   => __('New Form Submission', 'fluentform'),
            'message'   => '<p>{all_data}</p><p>This form submitted at: {embed_post.permalink}</p>',
            'conditionals' => [],
            'enabled'   => false
        ];
    }

    private function getAdvancedValidation()
    {
        return [
            'status'          => false,
            'type'            => 'all',
            'conditions'      => [
                [
                    'field'    => '',
                    'operator' => '=',
                    'value'    => ''
                ]
            ],
            'error_message'   => '',
            'validation_type' => 'fail_on_condition_met'
        ];
    }

    public function getEntries($formId)
    {
        if(!wpforms()->is_pro()){
            wp_send_json([
                'message' => __("Entries not available in WPForms Lite",'fluentform')
            ], 200);
        }
        $form = $this->getForm($formId);
        if (empty($form)) {
            return false;
        }
        $formFields = $this->getFields($form);
        if ($formFields) {
            $formFields = $formFields['fields'];
        }
        $args = [
            'form_id' => $form['ID'],
            'order'  => 'asc',
        ];
        $totalEntries = wpforms()->entry->get_entries($args, true);// 2nd parameter 'true' means return total entries count
        $args['number'] = apply_filters('fluentform/entry_migration_max_limit', static::DEFAULT_ENTRY_MIGRATION_MAX_LIMIT, $this->key,  $totalEntries, $formId);
        $submissions = wpforms()->entry->get_entries($args);
        $entries = [];
        if (!$submissions || !is_array($submissions)) {
            return $entries;
        }
        foreach ($submissions as $submission) {
            $fields = \json_decode( $submission->fields , true);
            if (!$fields) {
                continue;
            }
            $entry = [];
            foreach ($fields as $fieldId => $field) {
                if (!isset($formFields[$fieldId])) {
                    continue;
                }
                $formField = $formFields[$fieldId];
                $name = ArrayHelper::get($formField, 'attributes.name');
                if (!$name) {
                    continue;
                }
                $type = ArrayHelper::get($formField, 'element');
                // format entry value by field name
                $finalValue = ArrayHelper::get($field, 'value');
                if ("input_name" == $type) {
                    $finalValue = $this->getSubmissionNameValue($formField['fields'], $field);
                } elseif (
                    "input_checkbox" == $type ||
                    (
                        "select" == $type &&
                        ArrayHelper::isTrue($formField, 'attributes.multiple')
                    )
                ) {
                    $finalValue = explode("\n", $finalValue);
                } elseif ("address" == $type) {
                    $finalValue = [
                        "address_line_1" => ArrayHelper::get($field, 'address1', ''),
                        "address_line_2" => ArrayHelper::get($field, 'address2', ''),
                        "city" => ArrayHelper::get($field, 'city', ''),
                        "state" => ArrayHelper::get($field, 'state', ''),
                        "zip" => ArrayHelper::get($field, 'postal', ''),
                        "country" => ArrayHelper::get($field, 'country', ''),
                    ];
                } elseif ("input_file" == $type && $value = ArrayHelper::get($field, 'value')) {
                    $finalValue = $this->migrateFilesAndGetUrls($value);
                }
                if (null == $finalValue) {
                    $finalValue = "";
                }
                $entry[$name] = $finalValue;
            }
            if ($submission->date) {
                $entry['created_at'] = $submission->date;
            }
            if ($submission->date_modified) {
                $entry['updated_at'] = $submission->date_modified;
            }
            $entries[] = $entry;
        }
        return $entries;
    }

    protected function getSubmissionNameValue($nameFields, $submissionField) {
        $finalValue = [];
        foreach ($nameFields as $key => $field) {
            if ($name = ArrayHelper::get($field, 'attributes.name')) {
                $value = "";
                if ("first_name" == $key) {
                    $value = ArrayHelper::get($submissionField, 'first');
                } elseif ("middle_name" == $key) {
                    $value = ArrayHelper::get($submissionField, 'middle');
                } elseif ("last_name" == $key) {
                    $value = ArrayHelper::get($submissionField, 'last');
                }
                $finalValue[$name] = $value;
            }
        }
        return $finalValue;
    }
}
