<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use RRZE\Newsletter\Editor;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\Patterns\Patterns;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\EditorEnvironment as State;
use RRZE\Newsletter\Tests\Support\RequestEnvironment as Request;

final class EditorContractTest extends ApplicationTestCase
{
    private mixed $instance;
    private mixed $excerpt;
    private bool $hadFilters;
    private mixed $filters;

    protected function setUp(): void
    {
        parent::setUp();
        State::reset();
        Request::reset();
        $property = new \ReflectionProperty(Editor::class, 'instance');
        $this->instance = $property->getValue();
        $property->setValue(null, null);
        $this->excerpt = Editor::$newsletterExcerptLengthFilter;
        Editor::$newsletterExcerptLengthFilter = null;
        $this->hadFilters = array_key_exists('wp_filter', $GLOBALS);
        $this->filters = $GLOBALS['wp_filter'] ?? null;
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(Editor::class, 'instance'))->setValue(null, $this->instance);
        Editor::$newsletterExcerptLengthFilter = $this->excerpt;
        if ($this->hadFilters) { $GLOBALS['wp_filter'] = $this->filters; } else { unset($GLOBALS['wp_filter']); }
        State::reset();
        Request::reset();
        parent::tearDown();
    }

    public function testSingletonRegistersHooksOnlyOnceAndDefersThemeFilter(): void
    {
        $editor = Editor::instance();
        self::assertSame($editor, Editor::instance());
        self::assertCount(8, App::$hooks);
        self::assertContains(['action', 'after_setup_theme', [$editor, 'afterSetupTheme'], 99, 1], App::$hooks);
        self::assertContains(['action', 'rest_post_query', [Editor::class, 'maybeFilterExcerptLength'], 10, 2], App::$hooks);
        $editor->afterSetupTheme();
        self::assertContains(['filter', 'wp_theme_json_data_theme', [$editor, 'filterThemeJsonTheme'], 10, 1], App::$hooks);
        $editor->disableFeaturedImageSupport();
        self::assertSame([['remove_support', 'newsletter', 'thumbnail']], State::$calls);
    }

    public function testUnrelatedPostKeepsBlocksThemeAndAssetsUntouched(): void
    {
        $this->post(['post_type' => 'post']);
        $theme = new ThemeDataRecorder();
        self::assertFalse(Editor::isEditingNewsletter());
        self::assertSame(true, Editor::newsletterAllowedBlockTypes(true));
        self::assertSame($theme, (new Editor())->filterThemeJsonTheme($theme));
        Editor::removeEditorModifications();
        Editor::enqueueBlockEditorAssets();
        self::assertSame([], State::$calls);
        self::assertSame([], Request::$assets);
        self::assertSame([], $theme->updates);
    }

    public function testNewsletterAllowedBlocksExcludeInteractiveAndUnsupportedBlocks(): void
    {
        $this->post();
        self::assertTrue(Editor::isEditingNewsletter());
        self::assertSame([
            'core/spacer', 'core/block', 'core/group', 'core/paragraph', 'core/heading',
            'core/buttons', 'core/button', 'core/column', 'core/columns', 'core/image',
            'core/separator', 'core/list', 'core/list-item', 'core/social-links', 'core/social-link',
            'rrze-newsletter/post-inserter', 'rrze-newsletter/rss', 'rrze-newsletter/ics',
        ], Editor::newsletterAllowedBlockTypes(true));
    }

    public function testEditorCleanupPreservesAllowListedAssetCallbacks(): void
    {
        $this->post();
        $GLOBALS['wp_filter']['enqueue_block_editor_assets'] = (object) ['callbacks' => [
            10 => [Editor::class . '::enqueueBlockEditorAssets' => [], 'theme_assets' => []],
            20 => ['rrze_newsletter_enqueue_scripts' => [], 'wp_enqueue_editor_format_library_assets' => [], 'other_plugin' => []],
        ]];
        Editor::removeEditorModifications();
        self::assertSame([
            ['remove_action', 'enqueue_block_editor_assets', 'theme_assets', 10],
            ['remove_action', 'enqueue_block_editor_assets', 'other_plugin', 20],
            ['remove_editor_styles'],
            ['theme_support', 'disable-custom-colors'],
            ['theme_support', 'editor-color-palette', []],
            ['theme_support', 'disable-custom-gradients'],
            ['theme_support', 'editor-gradient-presets', []],
        ], State::$calls);
    }

    public function testThemeContractDefinesEmailWidthTypographyAndPalette(): void
    {
        $this->post();
        $theme = new ThemeDataRecorder();
        self::assertSame($theme, (new Editor())->filterThemeJsonTheme($theme));
        self::assertCount(1, $theme->updates);
        $data = $theme->updates[0];
        self::assertSame(2, $data['version']);
        self::assertSame('680px', $data['settings']['layout']['contentSize']);
        self::assertSame(['px', 'em', 'rem', '%'], $data['settings']['spacing']['units']);
        self::assertSame(['14px', '16px', '22px', '26px', '30px'], array_column($data['settings']['typography']['fontSizes'], 'size'));
        foreach (['fontStyle', 'fontWeight', 'letterSpacing', 'lineHeight', 'dropCap'] as $key) {
            self::assertFalse($data['settings']['typography'][$key], $key);
        }
        $palette = $data['settings']['color']['palette'];
        self::assertSame(['base', 'contrast', 'fau', 'phil', 'rw', 'med', 'nat', 'tf'], array_column($palette, 'slug'));
        self::assertSame(['#000000', '#ffffff', '#04316a', '#fdb735', '#c50f3c', '#18b4f1', '#7bb725', '#8C9FB1'], array_column($palette, 'color'));
        foreach (['core/group', 'core/paragraph', 'core/heading', 'core/column'] as $block) {
            self::assertSame($palette, $data['settings']['blocks'][$block]['color']['palette']);
        }
        self::assertFalse($data['settings']['color']['defaultGradients']);
        self::assertFalse($data['settings']['blocks']['core/group']['shadow']['defaultPresets']);
        self::assertSame(['text' => '#000000', 'background' => 'transparent'], $data['styles']['color']);
    }

    public function testAssetsUseBuiltMetadataAndNewsletterSpecificLocalization(): void
    {
        $this->post();
        Editor::enqueueBlockEditorAssets();
        $assets = array_column(Request::$assets, 1, 0);
        $metadata = include dirname(__DIR__, 2) . '/build/editor.asset.php';
        self::assertSame(['rrze-newsletter', 'rtl', 'replace'], $assets['style_data']);
        self::assertSame($metadata['dependencies'], $assets['script'][2]);
        self::assertSame($metadata['version'], $assets['script'][3]);
        self::assertTrue($assets['script'][4]);
        self::assertSame(['newsletter'], $assets['localize'][2]['mjml_handling_post_types']);
        self::assertSame('rrze_newsletter_email_html', $assets['localize'][2]['email_html_meta']);
        self::assertSame('rrze-newsletter', $assets['translations'][1]);
    }

    public function testExcerptRequestPreservesQueryAndRegistersRequestedLength(): void
    {
        $args = ['post_type' => 'post', 'posts_per_page' => 5];
        $request = new class { public array $params = []; public function get_params(): array { return $this->params; } };
        self::assertSame($args, Editor::maybeFilterExcerptLength($args, $request));
        self::assertSame([], App::$hooks);
        $request->params = ['excerpt_length' => '23'];
        self::assertSame($args, Editor::maybeFilterExcerptLength($args, $request));
        self::assertSame('excerpt_length', App::$hooks[0][1]);
        self::assertSame(23, (App::$hooks[0][2])());
        self::assertSame(999, App::$hooks[0][3]);
        // Deliberately not claiming successful removal: add_filter returns true, not the closure.
        Editor::filterExcerptLength('not-an-integer');
        self::assertCount(1, App::$hooks);
    }

    public function testLayoutRegistrationDeclaresEditorOnlyMetadata(): void
    {
        new NewsletterLayout();
        NewsletterLayout::registerPostType();
        NewsletterLayout::registerMeta();
        $type = App::$registrations['post_types']['newsletter_layout'];
        self::assertFalse($type['public']);
        self::assertTrue($type['show_in_rest']);
        self::assertSame(['editor', 'title', 'custom-fields'], $type['supports']);
        self::assertCount(3, App::$registrations['meta']);
        foreach (['font_header', 'font_body', 'background_color'] as $key) {
            $meta = App::$registrations['meta']['rrze_newsletter_' . $key];
            self::assertSame('newsletter_layout', $meta['object_subtype']);
            self::assertSame('string', $meta['type']);
            self::assertTrue($meta['single']);
            self::assertSame(['edit'], $meta['show_in_rest']['schema']['context']);
        }
    }

    public function testLayoutTokensUseSiteHeadingWithoutLogoAndImageWhenAvailable(): void
    {
        $template = '__SITENAME__ __LOGO__ __LOGO_OR_SITENAME__ __EXTRA__';
        $without = NewsletterLayout::layoutTokenReplacement($template, ['__EXTRA__' => 'Custom']);
        self::assertStringContainsString('<h1 class="has-text-align-center">Test Site</h1>', $without);
        self::assertStringNotContainsString('__', $without);
        self::assertStringContainsString('Custom', $without);
        self::assertStringNotContainsString('<img', $without);
        State::$logoId = 7;
        $with = NewsletterLayout::layoutTokenReplacement($template);
        self::assertStringContainsString('src="https://example.test/logo.png"', $with);
        self::assertStringContainsString('class="wp-image-7"', $with);
        self::assertStringNotContainsString('<h1', $with);
        self::assertSame([['image', 7, 'medium']], State::$calls);
    }

    public function testBundledLayoutsHaveStableIdsTitlesAndResolvedTokens(): void
    {
        $layouts = NewsletterLayout::getDefaultLayouts();
        self::assertCount(8, $layouts);
        self::assertSame(range(1, 8), array_column($layouts, 'ID'));
        self::assertCount(8, array_unique(array_column($layouts, 'post_title')));
        foreach ($layouts as $layout) {
            self::assertNotSame('', $layout['post_content']);
            self::assertStringNotContainsString('__SITENAME__', $layout['post_content']);
            self::assertStringNotContainsString('__LOGO_OR_SITENAME__', $layout['post_content']);
            self::assertDoesNotMatchRegularExpression('/(?:href|src)="\//', $layout['post_content']);
        }
    }

    public function testBundledPatternsRegisterNewsletterScopeAndResolvedAssetUrls(): void
    {
        Patterns::registerBlockPatterns();
        self::assertSame(['rrze-newsletter' => ['label' => 'Newsletter']], State::$patterns['categories']);
        self::assertSame(array_keys(Patterns::availablePatterns()), array_keys(State::$patterns['patterns']));
        self::assertCount(7, State::$patterns['patterns']);
        foreach (State::$patterns['patterns'] as $pattern) {
            self::assertSame(['newsletter'], $pattern['postTypes']);
            self::assertSame(['rrze-newsletter'], $pattern['categories']);
            self::assertStringContainsString('<!-- wp:', $pattern['content']);
            self::assertDoesNotMatchRegularExpression('/(?:href|src)="\//', $pattern['content']);
        }
    }
}

final class ThemeDataRecorder
{
    public array $updates = [];
    public function update_with(array $data): self { $this->updates[] = $data; return $this; }
}
