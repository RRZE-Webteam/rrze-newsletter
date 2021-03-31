/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./src/components/init-modal/index.js":
/*!********************************************!*\
  !*** ./src/components/init-modal/index.js ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _screens_layout_picker__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./screens/layout-picker */ "./src/components/init-modal/screens/layout-picker/index.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./style.scss */ "./src/components/init-modal/style.scss");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/components/init-modal/index.js",
    _this = undefined;

/**
 * WordPress dependencies
 */


/**
 * Plugin dependencies
 */



/* harmony default export */ __webpack_exports__["default"] = (function () {
  return wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Modal, {
    className: "rrze-newsletter-modal__frame",
    isDismissible: false,
    overlayClassName: "rrze-newsletter-modal__screen-overlay",
    shouldCloseOnClickOutside: false,
    shouldCloseOnEsc: false,
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Select a layout for the newsletter", "rrze-newsletter"),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 15,
      columnNumber: 9
    }
  }, wp.element.createElement(_screens_layout_picker__WEBPACK_IMPORTED_MODULE_2__.default, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 23,
      columnNumber: 14
    }
  }));
});

/***/ }),

/***/ "./src/components/init-modal/screens/layout-picker/SingleLayoutPreview.js":
/*!********************************************************************************!*\
  !*** ./src/components/init-modal/screens/layout-picker/SingleLayoutPreview.js ***!
  \********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/keycodes */ "@wordpress/keycodes");
/* harmony import */ var _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_keycodes__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _utils_consts__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../../../utils/consts */ "./src/utils/consts.js");
/* harmony import */ var _newsletter_preview__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../../newsletter-preview */ "./src/components/newsletter-preview/index.js");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/components/init-modal/screens/layout-picker/SingleLayoutPreview.js",
    _this = undefined;

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */







/**
 * Plugin dependencies
 */




var SingleLayoutPreview = function SingleLayoutPreview(_ref) {
  var isEditable = _ref.isEditable,
      deleteHandler = _ref.deleteHandler,
      saveLayout = _ref.saveLayout,
      selectedLayoutId = _ref.selectedLayoutId,
      setSelectedLayoutId = _ref.setSelectedLayoutId,
      ID = _ref.ID,
      title = _ref.post_title,
      content = _ref.post_content,
      meta = _ref.meta;

  var handleDelete = function handleDelete() {
    // eslint-disable-next-line no-alert
    if (confirm((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)("Are you sure you want to delete this layout?", "rrze-newsletter"))) {
      deleteHandler(ID);
    }
  };

  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(title),
      _useState2 = _slicedToArray(_useState, 2),
      layoutName = _useState2[0],
      setLayoutName = _useState2[1];

  var _useState3 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false),
      _useState4 = _slicedToArray(_useState3, 2),
      isSaving = _useState4[0],
      setIsSaving = _useState4[1];

  var handleLayoutNameChange = function handleLayoutNameChange() {
    if (layoutName !== title) {
      setIsSaving(true);
      saveLayout({
        title: layoutName
      }).then(function () {
        setIsSaving(false);
      });
    }
  };

  var setPreviewBlocks = function setPreviewBlocks(blocks) {
    return blocks.map(function (block) {
      //@todo
      return block;
    });
  };

  var blockPreviewBlocks = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useMemo)(function () {
    return setPreviewBlocks((0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__.parse)(content));
  }, [content]);
  return wp.element.createElement("div", {
    key: ID,
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()("rrze-newsletter-layouts__item", {
      "is-active": selectedLayoutId === ID
    }),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 67,
      columnNumber: 5
    }
  }, wp.element.createElement("div", {
    className: "rrze-newsletter-layouts__item-preview",
    onClick: function onClick() {
      return setSelectedLayoutId(ID);
    },
    onKeyDown: function onKeyDown(event) {
      if (_wordpress_keycodes__WEBPACK_IMPORTED_MODULE_5__.ENTER === event.keyCode || _wordpress_keycodes__WEBPACK_IMPORTED_MODULE_5__.SPACE === event.keyCode) {
        event.preventDefault();
        setSelectedLayoutId(ID);
      }
    },
    role: "button",
    tabIndex: "0",
    "aria-label": title,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 73,
      columnNumber: 7
    }
  }, "" === content ? null : wp.element.createElement(_newsletter_preview__WEBPACK_IMPORTED_MODULE_8__.default, {
    meta: meta,
    blocks: blockPreviewBlocks,
    viewportWidth: 600,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 87,
      columnNumber: 11
    }
  })), isEditable ? wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.TextControl, {
    className: "rrze-newsletter-layouts__item-label",
    value: layoutName,
    onChange: setLayoutName,
    onBlur: handleLayoutNameChange,
    disabled: isSaving,
    onKeyDown: function onKeyDown(event) {
      if (_wordpress_keycodes__WEBPACK_IMPORTED_MODULE_5__.ENTER === event.keyCode) {
        handleLayoutNameChange();
      }
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 95,
      columnNumber: 9
    }
  }) : wp.element.createElement("div", {
    className: "rrze-newsletter-layouts__item-label",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 108,
      columnNumber: 9
    }
  }, title), isEditable && wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
    isDestructive: true,
    isLink: true,
    onClick: handleDelete,
    disabled: isSaving,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 111,
      columnNumber: 9
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)("Delete", "rrze-newsletter")));
};

/* harmony default export */ __webpack_exports__["default"] = ((0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.withDispatch)(function (dispatch, _ref2) {
  var ID = _ref2.ID;

  var _dispatch = dispatch("core"),
      saveEntityRecord = _dispatch.saveEntityRecord;

  return {
    saveLayout: function saveLayout(payload) {
      return saveEntityRecord("postType", _utils_consts__WEBPACK_IMPORTED_MODULE_7__.LAYOUT_CPT_SLUG, _objectSpread({
        status: "publish",
        id: ID
      }, payload));
    }
  };
})(SingleLayoutPreview));

/***/ }),

/***/ "./src/components/init-modal/screens/layout-picker/index.js":
/*!******************************************************************!*\
  !*** ./src/components/init-modal/screens/layout-picker/index.js ***!
  \******************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _utils_consts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../../../utils/consts */ "./src/utils/consts.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../../../utils */ "./src/utils/index.js");
/* harmony import */ var _utils_hooks__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../../../../utils/hooks */ "./src/utils/hooks.js");
/* harmony import */ var _SingleLayoutPreview__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./SingleLayoutPreview */ "./src/components/init-modal/screens/layout-picker/SingleLayoutPreview.js");
/* harmony import */ var _newsletter_preview__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ../../../newsletter-preview */ "./src/components/newsletter-preview/index.js");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/components/init-modal/screens/layout-picker/index.js",
    _this = undefined;

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * External dependencies
 */


/**
 * WordPress dependencies
 */







/**
 * Plugin dependencies
 */






var LAYOUTS_TABS = [{
  title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__.__)("Prebuilt", "rrze-newsletter"),
  filter: function filter(layout) {
    return layout.post_author === undefined;
  }
}, {
  title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__.__)("Saved", "rrze-newsletter"),
  filter: _utils__WEBPACK_IMPORTED_MODULE_9__.isUserDefinedLayout,
  isEditable: true
}];

var LayoutPicker = function LayoutPicker(_ref) {
  var getBlocks = _ref.getBlocks,
      insertBlocks = _ref.insertBlocks,
      replaceBlocks = _ref.replaceBlocks,
      savePost = _ref.savePost,
      setNewsletterMeta = _ref.setNewsletterMeta;

  var _useLayoutsState = (0,_utils_hooks__WEBPACK_IMPORTED_MODULE_10__.useLayoutsState)(),
      layouts = _useLayoutsState.layouts,
      isFetchingLayouts = _useLayoutsState.isFetchingLayouts,
      deleteLayoutPost = _useLayoutsState.deleteLayoutPost;

  var insertLayout = function insertLayout(layoutId) {
    var _ref2 = (0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(layouts, {
      ID: layoutId
    }) || {},
        content = _ref2.post_content,
        _ref2$meta = _ref2.meta,
        meta = _ref2$meta === void 0 ? {} : _ref2$meta;

    var blocksToInsert = content ? (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__.parse)(content) : [];
    var existingBlocksIds = getBlocks().map(function (_ref3) {
      var clientId = _ref3.clientId;
      return clientId;
    });

    if (existingBlocksIds.length) {
      replaceBlocks(existingBlocksIds, blocksToInsert);
    } else {
      insertBlocks(blocksToInsert);
    }

    var metaPayload = _objectSpread({
      rrze_newsletter_template_id: layoutId
    }, meta);

    setNewsletterMeta(metaPayload);
    setTimeout(savePost, 1);
  };

  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(null),
      _useState2 = _slicedToArray(_useState, 2),
      selectedLayoutId = _useState2[0],
      setSelectedLayoutId = _useState2[1];

  var layoutPreviewProps = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useMemo)(function () {
    var layout = selectedLayoutId && (0,lodash__WEBPACK_IMPORTED_MODULE_1__.find)(layouts, {
      ID: selectedLayoutId
    });
    return layout ? {
      blocks: (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__.parse)(layout.post_content),
      meta: layout.meta
    } : null;
  }, [selectedLayoutId, layouts.length]);
  var canRenderPreview = layoutPreviewProps && layoutPreviewProps.blocks.length > 0;

  var renderPreview = function renderPreview() {
    return canRenderPreview ? wp.element.createElement(_newsletter_preview__WEBPACK_IMPORTED_MODULE_12__.default, _extends({}, layoutPreviewProps, {
      viewportWidth: 600,
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 78,
        columnNumber: 7
      }
    })) : wp.element.createElement("p", {
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 80,
        columnNumber: 7
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__.__)("Select a layout to preview.", "rrze-newsletter"));
  };

  var _useState3 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(0),
      _useState4 = _slicedToArray(_useState3, 2),
      activeTabIndex = _useState4[0],
      setActiveTabIndex = _useState4[1];

  var activeTab = LAYOUTS_TABS[activeTabIndex];
  var displayedLayouts = layouts.filter(activeTab.filter); // Switch tab to user layouts if there are any.

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(function () {
    if (layouts.filter(_utils__WEBPACK_IMPORTED_MODULE_9__.isUserDefinedLayout).length) {
      setActiveTabIndex(1);
    }
  }, [layouts.length]);
  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 95,
      columnNumber: 5
    }
  }, wp.element.createElement("div", {
    className: "rrze-newsletter-modal__content",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 96,
      columnNumber: 7
    }
  }, wp.element.createElement("div", {
    className: "rrze-newsletter-tabs rrze-newsletter-buttons-group",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 97,
      columnNumber: 9
    }
  }, LAYOUTS_TABS.map(function (_ref4, i) {
    var title = _ref4.title;
    return wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
      key: i,
      disabled: isFetchingLayouts,
      className: classnames__WEBPACK_IMPORTED_MODULE_0___default()("rrze-newsletter-tabs__button", {
        "rrze-newsletter-tabs__button--is-active": !isFetchingLayouts && i === activeTabIndex
      }),
      onClick: function onClick() {
        return setActiveTabIndex(i);
      },
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 99,
        columnNumber: 13
      }
    }, title);
  })), wp.element.createElement("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()("rrze-newsletter-modal__layouts", {
      "rrze-newsletter-modal__layouts--loading": isFetchingLayouts
    }),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 112,
      columnNumber: 9
    }
  }, isFetchingLayouts ? wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Spinner, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 118,
      columnNumber: 13
    }
  }) : wp.element.createElement("div", {
    className: classnames__WEBPACK_IMPORTED_MODULE_0___default()({
      "rrze-newsletter-layouts": displayedLayouts.length > 0
    }),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 120,
      columnNumber: 13
    }
  }, displayedLayouts.length ? displayedLayouts.map(function (layout) {
    return wp.element.createElement(_SingleLayoutPreview__WEBPACK_IMPORTED_MODULE_11__.default, _extends({
      key: layout.ID,
      selectedLayoutId: selectedLayoutId,
      setSelectedLayoutId: setSelectedLayoutId,
      deleteHandler: deleteLayoutPost,
      isEditable: activeTab.isEditable
    }, layout, {
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 127,
        columnNumber: 19
      }
    }));
  }) : wp.element.createElement("span", {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 137,
      columnNumber: 17
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__.__)('Turn any newsletter to a layout via the "Layout" sidebar menu in the editor.', "rrze-newsletter")))), wp.element.createElement("div", {
    className: "rrze-newsletter-modal__preview",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 148,
      columnNumber: 9
    }
  }, !isFetchingLayouts && renderPreview())), wp.element.createElement("div", {
    className: "rrze-newsletter-modal__action-buttons",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 152,
      columnNumber: 7
    }
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isSecondary: true,
    onClick: function onClick() {
      return insertLayout(_utils_consts__WEBPACK_IMPORTED_MODULE_8__.BLANK_LAYOUT_ID);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 153,
      columnNumber: 9
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__.__)("Start With A Blank Layout", "rrze-newsletter")), wp.element.createElement("span", {
    className: "separator",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 156,
      columnNumber: 9
    }
  }, " "), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isPrimary: true,
    disabled: isFetchingLayouts || !canRenderPreview,
    onClick: function onClick() {
      return insertLayout(selectedLayoutId);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 157,
      columnNumber: 9
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_7__.__)("Use Selected Layout", "rrze-newsletter"))));
};

