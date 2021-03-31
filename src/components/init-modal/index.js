/**
 * WordPress dependencies
 */
import { Modal } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Plugin dependencies
 */
import LayoutPicker from "./screens/layout-picker";
import "./style.scss";

export default () => {
    return (
        <Modal
            className="rrze-newsletter-modal__frame"
            isDismissible={false}
            overlayClassName="rrze-newsletter-modal__screen-overlay"
            shouldCloseOnClickOutside={false}
            shouldCloseOnEsc={false}
            title={__("Select a layout for the newsletter", "rrze-newsletter")}
        >
            {<LayoutPicker />}
        </Modal>
    );
};
