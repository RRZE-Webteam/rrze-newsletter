<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\ImageSizeResolver;
use RRZE\Newsletter\Tests\Support\ImageLookupEnvironment as Environment;
use RRZE\Newsletter\Tests\Support\ImageLookupError;

final class ImageSizeResolverTest extends TestCase
{
    private const LOCAL_URL = 'https://example.test/wp-content/images/wordpress-blue.png';
    private const REMOTE_URL = 'https://images.example.test/banner.gif';

    protected function setUp(): void
    {
        parent::setUp();
        Environment::reset();
        ImageSizeResolver::reset();
    }

    protected function tearDown(): void
    {
        ImageSizeResolver::reset();
        Environment::reset();
        parent::tearDown();
    }

    private function validResponse(): array
    {
        // Complete one-pixel GIF; decoded by the real getimagesizefromstring().
        return [
            'response' => ['code' => 200],
            'headers' => ['content-type' => 'image/gif'],
            'body' => base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', true),
        ];
    }

    private function remote(string $url, array|ImageLookupError|null $response = null): void
    {
        Environment::$allowedUrls[] = $url;
        Environment::$responses[$url] = $response ?? $this->validResponse();
    }

    private function transientKey(string $url): string
    {
        return 'rrze_newsletter_image_size_' . md5($url);
    }

    public function testEmptyUrlDoesNotConsultAnySource(): void
    {
        self::assertNull(ImageSizeResolver::resolve('', ['id' => 42]));
        self::assertSame([], Environment::$attachmentLookups);
        self::assertSame([], Environment::$localLookups);
        self::assertSame([], Environment::$requests);
    }

    public function testAttachmentDimensionsTakePriorityOverLocalFile(): void
    {
        Environment::$attachmentIds[self::LOCAL_URL] = 42;
        Environment::$attachments[42]['medium'] = [self::LOCAL_URL, '300', '200', true];

        self::assertSame([300, 200], ImageSizeResolver::resolve(self::LOCAL_URL, ['sizeSlug' => 'medium']));
        self::assertSame([[42, 'medium']], Environment::$attachmentSizeLookups);
        self::assertSame([], Environment::$localLookups);
        self::assertSame([], Environment::$requests);
    }

    public function testMissingAttachmentSizeSlugUsesFullSize(): void
    {
        Environment::$attachmentIds[self::REMOTE_URL] = 42;
        Environment::$attachments[42]['full'] = [self::REMOTE_URL, 800, 400, false];

        self::assertSame([800, 400], ImageSizeResolver::resolve(self::REMOTE_URL, []));
        self::assertSame([800, 400], ImageSizeResolver::resolve(self::REMOTE_URL, ['sizeSlug' => '']));
        self::assertSame([[42, 'full'], [42, 'full']], Environment::$attachmentSizeLookups);
    }

    public function testUploadUrlCanFallBackToBlockAttachmentId(): void
    {
        $url = 'https://example.test/wp-content/uploads/2026/image.png';
        Environment::$attachments[42]['full'] = [$url, 800, 400, false];

        self::assertSame([800, 400], ImageSizeResolver::resolve($url, ['id' => 42]));
        self::assertSame([[42, 'full']], Environment::$attachmentSizeLookups);
        self::assertSame([], Environment::$requests);
    }

    public function testUrlResolvedAttachmentIdWinsOverBlockId(): void
    {
        Environment::$attachmentIds[self::REMOTE_URL] = 10;
        Environment::$attachments[10]['full'] = [self::REMOTE_URL, 100, 50, false];
        Environment::$attachments[42]['full'] = [self::REMOTE_URL, 800, 400, false];

        self::assertSame([100, 50], ImageSizeResolver::resolve(self::REMOTE_URL, ['id' => 42]));
        self::assertSame([[10, 'full']], Environment::$attachmentSizeLookups);
    }

    public function testBlockIdIsNotUsedOutsideUploadsPath(): void
    {
        foreach ([
            'https://example.test/wp-content/uploads-other/image.png',
            'https://example.test/images/image.png',
        ] as $url) {
            self::assertNull(ImageSizeResolver::resolve($url, ['id' => 42]));
        }
        self::assertSame([], Environment::$attachmentSizeLookups);
        self::assertSame([], Environment::$requests);
    }

    public function testRequestCacheSeparatesAttachmentSizesAndIds(): void
    {
        $url = 'https://example.test/wp-content/uploads/image.png';
        Environment::$attachments = [
            42 => ['full' => [$url, 800, 400], 'medium' => [$url, 300, 150]],
            43 => ['full' => [$url, 600, 200]],
        ];

        self::assertSame([800, 400], ImageSizeResolver::resolve($url, ['id' => 42]));
        self::assertSame([300, 150], ImageSizeResolver::resolve($url, ['id' => 42, 'sizeSlug' => 'medium']));
        self::assertSame([600, 200], ImageSizeResolver::resolve($url, ['id' => 43]));
        self::assertSame([800, 400], ImageSizeResolver::resolve($url, ['id' => 42]));
        self::assertCount(3, Environment::$attachmentLookups);
        self::assertCount(3, Environment::$attachmentSizeLookups);
    }

