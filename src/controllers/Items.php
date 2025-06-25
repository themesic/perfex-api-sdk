<?php

namespace PerfexApiSdk\Controllers;

use PerfexApiSdk\Controllers\REST_Controller;

use PerfexApiSdk\Models\Invoice_items_model;
use PerfexApiSdk\Models\Custom_fields_model;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Items extends REST_Controller {
    private $invoice_items_model;
    private $custom_fields_model;

    function __construct() {
        // Construct the parent class
        parent::__construct();

        $this->invoice_items_model = new Invoice_items_model();
        $this->custom_fields_model = new Custom_fields_model();
    }

    /**
     * @api {get} api/items/:id Request Invoice Item's information
     * @apiVersion 0.1.0
     * @apiName GetItem
     * @apiGroup Items
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiSuccess {Object} Item item information.
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *	      "itemid": "1",
     *        "rate": "100.00",
     *        "taxrate": "5.00",
     *        "taxid": "1",
     *        "taxname": "PAYPAL",
     *        "taxrate_2": "9.00",
     *        "taxid_2": "2",
     *        "taxname_2": "CGST",
     *        "description": "JBL Soundbar",
     *        "long_description": "The JBL Cinema SB110 is a hassle-free soundbar",
     *        "group_id": "0",
     *        "group_name": null,
     *        "unit": ""
     *     }
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_get($id = '') {
        // If the id parameter doesn't exist return all the
        $data = $this->invoice_items_model->get($id, $this->playground());
        // Check if the data store contains
        if ($data) {
            $data = $this->custom_fields_model->get_custom_data($data, "items", $id, false, $this->playground());
            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code            
        } else {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * @api {get} api/items/search/:keysearch Search Invoice Item's information
     * @apiVersion 0.1.0
     * @apiName GetItemSearch
     * @apiGroup Items
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} keysearch Search Keywords
     *
     * @apiSuccess {Object} Item  Item Information
     *
     * @apiSuccessExample Success-Response:
     *	HTTP/1.1 200 OK
     *	{
     *	  "rate": "100.00",
     *	  "id": "1",
     *	  "name": "(100.00) JBL Soundbar",
     *	  "subtext": "The JBL Cinema SB110 is a hassle-free soundbar..."
     *	}
     *
     * @apiError {Boolean} status Request status
     * @apiError {String} message No data were found
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_search_get($key = '') {
        $data = $this->custom_fields_model->get_relation_data_api('invoice_items', $key, $this->playground());
        // Check if the data store contains
        if ($data) {
            $data = $this->custom_fields_model->get_custom_data($data, "items", "", false, $this->playground());
            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code            
        } else {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }
}