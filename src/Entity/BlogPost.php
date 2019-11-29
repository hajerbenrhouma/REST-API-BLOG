<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BlogPostRepository")
 * @ApiFilter(
 *     SearchFilter::class,
 *     properties={
 *          "id" : "exact",
 *          "author" : "exact",
 *          "title" : "partial",
 *          "content" : "partial",
 *          "author.name" : "partial"
 *     }
 * )
 * @ApiFilter(
 *     DateFilter::class,
 *     properties={
 *          "published"
 *     }
 * )
 * @ApiFilter(
 *     RangeFilter::class,
 *     properties={
 *          "id"
 *     }
 * )
 * @ApiFilter(
 *     OrderFilter::class,
 *     properties={"id", "title", "published"},
 *     arguments={"orderParameterName"="_order"}
 *     )
 * @ApiFilter(
 *     PropertyFilter::class,
 *     arguments={
 *          "parameterName"="properties",
 *          "overrideDefaultProperties": false,
 *           "whitelist"={"id", "author", "slug", "title", "content"}
 *      }
 *     )
 * @ApiResource(
 *     attributes={
 *              "order" = {"published" : "DESC"},
 *              "maximum_items_per_page" = 30,
 *              "pagination_partial" = true
 *     },
 *     itemOperations={
 *     "get" = {
 *                     "normalization_context"={
 *                          "groups"={"get-blog-post-with_author"}
 *                     }
 *     },
 *     "put" = {
 *                  "access_control" = "is_granted('ROLE_EDITOR') or (is_granted('ROLE_WRITER') and object.getAuthor() == user)"
 *           }
 *     },
 *     collectionOperations={
 *     "get",
 *     "post" = {
 *                  "access_control" = "is_granted('ROLE_WRITER')"
 *           }
 *     },
 *     denormalizationContext={
 *          "groups"={"post"}
 *     }
 * )
 *
 */
class BlogPost implements AuthoredEntityInterface, PublishedDateEntityInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"get-blog-post-with_author"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(min=10)
     * @Groups({"post", "get-blog-post-with_author"})
     */
    private $title;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"get-blog-post-with_author"})
     */
    private $published;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     * @Assert\Length(min=20)
     * @Groups({"post", "get-blog-post-with_author"})
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="posts")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"get-blog-post-with_author"})
     */
    private $author;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="blogPost")
     * @ApiSubresource()
     * @Groups({"get-blog-post-with_author"})
     */
    private $comments;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Groups({"post", "get-blog-post-with_author"})
     */
    private $slug;

    /**
     * @ORM\ManyToMany(targetEntity="Image")
     * @ORM\JoinTable()
     * @ApiSubresource()
     * @Groups({"post", "get-blog-post-with_author"})
     */
    private $images;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPublished(): ?\DateTimeInterface
    {
        return $this->published;
    }

    public function setPublished(\DateTimeInterface $published): PublishedDateEntityInterface
    {
        $this->published = $published;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return User
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setBlogPost($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getBlogPost() === $this) {
                $comment->setBlogPost(null);
            }
        }

        return $this;
    }

    /**
     * @param UserInterface $author
     * @return AuthoredEntityInterface
     */
    public function setAuthor(UserInterface $author): AuthoredEntityInterface
    {
        $this->author = $author;
        return $this;
    }

    public function getImages(): Collection
    {
        return $this->images;
    }

    public function setImages($images): void
    {
        $this->images = $images;
    }

    public function addImage(Image $image)
    {
        $this->images->add($image);
    }

    public function removeImage(Image $image)
    {
        $this->images->removeElement($image);
    }

    public function __toString()
    {
        return $this->title;
    }
}
