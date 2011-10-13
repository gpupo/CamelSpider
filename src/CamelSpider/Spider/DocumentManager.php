<?php

namespace CamelSpider\Spider;

use CamelSpider\Entity\InterfaceSubscription,
    CamelSpider\Entity\InterfaceLink;


/**
 * Especializado em consultas aos documentos,
 * em seus diversos lugares
 */

class DocumentManager
{
    /**
     * Verifica se o documento é novo
     * @todo implementar
     */
    public static function isFresh(string $body, InterfaceLink $link, InterfaceSubscription $subscription)
    {

        if($existent = $subscription->getLink($this->getId()))
        {
            if(
                SpiderText::diffPercentage(
                    $existent,
                    $this->getBody()
                )
                < $this->getConfig('requirement_diff', 40)
            ){
                return false;
            }
        }
        return true;
    }

}
