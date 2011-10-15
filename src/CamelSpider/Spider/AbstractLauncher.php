<?php

/*
* This file is part of the CamelSpider package.
*
* (c) Gilmar Pupo <g@g1mr.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace CamelSpider\Spider;

use CamelSpider\Entity\AbstractSpiderEgg,
    CamelSpider\Spider\Indexer,
    CamelSpider\Entity\FactorySubscription,
    CamelSpider\Spider\Pool;

/**
 * Aciona indexação para cada assinatura
 */
class AbstractLauncher extends AbstractSpiderEgg
{
    protected $name = 'launcher';

    protected $indexer; 

    public function __construct(Indexer $indexer, $logger)
    {
        $this->indexer = $indexer;
        $this->logger = $logger;
    }

    protected function getSampleSubscriptions()
    {
        return FactorySubscription::buildCollectionFromDomain(
            array(
                //'economia.estadao.com.br', 
                'noticias.terra.com.br'
                //,'www.uol.com.br'
            )
        );
    }

    protected function doSave(Pool $pool)
    {
    }


    public function run($subscriptionCollection = null)
    {
        if(!$subscriptionCollection)
        {
            //Tests only.
            $this->logger('Using subscriptions samples', 'err');
            $subscriptionCollection = $this->getSampleSubscriptions();
        }

        foreach($subscriptionCollection as $subscription)
        {
            $this->logger(
                'Checking updates fo the subscription '
                . $subscription->getDomainString()
            );
            try{
                $this->doSave($this->indexer->run($subscription));
            }
            catch (\Exception $e)
            {
                $this->logger($e->getMessage(), 'err');
            }
        }
    }

}

