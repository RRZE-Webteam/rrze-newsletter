/**
 * WordPress dependencies.
 */
import { useMergeRefs, useRefEffect } from "@wordpress/compose";
import { BlockPreview } from "@wordpress/block-editor";
import { Spinner } from "@wordpress/components";
import { Fragment, forwardRef } from "@wordpress/element";
import { useCustomFontsInIframe } from "../../../newsletter-editor/styling";

/**
 * External dependencies.
 */
import classnames from "classnames";

/**
 * Posts Preview component.
 */
const PostsPreview = ({ isReady, blocks, className, viewportWidth }, ref) => {
    const useIframeBorderFix = useRefEffect((node) => {
        const observerCallback = () => {
            const iframe = node.querySelector('iframe[title="Editor canvas"]');
            if (iframe) {
                const updateIframeStyle = () => {
                    iframe.style.border = 0;
                    observer.disconnect();
                };
                updateIframeStyle();
                iframe.addEventListener("load", updateIframeStyle);
            }
        };
        const observer = new MutationObserver(observerCallback);
        observer.observe(node, { childList: true, subtree: true });
        return () => {
            observer.disconnect();
        };
    }, []);

    // Append layout style if viewing layout preview.
    const useLayoutStyle = useRefEffect((node) => {
        const style = document.getElementById("rrze-newsletter__layout-css");
        if (!style) {
            return;
        }
        const clonedStyle = style.cloneNode(true);
        const observerCallback = () => {
            const iframe = node.querySelector('iframe[title="Editor canvas"]');
            if (iframe) {
                const doc = iframe.contentDocument;
                const appendStyle = () => {
                    doc.body.id = style.dataset.previewid;
                    if (!doc.contains(clonedStyle)) {
                        doc.head.appendChild(clonedStyle);
                    }
                    observer.disconnect();
                };
                appendStyle();
                iframe.addEventListener("load", appendStyle);
            }
        };
        const observer = new MutationObserver(observerCallback);
        observer.observe(node, { childList: true, subtree: true });
        return () => {
            observer.disconnect();
        };
    }, []);

    // Append custom styles to iframe.
    const stylesArray = [];

    return (
        <Fragment>
            <style id="rrze-newsletter__layout-css">
                {stylesArray.join("\n")}
            </style>
            <div
                className={classnames(
                    "rrze-newsletter-post-inserter__preview",
                    className
                )}
                ref={useMergeRefs([
                    ref,
                    useIframeBorderFix,
                    useLayoutStyle,
                    useCustomFontsInIframe(),
                ])}
            >
                {isReady ? (
                    <BlockPreview
                        blocks={blocks}
                        viewportWidth={viewportWidth}
                    />
                ) : (
                    <Spinner />
                )}
            </div>
        </Fragment>
    );
};

export default forwardRef(PostsPreview);
