<?php


namespace AppBundle\Controller;


use AppBundle\Entity\Auction;
use AppBundle\Form\AuctionType;
use AppBundle\Form\DeleteAuctionType;
use AppBundle\Form\FinishAuctionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\Routing\Annotation\Route;

class MyAuctionController extends Controller
{

    private $entityManager;

    /**
     * MyAuctionController constructor.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * @Route("/myauction/list", name="myauction_index")
     * @return Response
     */
    public function indexAction()
    {
        $auctions = $this->entityManager->getRepository(Auction::class)
            ->findMyOrdered($this->getUser());

        return $this->render(
            "MyAuction/list.twig",
            ["auctions" => $auctions]
        );
    }


    /**
     * @Route("/myauction/once/{id}", name="myauction_once")
     * @param Auction $auction
     * @return ResponseAlias
     */
    public function onceAction(/*$id*/ Auction $auction)
    {

        if ($auction->getStatus() == Auction::STATUS_FINISHED) {
            return $this->render(
                'Auction/once_finished.twig',
                ['auction' => $auction,]
            );
        }

        $deleteForm = $this->createForm(
            DeleteAuctionType::class,
            null,
            [
                'action' => $this->generateUrl(
                    "myauction_delete",
                    ["id" => $auction->getId()]
                ),
            ]
        );

        $finishForm = $this->createForm(
            FinishAuctionType::class,
            null,
            [
                'action' => $this->generateUrl(
                    "myauction_finish",
                    ["id" => $auction->getId()]
                ),
            ]
        );



        return $this->render(
            'MyAuction/once.twig', [
            'auction' => $auction,
            'deleteForm' => $deleteForm->createView(),
            'finishForm' => $finishForm->createView(),

            'offers' => $auction->getOffers(),
            ]
        );
    }


    /**
     * @Route("/myauction/edit/{id}", name="myauction_edit")
     * @param Request $request
     * @param Auction $auction
     * @return ResponseAlias
     */
    public function editAction(Request $request, Auction $auction)
    {

        if ($auction->getOwner() !== $this->getUser()) {
            $this->addFlash("error", "Tylko właściciel może edytować aukcje.");
            return $this->redirectToRoute(
                "myauction_once",
                ["id" => $auction->getId()]
            );
        }

        $form = $this->createForm(AuctionType::class, $auction);

        if ($request->isMethod("post")) {
            $form->handleRequest($request);

            $this->entityManager->persist($auction);
            $this->entityManager->flush();

            $this->addFlash(
                "success",
                "Aukcja {$auction->getTitle()} została poprawnie zaktualizowana."
            );


            return $this->redirectToRoute(
                "myauction_once", ["id" => $auction->getId()]
            );
        }

        return $this->render("MyAuction/edit.twig", ['form' => $form->createView()]);
    }


    /**
     * @Route("/myauction/delete/{id}", name="myauction_delete", methods={"DELETE"})
     * @param Auction $auction
     */
    public function deleteAction(Auction $auction)
    {
        if ($auction->getOwner() !== $this->getUser()) {
            $this->addFlash(
                "error",
                "Tylko właściciel może edytować aukcje."
            );
            return $this->redirectToRoute(
                "auction_once", ["id" => $auction->getId()]
            );
        }

        $this->entityManager->remove($auction);
        $this->entityManager->flush();

        $this->addFlash(
            "success",
            "Aukcja {$auction->getTitle()} została poprawnie usunięta."
        );


        return $this->redirectToRoute("myauction_index");
    }


    /**
     * @Route("/myauction/finish/{id}", name="myauction_finish", methods={"POST"})
     * @param Auction $auction
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */

    public function finishAction(Auction $auction)
    {
        if ($auction->getOwner() !== $this->getUser()) {
            $this->addFlash(
                "error", "Tylko właściciel może edytować aukcje."
            );

            return $this->redirectToRoute(
                "auction_once", ["id" => $auction->getId()]
            );
        }

        $auction
            ->setExpiresAt(new \DateTime())
            ->setStatus(Auction::STATUS_FINISHED);

        $this->entityManager->persist($auction);
        $this->entityManager->flush();

        $this->addFlash(
            "success",
            "Aukcja {$auction->getTitle()} została zakończona."
        );

        return $this->redirectToRoute(
            "myauction_once", ["id" => $auction->getId()]
        );
    }
}