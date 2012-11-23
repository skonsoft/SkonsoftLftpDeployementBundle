<?php

namespace Skonsoft\Bundle\LftpDeployementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('SkonsoftLftpDeployementBundle:Default:index.html.twig', array('name' => $name));
    }
}