/* harmony default export */ __webpack_exports__["default"] = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_4__.compose)([(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_5__.withSelect)(function (select) {
  var _select = select("core/block-editor"),
      getBlocks = _select.getBlocks;

  return {
    getBlocks: getBlocks
  };
}), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_5__.withDispatch)(function (dispatch) {
  var _dispatch = dispatch("core/editor"),
      savePost = _dispatch.savePost,
      editPost = _dispatch.editPost;

  var _dispatch2 = dispatch("core/block-editor"),
      insertBlocks = _dispatch2.insertBlocks,
      replaceBlocks = _dispatch2.replaceBlocks;

  return {
    savePost: savePost,
    insertBlocks: insertBlocks,
    replaceBlocks: replaceBlocks,
    setNewsletterMeta: function setNewsletterMeta(meta) {
      return editPost({
        meta: meta
      });
    }
  };
})])(LayoutPicker));

/***/ }),

/***/ "./src/components/newsletter-preview/index.js":
/*!****************************************************!*\
  !*** ./src/components/newsletter-preview/index.js ***!
  \****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./style.scss */ "./src/components/newsletter-preview/style.scss");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/components/newsletter-preview/index.js",
    _this = undefined;

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function _objectWithoutProperties(source, excluded) { if (source == null) return {}; var target = _objectWithoutPropertiesLoose(source, excluded); var key, i; if (Object.getOwnPropertySymbols) { var sourceSymbolKeys = Object.getOwnPropertySymbols(source); for (i = 0; i < sourceSymbolKeys.length; i++) { key = sourceSymbolKeys[i]; if (excluded.indexOf(key) >= 0) continue; if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue; target[key] = source[key]; } } return target; }

function _objectWithoutPropertiesLoose(source, excluded) { if (source == null) return {}; var target = {}; var sourceKeys = Object.keys(source); var key, i; for (i = 0; i < sourceKeys.length; i++) { key = sourceKeys[i]; if (excluded.indexOf(key) >= 0) continue; target[key] = source[key]; } return target; }

/**
 * WordPress dependencies
 */


/**
 * Plugin dependencies
 */



var NewsletterPreview = function NewsletterPreview(_ref) {
  var _ref$meta = _ref.meta,
      meta = _ref$meta === void 0 ? {} : _ref$meta,
      props = _objectWithoutProperties(_ref, ["meta"]);

  var ELEMENT_ID = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useMemo)(function () {
    return "preview-".concat(Math.round(Math.random() * 1000));
  }, []);
  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 19,
      columnNumber: 9
    }
  }, wp.element.createElement("style", {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 20,
      columnNumber: 13
    }
  }, "".concat(meta.rrze_newsletter_font_body ? "\n#".concat(ELEMENT_ID, " *:not( code ) {\n  font-family: ").concat(meta.rrze_newsletter_font_body, ";\n}") : " ").concat(meta.rrze_newsletter_font_header ? "\n#".concat(ELEMENT_ID, " h1, #").concat(ELEMENT_ID, " h2, #").concat(ELEMENT_ID, " h3, #").concat(ELEMENT_ID, " h4, #").concat(ELEMENT_ID, " h5, #").concat(ELEMENT_ID, " h6 {\n  font-family: ").concat(meta.rrze_newsletter_font_header, ";\n}") : " ")), wp.element.createElement("div", {
    id: ELEMENT_ID,
    className: "rrze-newsletter__layout-preview",
    style: {
      backgroundColor: meta.rrze_newsletter_background_color
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 35,
      columnNumber: 13
    }
  }, wp.element.createElement(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_0__.BlockPreview, _extends({}, props, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 42,
      columnNumber: 17
    }
  }))));
};

/* harmony default export */ __webpack_exports__["default"] = (NewsletterPreview);

/***/ }),

/***/ "./src/components/select-control-with-optgroup/index.js":
/*!**************************************************************!*\
  !*** ./src/components/select-control-with-optgroup/index.js ***!
  \**************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ SelectControlWithOptGroup; }
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/components/select-control-with-optgroup/index.js";

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _objectWithoutProperties(source, excluded) { if (source == null) return {}; var target = _objectWithoutPropertiesLoose(source, excluded); var key, i; if (Object.getOwnPropertySymbols) { var sourceSymbolKeys = Object.getOwnPropertySymbols(source); for (i = 0; i < sourceSymbolKeys.length; i++) { key = sourceSymbolKeys[i]; if (excluded.indexOf(key) >= 0) continue; if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue; target[key] = source[key]; } } return target; }

function _objectWithoutPropertiesLoose(source, excluded) { if (source == null) return {}; var target = {}; var sourceKeys = Object.keys(source); var key, i; for (i = 0; i < sourceKeys.length; i++) { key = sourceKeys[i]; if (excluded.indexOf(key) >= 0) continue; target[key] = source[key]; } return target; }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */



/**
 * SelectControl with optgroup support
 */

function SelectControlWithOptGroup(_ref) {
  var _this = this;

  var help = _ref.help,
      label = _ref.label,
      _ref$multiple = _ref.multiple,
      multiple = _ref$multiple === void 0 ? false : _ref$multiple,
      onChange = _ref.onChange,
      _ref$optgroups = _ref.optgroups,
      optgroups = _ref$optgroups === void 0 ? [] : _ref$optgroups,
      className = _ref.className,
      hideLabelFromVision = _ref.hideLabelFromVision,
      props = _objectWithoutProperties(_ref, ["help", "label", "multiple", "onChange", "optgroups", "className", "hideLabelFromVision"]);

  var instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.useInstanceId)(SelectControlWithOptGroup);
  var id = "inspector-select-control-".concat(instanceId);

  var onChangeValue = function onChangeValue(event) {
    if (multiple) {
      var selectedOptions = _toConsumableArray(event.target.options).filter(function (_ref2) {
        var selected = _ref2.selected;
        return selected;
      });

      var newValues = selectedOptions.map(function (_ref3) {
        var value = _ref3.value;
        return value;
      });
      onChange(newValues);
      return;
    }

    onChange(event.target.value);
  }; // Disable reason: A select with an onchange throws a warning


  if ((0,lodash__WEBPACK_IMPORTED_MODULE_0__.isEmpty)(optgroups)) {
    return null;
  }

  return wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.BaseControl, {
    label: label,
    hideLabelFromVision: hideLabelFromVision,
    id: id,
    help: help,
    className: className,
    __self: this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 46,
      columnNumber: 9
    }
  }, wp.element.createElement("select", _extends({
    id: id,
    className: "components-select-control__input",
    onChange: onChangeValue,
    "aria-describedby": !help ? "".concat(id, "__help") : undefined,
    multiple: multiple
  }, props, {
    __self: this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 53,
      columnNumber: 13
    }
  }), optgroups.map(function (_ref4, optgroupIndex) {
    var optgroupLabel = _ref4.label,
        options = _ref4.options;
    return wp.element.createElement("optgroup", {
      label: optgroupLabel,
      key: optgroupIndex,
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 63,
        columnNumber: 25
      }
    }, options.map(function (option, optionIndex) {
      return wp.element.createElement("option", {
        key: "".concat(option.label, "-").concat(option.value, "-").concat(optionIndex),
        value: option.value,
        disabled: option.disabled,
        __self: _this,
        __source: {
          fileName: _jsxFileName,
          lineNumber: 65,
          columnNumber: 33
        }
      }, option.label);
    }));
  })));
}

/***/ }),

/***/ "./src/components/send-button/index.js":
/*!*********************************************!*\
  !*** ./src/components/send-button/index.js ***!
  \*********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./style.scss */ "./src/components/send-button/style.scss");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/components/send-button/index.js",
    _this = undefined;

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

/**
 * WordPress dependencies
 */





/**
 * External dependencies
 */



