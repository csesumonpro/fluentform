const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { InspectorControls } = wp.blockEditor;
const { serverSideRender: ServerSideRender = wp.components.ServerSideRender } =
    wp;
const { apiFetch } = window.wp;

const { SelectControl, PanelBody } = wp.components;

const { Component } = window.wp.element;

const getFormMeta = async (formId, metaKey) => {
    if (!formId) {
        return;
    }

    const path = `${window.fluentform_block_vars.rest.namespace}/${window.fluentform_block_vars.rest.version}/settings/${formId}?meta_key=${metaKey}`;
    const response = await apiFetch({ path });

    return (response.length && response[0].value) || false;
};

const fluentLogo = wp.element.createElement(
    "svg",
    {
        width: 20,
        height: 20,
    },
    wp.element.createElement("path", {
        d: "M15.57,0H4.43A4.43,4.43,0,0,0,0,4.43V15.57A4.43,4.43,0,0,0,4.43,20H15.57A4.43,4.43,0,0,0,20,15.57V4.43A4.43,4.43,0,0,0,15.57,0ZM12.82,14a2.36,2.36,0,0,1-1.66.68H6.5A2.31,2.31,0,0,1,7.18,13a2.36,2.36,0,0,1,1.66-.68l4.66,0A2.34,2.34,0,0,1,12.82,14Zm3.3-3.46a2.36,2.36,0,0,1-1.66.68H3.21a2.25,2.25,0,0,1,.68-1.64,2.36,2.36,0,0,1,1.66-.68H16.79A2.25,2.25,0,0,1,16.12,10.53Zm0-3.73a2.36,2.36,0,0,1-1.66.68H3.21a2.25,2.25,0,0,1,.68-1.64,2.36,2.36,0,0,1,1.66-.68H16.79A2.25,2.25,0,0,1,16.12,6.81Z",
    })
);

class Edit extends Component {
    constructor() {
        super(...arguments);
    }

    componentDidMount() {
        const maybeSetStyle = !this.props.attributes.themeStyle && window.fluentform_block_vars.theme_style;

        if (maybeSetStyle) {
            this.props.setAttributes({
                themeStyle: window.fluentform_block_vars.theme_style,
            });
        }
    }

    checkIfConversationalForm = async formId => {
        if (!formId) {
            return;
        }

        const isConversationalForm = await getFormMeta(
            formId,
            "is_conversion_form"
        );

        this.props.setAttributes({
            isConversationalForm: isConversationalForm === "yes",
        });
    }

    handleFormChange = formId => {
        this.props.setAttributes({ formId });

        if (!formId) {
            this.props.setAttributes({
                themeStyle: "",
                isThemeChange: false,
                isConversationalForm: false,
            });
        } else {
            this.checkIfConversationalForm(formId);
        }
    }

    render() {
        const { attributes, setAttributes } = this.props;
        const config = window.fluentform_block_vars;
        const presets = config.style_presets;

        let settings;

        const blockContent = (
            <div className="flueform-guten-wrapper" key="ff-form-sub-wrapper">
                <div className="fluentform-logo">
                    <img src={config.logo} alt="Fluent Forms Logo" />
                </div>
                {settings}
            </div>
        );

        settings = [
            <InspectorControls key="ff-select-form">
                <PanelBody title="Select your Fluent Forms">
                    <SelectControl
                        label={__("Select a Form")}
                        value={attributes.formId}
                        options={config.forms.map(form => ({
                            value: form.id,
                            label: form.title,
                        }))}
                        onChange={this.handleFormChange}
                        key="sub_select_form"
                    />

                    {attributes.formId &&
                        attributes.hasOwnProperty("isConversationalForm") &&
                        attributes.isConversationalForm != true && (
                            <SelectControl
                                label={__("Select a Theme Style")}
                                value={attributes.themeStyle}
                                options={presets}
                                onChange={themeStyle => {
                                    setAttributes({
                                        themeStyle,
                                        isThemeChange: true,
                                    });
                                }}
                                key="ff-sub_select_theme"
                            />
                        )}
                </PanelBody>
            </InspectorControls>,
        ];

        if (attributes.formId) {
            if (attributes.isConversationalForm == true) {
                settings.push(
                    <div className="conv-demo" key="ff-conv-sub-wrapper">
                        <img
                            src={config.conversational_demo_img}
                            alt="Fluent Forms Conversational Form"
                        />
                        <p>
                            <strong>
                                {__(
                                    "This is a demo! The actual Conversational Form may look different in live pages."
                                )}
                            </strong>
                        </p>
                    </div>
                );
            } else {
                settings.push(
                    <ServerSideRender
                        key="ff-preview"
                        block="fluentfom/guten-block"
                        attributes={attributes}
                    />
                );
            }
        } else {
            settings.push(blockContent);
            settings.push(
                <SelectControl
                    label={__("Select a Form")}
                    value=""
                    options={config.forms.map(form => ({
                        value: form.id,
                        label: form.title,
                    }))}
                    onChange={this.handleFormChange}
                    key="ff-main-select-form"
                />
            );
        }

        return <div className="flueform-guten-wrapper">{settings}</div>;
    }
}

registerBlockType("fluentfom/guten-block", {
    title: __("Fluent Forms"),
    icon: fluentLogo,
    category: "formatting",
    keywords: [
        __("Contact Form"),
        __("Fluent Forms"),
        __("Forms"),
        __("Advanced Forms"),
        __("fluentforms-gutenberg-block"),
    ],

    edit: Edit,
});
