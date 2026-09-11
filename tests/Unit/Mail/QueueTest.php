<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\Mail;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\Mail\Queue;
use RRZE\Newsletter\Tests\Support\WordPressState;

final class QueueTest extends TestCase
{
    protected function setUp(): void
    {
        WordPressState::reset();
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
        self::assertNotEmpty(
            WordPressState::$postMeta[$post->ID]['rrze_newsletter_queue_sent_date_gmt']
        );
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