/* harmony default export */ __webpack_exports__["default"] = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.compose)([(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_0__.withDispatch)(function (dispatch) {
  var _dispatch = dispatch("core/editor"),
      editPost = _dispatch.editPost,
      savePost = _dispatch.savePost;

  return {
    editPost: editPost,
    savePost: savePost
  };
}), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_0__.withSelect)(function (select, _ref) {
  var forceIsDirty = _ref.forceIsDirty;

  var _select = select("core/editor"),
      getCurrentPost = _select.getCurrentPost,
      getEditedPostAttribute = _select.getEditedPostAttribute,
      getEditedPostVisibility = _select.getEditedPostVisibility,
      isEditedPostPublishable = _select.isEditedPostPublishable,
      isEditedPostSaveable = _select.isEditedPostSaveable,
      isSavingPost = _select.isSavingPost,
      isEditedPostBeingScheduled = _select.isEditedPostBeingScheduled,
      isCurrentPostPublished = _select.isCurrentPostPublished;

  return {
    isPublishable: forceIsDirty || isEditedPostPublishable(),
    isSaveable: isEditedPostSaveable(),
    status: getEditedPostAttribute("status"),
    isSaving: isSavingPost(),
    isEditedPostBeingScheduled: isEditedPostBeingScheduled(),
    hasPublishAction: (0,lodash__WEBPACK_IMPORTED_MODULE_5__.get)(getCurrentPost(), ["_links", "wp:action-publish"], false),
    visibility: getEditedPostVisibility(),
    meta: getEditedPostAttribute("meta"),
    isPublished: isCurrentPostPublished()
  };
})])(function (_ref2) {
  var editPost = _ref2.editPost,
      savePost = _ref2.savePost,
      isPublishable = _ref2.isPublishable,
      isSaveable = _ref2.isSaveable,
      isSaving = _ref2.isSaving,
      status = _ref2.status,
      isEditedPostBeingScheduled = _ref2.isEditedPostBeingScheduled,
      hasPublishAction = _ref2.hasPublishAction,
      visibility = _ref2.visibility,
      meta = _ref2.meta,
      isPublished = _ref2.isPublished;
  var _meta$newsletterValid = meta.newsletterValidationErrors,
      newsletterValidationErrors = _meta$newsletterValid === void 0 ? [] : _meta$newsletterValid,
      rrze_newsletter_is_public = meta.rrze_newsletter_is_public;
  var isButtonEnabled = (isPublishable || isEditedPostBeingScheduled) && isSaveable && !isPublished && !isSaving && 0 === newsletterValidationErrors.length;
  var label;

  if (isPublished) {
    if (isSaving) label = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Sending", "rrze-newsletter");else {
      label = rrze_newsletter_is_public ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Sent and Published", "rrze-newsletter") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Sent", "rrze-newsletter");
    }
  } else if ("future" === status) {
    // Scheduled to be sent
    label = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Scheduled", "rrze-newsletter");
  } else if (isEditedPostBeingScheduled) {
    label = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Schedule sending", "rrze-newsletter");
  } else {
    label = rrze_newsletter_is_public ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Send and Publish", "rrze-newsletter") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Send", "rrze-newsletter");
  }

  var publishStatus;

  if (!hasPublishAction) {
    publishStatus = "pending";
  } else if (visibility === "private") {
    publishStatus = "private";
  } else if (isEditedPostBeingScheduled) {
    publishStatus = "future";
  } else {
    publishStatus = "publish";
  }

  var triggerNewsletterSend = function triggerNewsletterSend() {
    editPost({
      status: publishStatus
    });
    savePost();
  };

  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(false),
      _useState2 = _slicedToArray(_useState, 2),
      modalVisible = _useState2[0],
      setModalVisible = _useState2[1]; // For sent newsletters, display the generic button text.


  if (isPublished) {
    return wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
      className: "editor-post-publish-button",
      isBusy: isSaving,
      isPrimary: true,
      disabled: isSaving,
      onClick: savePost,
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 111,
        columnNumber: 17
      }
    }, isSaving ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Updating...", "rrze-newsletter") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Update", "rrze-newsletter"));
  }

  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 126,
      columnNumber: 13
    }
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
    className: "editor-post-publish-button",
    isBusy: isSaving && "publish" === status,
    isPrimary: true,
    onClick: /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              _context.next = 2;
              return savePost();

            case 2:
              setModalVisible(true);

            case 3:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    })),
    disabled: !isButtonEnabled,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 127,
      columnNumber: 17
    }
  }, label), modalVisible && wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Modal, {
    className: "rrze-newsletter__modal",
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Send your newsletter?", "rrze-newsletter"),
    onRequestClose: function onRequestClose() {
      return setModalVisible(false);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 140,
      columnNumber: 21
    }
  }, newsletterValidationErrors.length ? wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Notice, {
    status: "error",
    isDismissible: false,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 146,
      columnNumber: 29
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("The following errors prevent the newsletter from being sent:", "rrze-newsletter"), wp.element.createElement("ul", {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 151,
      columnNumber: 33
    }
  }, newsletterValidationErrors.map(function (error, i) {
    return wp.element.createElement("li", {
      key: i,
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 154,
        columnNumber: 45
      }
    }, error);
  }))) : null, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
    isPrimary: true,
    disabled: newsletterValidationErrors.length > 0,
    onClick: function onClick() {
      triggerNewsletterSend();
      setModalVisible(false);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 160,
      columnNumber: 25
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Send", "rrze-newsletter")), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Button, {
    isSecondary: true,
    onClick: function onClick() {
      return setModalVisible(false);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 170,
      columnNumber: 25
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_4__.__)("Cancel", "rrze-newsletter"))));
}));

/***/ }),

/***/ "./src/components/with-api-handler/index.js":
/*!**************************************************!*\
  !*** ./src/components/with-api-handler/index.js ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__);
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/components/with-api-handler/index.js",
    _this = undefined;

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

/**
 * WordPress dependencies
 */





/* harmony default export */ __webpack_exports__["default"] = (function () {
  return (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__.createHigherOrderComponent)(function (OriginalComponent) {
    return function (props) {
      var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)(false),
          _useState2 = _slicedToArray(_useState, 2),
          inFlight = _useState2[0],
          setInFlight = _useState2[1];

      var _useState3 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.useState)({}),
          _useState4 = _slicedToArray(_useState3, 2),
          errors = _useState4[0],
          setErrors = _useState4[1];

      var _dispatch = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.dispatch)("core/notices"),
          createSuccessNotice = _dispatch.createSuccessNotice,
          createErrorNotice = _dispatch.createErrorNotice,
          removeNotice = _dispatch.removeNotice;

      var _select = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.select)("core/notices"),
          getNotices = _select.getNotices;

      var setInFlightForAsync = function setInFlightForAsync() {
        setInFlight(true);
      };

      var successNote = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Newsletter sent on ", "rrze-newsletter");

      var apiFetchWithErrorHandling = function apiFetchWithErrorHandling(apiRequest) {
        setInFlight(true);
        return new Promise(function (resolve, reject) {
          _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_1___default()(apiRequest).then(function (response) {
            var message = response.message;
            getNotices().forEach(function (notice) {
              if ("error" !== notice.status && ("success" !== notice.status || -1 === notice.content.indexOf(successNote))) {
                removeNotice(notice.id);
              }
            });

            if (message) {
              createSuccessNotice(message);
            }

            setInFlight(false);
            setErrors({});
            resolve(response);
          }).catch(function (error) {
            var message = error.message;
            getNotices().forEach(function (notice) {
              if ("error" !== notice.status && ("success" !== notice.status || -1 === notice.content.indexOf(successNote))) {
                removeNotice(notice.id);
              }
            });
            createErrorNotice(message);
            setInFlight(false);
            setErrors(_defineProperty({}, error.code, true));
            reject(error);
          });
        });
      };

      return wp.element.createElement(OriginalComponent, _extends({}, props, {
        apiFetchWithErrorHandling: apiFetchWithErrorHandling,
        errors: errors,
        setInFlightForAsync: setInFlightForAsync,
        inFlight: inFlight,
        successNote: successNote,
        __self: _this,
        __source: {
          fileName: _jsxFileName,
          lineNumber: 68,
          columnNumber: 17
        }
      }));
    };
  }, "with-api-handler");
});

/***/ }),

/***/ "./src/editor/blocks-validation/blocks-filters.js":
/*!********************************************************!*\
  !*** ./src/editor/blocks-validation/blocks-filters.js ***!
  \********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "addBlocksValidationFilter": function() { return /* binding */ addBlocksValidationFilter; }
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/editor/blocks-validation/blocks-filters.js",
    _this = undefined;

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */






