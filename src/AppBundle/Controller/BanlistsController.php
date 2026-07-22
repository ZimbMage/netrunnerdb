<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Card;
use AppBundle\Entity\Mwl;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class BanlistsController extends Controller
{
    /**
     * @param Request                $request
     * @param EntityManagerInterface $entityManager
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(Request $request, EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('AppBundle:Mwl');

        /** @var Mwl|null $activeMwl */
        $activeMwl = $repo->findOneBy(['active' => true]);
        if (!$activeMwl) {
            // Fall back to the most recent ban list if none is flagged active yet.
            $activeMwl = $repo->findOneBy([], ['dateStart' => 'DESC']);
        }

        $corp = [];
        $runner = [];

        if ($activeMwl) {
            $bannedCodes = $activeMwl->getBannedCardCodes();
            if (count($bannedCodes)) {
                /** @var Card[] $cards */
                $cards = $entityManager->getRepository('AppBundle:Card')
                    ->createQueryBuilder('c')
                    ->where('c.code IN (:codes)')
                    ->setParameter('codes', $bannedCodes)
                    ->getQuery()
                    ->getResult();

                // De-dupe by title (a card can have several printings) and bucket by side.
                $seen = [];
                foreach ($cards as $card) {
                    $title = $card->getTitle();
                    if (isset($seen[$title])) {
                        continue;
                    }
                    $seen[$title] = true;
                    $entry = ['title' => $title, 'code' => $card->getCode()];
                    if ($card->getSide() && $card->getSide()->getCode() === 'corp') {
                        $corp[] = $entry;
                    } else {
                        $runner[] = $entry;
                    }
                }

                $byTitle = function ($a, $b) {
                    return strcasecmp($a['title'], $b['title']);
                };
                usort($corp, $byTitle);
                usort($runner, $byTitle);
            }
        }

        return $this->render('/Banlists/banlists.html.twig', [
            'pagetitle'       => "Ban List",
            'pagedescription' => "The SoCal Ban List: cards banned in SanSan South's SoCal Eternal format.",
            'activeMwl'       => $activeMwl,
            'corpCards'       => $corp,
            'runnerCards'     => $runner,
        ]);
    }
}
