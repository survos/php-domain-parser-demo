<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pdp\Rules;
use Pdp\Domain;


class AppController extends AbstractController
{
    #[Route('/', name: 'app_app')]
    public function index(): Response
    {

        $publicSuffixList = Rules::fromPath(__DIR__ . '/../../suffix.dat');
        $domain = Domain::fromIDNA2008('www.PreF.OkiNawA.jP');
        $url = '//www.PreF.OkiNawA.jP/ayx';
        dd(parse_url($url));
        $domain = Domain::fromIDNA2008();

        $result = $publicSuffixList->resolve($domain);
        echo $result->domain()->toString();            //display 'www.pref.okinawa.jp';
        echo $result->subDomain()->toString();         //display 'www';
        echo $result->secondLevelDomain()->toString(); //display 'pref';
        echo $result->registrableDomain()->toString(); //display 'pref.okinawa.jp';
        echo $result->suffix()->toString();            //display 'okinawa.jp';
        $result->suffix()->isICANN();                  //return true;
        return $this->render('app/index.html.twig', [
            'controller_name' => 'AppController',
            'result' => $result
        ]);
    }
}