var handleSideAlignment = function handleSideAlignment(warnings, props) {
  if (props.attributes.align === "left" || props.attributes.align === "right") {
    warnings.push((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Side alignment", "rrze-newsletter"));
  }

  return warnings;
};

var isCenterAligned = function isCenterAligned(block) {
  return block.attributes.verticalAlignment === "center";
};

var getWarnings = function getWarnings(props) {
  var warnings = [];

  var _select = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.select)("core/block-editor"),
      getBlock = _select.getBlock;

  var block = getBlock(props.block.clientId);

  switch (props.name) {
    case "core/group":
      if (props.attributes.__nestedGroupWarning) {
        warnings.push((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Nested group", "rrze-newsletter"));
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
        var innerBlocks = block.innerBlocks;
        var isAnyColumnCenterAligned = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.some)(innerBlocks, isCenterAligned);
        var areAllColumnsCenterAligned = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.every)(innerBlocks, isCenterAligned);

        if (isAnyColumnCenterAligned && !areAllColumnsCenterAligned) {
          warnings.push((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Unequal middle alignment. All or none of the columns should be middle-aligned.", "rrze-newsletter"));
        }
      }

      break;

    case "core/column":
      if (props.attributes.__nestedColumnWarning) {
        warnings.push((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Nested columns", "rrze-newsletter"));
      }

      break;

    case "core/image":
      warnings = handleSideAlignment(warnings, props);

      if (props.attributes.align === "full") {
        warnings.push((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Full width", "rrze-newsletter"));
      }

      break;

    case "core/paragraph":
      if (props.attributes.content.indexOf("<img") >= 0) {
        warnings.push((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Inline image", "rrze-newsletter"));
      }

      if (props.attributes.dropCap) {
        warnings.push((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("Drop cap", "rrze-newsletter"));
      }

      break;
  }

  return warnings;
};

var withUnsupportedFeaturesNotices = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__.createHigherOrderComponent)(function (BlockListBlock) {
  return function (props) {
    var warnings = getWarnings(props);
    return warnings.length ? wp.element.createElement("div", {
      className: "rrze-newsletter__editor-block",
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 98,
        columnNumber: 17
      }
    }, wp.element.createElement("div", {
      className: "rrze-newsletter__editor-block__warning components-notice is-error",
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 99,
        columnNumber: 21
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)("These features will not be displayed correctly in an email, please remove them:", "rrze-newsletter"), warnings.map(function (warning, i) {
      return wp.element.createElement("strong", {
        key: i,
        __self: _this,
        __source: {
          fileName: _jsxFileName,
          lineNumber: 105,
          columnNumber: 29
        }
      }, wp.element.createElement("br", {
        __self: _this,
        __source: {
          fileName: _jsxFileName,
          lineNumber: 106,
          columnNumber: 33
        }
      }), warning);
    })), wp.element.createElement(BlockListBlock, _extends({}, props, {
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 111,
        columnNumber: 21
      }
    }))) : wp.element.createElement(BlockListBlock, _extends({}, props, {
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 114,
        columnNumber: 17
      }
    }));
  };
}, "withInspectorControl");
var addBlocksValidationFilter = function addBlocksValidationFilter() {
  (0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__.addFilter)("editor.BlockListBlock", "rrze-newsletter/unsupported-features-notices", withUnsupportedFeaturesNotices);
};

/***/ }),

/***/ "./src/editor/blocks-validation/nesting-detection.js":
/*!***********************************************************!*\
  !*** ./src/editor/blocks-validation/nesting-detection.js ***!
  \***********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "NestedColumnsDetection": function() { return /* binding */ NestedColumnsDetection; }
/* harmony export */ });
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */





var NestedColumnsDetectionBase = function NestedColumnsDetectionBase(_ref) {
  var blocks = _ref.blocks,
      updateBlock = _ref.updateBlock;

  var handleWarning = function handleWarning(block, condition, warningKeyName) {
    var hasWarning = block.attributes[warningKeyName] === true;

    if (condition && !hasWarning) {
      updateBlock(block.clientId, _objectSpread(_objectSpread({}, block), {}, {
        attributes: _objectSpread(_objectSpread({}, block.attributes), {}, _defineProperty({}, warningKeyName, true))
      }));
    } else if (!condition && hasWarning) {
      updateBlock(block.clientId, _objectSpread(_objectSpread({}, block), {}, {
        attributes: _objectSpread(_objectSpread({}, block.attributes), {}, _defineProperty({}, warningKeyName, false))
      }));
    }
  };

  var warnIfColumnHasColumns = function warnIfColumnHasColumns(block) {
    if (block.name === "core/column") {
      var hasColumns = (0,lodash__WEBPACK_IMPORTED_MODULE_0__.some)(block.innerBlocks, function (_ref2) {
        var name = _ref2.name;
        return name === "core/columns";
      });
      handleWarning(block, hasColumns, "__nestedColumnWarning");
    }

    block.innerBlocks.forEach(warnIfColumnHasColumns);
  };

  var warnIfIsGroupBlock = function warnIfIsGroupBlock(block) {
    handleWarning(block, block.name === "core/group", "__nestedGroupWarning");
    block.innerBlocks.forEach(warnIfIsGroupBlock);
  };

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(function () {
    blocks.forEach(function (block) {
      // A column cannot host columns.
      block.innerBlocks.forEach(warnIfColumnHasColumns); // Group can only be top-level.

      block.innerBlocks.forEach(warnIfIsGroupBlock);
    });
  }, [blocks]);
  return null;
};

var NestedColumnsDetection = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__.compose)([(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.withSelect)(function (select) {
  var _select = select("core/block-editor"),
      getBlocks = _select.getBlocks;

  return {
    blocks: getBlocks()
  };
}), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.withDispatch)(function (dispatch) {
  return {
    updateBlock: function updateBlock(id, block) {
      dispatch("core/block-editor").replaceBlock(id, block);
    }
  };
})])(NestedColumnsDetectionBase);

/***/ }),

/***/ "./src/newsletter-editor/editor/index.js":
/*!***********************************************!*\
  !*** ./src/newsletter-editor/editor/index.js ***!
  \***********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/plugins */ "@wordpress/plugins");
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _components_with_api_handler__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../components/with-api-handler */ "./src/components/with-api-handler/index.js");
/* harmony import */ var _components_send_button__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../components/send-button */ "./src/components/send-button/index.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./style.scss */ "./src/newsletter-editor/editor/style.scss");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/newsletter-editor/editor/index.js",
    _this = undefined;

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */





/**
 * Plugin dependencies
 */




var Editor = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.compose)([(0,_components_with_api_handler__WEBPACK_IMPORTED_MODULE_5__.default)(), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.withSelect)(function (select) {
  var _select = select("core/editor"),
      getCurrentPostId = _select.getCurrentPostId,
      getCurrentPostAttribute = _select.getCurrentPostAttribute,
      getEditedPostAttribute = _select.getEditedPostAttribute,
      isPublishingPost = _select.isPublishingPost,
      isSavingPost = _select.isSavingPost,
      isCleanNewPost = _select.isCleanNewPost;

  var _select2 = select("core/edit-post"),
      getActiveGeneralSidebarName = _select2.getActiveGeneralSidebarName;

  var meta = getEditedPostAttribute("meta");
  var status = getCurrentPostAttribute("status");
  var sentDate = getCurrentPostAttribute("date");
  return {
    isCleanNewPost: isCleanNewPost(),
    postId: getCurrentPostId(),
    isReady: meta.newsletterValidationErrors ? meta.newsletterValidationErrors.length === 0 : false,
    activeSidebarName: getActiveGeneralSidebarName(),
    isPublishingOrSavingPost: isSavingPost() || isPublishingPost(),
    status: status,
    sentDate: sentDate,
    isPublic: meta.rrze_newsletter_is_public
  };
}), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.withDispatch)(function (dispatch) {
  var _dispatch = dispatch("core/editor"),
      lockPostAutosaving = _dispatch.lockPostAutosaving,
      lockPostSaving = _dispatch.lockPostSaving,
      unlockPostSaving = _dispatch.unlockPostSaving,
      editPost = _dispatch.editPost;

  var _dispatch2 = dispatch("core/notices"),
      createNotice = _dispatch2.createNotice;

  return {
    lockPostAutosaving: lockPostAutosaving,
    lockPostSaving: lockPostSaving,
    unlockPostSaving: unlockPostSaving,
    editPost: editPost,
    createNotice: createNotice
  };
})])(function (props) {
  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(document.createElement("div")),
      _useState2 = _slicedToArray(_useState, 1),
      publishEl = _useState2[0]; // Create alternate publish button


  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(function () {
    var publishButton = document.getElementsByClassName("editor-post-publish-button__button")[0];
    publishButton.parentNode.insertBefore(publishEl, publishButton);
  }, []); // Set color palette option.

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(function () {
    if ((0,lodash__WEBPACK_IMPORTED_MODULE_0__.isEmpty)(props.colorPalette)) {
      return;
    }

    props.apiFetchWithErrorHandling({
      path: "/rrze-newsletter/v1/color-palette",
      data: props.colorPalette,
      method: "POST"
    });
  }, [JSON.stringify(props.colorPalette)]); // Lock or unlock post publishing.

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(function () {
    if (props.isReady) {
      props.unlockPostSaving("rrze-newsletter-post-lock");
    } else {
      props.lockPostSaving("rrze-newsletter-post-lock");
    }
  }, [props.isReady]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(function () {
    if ("publish" === props.status && !props.isPublishingOrSavingPost) {
      var dateTime = props.sentDate ? new Date(props.sentDate).toLocaleString() : ""; // Lock autosaving after a newsletter is sent.

      props.lockPostAutosaving(); // Show an editor notice if the newsletter has been sent.

      props.createNotice("success", props.successNote + dateTime, {
        isDismissible: false
      });
    }
  }, [props.status]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useEffect)(function () {
    // Hide post title if the newsletter is a not a public post.
    var editorTitleEl = document.querySelector(".editor-post-title");

    if (editorTitleEl) {
      editorTitleEl.classList[props.isPublic ? "remove" : "add"]("rrze-newsletter-post-title-hidden");
    }
  }, [props.isPublic]);
  return (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.createPortal)(wp.element.createElement(_components_send_button__WEBPACK_IMPORTED_MODULE_6__.default, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 123,
      columnNumber: 25
    }
  }), publishEl);
});
/* harmony default export */ __webpack_exports__["default"] = (function () {
  (0,_wordpress_plugins__WEBPACK_IMPORTED_MODULE_4__.registerPlugin)("rrze-newsletter-edit", {
    render: Editor
  });
});

/***/ }),

/***/ "./src/newsletter-editor/index.js":
/*!****************************************!*\
  !*** ./src/newsletter-editor/index.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_edit_post__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/edit-post */ "@wordpress/edit-post");
/* harmony import */ var _wordpress_edit_post__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/plugins */ "@wordpress/plugins");
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _components_init_modal__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../components/init-modal */ "./src/components/init-modal/index.js");
/* harmony import */ var _layout___WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./layout/ */ "./src/newsletter-editor/layout/index.js");
/* harmony import */ var _sidebar___WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./sidebar/ */ "./src/newsletter-editor/sidebar/index.js");
/* harmony import */ var _testing___WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./testing/ */ "./src/newsletter-editor/testing/index.js");
/* harmony import */ var _styling___WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./styling/ */ "./src/newsletter-editor/styling/index.js");
/* harmony import */ var _public__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./public */ "./src/newsletter-editor/public/index.js");
/* harmony import */ var _editor___WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./editor/ */ "./src/newsletter-editor/editor/index.js");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/newsletter-editor/index.js",
    _this = undefined;

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

/**
 * WordPress dependencies
 */






/**
 * Plugin dependencies
 */








(0,_editor___WEBPACK_IMPORTED_MODULE_12__.default)();

var NewsletterEdit = function NewsletterEdit(_ref) {
  var layoutId = _ref.layoutId;

  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(window && window.rrze_newsletter_data && window.rrze_newsletter_data.is_service_provider_configured !== "1"),
      _useState2 = _slicedToArray(_useState, 2),
      shouldDisplaySettings = _useState2[0],
      setShouldDisplaySettings = _useState2[1];

  var isDisplayingInitModal = shouldDisplaySettings || -1 === layoutId;
  return isDisplayingInitModal ? wp.element.createElement(_components_init_modal__WEBPACK_IMPORTED_MODULE_6__.default, {
    shouldDisplaySettings: shouldDisplaySettings,
    onSetupStatus: setShouldDisplaySettings,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 34,
      columnNumber: 9
    }
  }) : wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 39,
      columnNumber: 9
    }
  }, wp.element.createElement(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_4__.PluginDocumentSettingPanel, {
    name: "newsletters-settings-panel",
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Newsletter", "rrze-newsletter"),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 40,
      columnNumber: 13
    }
  }, wp.element.createElement(_sidebar___WEBPACK_IMPORTED_MODULE_8__.default, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 44,
      columnNumber: 17
    }
  }), wp.element.createElement(_public__WEBPACK_IMPORTED_MODULE_11__.PublicSettings, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 45,
      columnNumber: 17
    }
  })), wp.element.createElement(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_4__.PluginDocumentSettingPanel, {
    name: "newsletters-styling-panel",
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Styling", "rrze-newsletter"),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 47,
      columnNumber: 13
    }
  }, wp.element.createElement(_styling___WEBPACK_IMPORTED_MODULE_10__.Styling, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 51,
      columnNumber: 17
    }
  })), wp.element.createElement(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_4__.PluginDocumentSettingPanel, {
    name: "newsletters-testing-panel",
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Testing", "rrze-newsletter"),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 53,
      columnNumber: 13
    }
  }, wp.element.createElement(_testing___WEBPACK_IMPORTED_MODULE_9__.default, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 57,
      columnNumber: 17
    }
  })), wp.element.createElement(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_4__.PluginDocumentSettingPanel, {
    name: "newsletters-layout-panel",
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Layout", "rrze-newsletter"),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 59,
      columnNumber: 13
    }
  }, wp.element.createElement(_layout___WEBPACK_IMPORTED_MODULE_7__.default, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 63,
      columnNumber: 17
    }
  })), wp.element.createElement(_styling___WEBPACK_IMPORTED_MODULE_10__.ApplyStyling, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 66,
      columnNumber: 13
    }
  }));
};

