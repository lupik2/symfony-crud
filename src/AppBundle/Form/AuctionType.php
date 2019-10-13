<?php

namespace AppBundle\Form;

//Formularze w symfony nazywamy tak aby kończyły się słówkiem Type
use AppBundle\Entity\Auction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuctionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $today = new \DateTime();
        // form builder udostępniany jest przez klasę bazową Controller
        $builder
            /*warto oznaczać typy pól, symfony może zrobić to samo ale jest to dość cięzka operacja, która przy większych aplikacjach ma znaczenia w odniesniu do czasu.         */
            ->add('title', TextType::class, ["label" => "Tytuł ogłoszenia"])
            ->add('description', TextareaType::class, ["label" => "Opis ogłoszenia"])
            ->add('price', NumberType::class, ["label" => "Cena"])
            ->add('startingPrice', NumberType::class, ["label" => "Cena początkowa"])
            ->add("expiresAt", DateTimeType::class, ["label" => "Data zakończenia", "data" => $today->modify("+1 day, 1 hour")])
            ->add('submit', SubmitType::class, ["label" => "Dodaj", 'attr' => ['class' => 'btn-primary']]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // wskazujemy którą ENCJĘ obsługuje formularz. Dzięki temu nie musimy już w klasie wykorzystującej ten formularz przypisywać danych z formularza do obiektu encji ponieważ zrobi to za nas Symfony już na metodzie handleRequest
        $resolver->setDefaults([
            'data_class' => Auction::class
        ]);
    }
}