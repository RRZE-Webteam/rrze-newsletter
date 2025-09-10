/**
 * External dependencies
 */
import { includes, debounce } from "lodash";

/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    Button,
    QueryControls,
    FormTokenField,
    SelectControl,
    ToggleControl,
    Spinner,
} from "@wordpress/components";
import { addQueryArgs } from "@wordpress/url";
import {
    Fragment,
    useState,
    useEffect,
    useMemo,
    useRef,
} from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { decodeEntities } from "@wordpress/html-entities";

/**
 * Plugin dependencies
 */
import AutocompleteTokenField from "../../../components/autocomplete-tokenfield";

const fetchPostSuggestions = (postType) => (search) =>
    apiFetch({
        path: addQueryArgs("/wp/v2/search", {
            search,
            per_page: 20,
            _fields: "id,title",
            subtype: postType,
        }),
    }).then((posts) =>
        posts.map((post) => ({
            id: post.id,
            title:
                decodeEntities(post.title) ||
                __("(no title)", "rrze-newsletter"),
        }))
    );

const SEPARATOR = "--";
const encodePosts = (posts) =>
    posts.map((post) => [post.id, post.title].join(SEPARATOR));
const decodePost = (encodedPost) => {
    const match = encodedPost.match(new RegExp(`^([\\d]*)${SEPARATOR}(.*)`));
    if (match) {
        return [match[1], match[2]];
    }
    return encodedPost;
};

