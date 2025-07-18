<?php

namespace PerfexApiSdk\Controllers;

use PerfexApiSdk\Controllers\REST_Controller;

use PerfexApiSdk\Libraries\Parse_Input_Stream;

use PerfexApiSdk\Models\Leads_model;
use PerfexApiSdk\Models\Custom_fields_model;
use PerfexApiSdk\Models\Misc_model;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 */
class Leads extends REST_Controller {
    private $leads_model;
    private $misc_model;

    function __construct() {
        // Construct the parent class
        parent::__construct();
        
        $this->leads_model = new Leads_model();
        $this->misc_model = new Misc_model();
    }
	
    /**
     * @api {get} api/leads/ Request all Leads
     * @apiName GetLeads
     * @apiGroup Leads
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     *
     * @apiSuccess {Object} Lead information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "17",
     *         "hash": "c6e938f8b7a40b1bcfd98dc04f6eeee0-60d9c039da373a685fc0f74d4bfae631",
     *         "name": "Lead name",
     *         "contact": "",
     *         "title": "",
     *         "company": "Themesic Interactive",
     *         "description": "",
     *         "country": "243",
     *         "zip": null,
     *         "city": "London",
     *         "zip": "WC13KJ",
     *         "state": "London",
     *         "address": "1a The Alexander Suite Silk Point",
     *         "assigned": "5",
     *         "dateadded": "2019-07-18 08:59:28",
     *         "from_form_id": "0",
     *         "status": "0",
     *         "source": "4",
     *         ...
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
	 
    /**
     * @api {get} api/leads/:id Request Lead information
     * @apiName GetLead
     * @apiGroup Leads
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} id Lead unique ID.
     *
     * @apiSuccess {Object} Lead information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "17",
     *         "hash": "c6e938f8b7a40b1bcfd98dc04f6eeee0-60d9c039da373a685fc0f74d4bfae631",
     *         "name": "Lead name",
     *         "contact": "",
     *         "title": "",
     *         "company": "Themesic Interactive",
     *         "description": "",
     *         "country": "243",
     *         "zip": null,
     *         "city": "London",
     *         "zip": "WC13KJ",
     *         "state": "London",
     *         "address": "1a The Alexander Suite Silk Point",
     *         "assigned": "5",
     *         "dateadded": "2019-07-18 08:59:28",
     *         "from_form_id": "0",
     *         "status": "0",
     *         "source": "4",
     *         ...
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
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
        $data = $this->leads_model->get($id, [], $this->playground());
        // Check if the data store contains
        if ($data) {
            $data = $this->custom_fields_model->get_custom_data($data, "leads", $id, false, $this->playground());
            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * @api {get} api/leads/search/:keysearch Search Lead Information
     * @apiName GetLeadSearch
     * @apiGroup Leads
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} keysearch Search Keywords.
     *
     * @apiSuccess {Object} Lead information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "17",
     *         "hash": "c6e938f8b7a40b1bcfd98dc04f6eeee0-60d9c039da373a685fc0f74d4bfae631",
     *         "name": "Lead name",
     *         "contact": "",
     *         "title": "",
     *         "company": "Themesic Interactive",
     *         "description": "",
     *         "country": "243",
     *         "zip": null,
     *         "city": "London",
     *         "zip": "WC13KJ",
     *         "state": "London",
     *         "address": "1a The Alexander Suite Silk Point",
     *         "assigned": "5",
     *         "dateadded": "2019-07-18 08:59:28",
     *         "from_form_id": "0",
     *         "status": "0",
     *         "source": "4",
     *         ...
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_search_get($key = '') {
        $data = $this->custom_fields_model->get_relation_data_api('lead', $key, $this->playground());
        // Check if the data store contains
        if ($data) {
            $data = $this->custom_fields_model->get_custom_data($data, "leads", "", false, $this->playground());
            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code            
        } else {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * @api {post} api/leads Add New Lead
     * @apiName PostLead
     * @apiGroup Leads
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} source            Mandatory Lead source.
     * @apiParam {String} status            Mandatory Lead Status.
     * @apiParam {String} name              Mandatory Lead Name.
     * @apiParam {String} assigned          Mandatory Lead assigned.
     * @apiParam {String} [client_id]       Optional Lead From Customer.
     * @apiParam {String} [tags]            Optional Lead tags.
     * @apiParam {String} [contact]         Optional Lead contact.
     * @apiParam {String} [title]           Optional Position.
     * @apiParam {String} [email]           Optional Lead Email Address.
     * @apiParam {String} [website]         Optional Lead Website.
     * @apiParam {String} [phonenumber]     Optional Lead Phone.
     * @apiParam {String} [company]         Optional Lead company.
     * @apiParam {String} [address]         Optional Lead address.
     * @apiParam {String} [city]            Optional Lead City.
     * @apiParam {String} [zip]             Optional Zip code.
     * @apiParam {String} [state]           Optional Lead state.
     * @apiParam {String} [country]         Optional Lead Country.
     * @apiParam {String} [default_language]        Optional Lead Default Language.
     * @apiParam {String} [description]             Optional Lead description.
     * @apiParam {String} [custom_contact_date]     Optional Lead From Customer.
     * @apiParam {String} [contacted_today]         Optional Lead Contacted Today.
     * @apiParam {String} [is_public]               Optional Lead google sheet id.
     *
     * @apiParamExample {Multipart Form} Request-Example:
     *  array (size=20)
     *     'status' => string '2' (length=1)
     *     'source' => string '6' (length=1)
     *     'assigned' => string '1' (length=1)
     *     'client_id' => string '5' (length=1)
     *     'tags' => string '' (length=0)
     *     'name' => string 'Lead Name' (length=9)
     *     'contact' => string 'Contact A' (length=9)
     *     'title' => string 'Position A' (length=10)
     *     'email' => string 'AAA@gmail.com' (length=13)
     *     'website' => string '' (length=0)
     *     'phonenumber' => string '123456789' (length=9)
     *     'company' => string 'Themesic Interactive' (length=20)
     *     'address' => string '710-712 Cách Mạng Tháng Tám, P. 5, Q. Tân Bình' (length=33)
     *     'city' => string 'London' (length=6)
	 *     'zip' => string 'WC13KJ' (length=6)
     *     'state' => string '' (length=0)
     *     'default_language' => string 'english' (length=10)
     *     'description' => string 'Description' (length=11)
     *     'custom_contact_date' => string '' (length=0)
     *     'is_public' => string 'on' (length=2)
     *     'contacted_today' => string 'on' (length=2)
     *
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Lead add successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Lead add successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message add fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Lead add fail."
     *     }
     *
     */
    public function data_post() {        
        // form validation
        $this->form_validation->set_rules('name', 'Lead Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Lead Name'));
        $this->form_validation->set_rules('source', 'Source', 'trim|required', array('is_unique' => 'This %s already exists please enter another Lead source'));
        $this->form_validation->set_rules('status', 'Status', 'trim|required', array('is_unique' => 'This %s already exists please enter another Status'));
        $this->form_validation->set_rules('zip', 'Zip Core', 'trim', array('is_unique' => 'This %s already exists please enter another Zip code'));
        $this->form_validation->set_rules('assigned', 'Assigned', 'trim|required', array('is_unique' => 'This %s already exists please enter another Assigned'));
        if ($this->form_validation->run() == FALSE) {
            // form validation error
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $insert_data = ['name' => $this->input->post('name', TRUE), 'source' => $this->input->post('source', TRUE), 'status' => $this->input->post('status', TRUE), 'assigned' => $this->input->post('assigned', TRUE), 'tags' => $this->misc_model->value($this->input->post('tags', TRUE)), 'title' => $this->misc_model->value($this->input->post('title', TRUE)), 'email' => $this->misc_model->value($this->input->post('email', TRUE)), 'website' => $this->misc_model->value($this->input->post('website', TRUE)), 'phonenumber' => $this->misc_model->value($this->input->post('phonenumber', TRUE)), 'company' => $this->misc_model->value($this->input->post('company', TRUE)), 'address' => $this->misc_model->value($this->input->post('address', TRUE)), 'city' => $this->misc_model->value($this->input->post('city', TRUE)), 'zip' => $this->input->post('zip', TRUE), 'state' => $this->misc_model->value($this->input->post('state', TRUE)), 'default_language' => $this->misc_model->value($this->input->post('default_language', TRUE)), 'description' => $this->misc_model->value($this->input->post('description', TRUE)), 'custom_contact_date' => $this->misc_model->value($this->input->post('custom_contact_date', TRUE)), 'is_public' => $this->misc_model->value($this->input->post('is_public', TRUE)), 'contacted_today' => $this->misc_model->value($this->input->post('contacted_today', TRUE)) ];
            if (!empty($this->input->post('custom_fields', TRUE))) {
                $insert_data['custom_fields'] = $this->misc_model->value($this->input->post('custom_fields', TRUE));
            }
            // insert data
            $output = $this->leads_model->add($insert_data);
            if ($output > 0 && !empty($output)) {
                // success
                $this->handle_lead_attachments_array($output, 'file', $this->playground());
                $message = array('status' => TRUE, 'message' => 'Lead add successful.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Lead add fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {delete} api/delete/leads/:id Delete a Lead
     * @apiName DeleteLead
     * @apiGroup Leads
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} id lead unique ID.
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Lead Delete Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Lead Delete Successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Lead Delete Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Lead Delete Fail."
     *     }
     */
    public function data_delete($id = '') {
        $id = $this->security->xss_clean($id);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Lead ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            $output = $this->leads_model->delete($id);
            if ($output === TRUE) {
                // success
                $message = array('status' => TRUE, 'message' => 'Lead Delete Successful.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Lead Delete Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {put} api/leads/:id Update a lead
     * @apiName PutLead
     * @apiGroup Leads
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} source            Mandatory Lead source.
     * @apiParam {String} status            Mandatory Lead Status.
     * @apiParam {String} name              Mandatory Lead Name.
     * @apiParam {String} assigned          Mandatory Lead assigned.
     * @apiParam {String} [client_id]       Optional Lead From Customer.
     * @apiParam {String} [tags]            Optional Lead tags.
     * @apiParam {String} [contact]         Optional Lead contact.
     * @apiParam {String} [title]           Optional Position.
     * @apiParam {String} [email]           Optional Lead Email Address.
     * @apiParam {String} [website]         Optional Lead Website.
     * @apiParam {String} [phonenumber]     Optional Lead Phone.
     * @apiParam {String} [company]         Optional Lead company.
     * @apiParam {String} [address]         Optional Lead address.
     * @apiParam {String} [city]            Optional Lead City.
	 * @apiParam {String} [zip]             Optional Zip Code.
     * @apiParam {String} [state]           Optional Lead state.
     * @apiParam {String} [country]         Optional Lead Country.
     * @apiParam {String} [default_language]        Optional Lead Default Language.
     * @apiParam {String} [description]             Optional Lead description.
     * @apiParam {String} [lastcontact]             Optional Lead Last Contact.
     * @apiParam {String} [is_public]               Optional Lead google sheet id.
     *
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *       "name": "Lead name",
     *       "contact": "contact",
     *       "title": "title",
     *       "company": "C.TY TNHH TM VẬN TẢI & DU LỊCH ĐẠI BẢO AN",
     *       "description": "description",
     *       "tags": "",
     *       "city": "London",
     *       "zip": "WC13KJ",
     *       "state": "London",
     *       "address": "1a The Alexander Suite Silk Point",
     *       "assigned": "5",
     *       "source": "4",
     *       "email": "AA@gmail.com",
     *       "website": "www.themesic.com",
     *       "phonenumber": "123456789",
     *       "is_public": "on",
     *       "default_language": "english",
     *       "client_id": "3",
     *       "lastcontact": "25/07/2019 08:38:04"
     *   }
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Lead Update Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Lead Update Successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Lead Update Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Lead Update Fail."
     *     }
     */
    public function data_put($id = '') {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($_POST) || !isset($_POST)) {
            $parse_input_stream = new Parse_Input_Stream();
            $_POST = $parse_input_stream->parse_parameters();
            $_FILES = $parse_input_stream->parse_files();
            if (empty($_POST) || !isset($_POST)) {
                $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
                $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
            }
        }
        $this->form_validation->set_data($_POST);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Lead ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $update_data = $this->input->post();
            $update_file = isset($update_data['file']) ? $update_data['file'] : null;
            unset($update_data['file']);
            // update data
            $output = $this->leads_model->update($update_data, $id);
            if (!empty($update_file) && count($update_file)) {
                if ($output <= 0 || empty($output)) {
                    $output = $id;
                }
            }
            
            if ($output > 0 && !empty($output)) {
                // success
                $attachments = $this->leads_model->get_lead_attachments($output, '', [], $this->playground());
                foreach ($attachments as $attachment) {
                    $this->leads_model->delete_lead_attachment($attachment['id'], $this->playground());
                }
                $this->handle_lead_attachments_array($output, 'file', $this->playground());
                $message = array('status' => TRUE, 'message' => 'Lead Update Successful.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Lead Update Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    function handle_lead_attachments_array($leadid, $index_name = 'file', $playground = false) {
        $path = $this->misc_model->get_upload_path_by_type('lead', $playground) . $leadid . '/';
        if (isset($_FILES[$index_name]['name']) && ($_FILES[$index_name]['name'] != '' || is_array($_FILES[$index_name]['name']) && count($_FILES[$index_name]['name']) > 0)) {
            if (!is_array($_FILES[$index_name]['name'])) {
                $_FILES[$index_name]['name'] = [$_FILES[$index_name]['name']];
                $_FILES[$index_name]['type'] = [$_FILES[$index_name]['type']];
                $_FILES[$index_name]['tmp_name'] = [$_FILES[$index_name]['tmp_name']];
                $_FILES[$index_name]['error'] = [$_FILES[$index_name]['error']];
                $_FILES[$index_name]['size'] = [$_FILES[$index_name]['size']];
            }
            _file_attachments_index_fix($index_name);
            for ($i = 0; $i < count($_FILES[$index_name]['name']); $i++) {
                // Get the temp file path
                $tmpFilePath = $_FILES[$index_name]['tmp_name'][$i];
                // Make sure we have a filepath
                if (!empty($tmpFilePath) && $tmpFilePath != '') {
                    if (_perfex_upload_error($_FILES[$index_name]['error'][$i]) || !_upload_extension_allowed($_FILES[$index_name]['name'][$i])) {
                        continue;
                    }
                    _maybe_create_upload_path($path);
                    $filename = unique_filename($path, $_FILES[$index_name]['name'][$i]);
                    $newFilePath = $path . $filename;
                    // Upload the file into the temp dir
                    if (copy($tmpFilePath, $newFilePath)) {
                        unlink($tmpFilePath);
                        $data = [];
                        $data[] = ['file_name' => $filename, 'filetype' => $_FILES[$index_name]['type'][$i], ];
                        $this->leads_model->add_attachment_to_database($leadid, $data, false, false, $playground);
                    }
                }
            }
        }
        return true;
    }
}