<?php

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseEntity;

/**
 * Class Relationship
 *
 * @see https://docs.joinmastodon.org/entities/status/
 */
class Status extends BaseEntity
{
	// Base attributes
	/** @var string */
	protected $id;
	/** @var string */
	protected $uri;
	/** @var string (Date) */
	protected $created_at;
	/** @var Account */
	protected $account;
	/** @var string (HTML) */
	protected $content;
	/** @var string (BBCode) */
	protected $text;
	/** @var string (public|unlisted|private|direct) */
	protected $visibility;
	/** @var bool */
	protected $sensitive;
	/** @var string */
	protected $spoiler_text;
	/** @var Attachment[] */
	protected $media_attachments;
	/** @var string */
	protected $application;

	// Rendering attributes
	/** @var Mention[] */
	protected $mentions;
	/** @var Tag[] */
	protected $tags;
	/** @var Emoji[] */
	protected $emojis;

	// Informational attributes
	/** @var int */
	protected $reblogs_count = 0;
	/** @var int */
	protected $favourites_count = 0;
	/** @var int */
	protected $replies_count = 0;

	// Nullable attributes
	/** @var string|null */
	protected $url;
	/** @var string|null */
	protected $in_reply_to_id;
	/** @var Status|null */
	protected $reblog;
	/**
	 * Unsupported
	 * @var Poll|null
	 */
	protected $poll = null;
	/** @var Card|null */
	protected $card;
	/** @var string (ISO 639 Part 1 two-letter language code) */
	protected $language;

	// Authorized user attributes
	/** @var bool */
	protected $favourited;
	/** @var bool */
	protected $reblogged;
	/** @var bool */
	protected $muted;
	/** @var bool */
	protected $bookmarked;
	/** @var bool */
	protected $pinned;










	/**
	 * @param int   $userContactId Contact row Id with uid != 0
	 * @param array $userContact   Full Contact table record with uid != 0
	 */
	public function __construct(int $userContactId, array $userContact = [])
	{
		$this->id                   = $userContactId;

		return $this;
	}
}
