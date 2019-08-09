<?php

namespace Marello\Bundle\PackingBundle\Controller;

use Marello\Bundle\PackingBundle\Entity\PackingSlip;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as Config;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PackingSlipController extends AbstractController
{
    /**
     * @Config\Route("/", name="marello_packing_packingslip_index")
     * @Config\Template
     * @AclAncestor("marello_packing_slip_view")
     */
    public function indexAction()
    {
        return ['entity_class' => 'MarelloPackingBundle:PackingSlip'];
    }

    /**
     * @Config\Route("/view/{id}", requirements={"id"="\d+"}, name="marello_packing_packingslip_view")
     * @Config\Template
     * @AclAncestor("marello_packing_slip_view")
     *
     * @param PackingSlip $packingSlip
     *
     * @return array
     */
    public function viewAction(PackingSlip $packingSlip)
    {
        return ['entity' => $packingSlip];
    }
}
