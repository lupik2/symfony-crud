<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Auction;
use AppBundle\Form\AuctionType;
use AppBundle\Form\BidType;
use AppBundle\Factory\AuctionStaticFactory;

use AppBundle\Form\BuyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class AuctionController extends Controller
{

    private $entityManager;


    /**
     * AuctionController constructor.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }


    /**
     * @Route("/", name="auction_index")
     * @return ResponseAlias
     */
    public function indexAction()
    {
        $auctions = $this->entityManager->getRepository(Auction::class)
            ->findActiveOrdered();

        return $this->render(
            'Auction/index.twig',
            ['auctions' => $auctions]
        );
    }

    // nie można zrobić route /auction/{id} - dlaczego?
    /**
     * @Route("/auction/once/{id}", name="auction_once")
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


        // Param converter - porównuje argument z Route do klucza głównego
        // z Encji wskazanej w argumencie akcji (Auction), jeżeli pasuje i
        // istnieje w bazie, pobiera dane i przekształca je na obiekt.
        // Sprawia to, że entity manager i wywołania na nim są niepotrzebne
        // ponieważ cała ta operacja wykona się 'w tle',
        // a kodu w akcji będzie jeszcze mniej.



        $buyForm = $this->createForm(
            BuyType::class,
            null,
            [
                'action' =>
                    $this->generateUrl("offer_buy", ["id" => $auction->getId()])
            ]
        );


        $bidForm = $this->createForm(
            BidType::class,
            null,
            [
                "action" =>
                    $this->generateUrl("offer_bid", ["id" => $auction->getId()])
            ]
        );


        return $this->render(
            'Auction/once.twig',
            [
            'auction' => $auction,
            'buyForm' => $buyForm->createView(),
            'bidForm' => $bidForm->createView(),

            'offers' => $auction->getOffers(),
            ]
        );
    }

    /**
     * @Route("/auction/add", name="auction_add")
     * @var Request
     * @return ResponseAlias
     */
    public function addAction(Request $request)
    {
        $auction = AuctionStaticFactory::createAuction();

        // tworzy nam formularz na podstawie klasy formularza (arg1),
        // obsługujący encje $auction (arg2)
        $form = $this->createForm(AuctionType::class, $auction);


        // Za pomocą poniższej metody wszystko co przyszło do fornularza z Request'a i pasuje do wzorca (POLA) wstawione będzie do formularza i wyświetlona w nim po odświeżeniu strony (Wypełnione zostanie pole VALUE w inpucie)
        $form->handleRequest($request);

        if ($auction->getStartingPrice() >= $auction->getPrice()) {
            $form->get("startingPrice")
                ->addError(
                    new FormError("Cena wywoławcza musi być niższa niż cena zakupu.")
                );
        }

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $auction
                    ->setStatus(Auction::STATUS_ACTIVE)
                    ->setOwner($this->getUser());


                $this->entityManager->persist($auction);
                $this->entityManager->flush();

                $this->addFlash("success", "Aukcja została dodana do systemu.");

                return $this->redirectToRoute(
                    "auction_once", ["id" => $auction->getId()]
                );
            }

            $this->addFlash("error", "Nie udało się dodać aukcji.");
        }



/*         nie ma sensu przekazywania całego obiektu FORM, ponieważ jest on zbyt duży i zawiera zbędne informacje. Metoda CreateView tworzy uproszczony obiekt ormualrza do wyświetlenia*/
        return $this->render("Auction/add.twig", ['form' => $form->createView()]);
    }

}