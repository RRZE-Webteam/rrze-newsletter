<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\Mail;

use RRZE\Newsletter\Mail\Queue;
use RRZE\Newsletter\Tags;
use RRZE\Newsletter\Utils;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\WordPressState as WP;
use RRZE\Newsletter\Tests\Support\QueueEnvironment as Clock;
use RRZE\Newsletter\Tests\Support\QueueCreationEnvironment as State;
use RRZE\Newsletter\Tests\Support\MailEnvironment as Mail;
use RRZE\Newsletter\Tests\Support\SettingsEnvironment as Settings;
use RRZE\Newsletter\Tests\Support\SubscriptionEnvironment as Subscription;
use RRZE\Newsletter\Tests\Support\RecipientEnvironment as Recipient;

final class QueueCreationTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WP::reset(); Clock::reset(); State::reset(); Mail::reset();
        $this->post();
        WP::$postTypes[42] = 'newsletter';
        App::$meta[42] = [
            'rrze_newsletter_status' => 'send', 'rrze_newsletter_from_email' => 'sender@example.test',
            'rrze_newsletter_from_name' => 'Sender', 'rrze_newsletter_email_html' => '<p>Hello {{=FNAME}} {{=LNAME}}, {{=EMAIL}}</p><a href="{{=ARCHIVE}}">Archive</a>',
            'rrze_newsletter_send_date_gmt' => '2026-09-01 08:00:00',
        ];
        Settings::$fields['subscription'] = [['name' => 'disabled', 'default' => 'on'], ['name' => 'page_id', 'default' => 0]];
        Settings::$fields['mailing_list'] = [['name' => 'unsubscribed', 'default' => 'global@example.test']];
    }

    protected function tearDown(): void
    {
        WP::reset(); Clock::reset(); State::reset(); Mail::reset();
        parent::tearDown();
    }

    private function lists(): void
    {
        App::$terms[42]['newsletter_mailing_list'] = [(object) ['term_id' => 12], (object) ['term_id' => 34], (object) ['term_id' => 56]];
        Subscription::$termMeta[12] = [
            'rrze_newsletter_mailing_list' => "ada@example.test,Ada,Lovelace\nglobal@example.test\nlocal@example.test\ninvalid",
            'rrze_newsletter_mailing_list_unsubscribed' => 'local@example.test',
        ];
        Subscription::$termMeta[34] = ['rrze_newsletter_mailing_list' => "ada@example.test,Augusta,King\nbea@example.test,Bea,Example"];
    }

    public function testQueueCreationDeduplicatesFiltersAndPersonalizesRecipients(): void
    {
        $this->lists();
        (new Queue())->set(42);
        self::assertCount(2, State::$inserts);
        self::assertSame(['ada@example.test', 'bea@example.test'], array_column(WP::$postMeta, 'rrze_newsletter_queue_to'));
        self::assertStringContainsString('Hello Augusta King, ada@example.test', base64_decode(WP::$postUpdates[0]['post_content']));
        self::assertStringContainsString('Hello Bea Example, bea@example.test', WP::$postUpdates[1]['post_excerpt']);
        self::assertStringContainsString('/newsletter/archive/' . Utils::encryptQueryVar('101'), base64_decode(WP::$postUpdates[0]['post_content']));
        foreach (State::$inserts as $post) {
            self::assertSame('newsletter_queue', $post['post_type']);
            self::assertSame('mail-queued', $post['post_status']);
            self::assertSame('2026-09-15 08:30:00', $post['post_date_gmt']);
            self::assertSame('Test newsletter', $post['post_title']);
        }
        self::assertSame(42, WP::$postMeta[101]['rrze_newsletter_queue_newsletter_id']);
        self::assertSame(0, WP::$postMeta[101]['rrze_newsletter_queue_retries']);
        self::assertSame('sent', App::$meta[42]['rrze_newsletter_status']);
        self::assertSame('2026-09-15 08:30:00', WP::$postMeta[42]['rrze_newsletter_send_date_gmt']);
        self::assertContains(['count', [112, 134], 'newsletter_mailing_list'], State::$taxonomyCalls);
        self::assertSame([], Mail::$messages, 'Queue creation must not send mail immediately.');
    }

    public function testWrongTypeDraftAndUnrequestedSendsDoNotWrite(): void
    {
        $queue = new Queue();
        WP::$postTypes[42] = 'post'; $queue->set(42);
        WP::$postTypes[42] = 'newsletter'; App::$posts[42]->post_status = 'draft'; $queue->set(42);
        App::$posts[42]->post_status = 'publish'; App::$meta[42]['rrze_newsletter_status'] = 'sent'; $queue->set(42);
        self::assertSame([], State::$inserts);
        self::assertSame([], App::$writes);
        self::assertSame([], WP::$postMetaUpdates);
    }

    public function testMissingRenderedBodyMarksErrorWithoutAdvancingSendDate(): void
    {
        unset(App::$meta[42]['rrze_newsletter_email_html']);
        (new Queue())->set(42);
        self::assertSame('error', App::$meta[42]['rrze_newsletter_status']);
        self::assertSame([], State::$inserts);
        self::assertSame([], WP::$postMetaUpdates);
        self::assertStringContainsString('data is empty or wrong', Recipient::$actions[0][1][0]['message']);
    }

    public function testEmptyRecipientsMarkErrorWithoutCreatingQueueEntries(): void
    {
        (new Queue())->set(42);
        self::assertSame('error', App::$meta[42]['rrze_newsletter_status']);
        self::assertSame([], State::$inserts);
        self::assertSame([], WP::$postMetaUpdates);
        self::assertStringContainsString("recipient's email address array is empty", Recipient::$actions[0][1][0]['message']);
    }

    public function testConditionalSkipPreservesPreviousSendDateAndStillReschedules(): void
    {
        WP::$postMeta[42] = ['rrze_newsletter_has_conditionals' => true, 'rrze_newsletter_conditionals_rss_block' => true,
            'rrze_newsletter_is_recurring' => true, 'rrze_newsletter_recurrence_repeat' => 'DAILY'];
        (new Queue())->set(42);
        self::assertSame('skipped', App::$meta[42]['rrze_newsletter_status']);
        self::assertSame('future', WP::$postUpdates[0]['post_status']);
        self::assertSame('2026-09-16 10:30:00', WP::$postUpdates[0]['post_date']);
        self::assertSame([], State::$inserts);
        self::assertSame([], WP::$postMetaUpdates);
        self::assertSame('2026-09-01 08:00:00', App::$meta[42]['rrze_newsletter_send_date_gmt']);
    }

    public function testDirectRecipientModeUsesRecipientDomainValidation(): void
    {
        App::$filters['rrze_newsletter_disable_mailing_list'] = true;
        WP::$postMeta[42]['rrze_newsletter_to_email'] = 'ada@example.test';
        Recipient::$filters['rrze_newsletter_recipient_allowed_domains'] = ['example.test'];
        (new Queue())->set(42);
        self::assertCount(1, State::$inserts);
        self::assertSame('ada@example.test', WP::$postMeta[101]['rrze_newsletter_queue_to']);
        self::assertSame([], Subscription::$queries);
        App::$meta[42]['rrze_newsletter_status'] = 'send';
        WP::$postMeta[42]['rrze_newsletter_to_email'] = 'ada@blocked.test';
        (new Queue())->set(42);
        self::assertCount(1, State::$inserts);
        self::assertSame('error', App::$meta[42]['rrze_newsletter_status']);
    }

    public function testFailedInsertDoesNotWriteMetadataAndNextRecipientStillQueues(): void
    {
        $this->lists();
        State::$insertResults = [0, 102];
        (new Queue())->set(42);
        self::assertCount(2, State::$inserts);
        self::assertCount(1, WP::$postUpdates);
        self::assertSame(102, WP::$postUpdates[0]['ID']);
        self::assertSame('bea@example.test', WP::$postMeta[102]['rrze_newsletter_queue_to']);
        self::assertArrayNotHasKey(101, WP::$postMeta);
    }

    public function testFailedContentUpdateDoesNotAttachSendableMetadata(): void
    {
        $this->lists();
        WP::$postUpdateResults = [0, 102];
        (new Queue())->set(42);
        self::assertCount(2, WP::$postUpdates);
        self::assertArrayNotHasKey(101, WP::$postMeta);
        self::assertSame('bea@example.test', WP::$postMeta[102]['rrze_newsletter_queue_to']);
    }

    public function testTagsDropUnknownFieldsAndGeneratePersonalizedSubscriptionLinks(): void
    {
        Settings::$stored['subscription_disabled'] = '';
        Settings::$stored['subscription_page_id'] = 9;
        $this->post(['ID' => 9, 'post_name' => 'subscribe', 'post_type' => 'page']);
        App::$options['permalink_structure'] = '/%postname%/';
        $tags = Tags::sanitizeTags(42, ['FNAME' => 'Ada', 'LNAME' => 'Lovelace', 'EMAIL' => 'ada@example.test', 'ARCHIVE' => 'https://example.test/archive/', 'UNKNOWN' => 'omit']);
        self::assertSame('Ada Lovelace', $tags['NAME']);
        self::assertArrayNotHasKey('UNKNOWN', $tags);
        self::assertSame('https://example.test/archive', $tags['ARCHIVE']);
        self::assertSame('2026-09-15', $tags['DATE']);
        self::assertSame('2026', $tags['CURRENT_YEAR']);
        foreach (['UNSUB' => 'unsub', 'UPDATE' => 'update'] as $key => $action) {
            parse_str(parse_url($tags[$key], PHP_URL_QUERY), $query);
            self::assertSame($action . '|ada@example.test', Utils::decryptQueryVar($query['a']));
        }
        App::$options['permalink_structure'] = '';
        self::assertStringContainsString('page_id=9', Tags::sanitizeTags(42)['UPDATE']);
        unset(App::$posts[9]);
        self::assertSame('', Tags::sanitizeTags(42)['UPDATE']);
        App::$filters['rrze_newsletter_disable_subscription'] = true;
        self::assertSame('', Tags::sanitizeTags(42)['UNSUB']);
    }
}
