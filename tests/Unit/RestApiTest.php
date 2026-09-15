<?php
declare(strict_types=1);
namespace RRZE\Newsletter\Tests\Unit;

use RRZE\Newsletter\RestApi;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;
use RRZE\Newsletter\Tests\Support\RecipientEnvironment;
use RRZE\Newsletter\Tests\Support\SubscriptionEnvironment;

final class RestApiTest extends ApplicationTestCase
{
    public function testRoutesDeclarePermissionCallbacksAndInputContracts(): void
    {
        $api = new RestApi();
        self::assertSame(['action', 'rest_api_init', [$api, 'restApiInit'], 10, 1], App::$hooks[0]);
        $api->restApiInit();
        $routes = App::$registrations['routes'];
        self::assertCount(10, $routes);
        foreach ($routes as $route) {
            self::assertSame([$api, 'apiAuthoringPermissionsCheck'], $route['permission_callback']);
            self::assertIsCallable($route['callback']);
            self::assertContains($route['methods'], [\WP_REST_Server::READABLE, \WP_REST_Server::EDITABLE]);
            if (isset($route['args']['id'])) {
                self::assertSame('absint', $route['args']['id']['sanitize_callback']);
                self::assertSame([$api, 'validateNewsletterId'], $route['args']['id']['validate_callback']);
            }
        }
        $mjml = $routes['rrze-newsletter/v1/post-mjml'];
        self::assertTrue($mjml['args']['post_id']['required']);
        self::assertTrue($mjml['args']['content']['required']);
        self::assertSame(['edit'], App::$registrations['fields']['post/newsletter_author_info']['schema']['context']);
    }

    public function testAuthoringPermissionRequiresNewsletterCapability(): void
    {
        $api = new RestApi();
        $error = $api->apiAuthoringPermissionsCheck([]);
        self::assertInstanceOf(\WP_Error::class, $error);
        self::assertSame('rrze_newsletter_rest_forbidden', $error->get_error_code());
        self::assertSame(['status' => 403], $error->get_error_data());
        App::$capabilities['edit_others_newsletters'] = true;
        self::assertTrue($api->apiAuthoringPermissionsCheck([]));
        self::assertSame(['edit_others_newsletters', 'edit_others_newsletters'], App::$capabilityCalls);
    }

    public function testAdministrationPermissionUsesManageOptionsIndependently(): void
    {
        $api = new RestApi();
        App::$capabilities['edit_others_newsletters'] = true;
        self::assertInstanceOf(\WP_Error::class, $api->apiAdministrationPermissionsCheck([]));
        App::$capabilities['manage_options'] = true;
        self::assertTrue($api->apiAdministrationPermissionsCheck([]));
        self::assertSame(['manage_options', 'manage_options'], App::$capabilityCalls);
    }

    public function testNewsletterIdValidationRejectsMissingAndOtherPostTypes(): void
    {
        $api = new RestApi();
        $post = $this->post();
        self::assertTrue($api->validateNewsletterId(42));
        self::assertFalse($api->validateNewsletterId(999));
        $post->post_type = 'post';
        self::assertFalse($api->validateNewsletterId(42));
    }

    public function testMetadataAllowListExcludesStatusAndRenderedBody(): void
    {
        $api = new RestApi();
        foreach (['from_name', 'from_email', 'replyto', 'font_header', 'font_body', 'background_color', 'preview_text'] as $suffix) {
            self::assertTrue($api->validateNewsletterPostMetaKey('rrze_newsletter_' . $suffix));
        }
        foreach (['rrze_newsletter_status', 'rrze_newsletter_email_html', 'rrze_newsletter_send_date_gmt', 'unknown', ''] as $key) {
            self::assertFalse($api->validateNewsletterPostMetaKey($key));
        }
    }

    public function testMetadataHandlerForwardsOnlyTheRequestedWrite(): void
    {
        self::assertSame([], (new RestApi())->apiSetPostMeta(['id' => 42, 'key' => 'rrze_newsletter_preview_text', 'value' => 'Preview']));
        self::assertSame([[42, 'rrze_newsletter_preview_text', 'Preview']], App::$writes);
    }

    public function testSenderAndRecipientValidationReturnUserFacingMessages(): void
    {
        $api = new RestApi();
        self::assertSame(['message' => ''], $api->apiSender(['id' => 42, 'from_name' => 'Name', 'from_email' => ' sender@example.test ', 'replyto' => 'reply@example.test']));
        self::assertSame(['message' => 'The sender email address is not valid.'], $api->sender(42, '', 'invalid', ''));
        self::assertSame(['message' => 'The recipient email address is not valid.'], $api->apiRecipient(['id' => 42, 'to_email' => 'invalid']));
        RecipientEnvironment::$filters['rrze_newsletter_recipient_allowed_domains'] = ['example.test'];
        self::assertSame(['message' => 'The recipient email domain is not allowed.'], $api->recipient(42, 'ada@blocked.test'));
        self::assertSame(['message' => ''], $api->recipient(42, 'ada@example.test'));
        self::assertSame([], App::$writes);
    }

    public function testWeeklyAndMonthlyLabelsUsePostDate(): void
    {
        $this->post();
        $api = new RestApi();
        self::assertSame('Weekly on Tuesday', json_decode($api->apiRetrieveWeeklyRrules(['id' => 42]), true));
        self::assertSame([
            ['label' => 'Monthly on day 15', 'value' => 'BYMONTHDAY'],
            ['label' => 'Monthly on the third Tuesday', 'value' => 'BYSETPOS'],
        ], json_decode($api->apiRetrieveMonthlyRrules(['id' => 42]), true));
    }

    public function testRetrieveAndAuthorMetadataHaveExpectedPayloads(): void
    {
        $api = new RestApi();
        self::assertSame('42', $api->apiRetrieve(['id' => 42]));
        self::assertSame([['display_name' => 'Author 7', 'id' => 7, 'author_link' => 'https://example.test/author/7']], $api->getAuthorInfo(['author' => 7]));
    }

    public function testPaletteUpdateMergesColorsAndOverridesOnlyMatchingKeys(): void
    {
        App::$options['rrze_newsletter_color_palette'] = '{"primary":"#111111","secondary":"#222222"}';
        $request = new class { public function get_body(): string { return '{"primary":"#abcdef","new":"#123456"}'; } };
        self::assertSame([], (new RestApi())->apiSetColorPalette($request));
        self::assertSame('rrze_newsletter_color_palette', SubscriptionEnvironment::$optionWrites[0][0]);
        self::assertSame(['primary' => '#abcdef', 'secondary' => '#222222', 'new' => '#123456'], json_decode(SubscriptionEnvironment::$optionWrites[0][1], true));
    }

    public function testEditorMjmlUsesUnsavedTitleAndContent(): void
    {
        $this->post();
        $output = (new RestApi())->apiGetMjml(['post_id' => 42, 'title' => 'Unsaved title', 'content' => 'empty-editor-content']);
        self::assertStringContainsString('<mj-title>Unsaved title</mj-title>', $output['mjml']);
        self::assertSame('empty-editor-content', App::$posts[42]->post_content);
        self::assertSame([], App::$writes);
    }

    public function testMailPreviewCannotSendWithoutRenderedHtml(): void
    {
        $this->post();
        $error = (new RestApi())->test(42, ['ada@example.test']);
        self::assertInstanceOf(\WP_Error::class, $error);
        self::assertSame('rrze_newsletter_mjml_render_error', $error->get_error_code());
    }
}
