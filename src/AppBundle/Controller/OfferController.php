<?php


namespace AppBundle\Controller;

use AppBundle\Entity\Auction;
use AppBundle\Entity\Offer;
use AppBundle\Form\BidType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OfferController extends Controller
{

    /**
     * @Route("/auction/buy/{id}", name="offer_buy", methods={"POST"})
     * @param Auction $auction
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function buyAction(Auction $auction){
        $this->denyAccessUnlessGranted("ROLE_USER");


        $offer = new Offer();

        $offer
            ->setAuction($auction)
            ->setType(Offer::TYPE_BUY)
            ->setPrice($auction->getPrice());

        $auction
            ->setStatus(Auction::STATUS_FINISHED);

        $em = $this->getDoctrine()->getManager();

        $em->persist($auction);
        $em->persist($offer);

        $em->flush();

        $this->addFlash("success", "Gratulacje! Kupiłeś przedmiot {$auction->getTitle()} za kwotę {$auction->getPrice()}");


        return $this->redirectToRoute("auction_once", ["id" => $auction->getId()]);
    }


    /**
     * @Route("/auction/bid/{id}", name="offer_bid", methods={"POST"})
     * @param Auction $auction
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function bidAction(Auction $auction, Request $request){
        $this->denyAccessUnlessGranted("ROLE_USER");

        $offer = new Offer();
        $bidForm = $this->createForm(BidType::class, $offer);

        $bidForm->handleRequest($request);


        if($bidForm->isValid()){
            $em = $this->getDoctrine()->getManager();
            $lastOffer = $em->getRepository(Offer::class)
                ->findOneBy(["auction" => $auction], ["createdAt" => "DESC"]);

            if(isset($lastOffer) && ($offer->getPrice() <= $lastOffer->getPrice())){
                $this->addFlash("error", "Proponowana cena jest mniejsza niż ostatnia złożona oferta.");
                return $this->redirectToRoute("auction_once", ["id" => $auction->getId()]);
            }
            else{
                if($offer->getPrice() < $auction->getStartingPrice()){
                    $this->addFlash("error", "Twoja oferta nie może być niższa od ceny wywoławczej.");
                    return $this->redirectToRoute("auction_once", ["id" => $auction->getId()]);

                }
            }


            $offer
                ->setType(Offer::TYPE_BID)
                ->setOwner($this->getUser())
                ->setAuction($auction);


            $em->persist($offer);
            $em->flush();

            $this->addFlash("success", "Oferta została poprawnie dodana.");
        }else{
            $this->addFlash("error", "Oferta nie została złożona z powodu błędu..");

        }
        return $this->redirectToRoute("auction_once", ["id" => $auction->getId()]);
    }
}