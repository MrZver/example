<?php

namespace Boodmo\Sales\Model\Workflow\Note;

use Doctrine\ORM\Mapping as ORM;

trait NotesableEntityTrait
{
    /**
     * @var array
     *
     * @ORM\Column(name="notes", type="json_array", precision=0, scale=0, nullable=false, unique=false,
     *     options={"default" = "{}"}, columnDefinition="JSONB DEFAULT '{}'::jsonb NOT NULL")
     */
    private $notes = [];

    /**
     * @return array
     */
    public function getNotes(): array
    {
        return $this->notes;
    }

    /**
     * @param array $notes
     *
     * @return $this
     */
    public function setNotes(array $notes)
    {
        $this->notes = $notes;
        return $this;
    }

    public function addMessageToNotes(NotesMessage $msg): void
    {
        $stack = $this->notes[$msg->getContext()] ?? [];
        array_unshift($stack, $msg->toArray());
        $this->notes[$msg->getContext()] = $stack;
    }
}
