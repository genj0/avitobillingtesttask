<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

final class DefaultController extends AbstractController
{
    /**
     * @Route("/doc", name="doc")
     */
    public function index()
    {
        return $this->redirect('https://app.swaggerhub.com/apis-docs/MrSmile2114/avito-billing/');
    }
}
