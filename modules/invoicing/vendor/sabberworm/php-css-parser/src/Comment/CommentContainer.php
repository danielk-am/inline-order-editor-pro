<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS\Comment;

/**
 * Provides a standard reusable implementation of `Commentable`.
 *
 * @internal
 *
 * @phpstan-require-implements Commentable
 */
trait CommentContainer
{
    /**
     * @var list<Comment>
     */
    protected $comments = [];
    /**
     * @param list<Comment> $comments
     */
    public function addComments(array $comments): void
    {
        $this->comments = \array_merge($this->comments, $comments);
    }
    /**
     * @return list<Comment>
     */
    public function getComments(): array
    {
        return $this->comments;
    }
    /**
     * @param list<Comment> $comments
     */
    public function setComments(array $comments): void
    {
        $this->comments = $comments;
    }
}
