<?php

namespace Boodmo\Sales\Model\Workflow\Note;

interface NotesableEntityIntarface
{
    public function getNotes(): array;

    public function setNotes(array $notes);

    public function addMessageToNotes(NotesMessage $msg): void;
}
