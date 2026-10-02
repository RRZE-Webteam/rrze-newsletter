<?php
declare(strict_types=1);
namespace RRZE\Newsletter\Tests\Unit\CPT;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterQueue;
use RRZE\Newsletter\Capabilities;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;

final class NewsletterContractTest extends ApplicationTestCase
{
    public function testNewsletterPostTypeExposesEditorButNotPublicQueries(): void
    {
        Newsletter::registerPostType();
        $args = App::$registrations['post_types']['newsletter'];
        self::assertFalse($args['public']);
        self::assertTrue($args['show_ui']);
        self::assertTrue($args['show_in_rest']);
        self::assertSame(['newsletter', 'newsletters'], $args['capability_type']);
        self::assertTrue($args['map_meta_cap']);
        self::assertContains('custom-fields', $args['supports']);
        self::assertContains('revisions', $args['supports']);
        self::assertSame(['newsletter_category', 'newsletter_mailing_list'], $args['taxonomies']);
        self::assertSame(['newsletter', 'newsletters'], App::$capabilityCalls[0]->capability_type);
    }

    public function testAllEditorMetaHasNewsletterSubtypeSingleValuesAndEditContext(): void
    {
        Newsletter::registerMeta();
        $expected = [
            'data' => 'object', 'validation_errors' => 'array', 'email_html' => 'string',
            'from_name' => 'string', 'from_email' => 'string', 'replyto' => 'string', 'preview_text' => 'string',
            'has_conditionals' => 'boolean', 'conditionals_rss_block' => 'boolean', 'conditionals_ics_block' => 'boolean',
            'is_recurring' => 'boolean', 'recurrence_repeat' => 'string', 'recurrence_monthly' => 'string',
            'template_id' => 'integer', 'font_header' => 'string', 'font_body' => 'string', 'background_color' => 'string',
            'contrast_protection' => 'boolean',
            'spacing_mode' => 'string',
        ];
        self::assertCount(count($expected), App::$registrations['meta']);
        foreach ($expected as $suffix => $type) {
            $meta = App::$registrations['meta']['rrze_newsletter_' . $suffix];
            self::assertSame('post', $meta['object_type']);
            self::assertSame('newsletter', $meta['object_subtype']);
            self::assertSame($type, $meta['type']);
            self::assertTrue($meta['single']);
            self::assertSame(['edit'], $meta['show_in_rest']['schema']['context']);
        }
        self::assertSame(['type' => 'string'], App::$registrations['meta']['rrze_newsletter_validation_errors']['show_in_rest']['schema']['items']);
        self::assertTrue(App::$registrations['meta']['rrze_newsletter_data']['show_in_rest']['schema']['additionalProperties']);
        self::assertSame('DAILY', App::$registrations['meta']['rrze_newsletter_recurrence_repeat']['default']);
        self::assertSame('BYSETPOS', App::$registrations['meta']['rrze_newsletter_recurrence_monthly']['default']);
        self::assertSame(-1, App::$registrations['meta']['rrze_newsletter_template_id']['default']);
        self::assertTrue(App::$registrations['meta']['rrze_newsletter_contrast_protection']['default']);
        self::assertSame('inherit', App::$registrations['meta']['rrze_newsletter_spacing_mode']['default']);
        self::assertSame(['inherit', 'managed', 'expert'], App::$registrations['meta']['rrze_newsletter_spacing_mode']['show_in_rest']['schema']['enum']);
    }

    public function testDirectRecipientMetaAppearsOnlyWhenMailingListsAreDisabled(): void
    {
        App::$filters['rrze_newsletter_disable_mailing_list'] = true;
        Newsletter::registerMeta();
        self::assertSame('string', App::$registrations['meta']['rrze_newsletter_to_email']['type']);
        self::assertSame(['edit'], App::$registrations['meta']['rrze_newsletter_to_email']['show_in_rest']['schema']['context']);
    }

