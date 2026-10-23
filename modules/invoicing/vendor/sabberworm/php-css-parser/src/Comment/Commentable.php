<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS\Comment;

/**
 * A standard implementation of this interface is available in the `CommentContainer` trait.
 */
interface Commentable
{
    /**
     * @param list<Comment> $comments
     */
    public function addComments(array $comments): void;
    /**
     * @return list<Comment>
     */
    public function getComments(): array;
    /**
     * @param list<Comment> $comments
     */
    public function setComments(array $comments): void;
}