var NewsletterEditWithSelect = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__.compose)([(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.withSelect)(function (select) {
  var _select = select("core/editor"),
      getEditedPostAttribute = _select.getEditedPostAttribute;

  var meta = getEditedPostAttribute("meta");
  return {
    layoutId: meta.rrze_newsletter_template_id
  };
})])(NewsletterEdit);
(0,_wordpress_plugins__WEBPACK_IMPORTED_MODULE_5__.registerPlugin)("rrze-newsletter-sidebar", {
  render: NewsletterEditWithSelect,
  icon: null
});

/***/ }),

/***/ "./src/newsletter-editor/layout/index.js":
/*!***********************************************!*\
  !*** ./src/newsletter-editor/layout/index.js ***!
  \***********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! lodash */ "lodash");
/* harmony import */ var lodash__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(lodash__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _utils_hooks__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../utils/hooks */ "./src/utils/hooks.js");
/* harmony import */ var _utils_consts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../utils/consts */ "./src/utils/consts.js");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../utils */ "./src/utils/index.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./style.scss */ "./src/newsletter-editor/layout/style.scss");
/* harmony import */ var _components_newsletter_preview__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../../components/newsletter-preview */ "./src/components/newsletter-preview/index.js");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/newsletter-editor/layout/index.js",
    _this = undefined;

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * External dependencies
 */

/**
 * WordPress dependencies
 */







/**
 * Plugin dependencies
 */






/* harmony default export */ __webpack_exports__["default"] = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.compose)([(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.withSelect)(function (select) {
  var _select = select("core/editor"),
      getEditedPostAttribute = _select.getEditedPostAttribute,
      isEditedPostEmpty = _select.isEditedPostEmpty,
      getCurrentPostId = _select.getCurrentPostId;

  var _select2 = select("core/block-editor"),
      getBlocks = _select2.getBlocks;

  var meta = getEditedPostAttribute("meta");
  var layoutId = meta.rrze_newsletter_template_id,
      rrze_newsletter_background_color = meta.rrze_newsletter_background_color,
      rrze_newsletter_font_body = meta.rrze_newsletter_font_body,
      rrze_newsletter_font_header = meta.rrze_newsletter_font_header;
  return {
    layoutId: layoutId,
    postTitle: getEditedPostAttribute("title"),
    postBlocks: getBlocks(),
    isEditedPostEmpty: isEditedPostEmpty(),
    currentPostId: getCurrentPostId(),
    stylingMeta: {
      rrze_newsletter_background_color: rrze_newsletter_background_color,
      rrze_newsletter_font_body: rrze_newsletter_font_body,
      rrze_newsletter_font_header: rrze_newsletter_font_header
    }
  };
}), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.withDispatch)(function (dispatch, _ref) {
  var currentPostId = _ref.currentPostId,
      stylingMeta = _ref.stylingMeta;

  var _dispatch = dispatch("core/block-editor"),
      replaceBlocks = _dispatch.replaceBlocks;

  var _dispatch2 = dispatch("core/editor"),
      editPost = _dispatch2.editPost;

  var _dispatch3 = dispatch("core"),
      saveEntityRecord = _dispatch3.saveEntityRecord;

  return {
    replaceBlocks: replaceBlocks,
    saveLayoutIdMeta: function saveLayoutIdMeta(id) {
      editPost({
        meta: {
          rrze_newsletter_template_id: id
        }
      });
      saveEntityRecord("postType", _utils_consts__WEBPACK_IMPORTED_MODULE_8__.NEWSLETTER_CPT_SLUG, {
        id: currentPostId,
        meta: _objectSpread({
          rrze_newsletter_template_id: id
        }, stylingMeta)
      });
    },
    saveLayout: function saveLayout(payload) {
      return saveEntityRecord("postType", _utils_consts__WEBPACK_IMPORTED_MODULE_8__.LAYOUT_CPT_SLUG, _objectSpread({
        status: "publish"
      }, payload));
    }
  };
})])(function (_ref2) {
  var saveLayoutIdMeta = _ref2.saveLayoutIdMeta,
      layoutId = _ref2.layoutId,
      replaceBlocks = _ref2.replaceBlocks,
      saveLayout = _ref2.saveLayout,
      postBlocks = _ref2.postBlocks,
      postTitle = _ref2.postTitle,
      isEditedPostEmpty = _ref2.isEditedPostEmpty,
      stylingMeta = _ref2.stylingMeta;

  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useState)(false),
      _useState2 = _slicedToArray(_useState, 2),
      warningModalVisible = _useState2[0],
      setWarningModalVisible = _useState2[1];

  var _useLayoutsState = (0,_utils_hooks__WEBPACK_IMPORTED_MODULE_7__.useLayoutsState)(),
      layouts = _useLayoutsState.layouts,
      isFetchingLayouts = _useLayoutsState.isFetchingLayouts;

  var _useState3 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useState)({}),
      _useState4 = _slicedToArray(_useState3, 2),
      usedLayout = _useState4[0],
      setUsedLayout = _useState4[1];

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(function () {
    setUsedLayout((0,lodash__WEBPACK_IMPORTED_MODULE_0__.find)(layouts, {
      ID: layoutId
    }) || {});
  }, [layouts.length]);
  var blockPreview = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useMemo)(function () {
    return usedLayout.post_content ? (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__.parse)(usedLayout.post_content) : null;
  }, [usedLayout]);

  var clearPost = function clearPost() {
    var clientIds = postBlocks.map(function (_ref3) {
      var clientId = _ref3.clientId;
      return clientId;
    });

    if (clientIds && clientIds.length) {
      replaceBlocks(clientIds, []);
    }
  };

  var _useState5 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useState)(false),
      _useState6 = _slicedToArray(_useState5, 2),
      isSavingLayout = _useState6[0],
      setIsSavingLayout = _useState6[1];

  var _useState7 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useState)(null),
      _useState8 = _slicedToArray(_useState7, 2),
      isManageModalVisible = _useState8[0],
      setIsManageModalVisible = _useState8[1];

  var _useState9 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useState)(postTitle),
      _useState10 = _slicedToArray(_useState9, 2),
      newLayoutName = _useState10[0],
      setNewLayoutName = _useState10[1];

  var handleLayoutUpdate = function handleLayoutUpdate(updatedLayout) {
    setIsSavingLayout(false); // Set this new layout as the newsletter's layout

    saveLayoutIdMeta(updatedLayout.id); // Update the layout preview
    // The shape of this data is different than the API response for CPT

    setUsedLayout(_objectSpread(_objectSpread({}, updatedLayout), {}, {
      post_content: updatedLayout.content.raw,
      post_title: updatedLayout.title.raw,
      post_type: _utils_consts__WEBPACK_IMPORTED_MODULE_8__.LAYOUT_CPT_SLUG
    }));
  };

  var postContent = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useMemo)(function () {
    return (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_2__.serialize)(postBlocks);
  }, [postBlocks]);
  var isPostContentSameAsLayout = postContent === usedLayout.post_content && (0,lodash__WEBPACK_IMPORTED_MODULE_0__.isEqual)(usedLayout.meta, stylingMeta);

  var handleSaveAsLayout = function handleSaveAsLayout() {
    setIsSavingLayout(true);
    var updatePayload = {
      title: newLayoutName,
      content: postContent,
      meta: stylingMeta
    };
    saveLayout(updatePayload).then(function (newLayout) {
      setIsManageModalVisible(false);
      handleLayoutUpdate(newLayout);
    });
  };

  var handeLayoutUpdate = function handeLayoutUpdate() {
    if (confirm((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Are you sure you want to overwrite this layout?", "rrze-newsletter"))) {
      setIsSavingLayout(true);
      var updatePayload = {
        id: usedLayout.ID,
        content: postContent,
        meta: stylingMeta
      };
      saveLayout(updatePayload).then(handleLayoutUpdate);
    }
  };

  var isUsingCustomLayout = (0,_utils__WEBPACK_IMPORTED_MODULE_9__.isUserDefinedLayout)(usedLayout);
  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 166,
      columnNumber: 13
    }
  }, Boolean(layoutId && isFetchingLayouts) && wp.element.createElement("div", {
    className: "rrze-newsletter-layouts__spinner",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 168,
      columnNumber: 21
    }
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Spinner, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 169,
      columnNumber: 25
    }
  })), blockPreview !== null && wp.element.createElement("div", {
    className: "rrze-newsletter-layouts",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 173,
      columnNumber: 21
    }
  }, wp.element.createElement("div", {
    className: "rrze-newsletter-layouts__item",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 174,
      columnNumber: 25
    }
  }, wp.element.createElement("div", {
    className: "rrze-newsletter-layouts__item-preview",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 175,
      columnNumber: 29
    }
  }, wp.element.createElement(_components_newsletter_preview__WEBPACK_IMPORTED_MODULE_11__.default, {
    meta: usedLayout.meta,
    blocks: blockPreview,
    viewportWidth: 600,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 176,
      columnNumber: 33
    }
  })), wp.element.createElement("div", {
    className: "rrze-newsletter-layouts__item-label",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 182,
      columnNumber: 29
    }
  }, usedLayout.post_title))), wp.element.createElement("div", {
    className: "rrze-newsletter-buttons-group rrze-newsletter-buttons-group--spaced",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 188,
      columnNumber: 17
    }
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isPrimary: true,
    disabled: isEditedPostEmpty || isSavingLayout,
    onClick: function onClick() {
      return setIsManageModalVisible(true);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 189,
      columnNumber: 21
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Save new layout", "rrze-newsletter")), isUsingCustomLayout && wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isSecondary: true,
    disabled: isPostContentSameAsLayout || isSavingLayout,
    onClick: handeLayoutUpdate,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 198,
      columnNumber: 25
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Update layout", "rrze-newsletter"))), wp.element.createElement("br", {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 210,
      columnNumber: 17
    }
  }), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isSecondary: true,
    isLink: true,
    isDestructive: true,
    onClick: function onClick() {
      return setWarningModalVisible(true);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 212,
      columnNumber: 17
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Reset newsletter layout", "rrze-newsletter")), isManageModalVisible && wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Modal, {
    className: "rrze-newsletter__modal",
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Save newsletter as a layout", "rrze-newsletter"),
    onRequestClose: function onRequestClose() {
      return setIsManageModalVisible(null);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 222,
      columnNumber: 21
    }
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Title", "rrze-newsletter"),
    disabled: isSavingLayout,
    value: newLayoutName,
    onChange: setNewLayoutName,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 230,
      columnNumber: 25
    }
  }), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isPrimary: true,
    disabled: isSavingLayout || newLayoutName.length === 0,
    onClick: handleSaveAsLayout,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 236,
      columnNumber: 25
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Save", "rrze-newsletter")), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isSecondary: true,
    onClick: function onClick() {
      return setIsManageModalVisible(null);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 245,
      columnNumber: 25
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Cancel", "rrze-newsletter"))), warningModalVisible && wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Modal, {
    className: "rrze-newsletter__modal",
    title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Overwrite newsletter content?", "rrze-newsletter"),
    onRequestClose: function onRequestClose() {
      return setWarningModalVisible(false);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 255,
      columnNumber: 21
    }
  }, wp.element.createElement("p", {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 263,
      columnNumber: 25
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Changing the newsletter's layout will remove any customizations or edits you have already made.", "rrze-newsletter")), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isPrimary: true,
    onClick: function onClick() {
      clearPost();
      saveLayoutIdMeta(-1);
      setWarningModalVisible(false);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 269,
      columnNumber: 25
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Reset layout", "rrze-newsletter")), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_6__.Button, {
    isSecondary: true,
    onClick: function onClick() {
      return setWarningModalVisible(false);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 279,
      columnNumber: 25
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Cancel", "rrze-newsletter"))));
}));

/***/ }),