    public function testCategoryAndMailingListHaveDistinctVisibilityAndAuthoringCapabilities(): void
    {
        Newsletter::registerCategory();
        Newsletter::registerMailingList();
        $category = App::$registrations['taxonomies']['newsletter_category'];
        $list = App::$registrations['taxonomies']['newsletter_mailing_list'];
        self::assertTrue($category['public']);
        self::assertFalse($list['public']);
        foreach ([$category, $list] as $taxonomy) {
            self::assertTrue($taxonomy['hierarchical']);
            self::assertTrue($taxonomy['show_in_rest']);
            self::assertSame('newsletter', $taxonomy['object_type']);
            self::assertSame(array_fill_keys(['manage_terms', 'edit_terms', 'delete_terms', 'assign_terms'], 'edit_others_newsletters'), $taxonomy['capabilities']);
        }
        App::$registrations = [];
        App::$filters['rrze_newsletter_disable_mailing_list'] = true;
        Newsletter::registerMailingList();
        self::assertSame([], App::$registrations);
    }

    public function testLifecycleHooksKeepRegistrationAndMailingListToggleSeparate(): void
    {
        $newsletter = new Newsletter();
        self::assertSame(['registerPostType', 'registerMeta', 'registerCategory', 'registerMailingList'], array_map(static fn ($hook) => $hook[2][1], App::$hooks));
        $newsletter->onLoaded();
        self::assertContains('created_newsletter_mailing_list', array_column(App::$hooks, 1));
        self::assertContains('save_post_newsletter', array_column(App::$hooks, 1));
        App::$hooks = [];
        App::$filters['rrze_newsletter_disable_mailing_list'] = true;
        $newsletter->onLoaded();
        self::assertNotContains('created_newsletter_mailing_list', array_column(App::$hooks, 1));
        self::assertContains('save_post_newsletter', array_column(App::$hooks, 1));
    }

    public function testStatusWritesOnlyTargetNewsletterPosts(): void
    {
        $post = $this->post();
        self::assertTrue(Newsletter::setStatus(42, 'sent'));
        self::assertSame('sent', Newsletter::getStatus(42));
        $post->post_type = 'post';
        Newsletter::setStatus(42, 'error');
        self::assertSame([[42, 'rrze_newsletter_status', 'sent']], App::$writes);
        self::assertFalse(Newsletter::validateNewsletterId(42));
        self::assertFalse(Newsletter::validateNewsletterId(999));
    }

    public function testFirstSaveInitializesTemplateAndStatusButUpdatesPreserveThem(): void
    {
        $post = $this->post();
        Newsletter::savePost(42, $post, false);
        self::assertSame([[42, 'rrze_newsletter_template_id', -1], [42, 'rrze_newsletter_status', '']], App::$writes);
        App::$writes = [];
        Newsletter::savePost(42, $post, true);
        self::assertSame([], App::$writes);
    }

    public function testLastSendDateRequiresPublishedOrScheduledPostAndValidDate(): void
    {
        $post = $this->post();
        foreach (['publish', 'future', 'draft'] as $status) {
            $post->post_status = $status;
            App::$meta[42]['rrze_newsletter_send_date_gmt'] = '2026-09-10 12:00:00';
            self::assertSame($status === 'draft' ? '1970-01-01 01:00:00' : '2026-09-10 12:00:00', Newsletter::getLastSendDateGmt(42));
        }
        $post->post_status = 'publish';
        foreach (['', '2026-02-30 12:00:00', 'invalid'] as $date) {
            App::$meta[42]['rrze_newsletter_send_date_gmt'] = $date;
            self::assertSame('1970-01-01 01:00:00', Newsletter::getLastSendDateGmt(42));
        }
    }

