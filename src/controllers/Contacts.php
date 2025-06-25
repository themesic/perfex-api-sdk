<?php

namespace PerfexApiSdk\Controllers;

use PerfexApiSdk\Controllers\REST_Controller;

use PerfexApiSdk\Models\Clients_model;
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
class Contacts extends REST_Controller {
    private $clients_model;
    private $custom_fields_model;

    function __construct() {
        // Construct the parent class
        parent::__construct();

        $this->clients_model = new clients_model();
        $this->custom_fields_model = new Custom_fields_model();
    }

    /**
     * @api {get} api/contacts/:customer_id/:contact_id List all Contacts of a Customer
     * @apiVersion 0.1.0
     * @apiName GetContact
     * @apiGroup Contacts
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} customer_id Mandatory Customer unique ID
     * @apiParam {Number} contact_id Optional Contact unique ID <br/><i>Note : if you don't pass Contact id then it will list all contacts of the customer</i>
     *
     * @apiSuccess {Object} Contact Contact information
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *	{
     *		"id": "6",
     *		"userid": "1",
     *		"company": "xyz",
     *		"vat": "",
     *		"phonenumber": "1234567890",
     *		"country": "0",
     *		"city": "",
     *		"zip": "360005",
     *		"state": "",
     * 		"address": "",
     *		"website": "",
     *		"datecreated": "2020-08-19 20:07:49",
     *		"active": "1",
     *		"leadid": null,
     *		"billing_street": "",
     *		"billing_city": "",
     *		"billing_state": "",
     *		"billing_zip": "",
     *		"billing_country": "0",
     *		"shipping_street": "",
     *		"shipping_city": "",
     *		"shipping_state": "",
     * 		"shipping_zip": "",
     *		"shipping_country": "0",
     *		"longitude": null,
     *		"latitude": null,
     *		"default_language": "english",
     *		"default_currency": "0",
     *		"show_primary_contact": "0",
     *		"stripe_id": null,
     *		"registration_confirmed": "1",
     *		"addedfrom": "1"
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
    public function data_get($customer_id = '', $contact_id = '') {
        // If the id parameter doesn't exist return all the
        if (empty($contact_id) && !empty($customer_id)) {
            $data = $this->clients_model->get_contacts($customer_id, ['active' => 1], [], $this->playground());
        }
        if (!empty($contact_id) && !empty($customer_id)) {
            $data = $this->clients_model->get_contact($contact_id, ['active' => 1], [], $this->playground());
        }
        if (empty($contact_id) && empty($customer_id)) {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code            
        }
        // Check if the data store contains
        if ($data) {
            $data = $this->custom_fields_model->get_custom_data($data, "contacts", $contact_id, false, $this->playground());
            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code            
        } else {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code            
        }
    }

    /**
     * @api {get} api/contacts/search/:keysearch Search Contact Information
     * @apiVersion 0.1.0
     * @apiName GetContactSearch
     * @apiGroup Contacts
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} keysearch Search Keywords
     *
     * @apiSuccess {Object} Contact Contact information
     *
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK
     *   {
     *      "id": "8",
     *      "userid": "1",
     *      "is_primary": "0",
     *      "firstname": "chirag",
     *      "lastname": "jagani",
     *      "email": "useremail@gmail.com",
     *      "phonenumber": "",
     *      "title": null,
     *      "datecreated": "2020-05-19 20:07:49",
     *      "password": "$2a$08$6DLJFalqvJGVymCwW2ppNe9HOG5YUP04vzthXZjOFFUQknxfG6QHe",
     *      "new_pass_key": null,
     *      "new_pass_key_requested": null,
     *      "email_verified_at": "2020-08-28 21:36:06",
     *      "email_verification_key": null,
     *      "email_verification_sent_at": null,
     *      "last_ip": null,
     *      "last_login": null,
     *      "last_password_change": null,
     *      "active": "1",
     *      "profile_image": null,
     *      "direction": null,
     *      "invoice_emails": "0",
     *      "estimate_emails": "0",
     *      "credit_note_emails": "0",
     *      "contract_emails": "0",
     *      "task_emails": "0",
     *      "project_emails": "0",
     *      "ticket_emails": "0",
     *      "company": "trueline",
     *      "vat": "",
     *      "country": "0",
     *      "city": "",
     *      "zip": "",
     *      "state": "",
     *      "address": "",
     *      "website": "",
     *      "leadid": null,
     *      "billing_street": "",
     *      "billing_city": "",
     *      "billing_state": "",
     *      "billing_zip": "",
     *      "billing_country": "0",
     *      "shipping_street": "",
     *      "shipping_city": "",
     *      "shipping_state": "",
     *      "shipping_zip": "",
     *      "shipping_country": "0",
     *      "longitude": null,
     *      "latitude": null,
     *      "default_language": "english",
     *      "default_currency": "0",
     *      "show_primary_contact": "0",
     *      "stripe_id": null,
     *      "registration_confirmed": "1",
     *      "addedfrom": "1"
     *  }
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
        // If the id parameter doesn't exist return all the
        $data = $this->custom_fields_model->get_relation_data_api('contacts', $key, $this->playground());
        // Check if the data store contains
        if ($data) {
            $data = $this->custom_fields_model->get_custom_data($data, "contacts", '', false, $this->playground());
            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code            
        } else {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code            
        }
    }

    /**
     * @api {post} api/contacts/ Add New Contact
     * @apiVersion 0.1.0
     * @apiName PostContact
     * @apiGroup Contacts
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} customer_id               Mandatory Customer id.
     * @apiParam {String} firstname               	Mandatory First Name
     * @apiParam {String} lastname         			Mandatory Last Name
     * @apiParam {String} email           			Mandatory E-mail
     * @apiParam {String} [title]         			Optional Position
     * @apiParam {String} [phonenumber]         	Optional Phone Number
     * @apiParam {String} [direction = 'rtl']       Optional Direction (rtl or ltr)
     * @apiParam {String} [password]         		Optional password (only required if you pass send_set_password_email parameter)
     * @apiParam {String} [is_primary = 'on']       Optional Primary Contact (set on or don't pass it)
     * @apiParam {String} [donotsendwelcomeemail]   Optional Do Not Send Welcome Email (set on or don't pass it)
     * @apiParam {String} [send_set_password_email] Optional Send Set Password Email (set on or don't pass it)
     * @apiParam {Array}  [permissions]         	Optional Permissions for this contact(["1", "2", "3", "4", "5", "6" ])<br/>
     *															[<br/>
     *											                	"1",    // Invoices permission<br/>
     *											                	"2",    // Estimates permission<br/>
     *											                	"3",    // Contracts permission<br/>
     *											                	"4",    // Proposals permission<br/>
     *											                	"5",    // Support permission<br/>
     *											                	"6"     // Projects permission<br/>
     *											            	]
     * @apiParam {String} [invoice_emails = "invoice_emails"]         	Optional E-Mail Notification for Invoices (set value same as name or don't pass it)
     * @apiParam {String} [estimate_emails = "estimate_emails"]         Optional E-Mail Notification for Estimate (set value same as name or don't pass it)
     * @apiParam {String} [credit_note_emails = "credit_note_emails"]   Optional E-Mail Notification for Credit Note (set value same as name or don't pass it)
     * @apiParam {String} [project_emails = "project_emails"]         	Optional E-Mail Notification for Project (set value same as name or don't pass it)
     * @apiParam {String} [ticket_emails = "ticket_emails"]         	Optional E-Mail Notification for Tickets (set value same as name or don't pass it)
     * @apiParam {String} [task_emails = "task_emails"]         		Optional E-Mail Notification for Task (set value same as name or don't pass it)
     * @apiParam {String} [contract_emails ="contract_emails"]         	Optional E-Mail Notification for Contract (set value same as name or don't pass it)
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Contact added successfully.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Contact added successfully"
     *     }
     *
     * @apiError {Boolean} status Request status
     * @apiError {String} message Contact add fail
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Contact add fail"
     *     }
     *
     * @apiError {String} email This Email is already exists
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 409 Conflict
     *     {
     *       "status": false,
     *       "error": {
     *			"email":"This Email is already exists"
     *		},
     * 		"message": "This Email is already exists"
     *     }
     */
    public function data_post() {
        $data = $this->input->post();
        $send_set_password_email = isset($data['send_set_password_email']) ? true : false;
        if ($send_set_password_email) {
            unset($data['password']);
        }
        $this->form_validation->set_rules('firstname', 'First Name', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('lastname', 'Last Name', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[255]|is_unique[' . db_prefix() . ($this->playground() ? 'playground_' : '') . 'contacts.email]', array('is_unique' => 'This %s is already exists'));
        if (!$send_set_password_email) {
            $this->form_validation->set_rules('password', 'Password', 'trim|required|max_length[255]');
        }
        $this->form_validation->set_rules('customer_id', 'Customer Id', 'trim|required|numeric|callback_client_id_check');
        if ($this->form_validation->run() == FALSE) {
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_CONFLICT);
        } else {
            $customer_id = $data['customer_id'];
            unset($data['customer_id']);
            $id = $this->clients_model->add_contact($data, $customer_id, false, $this->playground());
            if ($id > 0 && !empty($id)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Contact added successfully.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Contact add fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {delete} api/delete/contacts/:id Delete Contact
     * @apiVersion 0.1.0
     * @apiName DeleteContact
     * @apiGroup Contacts
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} customer_id 	unique Customer id
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Contact Deleted Successfully
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Contact Deleted Successfully"
     *     }
     *
     * @apiError {Boolean} status Request status
     * @apiError {String} message Contact Delete Fail
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Contact Delete Fail"
     *     }
     */
    public function data_delete($customer_id = '') {
        $id = $this->security->xss_clean($customer_id);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Contact ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $is_exist = $this->clients_model->get_contact($id, ['active' => 1], [], $this->playground());
            if (is_object($is_exist)) {
                $output = $this->clients_model->delete_contact($id);
                if ($output === TRUE) {
                    // success
                    $message = array('status' => TRUE, 'message' => 'Contact Deleted Successfuly.');
                    $this->response($message, REST_Controller::HTTP_OK);
                } else {
                    // error
                    $message = array('status' => FALSE, 'message' => 'Contact Delete Fail.');
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            } else {
                $message = array('status' => FALSE, 'message' => 'Invalid Contact ID');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {put} api/contacts/:id Update Contact Information
     * @apiVersion 0.1.0
     * @apiName PutContact
     * @apiGroup Contacts
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} id               			Mandatory Customer Contact id.
     * @apiParam {String} firstname               	Mandatory First Name
     * @apiParam {String} lastname         			Mandatory Last Name
     * @apiParam {String} email           			Mandatory E-mail
     * @apiParam {String} [title]         			Optional Position
     * @apiParam {String} [phonenumber]         	Optional Phone Number
     * @apiParam {String} [direction = 'rtl']       Optional Direction (rtl or ltr)
     * @apiParam {String} [password]         		Optional password (only required if you pass send_set_password_email parameter)
     * @apiParam {String} [is_primary = 'on']       Optional Primary Contact (set on or don't pass it)
     * @apiParam {String} [donotsendwelcomeemail]   Optional Do Not Send Welcome Email (set on or don't pass it)
     * @apiParam {String} [send_set_password_email] Optional Send Set Password Email (set on or don't pass it)
     * @apiParam {Array}  [permissions]         	Optional Permissions for this contact(["1", "2", "3", "4", "5", "6" ])<br/>
     *															[<br/>
     *											                	"1",    // Invoices permission<br/>
     *											                	"2",    // Estimates permission<br/>
     *											                	"3",    // Contracts permission<br/>
     *											                	"4",    // Proposals permission<br/>
     *											                	"5",    // Support permission<br/>
     *											                	"6"     // Projects permission<br/>
     *											            	]
     * @apiParam {String} [invoice_emails = "invoice_emails"]         	Optional E-Mail Notification for Invoices (set value same as name or don't pass it)
     * @apiParam {String} [estimate_emails = "estimate_emails"]         Optional E-Mail Notification for Estimate (set value same as name or don't pass it)
     * @apiParam {String} [credit_note_emails = "credit_note_emails"]   Optional E-Mail Notification for Credit Note (set value same as name or don't pass it)
     * @apiParam {String} [project_emails = "project_emails"]         	Optional E-Mail Notification for Project (set value same as name or don't pass it)
     * @apiParam {String} [ticket_emails = "ticket_emails"]         	Optional E-Mail Notification for Tickets (set value same as name or don't pass it)
     * @apiParam {String} [task_emails = "task_emails"]         		Optional E-Mail Notification for Task (set value same as name or don't pass it)
     * @apiParam {String} [contract_emails ="contract_emails"]         	Optional E-Mail Notification for Contract (set value same as name or don't pass it)
     *
     * @apiParamExample {json} Request-Example:
     *    {
     *	 	  "firstname":"new first name",
     *        "lastname":"new last name",
     *        "email":"dummy@gmail.com",
     *        "title":"",
     *        "phonenumber":"9909999099",
     *        "direction":"rtl",
     *        "password":"123456",
     *        "is_primary":"on",
     *        "send_set_password_email":"on",
     *        "permissions":["1", "2", "3", "4", "5", "6" ],
     *        "invoice_emails":"invoice_emails",
     *        "estimate_emails":"estimate_emails",
     *        "credit_note_emails":"credit_note_emails",
     *        "project_emails":"project_emails",
     *        "ticket_emails":"ticket_emails",
     *        "task_emails":"task_emails",
     *        "contract_emails":"contract_emails"
     *    }
     *
     * @apiSuccess {Boolean} status Request status
     * @apiSuccess {String} message Contact updated successful
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Contact Updated Successfully"
     *     }
     *
     * @apiError {String} email This Email is already exists
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 409 Conflict
     *     {
     *       "status": false,
     *       "error": {
     *			"email":"This Email is already exists"
     *		},
     * 		"message": "This Email is already exists"
     *     }
     * @apiError {Boolean} status Request status
     * @apiError {String} message Contact add fail
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Contact Update fail"
     *     }
     *
     */
    public function data_put($id = '') {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($_POST) || !isset($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
        $this->form_validation->set_data($_POST);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Client ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $data = $this->input->post();
            $is_exist = $this->clients_model->get_contact($id, ['active' => 1], [], $this->playground());
            if (!is_object($is_exist)) {
                $message = array('status' => FALSE, 'message' => 'Contact ID Doesn\'t Not Exist.');
                $this->response($message, REST_Controller::HTTP_CONFLICT);
            }
            $_current_email = $this->db->where('id', $id)->get(db_prefix() . ($this->playground() ? 'playground_' : '') . 'contacts')->row();
            if ($_current_email->email == $this->input->post('email')) {
                $this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[255]');
            } else {
                $this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[255]|is_unique[' . db_prefix() . ($this->playground() ? 'playground_' : '') . 'contacts.email]', array('is_unique' => 'This %s is already exists'));
            }
            if ($this->form_validation->run() == FALSE) {
                $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
                $this->response($message, REST_Controller::HTTP_CONFLICT);
            }
            $success = $this->clients_model->update_contact($data, $id);
            $updated = false;
            if (is_array($success)) {
                if (isset($success['set_password_email_sent'])) {
                    $message_str = _l('set_password_email_sent_to_client');
                } else if (isset($success['set_password_email_sent_and_profile_updated'])) {
                    $updated = true;
                    $message_str = _l('set_password_email_sent_to_client_and_profile_updated');
                }
            } else {
                if ($success == true) {
                    $updated = true;
                    $message_str = "Contact Updated Successfully";
                }
            }
            if ($updated == true) {
                $message = array('status' => TRUE, 'message' => $message_str);
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Client Update Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function client_id_check($customer_id) {
        $this->form_validation->set_message('client_id_check', 'The {field} is Invalid');
        if (empty($customer_id)) {
            return FALSE;
        }
        $query = $this->db->get_where(db_prefix() . ($this->playground() ? 'playground_' : '') . 'clients', array('userid' => $customer_id));
        return $query->num_rows() > 0;
    }
}
