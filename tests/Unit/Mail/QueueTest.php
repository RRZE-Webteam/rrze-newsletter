<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\Mail;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\Mail\Queue;
use RRZE\Newsletter\Tests\Support\QueueEnvironment;
use RRZE\Newsletter\Tests\Support\WordPressState;

final class QueueTest extends TestCase
{
    protected function setUp(): void
    {
        WordPressState::reset();
        QueueEnvironment::reset();
    }

    protected function tearDown(): void
    {
        WordPressState::reset();
        QueueEnvironment::reset();
    }

    public function testGetSelectsDueQueuedEntriesUpToConfiguredLimit(): void
    {
        $post = $this->queuePost();
        WordPressState::$posts = [$post];

        $queue = $this->queue(new FakeSmtp(), 7);

        self::assertSame([$post], $queue->get());
        self::assertSame('mail-queued', WordPressState::$lastPostsQuery['post_status']);
        self::assertSame(7, WordPressState::$lastPostsQuery['numberposts']);
        self::assertSame('post_date_gmt', WordPressState::$lastPostsQuery['date_query'][0]['column']);
        self::assertFalse(WordPressState::$lastPostsQuery['date_query'][0]['inclusive']);
        self::assertSame(['newsletter_queue'], WordPressState::$lastPostsQuery['post_type']);
        self::assertSame('ASC', WordPressState::$lastPostsQuery['order']);
        self::assertSame('date', WordPressState::$lastPostsQuery['orderby']);
        self::assertSame('2026-01-01 00:00:00', WordPressState::$lastPostsQuery['date_query'][0]['before']);
    }

    public function testSuccessfulTransportMarksQueueEntryAsSent(): void
    {
        $post = $this->queuePost();
        WordPressState::$posts = [$post];
        $this->provideNewsletterAndMailMeta($post->ID);

        $smtp = new FakeSmtp([true]);
        $this->queue($smtp)->process();

        self::assertCount(1, $smtp->calls);
        self::assertSame('recipient@example.test', $smtp->calls[0]['to']);
        self::assertSame('<p>Hello Ada</p>', $smtp->calls[0]['body']);
        self::assertSame('mail-sent', $post->post_status);
        self::assertSame('2026-01-01 00:00:00',
            WordPressState::$postMeta[$post->ID]['rrze_newsletter_queue_sent_date_gmt']
        );
    }

    public function testTransportReceivesCompleteMessageAndHeaders(): void
    {
        $post = $this->queuePost();
        WordPressState::$posts = [$post];
        $this->provideNewsletterAndMailMeta($post->ID);
        $smtp = new FakeSmtp([true]);

        $this->queue($smtp)->process();

        self::assertSame([[
            'from' => 'sender@example.test',
            'fromName' => 'Sender',
            'to' => 'recipient@example.test',
            'subject' => 'Test Newsletter',
            'body' => '<p>Hello Ada</p>',
            'altBody' => 'Hello Ada',
            'headers' => [
                'Content-Type: text/html; charset=UTF-8',
                'X-Mailtool: RRZE-Newsletter Plugin Vtest on Test Site',
                'Reply-To: reply@example.test',
            ],
            'attachments' => [],
        ]], $smtp->calls);
        self::assertSame([[$post->ID, 'rrze_newsletter_queue_sent_date_gmt', '2026-01-01 00:00:00', true]], WordPressState::$postMetaAdds);
        self::assertSame([], WordPressState::$postMetaUpdates);
    }

    public function testLegacyUnencodedHtmlIsPreserved(): void
    {
        $post = $this->queuePost();
        $post->post_content = '<p>Grüße &amp; willkommen!</p>';
        WordPressState::$posts = [$post];
        $this->provideNewsletterAndMailMeta($post->ID);
        $smtp = new FakeSmtp([true]);

        $this->queue($smtp)->process();

        self::assertSame($post->post_content, $smtp->calls[0]['body']);
    }

    public function testEmptyBlogNameFallsBackToSiteHostInHeader(): void
    {
        QueueEnvironment::$blogName = '';
        $post = $this->queuePost();
        WordPressState::$posts = [$post];
        $this->provideNewsletterAndMailMeta($post->ID);
        $smtp = new FakeSmtp([true]);

        $this->queue($smtp)->process();

        self::assertSame('X-Mailtool: RRZE-Newsletter Plugin Vtest on example.test', $smtp->calls[0]['headers'][1]);
    }

    public function testEmptyQueueHasNoTransportOrStorageSideEffects(): void
    {
        $smtp = new FakeSmtp();
        $this->queue($smtp)->process();

        self::assertSame([], $smtp->calls);
        self::assertSame([], WordPressState::$postUpdates);
        self::assertSame([], WordPressState::$postMetaUpdates);
        self::assertSame([], WordPressState::$postMetaAdds);
    }

