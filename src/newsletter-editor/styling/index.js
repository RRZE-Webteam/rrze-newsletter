/**
 * WordPress dependencies
 */
import { compose, useInstanceId } from "@wordpress/compose";
import {
    ColorPicker,
    BaseControl,
    PanelBody,
    PanelRow,
    SelectControl,
    ToggleControl,
    Notice,
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { useSelect, withDispatch, withSelect } from "@wordpress/data";
import { Fragment, useEffect, useRef } from "@wordpress/element";
import SelectControlWithOptGroup from "../../components/select-control-with-optgroup/";

/**
 * Plugin dependencies
 */
import "./style.scss";
import { isManagedSpacing, mountManagedSpacing } from "./managed-spacing.mjs";
import { mountCanvasBackground } from "./canvas-background.mjs";

const fontOptgroups = [
    {
        label: __("Sans Serif", "rrze-newsletter"),
        options: [
            {
                value: "Arial, Helvetica, sans-serif",
                label: __("Arial", "rrze-newsletter"),
            },
            {
                value: "Calibri, sans-serif",
                label: __("Calibri", "rrze-newsletter"),
            },
            {
                value: "Tahoma, sans-serif",
                label: __("Tahoma", "rrze-newsletter"),
            },
            {
                value: "Trebuchet MS, sans-serif",
                label: __("Trebuchet", "rrze-newsletter"),
            },
            {
                value: "Verdana, sans-serif",
                label: __("Verdana", "rrze-newsletter"),
            },
        ],
    },

    {
        label: __("Serif", "rrze-newsletter"),
        options: [
            {
                value: "Cambria, serif",
                label: __("Cambria", "rrze-newsletter"),
            },
            {
                value: "Georgia, serif",
                label: __("Georgia", "rrze-newsletter"),
            },
            {
                value: "Palatino, serif",
                label: __("Palatino", "rrze-newsletter"),
            },
            {
                value: "Times New Roman, serif",
                label: __("Times New Roman", "rrze-newsletter"),
            },
        ],
    },

    {
        label: __("Monospace", "rrze-newsletter"),
        options: [
            {
                value: "Courier, monospace",
                label: __("Courier", "rrze-newsletter"),
            },
        ],
    },
];

const customStylesSelector = (select) => {
    const { getEditedPostAttribute } = select("core/editor");
    const meta = getEditedPostAttribute("meta");
    return {
        fontBody:
            meta.rrze_newsletter_font_body || fontOptgroups[0].options[0].value,
        fontHeader:
            meta.rrze_newsletter_font_header ||
            fontOptgroups[0].options[0].value,
        backgroundColor: meta.rrze_newsletter_background_color || "#fff",
        contrastProtection: meta.rrze_newsletter_contrast_protection !== false,
        spacingMode: meta.rrze_newsletter_spacing_mode || "inherit",
        linkColor: meta.rrze_newsletter_link_color || "inherit",
        linkTextDecoration:
            meta.rrze_newsletter_link_text_decoration || "underline",
    };
};

// Create a temporary DOM document (not displayed) for parsing CSS rules.
const doc = document.implementation.createHTMLDocument("Temp");

/**
 * Takes a given CSS string, parses it, and scopes all its rules to the given `scope`.
 *
 * @param {string} scope The scope to apply to each rule in the CSS.
 * @param {string} css   The CSS to scope.
 * @return {string} Scoped CSS string.
 */
export const getScopedCss = (scope, css) => {
    const style = doc.querySelector("style") || document.createElement("style");

    style.textContent = css;
    doc.head.appendChild(style);

    const rules = [...style.sheet.cssRules];
    return rules
        .map((rule) => {
            rule.selectorText = rule.selectorText
                .split(",")
                .map((selector) => `${scope} ${selector}`)
                .join(", ");
            return rule.cssText;
        })
        .join("\n");
};

/**
 * Hook to apply body and header fonts variables in store to an iframe as root
 * element style property.
 *
 * @return {import('react').RefObject} The component to be rendered.
 */
export const useCustomFontsInIframe = () => {
    const ref = useRef();
    const { fontBody, fontHeader } = useSelect(customStylesSelector);
    useEffect(() => {
        const node = ref.current;
        const updateIframe = () => {
            const iframe = node.querySelector('iframe[title="Editor canvas"]');
            if (iframe) {
                const updateStyleProperties = () => {
                    const element = iframe.contentDocument?.documentElement;
                    if (element) {
                        element.style.setProperty(
                            "--rrze-newsletter-body-font",
                            fontBody
                        );
                        element.style.setProperty(
                            "--rrze-newsletter-header-font",
                            fontHeader
                        );
                        element
                            .querySelector("body")
                            .style.setProperty("background", "none");
                    }
                };
                updateStyleProperties();
                // Handle Firefox iframe.
                iframe.addEventListener("load", updateStyleProperties);
                return () => {
                    iframe.removeEventListener("load", updateStyleProperties);
                };
            }
        };
        updateIframe();
        const observer = new MutationObserver(updateIframe);
        observer.observe(node, { childList: true, subtree: true });
        return () => {
            observer.disconnect();
        };
    }, [fontBody, fontHeader]);
    return ref;
};

export const ApplyStyling = withSelect(customStylesSelector)(({
    fontBody,
    fontHeader,
    backgroundColor,
    spacingMode,
}) => {
    const managedSpacing = isManagedSpacing(spacingMode, window.rrze_newsletter_data?.global_managed_spacing);
    useEffect(() => {
        if (managedSpacing) {
            return mountManagedSpacing(document);
        }
    }, [managedSpacing]);
    useEffect(() => {
        document.documentElement.style.setProperty(
            "--rrze-newsletter-body-font",
            fontBody
        );
    }, [fontBody]);
    useEffect(() => {
        document.documentElement.style.setProperty(
            "--rrze-newsletter-header-font",
            fontHeader
        );
    }, [fontHeader]);
    useEffect(() => mountCanvasBackground(document, backgroundColor), [backgroundColor]);

    return null;
});

export const Styling = compose([
    withDispatch((dispatch) => {
        const { editPost } = dispatch("core/editor");
        return { editPost };
    }),
    withSelect(customStylesSelector),
])(({ editPost, fontBody, fontHeader, backgroundColor, contrastProtection, spacingMode, PanelComponent = PanelBody }) => {
    const updateStyleValue = (key, value) => {
        editPost({ meta: { [key]: value } });
    };

    const instanceId = useInstanceId(SelectControlWithOptGroup);
    const id = `inspector-select-control-${instanceId}`;

    return (
        <Fragment>
            <PanelComponent name="rrze-newsletter-spacing-panel" title={__("Email spacing", "rrze-newsletter")}>
                <SelectControl
                    label={__("Spacing mode", "rrze-newsletter")}
                    value={spacingMode}
                    options={[
                        { value: "inherit", label: __("Use global setting", "rrze-newsletter") },
                        { value: "managed", label: __("Managed spacing", "rrze-newsletter") },
                        { value: "expert", label: __("Expert mode (manual spacing)", "rrze-newsletter") },
                    ]}
                    onChange={(value) => updateStyleValue("rrze_newsletter_spacing_mode", value)}
                    help={isManagedSpacing("inherit", window.rrze_newsletter_data?.global_managed_spacing)
                        ? __("Global setting: managed spacing enabled. Save and preview the generated email to see the result. Editor blocks stay unchanged.", "rrze-newsletter")
                        : __("Global setting: manual spacing. Save and preview the generated email to see the result. Editor blocks stay unchanged.", "rrze-newsletter")}
                />
                {spacingMode === "expert" && (
                    <Notice status="warning" isDismissible={false}>
                        {__("Expert mode disables spacing safeguards for this newsletter. Large gaps and deeply nested group padding can reduce readability on small screens. Check the generated email before sending. Contrast protection is controlled separately.", "rrze-newsletter")}
                    </Notice>
                )}
                {isManagedSpacing(spacingMode, window.rrze_newsletter_data?.global_managed_spacing) && (
                    <p>{__("The editor now approximates the email's consistent content gaps and outer gutters. Manual spacing values stay saved but are overridden while managed spacing is active. Nested groups do not add extra padding. Use the generated email preview for the final check.", "rrze-newsletter")}</p>
                )}
            </PanelComponent>
            <PanelComponent
                name="rrze-newsletter-typography-panel"
                title={__("Typography", "rrze-newsletter")}
            >
                <PanelRow>
                    <SelectControlWithOptGroup
                        label={__("Headings font", "rrze-newsletter")}
                        value={fontHeader}
                        optgroups={fontOptgroups}
                        onChange={(value) =>
                            updateStyleValue(
                                "rrze_newsletter_font_header",
                                value
                            )
                        }
                    />
                </PanelRow>
                <PanelRow>
                    <SelectControlWithOptGroup
                        label={__("Body font", "rrze-newsletter")}
                        value={fontBody}
                        optgroups={fontOptgroups}
                        onChange={(value) =>
                            updateStyleValue("rrze_newsletter_font_body", value)
                        }
                    />
                </PanelRow>
            </PanelComponent>
            <PanelComponent
                name="rrze-newsletter-background-color-panel"
                title={__("Background", "rrze-newsletter")}
            >
                <PanelRow className="rrze-newsletter__background-color-panel">
                    <BaseControl
                        label={__("Background color", "rrze-newsletter")}
                        id={`${id}-background-color`}
                    >
                        <ColorPicker
                            id={`${id}-background-color`}
                            color={backgroundColor}
                            onChangeComplete={(value) =>
                                updateStyleValue(
                                    "rrze_newsletter_background_color",
                                    value.hex
                                )
                            }
                            disableAlpha
                        />
                    </BaseControl>
                </PanelRow>
            </PanelComponent>
            <PanelComponent name="rrze-newsletter-contrast-panel" title={__("Email contrast protection", "rrze-newsletter")}>
                <ToggleControl
                    label={__("Automatically improve text contrast", "rrze-newsletter")}
                    checked={contrastProtection}
                    onChange={(value) => updateStyleValue("rrze_newsletter_contrast_protection", value)}
                    help={__("When saving, adjust low-contrast text on solid backgrounds in the generated email. Block colors stay unchanged. A notice lets you preview corrections and review backgrounds that cannot be checked safely.", "rrze-newsletter")}
                />
            </PanelComponent>
        </Fragment>
    );
});
