<?php
declare(strict_types=1);
namespace RRZE\Newsletter\Tests\Support;

abstract class ApplicationTestCase extends SettingsTestCase
{
    private array $rendererState = [];

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationEnvironment::reset();
        SubscriptionEnvironment::reset();
        foreach (['colorPalette', 'fontHeader', 'fontBody', 'linkColor', 'linkTextDecoration'] as $name) {
            $property = new \ReflectionProperty(\RRZE\Newsletter\MJML\Renderer::class, $name);
            $this->rendererState[$name] = $property->getValue();
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->rendererState as $name => $value) {
            (new \ReflectionProperty(\RRZE\Newsletter\MJML\Renderer::class, $name))->setValue(null, $value);
        }
        ApplicationEnvironment::reset();
        SubscriptionEnvironment::reset();
        parent::tearDown();
    }

    protected function post(array $values = []): \WP_Post
    {
        $post = new \WP_Post((object) $values);
        ApplicationEnvironment::$posts[$post->ID] = $post;
        return $post;
    }
}