    public function testZeroRetriesMarksFirstFailureAsError(): void
    {
        $post = $this->queuePost();
        WordPressState::$posts = [$post];
        $this->provideNewsletterAndMailMeta($post->ID);
        $smtp = new FakeSmtp([false]);

        $this->queue($smtp, 15, 0)->process();

        self::assertSame('mail-error', $post->post_status);
        self::assertSame(0, WordPressState::$postMeta[$post->ID]['rrze_newsletter_queue_retries']);
        self::assertSame([], WordPressState::$postMetaAdds);
        self::assertCount(1, $smtp->calls);
    }

    public function testRetriesBelowAndAtLimitHaveDifferentOutcomes(): void
    {
        foreach ([2 => 'mail-queued', 3 => 'mail-error', 4 => 'mail-error'] as $retries => $status) {
            WordPressState::reset();
            $post = $this->queuePost();
            WordPressState::$posts = [$post];
            $this->provideNewsletterAndMailMeta($post->ID);
            WordPressState::$postMeta[$post->ID]['rrze_newsletter_queue_retries'] = (string) $retries;
            $smtp = new FakeSmtp([false]);

            $this->queue($smtp, 15, 3)->process();

            self::assertSame($status, $post->post_status, 'Starting retries: ' . $retries);
            self::assertSame($retries < 3 ? 3 : (string) $retries, WordPressState::$postMeta[$post->ID]['rrze_newsletter_queue_retries']);
            self::assertSame([], WordPressState::$postMetaAdds);
            self::assertCount(1, $smtp->calls);
        }
    }

    public function testSuccessfulRetryMarksSentWithoutIncreasingRetryCount(): void
    {
        $post = $this->queuePost();
        WordPressState::$posts = [$post];
        $this->provideNewsletterAndMailMeta($post->ID);
        $smtp = new FakeSmtp([false, true]);
        $queue = $this->queue($smtp);

        $queue->process();
        $queue->process();

        self::assertSame('mail-sent', $post->post_status);
        self::assertSame(1, WordPressState::$postMeta[$post->ID]['rrze_newsletter_queue_retries']);
        self::assertCount(2, $smtp->calls);
        self::assertCount(1, WordPressState::$postMetaAdds);
    }

    public function testFailedEntryDoesNotPreventLaterEntryFromSending(): void
    {
        $first = $this->queuePost();
        $second = $this->queuePost();
        $second->ID = 102;
        WordPressState::$posts = [$first, $second];
        $this->provideNewsletterAndMailMeta(101);
        $this->provideNewsletterAndMailMeta(102);
        $smtp = new FakeSmtp([false, true]);

        $this->queue($smtp)->process();

        self::assertSame('mail-queued', $first->post_status);
        self::assertSame('mail-sent', $second->post_status);
        self::assertSame(1, WordPressState::$postMeta[101]['rrze_newsletter_queue_retries']);
        self::assertSame(0, WordPressState::$postMeta[102]['rrze_newsletter_queue_retries']);
        self::assertCount(2, $smtp->calls);
    }

    public function testWrongNewsletterTypeDoesNotBlockLaterValidEntry(): void
    {
        $first = $this->queuePost();
        $second = $this->queuePost();
        $second->ID = 102;
        WordPressState::$posts = [$first, $second];
        $this->provideNewsletterAndMailMeta(101);
        $this->provideNewsletterAndMailMeta(102);
        WordPressState::$postTypes[99] = 'post';
        WordPressState::$postMeta[101]['rrze_newsletter_queue_newsletter_id'] = 99;
        $smtp = new FakeSmtp([true]);

        $this->queue($smtp)->process();

        self::assertCount(1, $smtp->calls);
        self::assertSame('mail-queued', $first->post_status);
        self::assertSame([['ID' => 102, 'post_status' => 'mail-sent']], WordPressState::$postUpdates);
    }

    public function testProcessingStopsExactlyAtOneMinute(): void
    {
        $first = $this->queuePost();
        $second = $this->queuePost();
        $second->ID = 102;
        WordPressState::$posts = [$first, $second];
        $this->provideNewsletterAndMailMeta(101);
        $this->provideNewsletterAndMailMeta(102);
        QueueEnvironment::$microtimes = [100.0, 159.999, 160.0];
        $smtp = new FakeSmtp([true, true]);

        $this->queue($smtp)->process();

        self::assertCount(1, $smtp->calls);
        self::assertSame('mail-sent', $first->post_status);
        self::assertSame('mail-queued', $second->post_status);
        self::assertArrayNotHasKey('rrze_newsletter_queue_sent_date_gmt', WordPressState::$postMeta[102]);
    }

