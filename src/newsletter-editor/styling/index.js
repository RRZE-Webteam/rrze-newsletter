/**
 * WordPress dependencies
 */
import apiFetch from "@wordpress/api-fetch";
import { compose, useInstanceId } from "@wordpress/compose";
import { ColorPicker, BaseControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { withDispatch, withSelect } from "@wordpress/data";
import { Fragment, useEffect } from "@wordpress/element";
import SelectControlWithOptGroup from "../../components/select-control-with-optgroup/";

const fontOptgroups = [
    {
        label: __("Sans Serif", "rrze-newsletter"),
        options: [
            {
                value: "Arial, Helvetica, sans-serif",
                label: __("Arial", "rrze-newsletter")
            },
            {
                value: "Tahoma, sans-serif",
                label: __("Tahoma", "rrze-newsletter")
            },
            {
                value: "Trebuchet MS, sans-serif",
                label: __("Trebuchet", "rrze-newsletter")
            },
            {
                value: "Verdana, sans-serif",
                label: __("Verdana", "rrze-newsletter")
            }
        ]
    },

    {
        label: __("Serif", "rrze-newsletter"),
        options: [
            {
                value: "Georgia, serif",
                label: __("Georgia", "rrze-newsletter")
            },
            {
                value: "Palatino, serif",
                label: __("Palatino", "rrze-newsletter")
            },
            {
                value: "Times New Roman, serif",
                label: __("Times New Roman", "rrze-newsletter")
            }
        ]
    },

    {
        label: __("Monospace", "rrze-newsletter"),
        options: [
            {
                value: "Courier, monospace",
                label: __("Courier", "rrze-newsletter")
            }
        ]
    }
];

const customStylesSelector = select => {
    const { getEditedPostAttribute } = select("core/editor");
    const meta = getEditedPostAttribute("meta");
    return {
        fontBody:
            meta.rrze_newsletter_font_body || fontOptgroups[1].options[0].value,
        fontHeader:
            meta.rrze_newsletter_font_header ||
            fontOptgroups[0].options[0].value,
        backgroundColor: meta.rrze_newsletter_background_color || "#ffffff"
    };
};

export const ApplyStyling = withSelect(customStylesSelector)(
    ({ fontBody, fontHeader, backgroundColor }) => {
        useEffect(() => {
            document.documentElement.style.setProperty("--body-font", fontBody);
        }, [fontBody]);
        useEffect(() => {
            document.documentElement.style.setProperty(
                "--header-font",
                fontHeader
            );
        }, [fontHeader]);
        useEffect(() => {
            const editorElement = document.querySelector(
                ".edit-post-visual-editor"
            );
            if (editorElement) {
                editorElement.style.backgroundColor = backgroundColor;
            }
        }, [backgroundColor]);

        return null;
    }
);

export const Styling = compose([
    withDispatch(dispatch => {
        const { editPost } = dispatch("core/editor");
        return { editPost };
    }),
    withSelect(select => {
        const { getCurrentPostId } = select("core/editor");
        return {
            postId: getCurrentPostId(),
            ...customStylesSelector(select)
        };
    })
])(({ editPost, fontBody, fontHeader, backgroundColor, postId }) => {
    const updateStyleValue = (key, value) => {
        editPost({ meta: { [key]: value } });
        apiFetch({
            data: { key, value },
            method: "POST",
            path: `/rrze-newsletter/v1/post-meta/${postId}`
        });
    };

    const instanceId = useInstanceId(SelectControlWithOptGroup);
    const id = `inspector-select-control-${instanceId}`;

    return (
        <Fragment>
            <SelectControlWithOptGroup
                label={__("Headings font", "rrze-newsletter")}
                value={fontHeader}
                optgroups={fontOptgroups}
                onChange={value =>
                    updateStyleValue("rrze_newsletter_font_header", value)
                }
            />
            <SelectControlWithOptGroup
                label={__("Body font", "rrze-newsletter")}
                value={fontBody}
                optgroups={fontOptgroups}
                onChange={value =>
                    updateStyleValue("rrze_newsletter_font_body", value)
                }
            />
            <BaseControl
                label={__("Background color", "rrze-newsletter")}
                id={id}
            >
                <ColorPicker
                    id={id}
                    color={backgroundColor}
                    onChangeComplete={value =>
                        updateStyleValue(
                            "rrze_newsletter_background_color",
                            value.hex
                        )
                    }
                    disableAlpha
                />
            </BaseControl>
        </Fragment>
    );
});
