<?php


namespace AppBundle\Factory;


use AppBundle\Entity\Offer;

class OfferStaticFactory
{

    /**
     * @return Offer
     */
    public static function createAuction()
    {
        return new Offer();
    }

}