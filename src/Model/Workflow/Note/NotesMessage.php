<?php

namespace Boodmo\Sales\Model\Workflow\Note;

use Boodmo\User\Entity\User;

class NotesMessage
{
    /**
     * @var string
     */
    private $context;

    /**
     * @var string
     */
    private $message;

    /**
     * @var User|null
     */
    private $author = null;

    /**
     * @var \DateTime
     */
    private $date;

    public function __construct(string $context, string $message, User $author = null)
    {
        $this->date = new \DateTime();
        $this->context = $context;
        $this->message = $message;
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return User|null
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'date' => $this->getDate()->getTimestamp(),
            'author' => $this->getAuthor() ? $this->getAuthor()->getEmail() : 'system@boodmo.com',
        ];
    }
}
