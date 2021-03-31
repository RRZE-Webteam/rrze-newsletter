/**
 * External dependencies
 */
import { every, some } from "lodash";

/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { addFilter } from "@wordpress/hooks";
import { createHigherOrderComponent } from "@wordpress/compose";
import { select } from "@wordpress/data";

const handleSideAlignment = (warnings, props) => {
    if (
        props.attributes.align === "left" ||
        props.attributes.align === "right"
    ) {
        warnings.push(__("Side alignment", "rrze-newsletter"));
    }
    return warnings;
};

const isCenterAligned = (block) =>
    block.attributes.verticalAlignment === "center";

const getWarnings = (props) => {
    let warnings = [];
    const { getBlock } = select("core/block-editor");
    const block = getBlock(props.block.clientId);
    switch (props.name) {
        case "core/group":
            if (props.attributes.__nestedGroupWarning) {
                warnings.push(__("Nested group", "rrze-newsletter"));
            }
            break;

        // `vertical-align='middle'` will only work if all columns are middle-aligned.
        // This is different in Gutenberg, because it uses flexbox layout (not available in email HTML).
        //
        // If a user chooses middle-alignment of a column, they will be prompted to
        // middle-align all of the columns.
        //
        // Middle alignment option should be removed from the UI for a single column, when that's
        // handled by the block editor filters.
        case "core/columns":
            if (block) {
                const { innerBlocks } = block;
                const isAnyColumnCenterAligned = some(
                    innerBlocks,
                    isCenterAligned
                );
                const areAllColumnsCenterAligned = every(
                    innerBlocks,
                    isCenterAligned
                );
                if (isAnyColumnCenterAligned && !areAllColumnsCenterAligned) {
                    warnings.push(
                        __(
                            "Unequal middle alignment. All or none of the columns should be middle-aligned.",
                            "rrze-newsletter"
                        )
                    );
                }
            }
            break;

        case "core/column":
            if (props.attributes.__nestedColumnWarning) {
                warnings.push(__("Nested columns", "rrze-newsletter"));
            }
            break;

        case "core/image":
            warnings = handleSideAlignment(warnings, props);
            if (props.attributes.align === "full") {
                warnings.push(__("Full width", "rrze-newsletter"));
            }
            break;

        case "core/paragraph":
            if (props.attributes.content.indexOf("<img") >= 0) {
                warnings.push(__("Inline image", "rrze-newsletter"));
            }
            if (props.attributes.dropCap) {
                warnings.push(__("Drop cap", "rrze-newsletter"));
            }
            break;
    }
    return warnings;
};

const withUnsupportedFeaturesNotices = createHigherOrderComponent(
    (BlockListBlock) => {
        return (props) => {
            const warnings = getWarnings(props);
            return warnings.length ? (
                <div className="rrze-newsletter__editor-block">
                    <div className="rrze-newsletter__editor-block__warning components-notice is-error">
                        {__(
                            "These features will not be displayed correctly in an email, please remove them:",
                            "rrze-newsletter"
                        )}
                        {warnings.map((warning, i) => (
                            <strong key={i}>
                                <br />
                                {warning}
                            </strong>
                        ))}
                    </div>
                    <BlockListBlock {...props} />
                </div>
            ) : (
                <BlockListBlock {...props} />
            );
        };
    },
    "withInspectorControl"
);

export const addBlocksValidationFilter = () => {
    addFilter(
        "editor.BlockListBlock",
        "rrze-newsletter/unsupported-features-notices",
        withUnsupportedFeaturesNotices
    );
};