/***/ "./src/newsletter-editor/public/index.js":
/*!***********************************************!*\
  !*** ./src/newsletter-editor/public/index.js ***!
  \***********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "PublicSettings": function() { return /* binding */ PublicSettings; }
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./style.scss */ "./src/newsletter-editor/public/style.scss");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/newsletter-editor/public/index.js",
    _this = undefined;

/**
 * WordPress dependencies
 */





/**
 * Plugin dependencies
 */



var PublicSettingsComponent = function PublicSettingsComponent(props) {
  var meta = props.meta,
      updateIsPublic = props.updateIsPublic;
  var rrze_newsletter_is_public = meta.rrze_newsletter_is_public;
  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_4__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 20,
      columnNumber: 9
    }
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.ToggleControl, {
    className: "rrze-newsletter__public-toggle-control",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Make newsletter page public?", "rrze-newsletter"),
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Make this newsletter viewable as a public page once it’s been sent.", "rrze-newsletter"),
    checked: rrze_newsletter_is_public,
    onChange: function onChange(value) {
      return updateIsPublic(value);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 21,
      columnNumber: 13
    }
  }));
};

var mapStateToProps = function mapStateToProps(select) {
  var _select = select("core/editor"),
      getEditedPostAttribute = _select.getEditedPostAttribute;

  return {
    meta: getEditedPostAttribute("meta")
  };
};

var mapDispatchToProps = function mapDispatchToProps(dispatch) {
  var _dispatch = dispatch("core/editor"),
      editPost = _dispatch.editPost;

  return {
    updateIsPublic: function updateIsPublic(value) {
      return editPost({
        meta: {
          rrze_newsletter_is_public: value
        }
      });
    }
  };
};

var PublicSettings = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__.compose)([(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.withSelect)(mapStateToProps), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_3__.withDispatch)(mapDispatchToProps)])(PublicSettingsComponent);

/***/ }),

/***/ "./src/newsletter-editor/sidebar/index.js":
/*!************************************************!*\
  !*** ./src/newsletter-editor/sidebar/index.js ***!
  \************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! classnames */ "./node_modules/classnames/index.js");
/* harmony import */ var classnames__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(classnames__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils */ "./src/newsletter-editor/utils.js");
/* harmony import */ var _service_providers__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../../service-providers */ "./src/service-providers/index.js");
/* harmony import */ var _components_with_api_handler__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../components/with-api-handler */ "./src/components/with-api-handler/index.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./style.scss */ "./src/newsletter-editor/sidebar/style.scss");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/newsletter-editor/sidebar/index.js",
    _this = undefined;

/**
 * WordPress dependencies
 */





/**
 * External dependencies
 */


/**
 * Plugin dependencies
 */






var Sidebar = function Sidebar(_ref) {
  var inFlight = _ref.inFlight,
      errors = _ref.errors,
      editPost = _ref.editPost,
      title = _ref.title,
      senderName = _ref.senderName,
      senderEmail = _ref.senderEmail,
      replytoEmail = _ref.replytoEmail,
      previewText = _ref.previewText,
      newsletterData = _ref.newsletterData,
      apiFetchWithErrorHandling = _ref.apiFetchWithErrorHandling,
      postId = _ref.postId;

  var renderSubject = function renderSubject() {
    return wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.TextControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Subject", "rrze-newsletter"),
      className: "rrze-newsletter__subject-textcontrol",
      value: title,
      disabled: inFlight,
      onChange: function onChange(value) {
        return editPost({
          title: value
        });
      },
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 37,
        columnNumber: 9
      }
    });
  };

  var senderEmailClasses = classnames__WEBPACK_IMPORTED_MODULE_5___default()("rrze-newsletter__email-textcontrol", errors.rrze_newsletter_unverified_sender_domain && "rrze-newsletter__error");

  var updateMetaValueInAPI = function updateMetaValueInAPI(data) {
    return apiFetchWithErrorHandling({
      data: data,
      method: "POST",
      path: "/rrze-newsletter/v1/post-meta/".concat(postId)
    });
  };

  var renderFrom = function renderFrom() {
    return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 60,
        columnNumber: 9
      }
    }, wp.element.createElement("strong", {
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 61,
        columnNumber: 13
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("From", "rrze-newsletter")), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.TextControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Name", "rrze-newsletter"),
      className: "rrze-newsletter__name-textcontrol",
      value: senderName,
      disabled: inFlight,
      onChange: function onChange(value) {
        return editPost({
          meta: {
            rrze_newsletter_from_name: value
          }
        });
      },
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 62,
        columnNumber: 13
      }
    }), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.TextControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Email", "rrze-newsletter"),
      className: senderEmailClasses,
      value: senderEmail,
      type: "email",
      disabled: inFlight,
      onChange: function onChange(value) {
        return editPost({
          meta: {
            rrze_newsletter_from_email: value
          }
        });
      },
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 69,
        columnNumber: 13
      }
    }), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.TextControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("ReplyTo", "rrze-newsletter"),
      className: senderEmailClasses,
      value: replytoEmail,
      type: "email",
      disabled: inFlight,
      onChange: function onChange(value) {
        return editPost({
          meta: {
            rrze_newsletter_replyto: value
          }
        });
      },
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 77,
        columnNumber: 13
      }
    }), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
      isLink: true,
      onClick: function onClick() {
        updateMetaValueInAPI({
          key: "rrze_newsletter_from_name",
          value: senderName
        });
        updateMetaValueInAPI({
          key: "rrze_newsletter_from_email",
          value: senderEmail
        });
        updateMetaValueInAPI({
          key: "rrze_newsletter_replyto",
          value: replytoEmail
        });
      },
      disabled: inFlight || (senderEmail.length ? !(0,_utils__WEBPACK_IMPORTED_MODULE_6__.hasValidEmail)(senderEmail) : false) || (replytoEmail.length ? !(0,_utils__WEBPACK_IMPORTED_MODULE_6__.hasValidEmail)(replytoEmail) : false),
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 85,
        columnNumber: 13
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Update Sender", "rrze-newsletter")), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.TextareaControl, {
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Preview text", "rrze-newsletter"),
      className: "rrze-newsletter__name-textcontrol rrze-newsletter__name-textcontrol--separated",
      value: previewText,
      disabled: inFlight,
      onChange: function onChange(value) {
        return editPost({
          meta: {
            rrze_newsletter_preview_text: value
          }
        });
      },
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 112,
        columnNumber: 13
      }
    }), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
      isLink: true,
      onClick: function onClick() {
        return updateMetaValueInAPI({
          key: "rrze_newsletter_preview_text",
          value: previewText
        });
      },
      disabled: inFlight,
      __self: _this,
      __source: {
        fileName: _jsxFileName,
        lineNumber: 121,
        columnNumber: 13
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Update preview text", "rrze-newsletter")));
  };

  var _getServiceProvider = (0,_service_providers__WEBPACK_IMPORTED_MODULE_7__.getServiceProvider)(),
      ProviderSidebar = _getServiceProvider.ProviderSidebar;

  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 138,
      columnNumber: 9
    }
  }, wp.element.createElement(ProviderSidebar, {
    postId: postId,
    newsletterData: newsletterData,
    inFlight: inFlight,
    renderSubject: renderSubject,
    renderFrom: renderFrom,
    updateMeta: function updateMeta(meta) {
      return editPost({
        meta: meta
      });
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 139,
      columnNumber: 13
    }
  }));
};

/* harmony default export */ __webpack_exports__["default"] = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.compose)([(0,_components_with_api_handler__WEBPACK_IMPORTED_MODULE_8__.default)(), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.withSelect)(function (select) {
  var _select = select("core/editor"),
      getEditedPostAttribute = _select.getEditedPostAttribute,
      getCurrentPostId = _select.getCurrentPostId;

  var meta = getEditedPostAttribute("meta");
  return {
    title: getEditedPostAttribute("title"),
    postId: getCurrentPostId(),
    senderName: meta.rrze_newsletter_from_name || "",
    senderEmail: meta.rrze_newsletter_from_email || "",
    replytoEmail: meta.rrze_newsletter_replyto || "",
    previewText: meta.rrze_newsletter_preview_text || "",
    newsletterData: meta.newsletterData || {}
  };
}), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.withDispatch)(function (dispatch) {
  var _dispatch = dispatch("core/editor"),
      editPost = _dispatch.editPost;

  return {
    editPost: editPost
  };
})])(Sidebar));

/***/ }),

/***/ "./src/newsletter-editor/styling/index.js":
/*!************************************************!*\
  !*** ./src/newsletter-editor/styling/index.js ***!
  \************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "ApplyStyling": function() { return /* binding */ ApplyStyling; },
/* harmony export */   "Styling": function() { return /* binding */ Styling; }
/* harmony export */ });
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _components_select_control_with_optgroup___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../components/select-control-with-optgroup/ */ "./src/components/select-control-with-optgroup/index.js");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/newsletter-editor/styling/index.js",
    _this = undefined;

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * WordPress dependencies
 */







