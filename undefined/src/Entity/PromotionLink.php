<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PromotionLinkRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PromotionLink
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"full", "concise"})
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"full"})
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"full"})
     */
    private $updated_at;

    /**
     * @ORM\Column(type="boolean", options={"default":true})
     * @Groups({"full"})
     */
    private $is_active = true;

    /**
     * @ORM\Column(type="string", length=64)
     * @Groups({"full", "concise"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"full", "concise"})
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"full", "concise"})
     */
    private $icon;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Promotion", inversedBy="links")
     * @ORM\JoinColumn(nullable=false)
     * @MaxDepth(1)
     * @Groups({"full", "concise"})
     */
    private $promotion;

    public function __construct()
    {

    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }

    public function setPromotion(?Promotion $promotion): self
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    /**
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }


    public function __toString()
    {
        return $this->getName() . ' : ' . $this->getUrl();
    }

}
