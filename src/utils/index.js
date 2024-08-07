/**
 * IntPluginernal dependencies
 */
import { LAYOUT_CPT_SLUG } from "./consts";

export const isUserDefinedLayout = (layout) =>
    layout && layout.post_type === LAYOUT_CPT_SLUG;

export const getBaseUrl = () => {
    const { protocol, hostname, port } = window.location;
    return `${protocol}//${hostname}${port ? `:${port}` : ""}`;
};

export const convertRelativeUrlsToAbsolute = (content, baseUrl) => {
    if (typeof content === "string") {
        const urlPattern = /(src|href)="([^"]*)"/g;
        return content.replace(urlPattern, (match, attr, url) => {
            if (!url.startsWith("http")) {
                return `${attr}="${baseUrl}${url}"`;
            }
            return match;
        });
    }
    return "";
};
