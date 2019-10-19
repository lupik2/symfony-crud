<?php


namespace AppBundle\Factory;


use AppBundle\Entity\Auction;

class AuctionStaticFactory
{

    /**
     * @return Auction
     */
    public static function createAuction()
    {
        return new Auction();
    }

}