var fontOptgroups = [{
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Sans Serif", "rrze-newsletter"),
  options: [{
    value: "Arial, Helvetica, sans-serif",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Arial", "rrze-newsletter")
  }, {
    value: "Tahoma, sans-serif",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Tahoma", "rrze-newsletter")
  }, {
    value: "Trebuchet MS, sans-serif",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Trebuchet", "rrze-newsletter")
  }, {
    value: "Verdana, sans-serif",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Verdana", "rrze-newsletter")
  }]
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Serif", "rrze-newsletter"),
  options: [{
    value: "Georgia, serif",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Georgia", "rrze-newsletter")
  }, {
    value: "Palatino, serif",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Palatino", "rrze-newsletter")
  }, {
    value: "Times New Roman, serif",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Times New Roman", "rrze-newsletter")
  }]
}, {
  label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Monospace", "rrze-newsletter"),
  options: [{
    value: "Courier, monospace",
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Courier", "rrze-newsletter")
  }]
}];

var customStylesSelector = function customStylesSelector(select) {
  var _select = select("core/editor"),
      getEditedPostAttribute = _select.getEditedPostAttribute;

  var meta = getEditedPostAttribute("meta");
  return {
    fontBody: meta.rrze_newsletter_font_body || fontOptgroups[1].options[0].value,
    fontHeader: meta.rrze_newsletter_font_header || fontOptgroups[0].options[0].value,
    backgroundColor: meta.rrze_newsletter_background_color || "#ffffff"
  };
};

var ApplyStyling = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.withSelect)(customStylesSelector)(function (_ref) {
  var fontBody = _ref.fontBody,
      fontHeader = _ref.fontHeader,
      backgroundColor = _ref.backgroundColor;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(function () {
    document.documentElement.style.setProperty("--body-font", fontBody);
  }, [fontBody]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(function () {
    document.documentElement.style.setProperty("--header-font", fontHeader);
  }, [fontHeader]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(function () {
    var editorElement = document.querySelector(".edit-post-visual-editor");

    if (editorElement) {
      editorElement.style.backgroundColor = backgroundColor;
    }
  }, [backgroundColor]);
  return null;
});
var Styling = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.compose)([(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.withDispatch)(function (dispatch) {
  var _dispatch = dispatch("core/editor"),
      editPost = _dispatch.editPost;

  return {
    editPost: editPost
  };
}), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.withSelect)(function (select) {
  var _select2 = select("core/editor"),
      getCurrentPostId = _select2.getCurrentPostId;

  return _objectSpread({
    postId: getCurrentPostId()
  }, customStylesSelector(select));
})])(function (_ref2) {
  var editPost = _ref2.editPost,
      fontBody = _ref2.fontBody,
      fontHeader = _ref2.fontHeader,
      backgroundColor = _ref2.backgroundColor,
      postId = _ref2.postId;

  var updateStyleValue = function updateStyleValue(key, value) {
    editPost({
      meta: _defineProperty({}, key, value)
    });
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      data: {
        key: key,
        value: value
      },
      method: "POST",
      path: "/rrze-newsletter/v1/post-meta/".concat(postId)
    });
  };

  var instanceId = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_1__.useInstanceId)(_components_select_control_with_optgroup___WEBPACK_IMPORTED_MODULE_6__.default);
  var id = "inspector-select-control-".concat(instanceId);
  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 124,
      columnNumber: 9
    }
  }, wp.element.createElement(_components_select_control_with_optgroup___WEBPACK_IMPORTED_MODULE_6__.default, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Headings font", "rrze-newsletter"),
    value: fontHeader,
    optgroups: fontOptgroups,
    onChange: function onChange(value) {
      return updateStyleValue("rrze_newsletter_font_header", value);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 125,
      columnNumber: 13
    }
  }), wp.element.createElement(_components_select_control_with_optgroup___WEBPACK_IMPORTED_MODULE_6__.default, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Body font", "rrze-newsletter"),
    value: fontBody,
    optgroups: fontOptgroups,
    onChange: function onChange(value) {
      return updateStyleValue("rrze_newsletter_font_body", value);
    },
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 131,
      columnNumber: 13
    }
  }), wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.BaseControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_3__.__)("Background color", "rrze-newsletter"),
    id: id,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 137,
      columnNumber: 13
    }
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ColorPicker, {
    id: id,
    color: backgroundColor,
    onChangeComplete: function onChangeComplete(value) {
      return updateStyleValue("rrze_newsletter_background_color", value.hex);
    },
    disableAlpha: true,
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 141,
      columnNumber: 17
    }
  })));
});

/***/ }),

/***/ "./src/newsletter-editor/testing/index.js":
/*!************************************************!*\
  !*** ./src/newsletter-editor/testing/index.js ***!
  \************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils */ "./src/newsletter-editor/utils.js");
/* harmony import */ var _components_with_api_handler__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../components/with-api-handler */ "./src/components/with-api-handler/index.js");
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./style.scss */ "./src/newsletter-editor/testing/style.scss");
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/newsletter-editor/testing/index.js",
    _this = undefined;

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

/**
 * WordPress dependencies
 */






/**
 * Plugin dependencies
 */



/* harmony default export */ __webpack_exports__["default"] = ((0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_2__.compose)([(0,_components_with_api_handler__WEBPACK_IMPORTED_MODULE_6__.default)(), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.withSelect)(function (select) {
  var _select = select("core/editor"),
      getCurrentPostId = _select.getCurrentPostId;

  return {
    postId: getCurrentPostId()
  };
}), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.withDispatch)(function (dispatch) {
  var _dispatch = dispatch("core/editor"),
      savePost = _dispatch.savePost;

  return {
    savePost: savePost
  };
})])(function (_ref) {
  var apiFetchWithErrorHandling = _ref.apiFetchWithErrorHandling,
      inFlight = _ref.inFlight,
      postId = _ref.postId,
      savePost = _ref.savePost,
      setInFlightForAsync = _ref.setInFlightForAsync;

  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.useState)(""),
      _useState2 = _slicedToArray(_useState, 2),
      testEmail = _useState2[0],
      setTestEmail = _useState2[1];

  var sendTestEmail = /*#__PURE__*/function () {
    var _ref2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
      var params;
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              setInFlightForAsync();
              _context.next = 3;
              return savePost();

            case 3:
              params = {
                path: "/rrze-newsletter/v1/email/".concat(postId, "/test"),
                data: {
                  test_email: testEmail
                },
                method: "POST"
              };
              apiFetchWithErrorHandling(params);

            case 5:
            case "end":
              return _context.stop();
          }
        }
      }, _callee);
    }));

    return function sendTestEmail() {
      return _ref2.apply(this, arguments);
    };
  }();

  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 51,
      columnNumber: 13
    }
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.TextControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Send a test to", "rrze-newsletter"),
    value: testEmail,
    type: "email",
    onChange: setTestEmail,
    help: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Use commas to separate multiple emails.", "rrze-newsletter"),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 52,
      columnNumber: 17
    }
  }), wp.element.createElement("div", {
    className: "rrze-newsletter__testing-controls",
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 62,
      columnNumber: 17
    }
  }, wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Button, {
    isPrimary: true,
    onClick: sendTestEmail,
    disabled: inFlight || !(0,_utils__WEBPACK_IMPORTED_MODULE_5__.hasValidEmail)(testEmail),
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 63,
      columnNumber: 21
    }
  }, inFlight ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Sending Test Email...", "rrze-newsletter") : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Send a Test Email", "rrze-newsletter")), inFlight && wp.element.createElement(_wordpress_components__WEBPACK_IMPORTED_MODULE_4__.Spinner, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 72,
      columnNumber: 34
    }
  })));
}));

/***/ }),

/***/ "./src/newsletter-editor/utils.js":
/*!****************************************!*\
  !*** ./src/newsletter-editor/utils.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "hasValidEmail": function() { return /* binding */ hasValidEmail; }
/* harmony export */ });
/**
 * Test if a string contains valid email addresses.
 *
 * @param  {string}  string String to test.
 * @return {boolean} True if it contains a valid email string.
 */
var hasValidEmail = function hasValidEmail(string) {
  return /\S+@\S+/.test(string);
};

/***/ }),

/***/ "./src/service-providers/generic/index.js":
/*!************************************************!*\
  !*** ./src/service-providers/generic/index.js ***!
  \************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
var _jsxFileName = "/Users/rolf/Sites/cms.wordpress.localhost/htdocs/wp-content/plugins/rrze-newsletter/src/service-providers/generic/index.js",
    _this = undefined;

/**
 * WordPress dependencies
 */


/**
 * Validation utility.
 *
 * @param  {Object} object data fetched using getFetchDataConfig
 * @return {string[]} Array of validation messages. If empty, newsletter is valid.
 */

var validateNewsletter = function validateNewsletter(_ref) {
  var status = _ref.status;
  var messages = [];

  if ("sent" === status || "sending" === status) {
    messages.push((0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Newsletter has already been sent.", "rrze-newsletter"));
  }

  return messages;
};
/**
 * Get config used to fetch newsletter data.
 * Should return apiFetch utility config:
 * https://www.npmjs.com/package/@wordpress/api-fetch
 *
 * @param {Object} object data to contruct the config.
 * @return {Object} Config fetching.
 */


var getFetchDataConfig = function getFetchDataConfig(_ref2) {
  var postId = _ref2.postId;
  return {
    path: "/rrze-newsletter/v1/email/".concat(postId)
  };
};
/**
 * Component to be rendered in the sidebar panel.
 * Has full control over the panel contents rendering,
 * so that it's possible to render e.g. a loader while
 * the data is not yet available.
 *
 * @param {Object} props props
 */


var ProviderSidebar = function ProviderSidebar(_ref3) {
  var postId = _ref3.postId,
      apiFetch = _ref3.apiFetch,
      renderSubject = _ref3.renderSubject,
      renderFrom = _ref3.renderFrom;

  var handleSenderUpdate = function handleSenderUpdate(_ref4) {
    var senderName = _ref4.senderName,
        senderEmail = _ref4.senderEmail;
    return apiFetch({
      path: "/rrze-newsletter/v1/email/".concat(postId, "/sender"),
      data: {
        rrze_newsletter_from_name: senderName,
        rrze_newsletter_replyto: senderEmail
      },
      method: "POST"
    });
  };

  return wp.element.createElement(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.Fragment, {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 76,
      columnNumber: 9
    }
  }, renderSubject(), renderFrom({
    handleSenderUpdate: handleSenderUpdate
  }));
};
/**
 * A function to render additional info in the pre-send confirmation modal.
 * Can return null if no additional info is to be presented.
 *
 * @param {Object} newsletterData the data returned by getFetchDataConfig handler
 * @return {any} A React component
 */


var renderPreSendInfo = function renderPreSendInfo() {
  var newsletterData = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  return wp.element.createElement("p", {
    __self: _this,
    __source: {
      fileName: _jsxFileName,
      lineNumber: 91,
      columnNumber: 5
    }
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Sending newsletter to:", "rrze-newsletter"), " ", newsletterData.listName);
};

/* harmony default export */ __webpack_exports__["default"] = ({
  validateNewsletter: validateNewsletter,
  getFetchDataConfig: getFetchDataConfig,
  ProviderSidebar: ProviderSidebar,
  renderPreSendInfo: renderPreSendInfo
});

/***/ }),

/***/ "./src/service-providers/index.js":
/*!****************************************!*\
  !*** ./src/service-providers/index.js ***!
  \****************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getServiceProvider": function() { return /* binding */ getServiceProvider; }
