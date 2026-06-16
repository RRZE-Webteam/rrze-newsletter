<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\Renderer;

final readonly class RenderContext
{
    /**
     * @param int $postId Newsletter post ID.
     * @param array<string, mixed> $defaultAttrs Attributes inherited by children.
     * @param bool $inColumn Whether rendering occurs inside an MJML column.
     * @param bool $inGroup Whether rendering occurs inside a group wrapper.
     * @param bool $inList Whether rendering occurs inside list markup.
     * @param int $availableWidth Available content width in pixels.
     */
    public function __construct(
        public int $postId,
        public array $defaultAttrs = [],
        public bool $inColumn = false,
        public bool $inGroup = false,
        public bool $inList = false,
        public int $availableWidth = Renderer::EMAIL_WIDTH
    ) {
    }

    /**
     * Creates the rendering context for a top-level block.
     *
     * @param int $postId Newsletter post ID.
     * @param int $availableWidth Available content width in pixels.
     * @return self Root rendering context.
     */
    public static function root(
        int $postId,
        int $availableWidth = Renderer::EMAIL_WIDTH
    ): self {
        return new self(
            postId: $postId,
            availableWidth: $availableWidth
        );
    }

    /**
     * Returns a context with different inherited attributes.
     *
     * @param array<string, mixed> $defaultAttrs Attributes inherited by children.
     * @return self Updated immutable context.
     */
    public function withDefaultAttrs(array $defaultAttrs): self
    {
        return $this->copy(defaultAttrs: $defaultAttrs);
    }

    /**
     * Returns a context with a different available content width.
     *
     * @param int $availableWidth Available content width in pixels.
     * @return self Updated immutable context.
     */
    public function withAvailableWidth(int $availableWidth): self
    {
        return $this->copy(availableWidth: $availableWidth);
    }

    /**
     * Marks the context as being inside an MJML column.
     *
     * @return self Updated immutable context.
     */
    public function insideColumn(): self
    {
        return $this->copy(inColumn: true);
    }

    /**
     * Marks the context as being inside a group.
     *
     * @return self Updated immutable context.
     */
    public function insideGroup(): self
    {
        return $this->copy(inGroup: true);
    }

    /**
     * Marks the context as being inside list markup.
     *
     * @return self Updated immutable context.
     */
    public function insideList(): self
    {
        return $this->copy(inList: true);
    }

    /**
     * Creates a modified copy while retaining unspecified values.
     *
     * @param array<string, mixed>|null $defaultAttrs Replacement inherited attributes.
     * @param bool|null $inColumn Replacement column state.
     * @param bool|null $inGroup Replacement group state.
     * @param bool|null $inList Replacement list state.
     * @param int|null $availableWidth Replacement width in pixels.
     * @return self Updated immutable context.
     */
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