    public function testInvalidAttachmentDimensionsFallBackToLocalImage(): void
    {
        foreach ([false, [self::LOCAL_URL, 0, 100], [self::LOCAL_URL, 100, 0]] as $attachment) {
            ImageSizeResolver::reset();
            Environment::$attachmentIds[self::LOCAL_URL] = 42;
            Environment::$attachments[42]['full'] = $attachment;

            self::assertSame([1500, 1000], ImageSizeResolver::resolve(self::LOCAL_URL, []));
        }
        self::assertCount(3, Environment::$localLookups);
        self::assertSame([], Environment::$requests);
    }

    public function testLocalFileDimensionsAreReadAndCachedWithoutHttp(): void
    {
        self::assertSame([1500, 1000], ImageSizeResolver::resolve(self::LOCAL_URL, []));
        self::assertSame([1500, 1000], ImageSizeResolver::resolve(self::LOCAL_URL, []));
        self::assertSame([realpath(WP_CONTENT_DIR . '/images/wordpress-blue.png')], Environment::$localLookups);
        self::assertCount(1, Environment::$attachmentLookups);
        self::assertSame([], Environment::$requests);
    }

    public function testContentHostComparisonIsCaseInsensitive(): void
    {
        self::assertSame([1500, 1000], ImageSizeResolver::resolve(
            'https://EXAMPLE.TEST/wp-content/images/wordpress-blue.png', []
        ));
        self::assertCount(1, Environment::$localLookups);
    }

    public function testForeignHostCannotReadSamePathFromLocalContentDirectory(): void
    {
        $url = 'https://images.example.test/wp-content/images/wordpress-blue.png';
        $this->remote($url);

        self::assertSame([1, 1], ImageSizeResolver::resolve($url, []));
        self::assertSame([], Environment::$localLookups);
        self::assertSame($url, Environment::$requests[0][0]);
    }

    public function testTraversalDirectoriesAndMissingFilesAreNotRead(): void
    {
        foreach ([
            'https://example.test/wp-content/../composer.json',
            'https://example.test/wp-content/images',
            'https://example.test/wp-content/images/does-not-exist.png',
            'https://example.test/wp-content-other/images/wordpress-blue.png',
            'https://example.test',
        ] as $url) {
            self::assertNull(ImageSizeResolver::resolve($url, []), $url);
        }
        self::assertSame([], Environment::$localLookups);
        self::assertSame([], Environment::$requests);
    }

    public function testUnreadableOrInvalidLocalImageFallsBackToRemote(): void
    {
        foreach ([false, [0, 10], [10, 0]] as $size) {
            ImageSizeResolver::reset();
            Environment::reset();
            Environment::$localSizes[realpath(WP_CONTENT_DIR . '/images/wordpress-blue.png')] = $size;
            $this->remote(self::LOCAL_URL);

            self::assertSame([1, 1], ImageSizeResolver::resolve(self::LOCAL_URL, []));
            self::assertCount(1, Environment::$localLookups);
            self::assertCount(1, Environment::$requests);
        }
    }

    public function testValidRemoteImageIsDecodedAndCachedForOneDay(): void
    {
        $response = $this->validResponse();
        $response['headers']['content-type'] = 'IMAGE/GIF';
        $this->remote(self::REMOTE_URL, $response);

        self::assertSame([1, 1], ImageSizeResolver::resolve(self::REMOTE_URL, []));
        self::assertSame([[
            $this->transientKey(self::REMOTE_URL), ['size' => [1, 1]], 86400,
        ]], Environment::$transientWrites);
        self::assertSame([[
            self::REMOTE_URL,
            ['timeout' => 1, 'redirection' => 3, 'limit_response_size' => 1048576],
        ]], Environment::$requests);
    }

    public function testRejectedUrlNeverReachesHttpOrTransientWrites(): void
    {
        self::assertNull(ImageSizeResolver::resolve(self::REMOTE_URL, []));
        self::assertSame([], Environment::$requests);
        self::assertSame([], Environment::$transientWrites);
    }

    public function testRemoteErrorIsNegativelyCachedForOneHourAcrossRenders(): void
    {
        $this->remote(self::REMOTE_URL, new ImageLookupError());
        self::assertNull(ImageSizeResolver::resolve(self::REMOTE_URL, []));
        self::assertNull(ImageSizeResolver::resolve(self::REMOTE_URL, []));
        self::assertCount(1, Environment::$attachmentLookups);

        BlockProcessor::beginRender();
        self::assertNull(ImageSizeResolver::resolve(self::REMOTE_URL, []));
        self::assertCount(1, Environment::$requests);
        self::assertSame([[
            $this->transientKey(self::REMOTE_URL), ['size' => null], 3600,
        ]], Environment::$transientWrites);
    }

