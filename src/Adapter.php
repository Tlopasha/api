<?php


/**
 * Part of the Antares Project package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Api
 * @version    0.9.0
 * @author     Antares Team
 * @license    BSD License (3-clause)
 * @copyright  (c) 2017, Antares Project
 * @link       http://antaresproject.io
 */




namespace Antares\Api;

use League\Fractal\Manager as FractalManager;
use Antares\Api\Contracts\AdapterContract;
use Illuminate\Contracts\Routing\ResponseFactory;

abstract class Adapter implements AdapterContract {
    
    /**
     *
     * @var ResponseFactory
     */
    protected $response;
    
    /**
     *
     * @var FractalManager
     */
    protected $fractalManager;
    
    /**
     * 
     * @param ResponseFactory $response
     * @param FractalManager $fractalManager
     */
    public function __construct(ResponseFactory $response, FractalManager $fractalManager) {
        $this->response         = $response;
        $this->fractalManager   = $fractalManager;
    }
    
    /**
     * 
     * @param mixed $data
     * @return array
     */
    public function transform($data) {
        return (array) $data;
    }
    
}