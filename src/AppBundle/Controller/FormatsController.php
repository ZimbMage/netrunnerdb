<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Mwl;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FormatsController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('AppBundle:Mwl');

        /** @var Mwl|null $activeMwl */
        $activeMwl = $repo->findOneBy(['active' => true]);
        if (!$activeMwl) {
            // Fall back to the most recent ban list if none is flagged active yet.
            $activeMwl = $repo->findOneBy([], ['dateStart' => 'DESC']);
        }

        $bannedCount = $activeMwl ? count($activeMwl->getBannedCardCodes()) : 0;

        return $this->render('/Formats/formats.html.twig', [
            'pagetitle'       => "Play Format",
            'pagedescription' => "SoCal Eternal: the SanSan South play format — Eternal with the strongest cards banned.",
            'activeMwl'       => $activeMwl,
            'bannedCount'     => $bannedCount,
        ]);
    }
}