    public function testInvalidHttpResponsesAreRejectedAndNegativelyCached(): void
    {
        $cases = [
            'redirect' => ['response' => ['code' => 302]],
            'not found' => ['response' => ['code' => 404]],
            'informational' => ['response' => ['code' => 199]],
            'html' => ['headers' => ['content-type' => 'text/html']],
            'missing type' => ['headers' => []],
            'invalid type' => ['headers' => ['content-type' => ['image/gif']]],
            'empty body' => ['body' => ''],
            'invalid image' => ['body' => 'not an image'],
        ];
        foreach ($cases as $label => $overrides) {
            ImageSizeResolver::reset();
            Environment::reset();
            $this->remote(self::REMOTE_URL, array_replace($this->validResponse(), $overrides));

            self::assertNull(ImageSizeResolver::resolve(self::REMOTE_URL, []), $label);
            self::assertSame([[
                $this->transientKey(self::REMOTE_URL), ['size' => null], 3600,
            ]], Environment::$transientWrites, $label);
        }
    }

    public function testPositiveTransientAvoidsHttpAfterRequestCacheReset(): void
    {
        $this->remote(self::REMOTE_URL);
        self::assertSame([1, 1], ImageSizeResolver::resolve(self::REMOTE_URL, []));

        BlockProcessor::beginRender();

        self::assertSame([1, 1], ImageSizeResolver::resolve(self::REMOTE_URL, []));
        self::assertCount(2, Environment::$attachmentLookups);
        self::assertCount(1, Environment::$requests);
        self::assertCount(1, Environment::$transientWrites);
    }

    public function testIncompleteTransientIsRefreshed(): void
    {
        foreach ([false, 'old-format', ['unrelated' => [800, 400]]] as $cached) {
            Environment::reset();
            ImageSizeResolver::reset();
            Environment::$transients[$this->transientKey(self::REMOTE_URL)] = $cached;
            $this->remote(self::REMOTE_URL);

            self::assertSame([1, 1], ImageSizeResolver::resolve(self::REMOTE_URL, []));
            self::assertCount(1, Environment::$requests);
            self::assertCount(1, Environment::$transientWrites);
        }
    }

    public function testAtMostTwoUncachedUrlsAreFetchedPerRender(): void
    {
        $first = self::REMOTE_URL . '?first';
        $second = self::REMOTE_URL . '?second';
        $third = self::REMOTE_URL . '?third';
        foreach ([$first, $second, $third] as $url) {
            $this->remote($url);
        }

        self::assertSame([1, 1], ImageSizeResolver::resolve($first, []));
        self::assertSame([1, 1], ImageSizeResolver::resolve($first, []));
        self::assertSame([1, 1], ImageSizeResolver::resolve($second, []));
        self::assertNull(ImageSizeResolver::resolve($third, []));
        self::assertSame([$first, $second], array_column(Environment::$requests, 0));
        // A budget skip must not poison the persistent cache for the next render.
        self::assertArrayNotHasKey($this->transientKey($third), Environment::$transients);

        BlockProcessor::beginRender();
        self::assertSame([1, 1], ImageSizeResolver::resolve($third, []));
        self::assertCount(3, Environment::$requests);
    }

    public function testFailuresConsumeLookupBudgetButCachedImagesStillResolve(): void
    {
        $urls = [self::REMOTE_URL . '?one', self::REMOTE_URL . '?two', self::REMOTE_URL . '?three'];
        foreach ($urls as $url) {
            $this->remote($url, new ImageLookupError());
        }
        foreach ($urls as $url) {
            self::assertNull(ImageSizeResolver::resolve($url, []));
        }
        self::assertCount(2, Environment::$requests);

        Environment::$allowedUrls[] = self::REMOTE_URL;
        Environment::$transients[$this->transientKey(self::REMOTE_URL)] = ['size' => [800, 400]];
        self::assertSame([800, 400], ImageSizeResolver::resolve(self::REMOTE_URL, []));
        self::assertSame([1500, 1000], ImageSizeResolver::resolve(self::LOCAL_URL, []));
        self::assertCount(2, Environment::$requests);
    }

    public function testLocalMissIsCachedUntilNextRender(): void
    {
        Environment::$localSizes[realpath(WP_CONTENT_DIR . '/images/wordpress-blue.png')] = false;
        self::assertNull(ImageSizeResolver::resolve(self::LOCAL_URL, []));
        unset(Environment::$localSizes[realpath(WP_CONTENT_DIR . '/images/wordpress-blue.png')]);

        self::assertNull(ImageSizeResolver::resolve(self::LOCAL_URL, []));
        self::assertCount(1, Environment::$localLookups);

        BlockProcessor::beginRender();
        self::assertSame([1500, 1000], ImageSizeResolver::resolve(self::LOCAL_URL, []));
        self::assertCount(2, Environment::$localLookups);
    }
}
