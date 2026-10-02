<?php
declare(strict_types=1);
namespace RRZE\Newsletter\Tests\Unit\CPT;

use RRZE\Newsletter\CPT\NewsletterQueue as Queue;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;

final class NewsletterQueueContractTest extends ApplicationTestCase
{
    public function testQueuePostTypeDisablesCreationPublicQueriesAndExport(): void
    {
        Queue::registerPostType();
        $args = App::$registrations['post_types']['newsletter_queue'];
        foreach (['public', 'publicly_queryable', 'can_export', 'has_archive', 'supports', 'show_in_menu'] as $flag) { self::assertFalse($args[$flag], $flag); }
        self::assertSame(['create_posts' => 'do_not_allow'], $args['capabilities']);
        self::assertSame(['newsletter_queue', 'newsletter_queues'], $args['capability_type']);
    }

    public function testAllQueueStatusesAppearInAdminAndAreExcludedFromSearch(): void
    {
        Queue::registerPostStatus();
        self::assertSame(['mail-queued', 'mail-sent', 'mail-error'], array_keys(App::$registrations['statuses']));
        foreach (['mail-queued' => 'Queued', 'mail-sent' => 'Sent', 'mail-error' => 'Error'] as $name => $label) {
            $args = App::$registrations['statuses'][$name];
            self::assertSame($label, $args['label']);
            self::assertTrue($args['exclude_from_search']);
            self::assertTrue($args['show_in_admin_all_list']);
            self::assertTrue($args['show_in_admin_status_list']);
            self::assertStringContainsString('%s', $args['label_count']['singular']);
        }
    }

    public function testQueueDataNormalizesCountersAndConvertsSentDate(): void
    {
        $post = $this->post(['post_type' => 'newsletter_queue', 'post_status' => 'mail-sent']);
        App::$meta[42] = ['rrze_newsletter_queue_newsletter_id' => '99', 'rrze_newsletter_queue_retries' => '-2', 'rrze_newsletter_queue_sent_date_gmt' => '2026-09-15 08:30:00', 'rrze_newsletter_queue_to' => 'ada@example.test'];
        $data = Queue::getData(42);
        self::assertSame(99, $data['newsletter_id']);
        self::assertSame(2, $data['retries']);
        self::assertSame('2026-09-15 10:30:00', $data['sent_date']);
        self::assertSame('ada@example.test', $data['to']);
        self::assertSame('mail-sent', $data['status']);
        self::assertSame($post->post_date_gmt, $data['send_date_gmt']);
        App::$meta[42]['rrze_newsletter_queue_sent_date_gmt'] = '';
        self::assertSame('', Queue::getData(42)['sent_date']);
        self::assertSame([], Queue::getData(999));
    }

    public function testErrorColumnAppearsOnlyInErrorView(): void
    {
        self::assertArrayNotHasKey('error', Queue::columns(['cb' => 'checkbox']));
        App::$queryStatus = 'mail-error';
        self::assertSame('Error', Queue::columns(['cb' => 'checkbox'])['error']);
        self::assertSame('checkbox', Queue::columns(['cb' => 'checkbox'])['cb']);
    }

    public function testQueueRowActionsRemoveEditsButPreserveTrashAndOtherTypes(): void
    {
        $actions = ['edit' => 'Edit', 'inline hide-if-no-js' => 'Quick edit', 'trash' => 'Trash'];
        foreach (['mail-queued', 'mail-sent', 'mail-error'] as $status) {
            self::assertSame(['trash' => 'Trash'], Queue::rowActions($actions, $this->post(['post_type' => 'newsletter_queue', 'post_status' => $status])));
        }
        self::assertSame($actions, Queue::rowActions($actions, $this->post(['post_type' => 'post'])));
        self::assertSame($actions, Queue::rowActions($actions, $this->post(['post_type' => 'newsletter_queue', 'post_status' => 'draft'])));
    }

    public function testListControlsPreserveUnrelatedActionsAndViews(): void
    {
        self::assertSame(['trash' => 'Trash'], Queue::bulkActions(['edit' => 'Edit', 'trash' => 'Trash']));
        self::assertSame(['all' => 'All'], Queue::views(['mine' => 'Mine', 'all' => 'All']));
        self::assertSame([], Queue::removeMonthsDropdown([1, 2], 'newsletter_queue'));
        self::assertSame([1, 2], Queue::removeMonthsDropdown([1, 2], 'post'));
        self::assertSame(['subject' => 'subject'], Queue::sortableColumns(['subject' => 'subject']));
    }
}
