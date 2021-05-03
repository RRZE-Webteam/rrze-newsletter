/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { withSelect } from "@wordpress/data";
import { compose } from "@wordpress/compose";
import { Fragment, useState } from "@wordpress/element";
import { PluginDocumentSettingPanel } from "@wordpress/edit-post";
import { registerPlugin } from "@wordpress/plugins";

/**
 * Plugin dependencies
 */
import InitModal from "../components/init-modal";
import Layout from "./layout/";
import Sidebar from "./sidebar/";
import Testing from "./testing/";
import { Styling, ApplyStyling } from "./styling/";
import { PublicSettings } from "./public";
import registerEditorPlugin from "./editor/";

registerEditorPlugin();

const NewsletterEdit = ({ layoutId }) => {
    const [shouldDisplaySettings, setShouldDisplaySettings] = useState(
        window &&
            window.rrze_newsletter_data &&
            window.rrze_newsletter_data.is_service_provider_configured !== "1"
    );

    const isDisplayingInitModal = shouldDisplaySettings || -1 === layoutId;

    return isDisplayingInitModal ? (
        <InitModal
            shouldDisplaySettings={shouldDisplaySettings}
            onSetupStatus={setShouldDisplaySettings}
        />
    ) : (
        <Fragment>
            <PluginDocumentSettingPanel
                name="newsletters-settings-panel"
                title={__("Newsletter", "rrze-newsletter")}
            >
                <Sidebar />
                <PublicSettings />
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
    withSelect(select => {
        const { getEditedPostAttribute } = select("core/editor");
        const meta = getEditedPostAttribute("meta");
        return { layoutId: meta.rrze_newsletter_template_id };
    })
])(NewsletterEdit);

registerPlugin("rrze-newsletter-sidebar", {
    render: NewsletterEditWithSelect,
    icon: null
});
