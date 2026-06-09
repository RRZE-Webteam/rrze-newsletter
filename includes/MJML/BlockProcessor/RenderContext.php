<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\Renderer;

final readonly class RenderContext
{
    public function __construct(
        public int $postId,
        public array $defaultAttrs = [],
        public bool $inColumn = false,
        public bool $inGroup = false,
        public bool $inList = false,
        public int $availableWidth = Renderer::EMAIL_WIDTH
    ) {
    }

    public static function root(
        int $postId,
        int $availableWidth = Renderer::EMAIL_WIDTH
    ): self {
        return new self(
            postId: $postId,
            availableWidth: $availableWidth
        );
    }

    public function withDefaultAttrs(array $defaultAttrs): self
    {
        return $this->copy(defaultAttrs: $defaultAttrs);
    }

    public function withAvailableWidth(int $availableWidth): self
    {
        return $this->copy(availableWidth: $availableWidth);
    }

    public function insideColumn(): self
    {
        return $this->copy(inColumn: true);
    }

    public function insideGroup(): self
    {
        return $this->copy(inGroup: true);
    }

    public function insideList(): self
    {
        return $this->copy(inList: true);
    }

    private function copy(
        ?array $defaultAttrs = null,
        ?bool $inColumn = null,
        ?bool $inGroup = null,
        ?bool $inList = null,
        ?int $availableWidth = null
    ): self {
        return new self(
            postId: $this->postId,
            defaultAttrs: $defaultAttrs ?? $this->defaultAttrs,
            inColumn: $inColumn ?? $this->inColumn,
            inGroup: $inGroup ?? $this->inGroup,
            inList: $inList ?? $this->inList,
            availableWidth: $availableWidth ?? $this->availableWidth
        );
    }
}
