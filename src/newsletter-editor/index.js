/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { withSelect, withDispatch } from "@wordpress/data";
import { compose } from "@wordpress/compose";
import { Fragment, useEffect, useState } from "@wordpress/element";
import {
    PluginDocumentSettingPanel,
    PluginSidebar,
    PluginSidebarMoreMenuItem,
} from "@wordpress/edit-post";
import { registerPlugin } from "@wordpress/plugins";
import { styles } from "@wordpress/icons";

/**
 * Plugin dependencies
 */
import InitModal from "../components/init-modal";
import Layout from "./layout/";
import Sidebar from "./sidebar/";
import Testing from "./testing/";
import { Styling, ApplyStyling } from "./styling/";
import { PublicSettings } from "./public";
import { AdvancedSettings } from "./advanced";
import registerEditorPlugin from "./editor/";

registerEditorPlugin();

const NewsletterEdit = ({ savePost, layoutId }) => {
    const [shouldDisplaySettings, setShouldDisplaySettings] = useState(
        window &&
            window.rrze_newsletter_data &&
            window.rrze_newsletter_data.is_service_provider_configured !== "1"
    );

    const isDisplayingInitModal = shouldDisplaySettings || -1 === layoutId;

    const stylingId = "rrze-newsletter-styling";
    const stylingTitle = __("Newsletter Styles", "rrze-newsletter");

    return isDisplayingInitModal ? (
        <InitModal
            shouldDisplaySettings={shouldDisplaySettings}
            onSetupStatus={setShouldDisplaySettings}
        />
    ) : (
        <Fragment>
            <PluginSidebar name={stylingId} icon={styles} title={stylingTitle}>
                <Styling />
            </PluginSidebar>
            <PluginSidebarMoreMenuItem target={stylingId} icon={styles}>
                {stylingTitle}
            </PluginSidebarMoreMenuItem>

            <PluginDocumentSettingPanel
                name="newsletters-settings-panel"
                title={__("Newsletter", "rrze-newsletter")}
            >
                <Sidebar />
                <PublicSettings />
                <AdvancedSettings />
            </PluginDocumentSettingPanel>
            <PluginDocumentSettingPanel
                name="newsletters-styling-panel"
                title={__("Styling", "rrze-newsletter")}
            >
                <Styling />
            </PluginDocumentSettingPanel>
            <PluginDocumentSettingPanel
                name="newsletters-testing-panel"
                title={__("Testing", "rrze-newsletter")}
            >
                <Testing />
            </PluginDocumentSettingPanel>
            <PluginDocumentSettingPanel
                name="newsletters-layout-panel"
                title={__("Layout", "rrze-newsletter")}
            >
                <Layout />
            </PluginDocumentSettingPanel>

            <ApplyStyling />
        </Fragment>
    );
};

const NewsletterEditWithSelect = compose([
    withSelect((select) => {
        const { getEditedPostAttribute } = select("core/editor");
        const meta = getEditedPostAttribute("meta");
        return { layoutId: meta.rrze_newsletter_template_id };
    }),
    withDispatch((dispatch) => {
        const { savePost } = dispatch("core/editor");
        return {
            savePost,
        };
    }),
])(NewsletterEdit);

registerPlugin("rrze-newsletter-sidebar", {
    render: NewsletterEditWithSelect,
    icon: null,
});