const QueryControlsSettings = ({ attributes, setAttributes }) => {
    // Destructure FIRST (prevents "Cannot access 'tags' before initialization")
    const {
        categoryExclusions,
        tags,
        tagExclusions,
        order,
        orderBy,
        isDisplayingSpecificPosts,
        specificPosts,
        postsToShow,
        categories,
        postType,
    } = attributes;

    const [categoriesList, setCategoriesList] = useState([]);
    const [postTypesList, setPostTypesList] = useState([
        { value: "post", label: "Posts" },
    ]);

    // Auto-open Advanced Filters if saved filters exist
    const defaultOrder = "desc";
    const defaultOrderBy = "date";
    const hasAdvancedSaved =
        (Array.isArray(tags) && tags.length > 0) ||
        (Array.isArray(tagExclusions) && tagExclusions.length > 0) ||
        (Array.isArray(categoryExclusions) && categoryExclusions.length > 0) ||
        (order && order !== defaultOrder) ||
        (orderBy && orderBy !== defaultOrderBy);
    const [showAdvancedFilters, setShowAdvancedFilters] =
        useState(hasAdvancedSaved);

    useEffect(() => {
        if (hasAdvancedSaved && !showAdvancedFilters)
            setShowAdvancedFilters(true);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        order,
        orderBy,
        tags?.length,
        tagExclusions?.length,
        categoryExclusions?.length,
    ]);

    useEffect(() => {
        apiFetch({
            path: addQueryArgs(`/wp/v2/categories`, { per_page: -1 }),
        }).then(setCategoriesList);
        // fetchPostTypes().then(setPostTypesList);
    }, []);

    const categorySuggestions = categoriesList.reduce(
        (acc, category) => ({ ...acc, [category.name]: category }),
        {}
    );

    const selectCategories = (tokens) => {
        const hasNoSuggestion = tokens.some(
            (token) => typeof token === "string" && !categorySuggestions[token]
        );
        if (hasNoSuggestion) return;

        const allCategories = tokens.map((token) =>
            typeof token === "string" ? categorySuggestions[token] : token
        );

        if (includes(allCategories, null)) return false;
        setAttributes({ categories: allCategories });
    };

    const selectTags = (tokens) =>
        setAttributes({ tags: tokens.filter(Boolean) });
    const selectExcludedTags = (tokens) =>
        setAttributes({ tagExclusions: tokens.filter(Boolean) });
    const selectExcludedCategories = (tokens) =>
        setAttributes({ categoryExclusions: tokens.filter(Boolean) });

    // Specific posts search
    const [isFetchingPosts, setIsFetchingPosts] = useState(false);
    const [foundPosts, setFoundPosts] = useState([]);
    const acRef = useRef(null);

    const handleSpecificPostsInput = async (search) => {
        if (isFetchingPosts || search.length === 0) return;
        acRef.current?.abort?.();
        const ac = new AbortController();
        acRef.current = ac;
        try {
            setIsFetchingPosts(true);
            const posts = await fetchPostSuggestions(postType)(search);
            if (!ac.signal.aborted) setFoundPosts(posts);
        } catch (e) {
            if (!ac.signal.aborted) setFoundPosts([]);
        } finally {
            if (!ac.signal.aborted) setIsFetchingPosts(false);
        }
    };

    const debouncedSpecificPostsInput = useMemo(
        () => debounce(handleSpecificPostsInput, 400),
        [postType]
    );
    useEffect(
        () => () => debouncedSpecificPostsInput.cancel(),
        [debouncedSpecificPostsInput]
    );
    useEffect(() => () => acRef.current?.abort?.(), []);

    const handleSpecificPostsSelection = (postTitles) => {
        setAttributes({
            specificPosts: postTitles.map((encodedTitle) => {
                const [id, title] = decodePost(encodedTitle);
                return { id: parseInt(id, 10), title };
            }),
        });
    };

    const fetchCategorySuggestions = (search) =>
        apiFetch({
            path: addQueryArgs("/wp/v2/categories", {
                search,
                per_page: 20,
                _fields: "id,name",
                orderby: "count",
                order: "desc",
            }),
        }).then((categories) =>
            categories.map((category) => ({
                value: category.id,
                label:
                    decodeEntities(category.name) ||
                    __("(no title)", "rrze-newsletter"),
            }))
        );

    const fetchPostTypes = () =>
        apiFetch({
            path: addQueryArgs("/wp/v2/types", { context: "edit" }),
        }).then((postTypes) =>
            Object.values(postTypes)
                .filter(
                    (pt) =>
                        pt.slug === "post" &&
                        pt.viewable === true &&
                        pt.visibility?.show_ui === true
                )
                .map((pt) => ({
                    value: pt.slug,
                    label:
                        decodeEntities(pt.name) ||
                        __("(no title)", "rrze-newsletter"),
                }))
        );

    const fetchSavedCategories = (categoryIDs) =>
        apiFetch({
            path: addQueryArgs("/wp/v2/categories", {
                per_page: 100,
                _fields: "id,name",
                include: categoryIDs.join(","),
            }),
        }).then((categories) =>
            categories.map((category) => ({
                value: category.id,
                label:
                    decodeEntities(category.name) ||
                    __("(no title)", "rrze-newsletter"),
            }))
        );

    const fetchTagSuggestions = (search) =>
        apiFetch({
            path: addQueryArgs("/wp/v2/tags", {
                search,
                per_page: 20,
                _fields: "id,name",
                orderby: "count",
                order: "desc",
            }),
        }).then((fetchedTags) =>
            fetchedTags.map((tag) => ({
                value: tag.id,
                label:
                    decodeEntities(tag.name) ||
                    __("(no title)", "rrze-newsletter"),
            }))
        );

    const fetchSavedTags = (tagIDs) =>
        apiFetch({
            path: addQueryArgs("/wp/v2/tags", {
                per_page: 100,
                _fields: "id,name",
                include: tagIDs.join(","),
            }),
        }).then((fetchedTags) =>
            fetchedTags.map((tag) => ({
                value: tag.id,
                label:
                    decodeEntities(tag.name) ||
                    __("(no title)", "rrze-newsletter"),
            }))
        );

    return (
        <div className="rrze-newsletter-query-controls">
            <ToggleControl
                label={__("Display specific posts", "rrze-newsletter")}
                checked={isDisplayingSpecificPosts}
                onChange={(value) =>
                    setAttributes({ isDisplayingSpecificPosts: value })
                }
            />

            {isDisplayingSpecificPosts ? (
                <FormTokenField
                    label={
                        <div>
                            {__("Add posts", "rrze-newsletter")}
                            {isFetchingPosts && <Spinner />}
                        </div>
                    }
                    onChange={handleSpecificPostsSelection}
                    value={encodePosts(specificPosts)}
                    suggestions={encodePosts(foundPosts)}
                    displayTransform={(string) => {
                        const [id, title] = decodePost(string);
                        return title || id || "";
                    }}
                    onInputChange={debouncedSpecificPostsInput}
                />
            ) : (
                <Fragment>
                    <QueryControls
                        numberOfItems={postsToShow}
                        onNumberOfItemsChange={(value) =>
                            setAttributes({ postsToShow: value })
                        }
                        categorySuggestions={categorySuggestions}
                        onCategoryChange={selectCategories}
                        selectedCategories={categories}
                        minItems={1}
                        maxItems={20}
                    />

                    <p key="toggle-advanced-filters">
                        <Button
                            variant="link"
                            onClick={() =>
                                setShowAdvancedFilters(!showAdvancedFilters)
                            }
                        >
                            {showAdvancedFilters
                                ? __("Hide Advanced Filters", "rrze-newsletter")
                                : __(
                                      "Show Advanced Filters",
                                      "rrze-newsletter"
                                  )}
                        </Button>
                    </p>

                    {showAdvancedFilters && (
                        <Fragment>
                            <AutocompleteTokenField
                                key="tags"
                                tokens={tags}
                                onChange={selectTags}
                                fetchSuggestions={fetchTagSuggestions}
                                fetchSavedInfo={fetchSavedTags}
                                label={__("Tags", "rrze-newsletter")}
                            />

                            <AutocompleteTokenField
                                key="category-exclusion"
                                tokens={categoryExclusions}
                                onChange={selectExcludedCategories}
                                fetchSuggestions={fetchCategorySuggestions}
                                fetchSavedInfo={fetchSavedCategories}
                                label={__(
                                    "Excluded Categories",
                                    "rrze-newsletter"
                                )}
                            />

                            <AutocompleteTokenField
                                key="tag-exclusion"
                                tokens={tagExclusions}
                                onChange={selectExcludedTags}
                                fetchSuggestions={fetchTagSuggestions}
                                fetchSavedInfo={fetchSavedTags}
                                label={__("Excluded Tags", "rrze-newsletter")}
                            />

                            <SelectControl
                                key="query-controls-order-select"
                                label={__("Order by", "rrze-newsletter")}
                                value={`${order || "date"}/${
                                    orderBy || "desc"
                                }`.replace("undefined", "")}
                                options={[
                                    {
                                        label: __(
                                            "Newest to oldest",
                                            "rrze-newsletter"
                                        ),
                                        value: "date/desc",
                                    },
                                    {
                                        label: __(
                                            "Oldest to newest",
                                            "rrze-newsletter"
                                        ),
                                        value: "date/asc",
                                    },
                                    {
                                        label: __("A → Z", "rrze-newsletter"),
                                        value: "title/asc",
                                    },
                                    {
                                        label: __("Z → A", "rrze-newsletter"),
                                        value: "title/desc",
                                    },
                                ]}
                                onChange={(value) => {
                                    const [newOrderBy, newOrder] =
                                        value.split("/");
                                    if (newOrder !== order)
                                        setAttributes({ order: newOrder });
                                    if (newOrderBy !== orderBy)
                                        setAttributes({ orderBy: newOrderBy });
                                }}
                            />
                        </Fragment>
                    )}
                </Fragment>
            )}
        </div>
    );
};

export default QueryControlsSettings;
