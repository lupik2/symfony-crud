<?php


namespace AppBundle\Controller;

use AppBundle\Entity\Auction;
use AppBundle\Entity\Offer;
use AppBundle\Factory\OfferStaticFactory;
use AppBundle\Form\BidType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OfferController extends Controller
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @Route("/auction/buy/{id}", name="offer_buy", methods={"POST"})
     * @param Auction $auction
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function buyAction(Auction $auction)
    {

        $offer = OfferStaticFactory::createAuction();

        $offer
            ->setAuction($auction)
            ->setType(Offer::TYPE_BUY)
            ->setOwner($this->getUser())
            ->setPrice($auction->getPrice());

        $auction
            ->setStatus(Auction::STATUS_FINISHED);


        $this->entityManager->persist($auction);
        $this->entityManager->persist($offer);

        $this->entityManager->flush();

        $this->addFlash(
            "success",
            "Gratulacje! Kupiłeś przedmiot 
            {$auction->getTitle()} za kwotę {$auction->getPrice()}"
        );


        return $this->redirectToRoute("auction_once", ["id" => $auction->getId()]);
    }


    /**
     * @Route("/auction/bid/{id}", name="offer_bid", methods={"POST"})
     * @param Auction $auction
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function bidAction(Auction $auction, Request $request)
    {
        $offer = OfferStaticFactory::createAuction();
        $bidForm = $this->createForm(BidType::class, $offer);

        $bidForm->handleRequest($request);

        if ($bidForm->isValid()) {
            $lastOffer = $this->entityManager->getRepository(Offer::class)
                ->findOneBy(
                    ["auction" => $auction],
                    ["createdAt" => "DESC"]
                );
            //@TODO sprawdzić czemu to działa skoro metoda przyjmuje
            // 1 argumetn a dostaje dwa



            if (isset($lastOffer)
                && ($offer->getPrice() <= $lastOffer->getPrice())
            ) {
                $this->addFlash(
                    "error",
                    "Proponowana cena jest mniejsza niż ostatnia złożona oferta."
                );

                return $this->redirectToRoute(
                    "auction_once",
                    ["id" => $auction->getId()]
                );

            } else {

                if ($offer->getPrice() < $auction->getStartingPrice()) {
                    $this->addFlash(
                        "error",
                        "Twoja oferta nie może być niższa od ceny wywoławczej."
                    );

                    return $this->redirectToRoute(
                        "auction_once",
                        ["id" => $auction->getId()]
                    );

                }

            }

            $offer
                ->setType(Offer::TYPE_BID)
                ->setOwner($this->getUser())
                ->setAuction($auction);

            $this->entityManager->persist($offer);
            $this->entityManager->flush();

            $this->addFlash(
                "success",
                "Oferta została poprawnie dodana."
            );
        } else {
            $this->addFlash(
                "error",
                "Oferta nie została złożona z powodu błędu."
            );

        }
        return $this->redirectToRoute("auction_once", ["id" => $auction->getId()]);
    }
}