/* harmony export */ });
/* harmony import */ var _generic__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./generic */ "./src/service-providers/generic/index.js");

var SERVICE_PROVIDERS = {
  generic: _generic__WEBPACK_IMPORTED_MODULE_0__.default
};
var getServiceProvider = function getServiceProvider() {
  return SERVICE_PROVIDERS["generic"];
};

/***/ }),

/***/ "./src/utils/consts.js":
/*!*****************************!*\
  !*** ./src/utils/consts.js ***!
  \*****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "LAYOUT_CPT_SLUG": function() { return /* binding */ LAYOUT_CPT_SLUG; },
/* harmony export */   "NEWSLETTER_CPT_SLUG": function() { return /* binding */ NEWSLETTER_CPT_SLUG; },
/* harmony export */   "BLANK_LAYOUT_ID": function() { return /* binding */ BLANK_LAYOUT_ID; }
/* harmony export */ });
var LAYOUT_CPT_SLUG = "newsletter_layout";
var NEWSLETTER_CPT_SLUG = "newsletter";
var BLANK_LAYOUT_ID = 0;

/***/ }),

/***/ "./src/utils/hooks.js":
/*!****************************!*\
  !*** ./src/utils/hooks.js ***!
  \****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "useLayoutsState": function() { return /* binding */ useLayoutsState; }
/* harmony export */ });
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _consts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./consts */ "./src/utils/consts.js");
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

/**
 * WordPress dependencies
 */


/**
 * Plugin dependencies
 */


/**
 * A React hook that provides the layouts list,
 * both default and user-defined.
 *
 * @return {Array} Array of layouts
 */

var useLayoutsState = function useLayoutsState() {
  var _useState = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true),
      _useState2 = _slicedToArray(_useState, 2),
      isFetching = _useState2[0],
      setIsFetching = _useState2[1];

  var _useState3 = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)([]),
      _useState4 = _slicedToArray(_useState3, 2),
      layouts = _useState4[0],
      setLayouts = _useState4[1];

  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(function () {
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: "/rrze-newsletter/v1/layouts"
    }).then(function (response) {
      setLayouts(response);
      setIsFetching(false);
    });
  }, []);

  var deleteLayoutPost = function deleteLayoutPost(id) {
    setLayouts(layouts.filter(function (_ref) {
      var ID = _ref.ID;
      return ID !== id;
    }));
    _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: "/wp/v2/".concat(_consts__WEBPACK_IMPORTED_MODULE_2__.LAYOUT_CPT_SLUG, "/").concat(id),
      method: "DELETE"
    });
  };

  return {
    layouts: layouts,
    isFetchingLayouts: isFetching,
    deleteLayoutPost: deleteLayoutPost
  };
};

/***/ }),

/***/ "./src/utils/index.js":
/*!****************************!*\
  !*** ./src/utils/index.js ***!
  \****************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "isUserDefinedLayout": function() { return /* binding */ isUserDefinedLayout; }
/* harmony export */ });
/* harmony import */ var _consts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./consts */ "./src/utils/consts.js");
/**
 * IntPluginernal dependencies
 */

var isUserDefinedLayout = function isUserDefinedLayout(layout) {
  return layout && layout.post_type === _consts__WEBPACK_IMPORTED_MODULE_0__.LAYOUT_CPT_SLUG;
};

/***/ }),

/***/ "./node_modules/classnames/index.js":
/*!******************************************!*\
  !*** ./node_modules/classnames/index.js ***!
  \******************************************/
/***/ (function(module, exports) {

var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;/*!
  Copyright (c) 2017 Jed Watson.
  Licensed under the MIT License (MIT), see
  http://jedwatson.github.io/classnames
*/
/* global define */

(function () {
	'use strict';

	var hasOwn = {}.hasOwnProperty;

	function classNames () {
		var classes = [];

		for (var i = 0; i < arguments.length; i++) {
			var arg = arguments[i];
			if (!arg) continue;

			var argType = typeof arg;

			if (argType === 'string' || argType === 'number') {
				classes.push(arg);
			} else if (Array.isArray(arg) && arg.length) {
				var inner = classNames.apply(null, arg);
				if (inner) {
					classes.push(inner);
				}
			} else if (argType === 'object') {
				for (var key in arg) {
					if (hasOwn.call(arg, key) && arg[key]) {
						classes.push(key);
					}
				}
			}
		}

		return classes.join(' ');
	}

	if ( true && module.exports) {
		classNames.default = classNames;
		module.exports = classNames;
	} else if (true) {
		// register as 'classnames', consistent with npm package name
		!(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = (function () {
			return classNames;
		}).apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__),
		__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	} else {}
}());


/***/ }),

/***/ "./src/components/init-modal/style.scss":
/*!**********************************************!*\
  !*** ./src/components/init-modal/style.scss ***!
  \**********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/components/newsletter-preview/style.scss":
/*!******************************************************!*\
  !*** ./src/components/newsletter-preview/style.scss ***!
  \******************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/components/send-button/style.scss":
/*!***********************************************!*\
  !*** ./src/components/send-button/style.scss ***!
  \***********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/editor/style.scss":
/*!*******************************!*\
  !*** ./src/editor/style.scss ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/newsletter-editor/editor/style.scss":
/*!*************************************************!*\
  !*** ./src/newsletter-editor/editor/style.scss ***!
  \*************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/newsletter-editor/layout/style.scss":
/*!*************************************************!*\
  !*** ./src/newsletter-editor/layout/style.scss ***!
  \*************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/newsletter-editor/public/style.scss":
/*!*************************************************!*\
  !*** ./src/newsletter-editor/public/style.scss ***!
  \*************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/newsletter-editor/sidebar/style.scss":
/*!**************************************************!*\
  !*** ./src/newsletter-editor/sidebar/style.scss ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./src/newsletter-editor/testing/style.scss":
/*!**************************************************!*\
  !*** ./src/newsletter-editor/testing/style.scss ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "lodash":
/*!*************************!*\
  !*** external "lodash" ***!
  \*************************/
/***/ (function(module) {

"use strict";
module.exports = lodash;

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ (function(module) {

"use strict";
module.exports = wp.apiFetch;

/***/ }),

/***/ "@wordpress/block-editor":
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
/***/ (function(module) {

"use strict";
module.exports = wp.blockEditor;

/***/ }),

/***/ "@wordpress/blocks":
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
/***/ (function(module) {

"use strict";
module.exports = wp.blocks;

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ (function(module) {

"use strict";
module.exports = wp.components;

/***/ }),

/***/ "@wordpress/compose":
/*!*********************************!*\
  !*** external ["wp","compose"] ***!
  \*********************************/
/***/ (function(module) {

"use strict";
module.exports = wp.compose;

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ (function(module) {

"use strict";
module.exports = wp.data;

/***/ }),

/***/ "@wordpress/dom-ready":
/*!**********************************!*\
  !*** external ["wp","domReady"] ***!
  \**********************************/
/***/ (function(module) {

"use strict";
module.exports = wp.domReady;

/***/ }),

/***/ "@wordpress/edit-post":
/*!**********************************!*\
  !*** external ["wp","editPost"] ***!
  \**********************************/
/***/ (function(module) {

"use strict";
module.exports = wp.editPost;

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ (function(module) {

"use strict";
module.exports = wp.element;

/***/ }),

/***/ "@wordpress/hooks":
/*!*******************************!*\
  !*** external ["wp","hooks"] ***!
  \*******************************/
/***/ (function(module) {

"use strict";
module.exports = wp.hooks;

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ (function(module) {

"use strict";
module.exports = wp.i18n;

/***/ }),

/***/ "@wordpress/keycodes":
/*!**********************************!*\
  !*** external ["wp","keycodes"] ***!
  \**********************************/
/***/ (function(module) {

"use strict";
module.exports = wp.keycodes;

/***/ }),

/***/ "@wordpress/plugins":
/*!*********************************!*\
  !*** external ["wp","plugins"] ***!
  \*********************************/
/***/ (function(module) {

"use strict";
module.exports = wp.plugins;

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be in strict mode.
!function() {
"use strict";
/*!*****************************!*\
  !*** ./src/editor/index.js ***!
  \*****************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/dom-ready */ "@wordpress/dom-ready");
/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/hooks */ "@wordpress/hooks");
/* harmony import */ var _wordpress_hooks__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/plugins */ "@wordpress/plugins");
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _style_scss__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./style.scss */ "./src/editor/style.scss");
/* harmony import */ var _blocks_validation_blocks_filters__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./blocks-validation/blocks-filters */ "./src/editor/blocks-validation/blocks-filters.js");
/* harmony import */ var _blocks_validation_nesting_detection__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./blocks-validation/nesting-detection */ "./src/editor/blocks-validation/nesting-detection.js");
/* harmony import */ var _newsletter_editor__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../newsletter-editor */ "./src/newsletter-editor/index.js");
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

/**
 * WordPress dependencies
 */




/**
 * Plugin dependencies
 */





(0,_blocks_validation_blocks_filters__WEBPACK_IMPORTED_MODULE_5__.addBlocksValidationFilter)();
/* Unregister core block styles that are unsupported in emails */

_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_1___default()(function () {
  (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.unregisterBlockStyle)("core/separator", "dots");
  (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.unregisterBlockStyle)("core/social-links", "logos-only");
  (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.unregisterBlockStyle)("core/social-links", "pill-shape");
});
(0,_wordpress_hooks__WEBPACK_IMPORTED_MODULE_2__.addFilter)("blocks.registerBlockType", "rrze-newsletter/core-blocks", function (settings, name) {
  /* Remove left/right alignment options wherever possible */
  if ("core/paragraph" === name || "core/buttons" === name || "core/columns" === name) {
    settings.supports = _objectSpread(_objectSpread({}, settings.supports), {}, {
      align: []
    });
  }

  if ("core/group" === name) {
    settings.supports = _objectSpread(_objectSpread({}, settings.supports), {}, {
      align: ["full"]
    });
  }

  return settings;
});
(0,_wordpress_plugins__WEBPACK_IMPORTED_MODULE_3__.registerPlugin)("rrze-newsletter-plugin", {
  render: _blocks_validation_nesting_detection__WEBPACK_IMPORTED_MODULE_6__.NestedColumnsDetection,
  icon: null
});
}();
/******/ })()
;
//# sourceMappingURL=editor.js.map