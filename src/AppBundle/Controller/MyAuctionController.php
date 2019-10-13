<?php


namespace AppBundle\Controller;


use AppBundle\Entity\Auction;
use AppBundle\Form\AuctionType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\Routing\Annotation\Route;

class MyAuctionController extends Controller
{

    /**
     * @Route("/myauction/list", name="myauction_index")
     * @return Response
     */
    public function indexAction(){
        $this->denyAccessUnlessGranted("ROLE_USER");

        $em = $this->getDoctrine()->getManager();

        $auctions = $em->getRepository(Auction::class)
            ->findMyOrdered($this->getUser());

        return $this->render("MyAuction/list.twig",[
            "auctions" => $auctions
        ]);
    }


    /**
     * @Route("/myauction/once/{id}", name="myauction_once")
     * @param Auction $auction
     * @return ResponseAlias
     */
    public function onceAction(/*$id*/ Auction $auction){ // uproszczenie param converter

        if($auction->getStatus() == Auction::STATUS_FINISHED){
            return $this->render('Auction/once_finished.twig', [
                'auction' => $auction,
            ]);
        }

        $deleteForm = $this->createFormBuilder()
            ->setAction($this->generateUrl("myauction_delete", ["id" => $auction->getId()]))
            ->setMethod(Request::METHOD_DELETE)
            ->add("submit", SubmitType::class, ["label" => "Usuń"])
            ->getForm();

        $finishForm = $this->createFormBuilder()
            ->setAction($this->generateUrl("myauction_finish", ["id" => $auction->getId()]))
            ->setMethod(Request::METHOD_POST)
            ->add("submit", SubmitType::class, ["label" => "Zakończ"])
            ->getForm();


        return $this->render('MyAuction/once.twig', [
            'auction' => $auction,
            'deleteForm' => $deleteForm->createView(),
            'finishForm' => $finishForm->createView(),

            'offers' => $auction->getOffers(),
        ]);
    }


    /**
     * @Route("/myauction/edit/{id}", name="myauction_edit")
     * @param Request $request
     * @param Auction $auction
     * @return ResponseAlias
     */
    public function editAction(Request $request, Auction $auction){
        $this->denyAccessUnlessGranted("ROLE_USER");

        if($auction->getOwner() !== $this->getUser()){
            $this->addFlash("error","Tylko właściciel może edytować aukcje.");
            return $this->redirectToRoute("myauction_once", ["id" => $auction->getId()]);
        }

        $form = $this->createForm(AuctionType::class, $auction);

        if($request->isMethod("post")){
            $form->handleRequest($request);

            $em = $this->getDoctrine()->getManager();
            $em->persist($auction);
            $em->flush();

            $this->addFlash("success", "Aukcja {$auction->getTitle()} została poprawnie zaktualizowana.");


            return $this->redirectToRoute("myauction_once", ["id" => $auction->getId()]);
        }

        return $this->render("MyAuction/edit.twig", ['form' => $form->createView()]);
    }
    /*

     Sposób ten usuwa aukcje za pomocą zwyklego linku url - podobno lepiej przy tego typu akcjach korzystać z formularzy
        /**
         * @Route("/auction/delete/{id}", name="auction_delete")
         * @param Auction $auction
         *./
        public function deleteAction(Auction $auction){
            $em = $this->getDoctrine()->getManager();
            $em->remove($auction);
            $em->flush();

            return $this->redirectToRoute("auction_index");
        }
    */

    /**
     * @Route("/myauction/delete/{id}", name="myauction_delete", methods={"DELETE"})
     * @param Auction $auction
     */
    public function deleteAction(Auction $auction){
        if($auction->getOwner() !== $this->getUser()){
            $this->addFlash("error","Tylko właściciel może edytować aukcje.");
            return $this->redirectToRoute("auction_once", ["id" => $auction->getId()]);
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($auction);
        $em->flush();


        $this->addFlash("success", "Aukcja {$auction->getTitle()} została poprawnie usunięta.");


        return $this->redirectToRoute("myauction_index");
    }


    /**
     * @Route("/myauction/finish/{id}", name="myauction_finish", methods={"POST"})
     * @param Auction $auction
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function finishAction(Auction $auction){
        if($auction->getOwner() !== $this->getUser()){
            $this->addFlash("error","Tylko właściciel może edytować aukcje.");
            return $this->redirectToRoute("auction_once", ["id" => $auction->getId()]);
        }

        $em = $this->getDoctrine()->getManager();

        $auction
            ->setExpiresAt(new \DateTime())
            ->setStatus(Auction::STATUS_FINISHED);

        $em->persist($auction);
        $em->flush();

        $this->addFlash("success", "Aukcja {$auction->getTitle()} została zakończona.");

        return $this->redirectToRoute("myauction_once", ["id" => $auction->getId()]);
    }
}