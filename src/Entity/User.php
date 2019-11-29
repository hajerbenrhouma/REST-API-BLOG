<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Controller\ResetPasswordAction;

/**
 *     (denormalization_context IS WHAT YOU SEND)
 *     (normalization_context IS WHAT YOU RETURN)
 * @ApiResource(
 *     normalizationContext={"groups"={"get"}},
 *     denormalizationContext={"groups"={"post"}},
 *
 *     itemOperations={
 *            "put-reset-password" = {
 *                  "access_control" = "is_granted('IS_AUTHENTICATED_FULLY') and object == user",
 *                  "method" = "PUT",
 *                  "path" = "/users/{id}/reset-password",
 *                  "controller" = ResetPasswordAction::class,
 *                  "denormalization_context"={
 *                          "groups" = {"put-reset-password"}
 *                  },
 *                  "validation_groups" = {"post"}
 *            },
 *
 *          "get" = {
 *                  "access_control" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *                  "normalization_context"={
 *                          "groups" = {"get"}
 *                  }
 *           },
 *          "put" = {
 *                  "access_control" = "is_granted('IS_AUTHENTICATED_FULLY') and object == user",
 *
 *                  "denormalization_context"={
 *                          "groups" = {"put"}
 *                  },
 *                  "normalization_context"={
 *                          "groups" = {"get"}
 *                  }
 *            }
 *     },
 *     collectionOperations={
 *          "post" = {
 *                  "denormalization_context"={
 *                          "groups" = {"post"}
 *                  },
 *                  "normalization_context"={
 *                          "groups" = {"get"}
 *                  },
 *                  "validation_groups" = {"post"}
 *           },
 *          "get" = {
 *                  "access_control" = "is_granted('IS_AUTHENTICATED_FULLY')",
 *                  "normalization_context"={
 *                          "groups" = {"get"}
 *                  }
 *           }
 *      },
 *
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity("username")
 * @UniqueEntity("email")
 */
class User implements UserInterface
{

    const ROLE_COMMENTATOR = 'ROLE_COMMENTATOR';
    const ROLE_WRITER = 'ROLE_WRITER';
    const ROLE_EDITOR = 'ROLE_EDITOR';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_SUPERADMIN = 'ROLE_SUPERADMIN';
    const DEFAULT_ROLES = [self::ROLE_COMMENTATOR];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"get","post", "get-comment-with_author", "get-blog-post-with_author"})
     * @Assert\NotBlank(groups={"post"})
     * @Assert\Length(min="5", groups={"post"})
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"post"})
     * @Assert\NotBlank(groups={"post"})
     * @Assert\Regex(
     *     pattern="/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{7,}/",
     *     message="Password must be seven characters long and contain on digit, one upper case letter and one lower case letter",
     *     groups={"post"}
     *      )
     * For using regex correctly you can use the website https://regex101.com/
     */
    private $password;

    /**
     * @Assert\NotBlank(groups={"post"})
     * @Groups({"post"})
     * @Assert\Expression(
     *     "this.getPassword() === this.getRetypePassword()",
     *     message="Passwords does not match",
     *     groups={"post"}
     *     ))
     */
    private $retypePassword;


    /**
     * @Groups({"put-reset-password"})
     * @Assert\NotBlank(groups={"put-reset-password"})
     * @Assert\Regex(
     *     pattern="/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{7,}/",
     *     message="Password must be seven characters long and contain on digit, one upper case letter and one lower case letter",
     *     groups={"put-reset-password"}
     * )
     * For using regex correctly you can use the website https://regex101.com/
     */
    private $newPassword;

    /**
     * @Assert\NotBlank(groups={"put-reset-password"})
     * @Groups({"put-reset-password"})
     * @Assert\Expression(
     *     "this.getNewPassword() === this.getNewRetypePassword()",
     *     message="Passwords does not match",
     *     groups={"put-reset-password"}
     *     ))
     */
    private $newRetypePassword;

    /**
     * @Groups({"put-reset-password"})
     * @Assert\NotBlank(groups={"put-reset-password"})
     * @UserPassword(groups={"put-reset-password"})
     */
    private $oldPassword;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"get","put", "post", "get-comment-with_author", "get-blog-post-with_author"})
     * @Assert\NotBlank(groups={"post"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"put", "post", "get-admin", "get-owner"})
     * @Assert\NotBlank(groups={"post"})
     * @Assert\Email(groups={"put", "post"})
     */
    private $email;

    /**
     * @ORM\OneToMany(targetEntity="BlogPost", mappedBy="author")
     * @Groups("get")
     */
    private $posts;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="author")
     * @Groups("get")
     */
    private $comments;

    /**
     * @ORM\Column(type="simple_array", length=200)
     * @Groups({"get-admin", "get-owner"})
     */
    private $roles;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $passwordChangeDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     */
    private $confirmationToken;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->roles = self::DEFAULT_ROLES;
        $this->enabled = false;
        $this->confirmationToken = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    /**
     * @param Collection $posts
     * @return User
     */
    public function setPosts(Collection $posts): self
    {
        $this->posts = $posts;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * @param Collection $comments
     * @return User
     */
    public function setComments(Collection $comments): self
    {
        $this->comments = $comments;
        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * Returns the roles granted to the user.
     *
     *     public function getRoles()
     *     {
     *         return ['ROLE_USER'];
     *     }
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles()
    {
        return $this->roles;
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        // TODO: Implement getSalt() method.
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function addPost(BlogPost $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts[] = $post;
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(BlogPost $post): self
    {
        if ($this->posts->contains($post)) {
            $this->posts->removeElement($post);
            // set the owning side to null (unless already changed)
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

        return $this;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setAuthor($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
            // set the owning side to null (unless already changed)
            if ($comment->getAuthor() === $this) {
                $comment->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRetypePassword()
    {
        return $this->retypePassword;
    }

    /**
     * @param mixed $retypePassword
     */
    public function setRetypePassword($retypePassword): void
    {
        $this->retypePassword = $retypePassword;
    }

    /**
     * @return mixed
     */
    public function getNewPassword()
    {
        return $this->newPassword;
    }

    /**
     * @param mixed $newPassword
     */
    public function setNewPassword($newPassword)
    {
        $this->newPassword = $newPassword;
    }

    /**
     * @return mixed
     */
    public function getNewRetypePassword()
    {
        return $this->newRetypePassword;
    }

    /**
     * @param mixed $newRetypePassword
     */
    public function setNewRetypePassword($newRetypePassword)
    {
        $this->newRetypePassword = $newRetypePassword;
    }

    /**
     * @return mixed
     */
    public function getOldPassword()
    {
        return $this->oldPassword;
    }

    /**
     * @param mixed $oldPassword
     */
    public function setOldPassword($oldPassword)
    {
        $this->oldPassword = $oldPassword;
    }

    /**
     * @return mixed
     */
    public function getPasswordChangeDate()
    {
        return $this->passwordChangeDate;
    }

    /**
     * @param mixed $passwordChangeDate
     */
    public function setPasswordChangeDate($passwordChangeDate)
    {
        $this->passwordChangeDate = $passwordChangeDate;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param mixed $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return mixed
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * @param mixed $confirmationToken
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;
    }
}