    public function testNewsletterDataCombinesRenderedBodySenderAndTerms(): void
    {
        $post = $this->post();
        App::$meta[42] = ['rrze_newsletter_email_html' => '<p>Ready</p>', 'rrze_newsletter_from_email' => 'sender@example.test', 'rrze_newsletter_from_name' => 'Sender', 'rrze_newsletter_replyto' => 'reply@example.test', 'rrze_newsletter_status' => 'send'];
        $terms = [(object) ['term_id' => 7]];
        App::$terms[42]['newsletter_mailing_list'] = $terms;
        $data = Newsletter::getData(42);
        self::assertSame('<p>Ready</p>', $data['content']);
        self::assertSame($post->post_date_gmt, $data['send_date_gmt']);
        self::assertSame('Sender <sender@example.test>', $data['from']);
        self::assertSame('reply@example.test', $data['replyto']);
        self::assertSame($terms, $data['mailing_list_terms']);
        self::assertSame('send', $data['status']);
        App::$meta[42]['rrze_newsletter_from_name'] = '';
        App::$terms[42] = [];
        self::assertSame('sender@example.test', Newsletter::getData(42)['from']);
        self::assertFalse(Newsletter::getData(42)['mailing_list_terms']);
    }

    public function testMissingPostOrRenderedBodyReturnsEmptyDataOrError(): void
    {
        self::assertSame([], Newsletter::getData(999));
        $this->post();
        $error = Newsletter::getData(42);
        self::assertInstanceOf(\WP_Error::class, $error);
        self::assertSame('rrze_newsletter_mjml_render_error', $error->get_error_code());
    }

    public function testStateBadgesReflectErrorsSkippedAndRecurringPosts(): void
    {
        $post = $this->post(['post_status' => 'future']);
        App::$meta[42] = ['rrze_newsletter_has_conditionals' => true, 'rrze_newsletter_is_recurring' => true, 'rrze_newsletter_status' => 'skipped'];
        $states = Newsletter::displayPostStates(['future' => 'Scheduled'], $post);
        self::assertStringContainsString('Scheduled', $states['future']);
        self::assertStringContainsString('dashicons-controls-skipforward', $states['future']);
        self::assertStringContainsString('dashicons-image-rotate', $states['future']);
        App::$meta[42]['rrze_newsletter_status'] = 'error';
        $states = Newsletter::displayPostStates([], $post);
        self::assertStringContainsString('dashicons-warning', $states['future']);
        self::assertStringNotContainsString('dashicons-image-rotate', $states['future']);
        $post->post_type = 'post';
        self::assertSame(['draft' => 'Draft'], Newsletter::displayPostStates(['draft' => 'Draft'], $post));
    }

    public function testSentStateUsesRelativeDateOnlyForRecentSends(): void
    {
        $post = $this->post();
        App::$meta[42]['rrze_newsletter_status'] = 'sent';
        self::assertSame('Sent 2 hours ago', Newsletter::displayPostStates([], $post)['publish']);
        $post->post_date = '2026-09-10 10:30:00';
        self::assertSame('Sent 2026-09-10', Newsletter::displayPostStates([], $post)['publish']);
    }

    public function testQuickEditIsRemovedOnlyForNewsletters(): void
    {
        $post = $this->post();
        $actions = ['edit' => 'Edit', 'inline hide-if-no-js' => 'Quick edit', 'trash' => 'Trash'];
        self::assertSame(['edit' => 'Edit', 'trash' => 'Trash'], Newsletter::removeQuickEdit($actions));
        $post->post_type = 'post';
        self::assertSame($actions, Newsletter::removeQuickEdit($actions));
    }

    public function testCapabilitiesKeepQueueCreationDisabledAndUnknownTypesUseDefaults(): void
    {
        self::assertSame(['create_posts' => 'do_not_allow'], Capabilities::getCptCustomCaps('newsletter_queue'));
        self::assertFalse(Capabilities::getCptMapMetaCap('newsletter_queue'));
        self::assertSame('post', Capabilities::getCptCapabilityType('unknown'));
        self::assertSame([], Capabilities::getCptCustomCaps('unknown'));
        self::assertSame(['newsletter', 'newsletter_queue', 'newsletter_layout'], array_keys(Capabilities::getCurrentCptArgs()));
    }
}
