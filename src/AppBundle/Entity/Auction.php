<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Auction
 *
 * @ORM\Table(name="auction")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuctionRepository")
 */
class Auction
{
    CONST STATUS_ACTIVE = "active";
    CONST STATUS_FINISHED = "finished";
    CONST STATUS_CANCELED = "canceled";


    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank(message="Tytuł nie może być pusty.")
     * @Assert\Length(
     *     min=5,
     *     max=255,
     *     minMessage="Tytuł nie może być krótszy niż 5 znaków.",
     *     maxMessage="Tytuł nie może być dłuższy niż 255 znaków."
     * )
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     * @Assert\NotBlank(message="Opis nie może być pusty.")
     * @Assert\Length(
     *     min=20,
     *     minMessage="Opis nie może być krótszy niż 20 znaków.",
     * )
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="decimal", precision=10, scale=2)
     * @Assert\NotBlank(message="Cena nie może być pusta.")
     * @Assert\GreaterThan(
     *      value="0",
     *      message="Cena musi być większa od 0."
     * )
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="starting_price", type="decimal", precision=10, scale=2)
     * @Assert\NotBlank(message="Cena startowa nie może być pusta.")
     * @Assert\GreaterThan(
     *      value="0",
     *      message="Cena startowa musi być większa od 0."
     * )
     */
    private $startingPrice;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    private $updatedAt;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires_at", type="datetime")
     * @Assert\NotBlank(message="Data aukcji musi być określona.")
     * @Assert\GreaterThan(
     *      value="+1 day",
     *      message="Aukcja nie może kończyć się za mniej niż 24h."
     * )
     */
    private $expiresAt;


    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=12)
     */
    private $status;

    /**
     * @var Offer[]
     *
     * @ORM\OneToMany(targetEntity="Offer", mappedBy="auction")
     */
    private $offers;


    /**
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="auctions")
     */
    private $owner;
    // w bazie pojawi sie kolumna owner_id , skąd taka nazwa? wiem, że człon
    // pierwszy jest z nazwy właściwości $owner, ale skad doctrine wie
    // aby dodać tam też _id

    /**
     * inicjalizacja dla Doctrina pola offers specjalnym typem, po co się to robi?
     *
     * Auction constructor.
     */

    public function __construct()
    {
        $this->offers = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Auction
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Auction
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set price
     *
     * @param string $price
     *
     * @return Auction
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set price
     *
     * @param float $startingPrice
     *
     * @return Auction
     */
    public function setStartingPrice($startingPrice)
    {
        $this->startingPrice = $startingPrice;

        return $this;
    }

    /**
     * Get price
     *
     * @return string
     */
    public function getStartingPrice()
    {
        return $this->startingPrice;
    }


    /**
     * @param  \Datetime $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }


    /**
     * @param  \Datetime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get $updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }


    /**
     * @param  \DateTime $expiresAt
     * @return $this
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Get $expiresAt
     *
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Auction
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return Offer[]|ArrayCollection
     */
    public function getOffers(){
        return $this->offers;
    }

    /**
     * @param Offer $offer
     * @return $this
     */
    public function addOffer(Offer $offer){
        $this->offers[] = $offer;

        return $this;
    }

    /**
     * @param User $owner
     * @return $this
     */
    public function setOwner(User $owner){
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwner(){
        return $this->owner;
    }
}

