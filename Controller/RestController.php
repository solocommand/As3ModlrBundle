<?php

namespace As3\Bundle\ModlrBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The REST API controller for handling all Modlr requests.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class RestController extends Controller
{
    /**
     * Handles all RESTful API requests.
     *
     * @param   Request     $request
     * @return  Response
     */
    public function indexAction(Request $request)
    {
        return $this->get('as3_modlr.rest.kernel')->handle($request);
    }
}
