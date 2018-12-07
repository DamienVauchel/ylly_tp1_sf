<?php

namespace AppBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait TranslationUpdatable
{
    /**
     * @ORM\Column(name="updated", type="boolean")
     */
    protected $updated;

    public function getUpdated(): bool
    {
        return $this->updated;
    }

    public function setUpdated($updated): void
    {
        $this->updated = $updated;
    }
}
