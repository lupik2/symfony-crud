<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Auction;
use AppBundle\Form\AuctionType;
use AppBundle\Form\BidType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AuctionController extends Controller
{

    /**
     * @Route("/", name="auction_index")
     * @return ResponseAlias
     */
    public function indexAction(){
        $em = $this->getDoctrine()->getManager();
        $auctions = $em->getRepository(Auction::class)->findActiveOrdered();

        return $this->render('Auction/index.twig', [
            'auctions' => $auctions
        ]);
    }

    // nie można zrobić route /auction/{id} - dlaczego?
    /**
     * @Route("/auction/once/{id}", name="auction_once")
     * @param Auction $auction
     * @return ResponseAlias
     */
    public function onceAction(/*$id*/ Auction $auction){ // uproszczenie param converter

        if($auction->getStatus() == Auction::STATUS_FINISHED){
            return $this->render('Auction/once_finished.twig', [
                'auction' => $auction,
            ]);
        }


        // Param converter - porównuje argument z Route do klucza głównego z Encji wskazanej w argumencie akcji (Auction), jeżeli pasuje i istnieje w bazie, pobiera dane i przekształca je na obiekt. Sprawia to, że entity manager i wywołania na nim są niepotrzebne ponieważ cała ta operacja wykona się 'w tle', a kodu w akcji będzie jeszcze mniej.

/*        $em = $this->getDoctrine()->getManager();
        $auction = $em->getRepository(Auction::class)->findOneBy(["id" => $id]);*/


        $buyForm = $this->createFormBuilder()
            ->setAction($this->generateUrl("offer_buy", ["id" => $auction->getId()]))
            ->setMethod(Request::METHOD_POST)
            ->add("submit", SubmitType::class, ["label" => "Kup"])
            ->getForm();


        $bidForm = $this->createForm(
            BidType::class,
            null,
            ["action" => $this->generateUrl("offer_bid", ["id" => $auction->getId()])]
        );


        return $this->render('Auction/once.twig', [
            'auction' => $auction,
            'buyForm' => $buyForm->createView(),
            'bidForm' => $bidForm->createView(),

            'offers' => $auction->getOffers(),
        ]);
    }

    /**
     * @Route("/auction/add", name="auction_add")
     * @var Request
     * @return ResponseAlias
     */
    public function addAction(Request $request){
        // sprawdzenia czy użytkownik jest zalogowany
        // 1. $this->getUser() instanceof User
        // 2. Votery - sprawdza czy dany uzytkownik ma daną rolę $this->isGranted("ROLE_USER")
        // 3. $this->denyAccessUnlessGranted() - jeżeli nie posiada takiej roli przekierowuje na strone logowania

        $this->denyAccessUnlessGranted("ROLE_USER");

        //request ma wszystkie dane przychodzące z protokołu http/s
        $auction = new Auction();

        // tworzy nam formularz na podstawie klasy formularza (arg1), obsługujący encje $auction (arg2)
        $form = $this->createForm(AuctionType::class, $auction);
        // createForm pobiera nam sam wcześniej utworzony (w tej klasie) formularz
        // createFormBuilder pozwala nam na wygenerowanie całego formularza z poziomu kontrolera (gorsze rozwiązanie)

        if($request->isMethod('POST')){
            // Za pomocą poniższej metody wszystko co przyszło do fornularza z Request'a i pasuje do wzorca (POLA) wstawione będzie do formularza i wyświetlona w nim po odświeżeniu strony (Wypełnione zostanie pole VALUE w inpucie)
            $form->handleRequest($request);

            if($auction->getStartingPrice() >= $auction->getPrice()){
                $form->get("startingPrice")->addError(new FormError("Cena wywoławcza musi być niższa niż cena zakupu."));
            }

            if($form->isValid()){
               $auction
                   ->setStatus(Auction::STATUS_ACTIVE)
                   ->setOwner($this->getUser());


                $em = $this->getDoctrine()->getManager();
                $em->persist($auction); // przygotowuje dane do zapisu, dzięki przygotowaniu zapytania chronieni jesteśmy przed atakiem typu sql injection(?)
                $em->flush(); //wykonuje przygotowane wcześniej zapytania, dzięki temu otiwerane jest jedno połączenie do bazy zamiast kilku(?)

                $this->addFlash("success", "Aukcja została dodana do systemu.");

                return $this->redirectToRoute("auction_once", ["id" => $auction->getId()]);
            }

            $this->addFlash("error", "Nie udało się dodać aukcji.");
        }

/*         nie ma sensu przekazywania całego obiektu FORM, ponieważ jest on zbyt duży i zawiera zbędne informacje. Metoda CreateView tworzy uproszczony obiekt ormualrza do wyświetlenia*/
        return $this->render("Auction/add.twig", ['form' => $form->createView()]);
    }

}