    public function testExpiredBudgetSendsNothing(): void
    {
        $post = $this->queuePost();
        WordPressState::$posts = [$post];
        $this->provideNewsletterAndMailMeta($post->ID);
        QueueEnvironment::$microtimes = [100.0, 161.0];
        $smtp = new FakeSmtp([true]);

        $this->queue($smtp)->process();

        self::assertSame([], $smtp->calls);
        self::assertSame([], WordPressState::$postUpdates);
        self::assertSame([], WordPressState::$postMetaAdds);
        self::assertSame([], WordPressState::$postMetaUpdates);
    }

    public function testConfiguredLimitsAreNonNegativeIntegers(): void
    {
        $queue = $this->queue(new FakeSmtp(), -7, -3);
        self::assertSame(7, $queue->sendLimit());
        self::assertSame(3, $queue->maxRetries());
    }

    public function testFailureIsRetriedAndEventuallyMarkedAsError(): void
    {
        $post = $this->queuePost();
        WordPressState::$posts = [$post];
        $this->provideNewsletterAndMailMeta($post->ID);

        $smtp = new FakeSmtp([false, false], 'SMTP unavailable');
        $queue = $this->queue($smtp, 15, 1);

        $queue->process();

        self::assertSame('mail-queued', $post->post_status);
        self::assertSame(
            1,
            WordPressState::$postMeta[$post->ID]['rrze_newsletter_queue_retries']
        );
        self::assertSame(
            'SMTP unavailable',
            WordPressState::$postMeta[$post->ID]['rrze_newsletter_queue_error']
        );

        $queue->process();

        self::assertCount(2, $smtp->calls);
        self::assertSame('mail-error', $post->post_status);
    }

    public function testQueueEntryWithMissingNewsletterIsNotSent(): void
    {
        $post = $this->queuePost();
        WordPressState::$posts = [$post];
        WordPressState::$postMeta[$post->ID] = [
            'rrze_newsletter_queue_newsletter_id' => 999,
        ];

        $smtp = new FakeSmtp([true]);
        $this->queue($smtp)->process();

        self::assertSame([], $smtp->calls);
        self::assertSame('mail-queued', $post->post_status);
    }

    private function queue(
        FakeSmtp $smtp,
        int $sendLimit = 15,
        int $maxRetries = 1
    ): Queue {
        $reflection = new ReflectionClass(Queue::class);
        $queue = $reflection->newInstanceWithoutConstructor();

        $options = $reflection->getProperty('options');
        $options->setValue(
            $queue,
            (object) [
                'mail_queue_send_limit' => $sendLimit,
                'mail_queue_max_retries' => $maxRetries,
            ]
        );

        $transport = $reflection->getProperty('smtp');
        $transport->setValue($queue, $smtp);

        return $queue;
    }

    private function queuePost(): object
    {
        return (object) [
            'ID' => 101,
            'post_title' => 'Test Newsletter',
            'post_content' => base64_encode('<p>Hello Ada</p>'),
            'post_excerpt' => 'Hello Ada',
            'post_status' => 'mail-queued',
        ];
    }

    private function provideNewsletterAndMailMeta(int $queueId): void
    {
        WordPressState::$postTypes[42] = Newsletter::POST_TYPE;
        WordPressState::$postMeta[$queueId] = [
            'rrze_newsletter_queue_newsletter_id' => 42,
            'rrze_newsletter_queue_from_email' => 'sender@example.test',
            'rrze_newsletter_queue_from_name' => 'Sender',
            'rrze_newsletter_queue_replyto' => 'reply@example.test',
            'rrze_newsletter_queue_to' => 'recipient@example.test',
            'rrze_newsletter_queue_retries' => 0,
        ];
    }
}

final class FakeSmtp
{
    public array $calls = [];

    private array $results;

    private object $error;

    public function __construct(
        array $results = [],
        string $errorMessage = 'Mail transport failed'
    ) {
        $this->results = $results;
        $this->error = new FakeMailError($errorMessage);
    }

    public function send(
        string $from,
        string $fromName,
        string $to,
        string $subject,
        string $body,
        string $altBody,
        array $headers,
        array $attachments = []
    ): bool {
        $this->calls[] = compact(
            'from',
            'fromName',
            'to',
            'subject',
            'body',
            'altBody',
            'headers',
            'attachments'
        );

        return array_shift($this->results) ?? false;
    }

    public function getError(): object
    {
        return $this->error;
    }
}

final class FakeMailError
{
    public function __construct(private readonly string $message)
    {
    }

    public function get_error_message(): string
    {
        return $this->message;
    }
}
