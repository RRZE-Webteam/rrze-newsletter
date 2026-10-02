<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\Mail;

use RRZE\Newsletter\Events;
use RRZE\Newsletter\Mail\Queue;
use RRZE\Newsletter\Scheduling\NewsletterLock;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\WordPressState as WP;
use RRZE\Newsletter\Tests\Support\QueueEnvironment as Clock;
use RRZE\Newsletter\Tests\Support\QueueCreationEnvironment as Creation;
use RRZE\Newsletter\Tests\Support\MailEnvironment as Mail;
use RRZE\Newsletter\Tests\Support\SettingsEnvironment as Settings;
use RRZE\Newsletter\Tests\Support\RecipientEnvironment as Recipient;

final class RecurringNewsletterRecoveryTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        WP::reset(); Clock::reset(); Creation::reset(); Mail::reset();
        Clock::$timestamp = strtotime('2026-09-15 08:30:00 UTC');
        Settings::$fields['subscription'] = [['name' => 'disabled', 'default' => 'on'], ['name' => 'page_id', 'default' => 0]];
        App::$filters['rrze_newsletter_disable_mailing_list'] = true;
    }

    protected function tearDown(): void
    {
        WP::reset(); Clock::reset(); Creation::reset(); Mail::reset();
        parent::tearDown();
    }

    private function recurring(array $values = []): \WP_Post
    {
        $post = $this->post($values);
        WP::$postTypes[$post->ID] = 'newsletter';
        WP::$postMeta[$post->ID] = [
            'rrze_newsletter_has_conditionals' => '1',
            'rrze_newsletter_is_recurring' => '1',
            'rrze_newsletter_recurrence_repeat' => 'DAILY',
            'rrze_newsletter_to_email' => 'reader@example.test',
        ];
        App::$meta[$post->ID] = [
            'rrze_newsletter_status' => 'send',
            'rrze_newsletter_email_html' => '<p>Issue {{=DATE}} / {{=CURRENT_YEAR}}</p>',
            'rrze_newsletter_from_email' => 'sender@example.test',
            'rrze_newsletter_from_name' => 'Sender',
            'rrze_newsletter_send_date_gmt' => '2026-09-01 08:00:00',
        ];
        return $post;
    }

    public function testNextEventExistsBeforeRenderingButQueueAndTagsKeepOriginalDate(): void
    {
        Clock::$siteNow = '2026-12-31 10:30:00';
        $this->recurring(['post_date' => '2026-12-31 10:30:00', 'post_date_gmt' => '2026-12-31 09:30:00']);
        $queue = new InspectRenderingQueue();
        $queue->set(42);

        self::assertSame(strtotime('2027-01-01 09:30:00 UTC'), $queue->eventAtRender);
        self::assertSame('future', App::$posts[42]->post_status);
        self::assertSame('2027-01-01 10:30:00', App::$posts[42]->post_date);
        self::assertCount(1, Creation::$inserts);
        self::assertSame('2026-12-31 09:30:00', Creation::$inserts[0]['post_date_gmt']);
        self::assertStringContainsString('Issue 2026-12-31 / 2026', base64_decode(WP::$postUpdates[1]['post_content']));
        self::assertSame('2026-09-01 08:00:00', $queue->cutoffAtRender);
        self::assertSame('sent', App::$meta[42]['rrze_newsletter_status']);
        $queue->set(42);
        $queue->recoverRecurringNewsletters();
        self::assertCount(1, Creation::$inserts, 'Repeated callbacks/reconciliation must not rebuild the issue.');
        self::assertSame([], Mail::$messages);
    }

    public function testThrownRenderingErrorKeepsNextEventAndPreviousRssCutoff(): void
    {
        $this->recurring();
        $queue = new InspectRenderingQueue();
        $queue->throwDuringRendering = true;
        $queue->set(42);

        self::assertSame('error', App::$meta[42]['rrze_newsletter_status']);
        self::assertSame(strtotime('2026-09-16 08:30:00 UTC'), WP::$scheduledEvents[42]);
        self::assertSame('2026-09-01 08:00:00', App::$meta[42]['rrze_newsletter_send_date_gmt']);
        self::assertSame([], WP::$postMetaUpdates);
        self::assertSame([], Creation::$inserts);
        self::assertStringContainsString('RuntimeException', Recipient::$actions[0][1][0]['message']);
    }

    public function testMissingRenderedHtmlDoesNotStopRecurrence(): void
    {
        $this->recurring();
        unset(App::$meta[42]['rrze_newsletter_email_html']);
        (new Queue())->set(42);

        self::assertSame('error', App::$meta[42]['rrze_newsletter_status']);
        self::assertArrayHasKey(42, WP::$scheduledEvents);
        self::assertSame([], Creation::$inserts);
    }

    public function testFailedPostUpdateStopsQueueAndCanBeRecoveredWithoutReplayingIt(): void
    {
        $this->recurring();
        WP::$postUpdateResults = [new \WP_Error('db_error', 'Database update failed.')];
        $queue = new InspectRenderingQueue();
        $queue->set(42);

        self::assertNull($queue->eventAtRender, 'Do not fetch RSS after a failed schedule change.');
        self::assertSame('error', App::$meta[42]['rrze_newsletter_status']);
        self::assertSame('publish', App::$posts[42]->post_status);
        self::assertSame([], Creation::$inserts);
        self::assertStringContainsString('Database update failed.', Recipient::$actions[0][1][0]['message']);

        $queue->recoverRecurringNewsletters();
        self::assertSame('future', App::$posts[42]->post_status);
        self::assertArrayHasKey(42, WP::$scheduledEvents);
        self::assertSame([], Creation::$inserts);
    }

    public function testZeroUpdateResultCannotBeReportedAsSkipped(): void
    {
        $this->recurring();
        WP::$postMeta[42]['rrze_newsletter_conditionals_rss_block'] = '1';
        WP::$postUpdateResults = [0];
        (new Queue())->set(42);
        self::assertSame('error', App::$meta[42]['rrze_newsletter_status']);
        self::assertSame([], Creation::$inserts);
        self::assertSame([], WP::$scheduledEvents);
    }

    public function testCronWriteFailureIsVisibleAndRepairedOnNextPass(): void
    {
        $this->recurring();
        WP::$coreSchedules = false;
        WP::$scheduleResults = [new \WP_Error('could_not_set', 'Cron write failed.')];
        $queue = new Queue();
        $queue->set(42);
        self::assertSame('future', App::$posts[42]->post_status);
        self::assertSame('error', App::$meta[42]['rrze_newsletter_status']);
        self::assertSame([], Creation::$inserts);
        self::assertSame([], WP::$scheduledEvents);
        self::assertStringContainsString('Cron write failed.', Recipient::$actions[0][1][0]['message']);

        $queue->recoverRecurringNewsletters();
        self::assertSame(strtotime('2026-09-16 08:30:00 UTC'), WP::$scheduledEvents[42]);
        self::assertCount(1, WP::$postUpdates, 'Retry only the missing cron write, not the date change.');
    }

    public function testMissingCoreEventIsExplicitlyScheduledAfterSavingNextDate(): void
    {
        $this->recurring();
        WP::$coreSchedules = false;
        (new Queue())->set(42);
        self::assertSame([[strtotime('2026-09-16 08:30:00 UTC'), 'publish_future_post', [42], true]], WP::$scheduleCalls);
        self::assertSame('sent', App::$meta[42]['rrze_newsletter_status']);
    }

    public function testLostOverdueEventIsRestoredOnceWithoutSendingMailOrChangingCutoff(): void
    {
        $this->recurring(['post_status' => 'future', 'post_date_gmt' => '2026-07-17 14:11:19']);
        App::$meta[42]['rrze_newsletter_status'] = 'skipped';
        $queue = new Queue();
        $queue->recoverRecurringNewsletters();
        $queue->recoverRecurringNewsletters();

        self::assertSame([[Clock::$timestamp + 60, 'publish_future_post', [42], true]], WP::$scheduleCalls);
        self::assertSame([], WP::$postUpdates);
        self::assertSame([], Creation::$inserts);
        self::assertSame([], WP::$postMetaUpdates);
        self::assertSame([], Mail::$messages);
        self::assertCount(1, Recipient::$actions);
        self::assertSame('rrze.log.info', Recipient::$actions[0][0]);
    }

    public function testUpcomingEventIsRestoredAtItsOriginalTime(): void
    {
        $this->recurring(['post_status' => 'future', 'post_date_gmt' => '2026-09-20 08:30:00']);
        (new Queue())->recoverRecurringNewsletters();
        self::assertSame(strtotime('2026-09-20 08:30:00 UTC'), WP::$scheduledEvents[42]);
        self::assertSame([], WP::$postUpdates);
    }

    public function testAlreadyScheduledDraftTrashAndNonRecurringSourcesAreUntouched(): void
    {
        $this->recurring(['post_status' => 'future']);
        WP::$scheduledEvents[42] = Clock::$timestamp - 100;
        $this->recurring(['ID' => 43, 'post_status' => 'draft']);
        $this->recurring(['ID' => 44, 'post_status' => 'trash']);
        $this->recurring(['ID' => 45, 'post_status' => 'future']);
        WP::$postMeta[45]['rrze_newsletter_is_recurring'] = '';
        $this->recurring(['ID' => 46, 'post_status' => 'publish']);
        WP::$postMeta[46]['rrze_newsletter_has_conditionals'] = '';
        (new Queue())->recoverRecurringNewsletters();
        self::assertSame([], WP::$postUpdates);
        self::assertSame([], WP::$scheduleCalls);
        self::assertSame([], Recipient::$actions);
    }

    public function testStrandedPublishedSourcesAdvanceWithoutRebuildingExistingQueue(): void
    {
        foreach (['send', 'sent', 'skipped', 'error'] as $index => $status) {
            $this->recurring(['ID' => 42 + $index]);
            App::$meta[42 + $index]['rrze_newsletter_status'] = $status;
        }
        WP::$posts = [(object) ['ID' => 101, 'post_status' => 'mail-queued', 'post_date_gmt' => '2026-09-15 08:30:00']];
        WP::$postMeta[101]['rrze_newsletter_queue_newsletter_id'] = 42;
        $queue = new Queue();
        $queue->recoverRecurringNewsletters();
        $queue->recoverRecurringNewsletters();
        self::assertCount(4, WP::$postUpdates);
        self::assertCount(4, WP::$scheduledEvents);
        self::assertSame([], Creation::$inserts);
        self::assertSame('mail-queued', WP::$posts[0]->post_status);
        self::assertSame([], WP::$postMetaUpdates);
    }

    public function testSnapshotsForSameOccurrencePreventReplayButOlderIssuesDoNot(): void
    {
        foreach (['mail-queued', 'mail-sent', 'mail-error'] as $index => $status) {
            $this->recurring(['ID' => 42 + $index, 'post_status' => 'future']);
            WP::$posts[] = (object) ['ID' => 101 + $index, 'post_status' => $status, 'post_date_gmt' => '2026-09-15 08:30:00'];
            WP::$postMeta[101 + $index]['rrze_newsletter_queue_newsletter_id'] = 42 + $index;
        }
        $this->recurring(['ID' => 45, 'post_status' => 'future']);
        WP::$posts[] = (object) ['ID' => 104, 'post_status' => 'mail-sent', 'post_date_gmt' => '2026-09-01 08:30:00'];
        WP::$postMeta[104]['rrze_newsletter_queue_newsletter_id'] = 45;
        (new Queue())->recoverRecurringNewsletters();
        self::assertCount(3, WP::$postUpdates);
        self::assertSame(strtotime('2026-09-16 08:30:00 UTC'), WP::$scheduledEvents[42]);
        self::assertSame(Clock::$timestamp + 60, WP::$scheduledEvents[45]);
        self::assertSame([], Creation::$inserts);
    }

    public function testRecoveryContinuesAfterOneFailureAndProcessesMoreThanOneBatch(): void
    {
        for ($id = 1; $id <= 101; $id++) {
            $this->recurring(['ID' => $id, 'post_status' => 'future']);
        }
        WP::$scheduleResults = [false];
        (new Queue())->recoverRecurringNewsletters();
        self::assertCount(100, WP::$scheduledEvents);
        self::assertArrayHasKey(101, WP::$scheduledEvents);
        self::assertSame('error', App::$meta[1]['rrze_newsletter_status']);
        self::assertSame([], Creation::$inserts);
    }

    public function testRecoveryRunsBeforeMailTransport(): void
    {
        $this->recurring(['post_status' => 'future']);
        $events = (new \ReflectionClass(Events::class))->newInstanceWithoutConstructor();
        $queue = new class extends Queue {
            public function process() { throw new \RuntimeException('Simulated transport crash'); }
        };
        (new \ReflectionProperty(Events::class, 'queue'))->setValue($events, $queue);
        try {
            $events->processMailQueue();
            self::fail('The simulated transport must fail.');
        } catch (\RuntimeException $error) {
            self::assertSame('Simulated transport crash', $error->getMessage());
        }
        self::assertArrayHasKey(42, WP::$scheduledEvents);
        self::assertSame([], Creation::$inserts);
    }

    public function testConcurrentPublicationWaitsForTheActiveBuildAndIsThenRecovered(): void
    {
        $this->recurring();
        $queue = new InspectRenderingQueue();
        $queue->duringRendering = static function (): void {
            // The early next event becomes due while an RSS request is still running.
            App::$posts[42]->post_status = 'publish';
            unset(WP::$scheduledEvents[42]);
            (new Queue())->set(42);
            (new Queue())->recoverRecurringNewsletters();
            self::assertCount(1, WP::$postUpdates);
            self::assertSame([], Creation::$inserts);
        };
        $queue->set(42);
        self::assertCount(1, Creation::$inserts);
        $queue->recoverRecurringNewsletters();
        self::assertSame('future', App::$posts[42]->post_status);
        self::assertArrayHasKey(42, WP::$scheduledEvents);
        self::assertCount(1, Creation::$inserts);
    }

    public function testExpiredBuilderDoesNotResumeWritingQueueOrCutoff(): void
    {
        $this->recurring();
        $queue = new InspectRenderingQueue();
        $queue->duringRendering = static function (): void { Clock::$timestamp += NewsletterLock::LIFETIME; };
        $queue->set(42);
        self::assertSame([], Creation::$inserts);
        self::assertSame([], WP::$postMetaUpdates);
        self::assertArrayHasKey(42, WP::$scheduledEvents);
        $lock = NewsletterLock::acquire(42);
        self::assertNotNull($lock, 'Even an expired builder must release its lease in finally.');
        $lock->release();
    }

    public function testStaleLockCanBeReplacedButItsOldOwnerCannotReleaseTheNewLock(): void
    {
        $this->recurring(['post_status' => 'future']);
        $old = NewsletterLock::acquire(42);
        self::assertNotNull($old);
        self::assertNull(NewsletterLock::acquire(42));
        (new Queue())->recoverRecurringNewsletters();
        self::assertSame([], WP::$scheduledEvents);

        Clock::$timestamp += NewsletterLock::LIFETIME;
        self::assertFalse($old->owns());
        $replacement = NewsletterLock::acquire(42);
        self::assertNotNull($replacement);
        $old->release();
        self::assertTrue($replacement->owns());
        self::assertNull(NewsletterLock::acquire(42));
        $replacement->release();
        (new Queue())->recoverRecurringNewsletters();
        self::assertArrayHasKey(42, WP::$scheduledEvents);
    }
}

final class InspectRenderingQueue extends Queue
{
    public ?int $eventAtRender = null;
    public string $cutoffAtRender = '';
    public bool $throwDuringRendering = false;
    public ?\Closure $duringRendering = null;

    protected function getNewsletterData(\WP_Post $post): \WP_Error|array|string
    {
        $this->eventAtRender = WP::$scheduledEvents[$post->ID] ?? null;
        $this->cutoffAtRender = App::$meta[$post->ID]['rrze_newsletter_send_date_gmt'];
        if ($this->duringRendering) { ($this->duringRendering)(); }
        if ($this->throwDuringRendering) { throw new \RuntimeException('Simulated RSS failure'); }
        return parent::getNewsletterData($post);
    }
}
