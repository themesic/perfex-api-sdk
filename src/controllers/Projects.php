<?php

namespace PerfexApiSdk\Controllers;

use PerfexApiSdk\Controllers\REST_Controller;

use PerfexApiSdk\Libraries\Parse_Input_Stream;

use PerfexApiSdk\Models\Projects_model;
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
class Projects extends REST_Controller {
    private $projects_model;
    private $custom_fields_model;
    private $misc_model;

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        
        $this->projects_model = new Projects_model();
        $this->custom_fields_model = new Custom_fields_model();
        $this->misc_model = new Misc_model();
    }

    /**
     * @api {get} api/projects/:id Request project information
     * @apiName GetProject
     * @apiGroup Projects
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} id project unique ID.
     *
     * @apiSuccess {Object} Project information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "id": "28",
     *          "name": "Test1",
     *          "description": null,
     *          "status": "1",
     *          "clientid": "11",
     *          "billing_type": "3",
     *          "start_date": "2019-04-19",
     *          "deadline": "2019-08-30",
     *          "project_created": "2019-07-16",
     *          "date_finished": null,
     *          "progress": "0",
     *          "progress_from_tasks": "1",
     *          "project_cost": "0.00",
     *          "project_rate_per_hour": "0.00",
     *          "estimated_hours": "0.00",
     *          "addedfrom": "5",
     *          "rel_type": "lead",
     *          "potential_revenue": "0.00",
     *          "potential_margin": "0.00",
     *          "external": "E",
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
    public function data_get($id = '')
    {
        // If the id parameter doesn't exist return all the
        $data = $this->projects_model->get_project($id, $this->playground());

        // Check if the data store contains
        if ($data)
        {
            $data = $this->custom_fields_model->get_custom_data($data, "projects", $id, false, $this->playground());

            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * @api {get} api/projects/search/:keysearch Search Project Information
     * @apiName GetProjectSearch
     * @apiGroup Projects
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} keysearch Search keywords.
     *
     * @apiSuccess {Object} Project information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "id": "28",
     *          "name": "Test1",
     *          "description": null,
     *          "status": "1",
     *          "clientid": "11",
     *          "billing_type": "3",
     *          "start_date": "2019-04-19",
     *          "deadline": "2019-08-30",
     *          "project_created": "2019-07-16",
     *          "date_finished": null,
     *          "progress": "0",
     *          "progress_from_tasks": "1",
     *          "project_cost": "0.00",
     *          "project_rate_per_hour": "0.00",
     *          "estimated_hours": "0.00",
     *          "addedfrom": "5",
     *          "rel_type": "lead",
     *          "potential_revenue": "0.00",
     *          "potential_margin": "0.00",
     *          "external": "E",
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
    public function data_search_get($key = '')
    {
        $data = $this->custom_fields_model->get_relation_data_api('project', $key, $this->playground());

        // Check if the data store contains
        if ($data)
        {
            $data = $this->custom_fields_model->get_custom_data($data, "projects", "", false, $this->playground());

            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * @api {post} api/projects Add New Project
     * @apiName PostProject
     * @apiGroup Projects
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} name                                  Mandatory Project Name.
     * @apiParam {string="lead","customer","internal"} rel_type Mandatory Project Related.
     * @apiParam {Number} clientid                              Mandatory Related ID.
     * @apiParam {Number} billing_type                          Mandatory Billing Type.
     * @apiParam {Date} start_date                              Mandatory Project Start Date.
     * @apiParam {Number} status                                Mandatory Project Status.
     * @apiParam {String} [progress_from_tasks]                 Optional on or off progress from tasks.
     * @apiParam {String} [project_cost]                        Optional Project Cost.
     * @apiParam {String} [progress]                            Optional project progress.
     * @apiParam {String} [project_rate_per_hour]               Optional project rate per hour.
     * @apiParam {String} [estimated_hours]                     Optional Project estimated hours.
     * @apiParam {Number[]} [project_members]                   Optional Project members.
     * @apiParam {Date} [deadline]                              Optional Project deadline.
     * @apiParam {String} [tags]                                Optional Project tags.
     * @apiParam {String} [description]                         Optional Project description.
     *
     * @apiParamExample {Multipart Form} Request-Example:
     *     array (size=15)
     *        'name' => string 'Project Name' (length=12)
     *        'rel_type' => string 'customer' (length=8)
     *        'clientid' => string '3' (length=1)
     *        'progress_from_tasks' => string 'on' (length=2)
     *        'progress' => string '0' (length=1)
     *        'billing_type' => string '3' (length=1)
     *        'status' => string '2' (length=1)
     *        'project_cost' => string '' (length=0)
     *        'project_rate_per_hour' => string '' (length=0)
     *        'estimated_hours' => string '' (length=0)
     *        'project_members' => 
     *          array (size=1)
     *            0 => string '1' (length=1)
     *        'start_date' => string '25/07/2019' (length=10)
     *        'deadline' => string '' (length=0)
     *        'tags' => string '' (length=0)
     *        'description' => string '' (length=0)
     *
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Project add successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Project add successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Project add fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Project add fail."
     *     }
     * 
     */
    public function data_post()
    {
        // form validation
        $this->form_validation->set_rules('name', 'Project Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Project Name'));
        //$this->form_validation->set_rules('rel_type', 'Related', 'trim|required', array('is_unique' => 'This %s already exists please enter another Project Related'));
        $this->form_validation->set_rules('billing_type', 'Billing Type', 'trim|required', array('is_unique' => 'This %s already exists please enter another Project Billing Type'));
        $this->form_validation->set_rules('start_date', 'Project Start Date', 'trim|required', array('is_unique' => 'This %s already exists please enter another Project Start Date'));
        $this->form_validation->set_rules('status', 'Project Status', 'trim|required', array('is_unique' => 'This %s already exists please enter another Project Status'));
        $related = $this->input->post('rel_type', TRUE);
        $this->form_validation->set_rules('clientid', ucwords($related), 'trim|required|max_length[11]', array('is_unique' => 'This %s already exists please enter another Project Name'));
        
        if ($this->form_validation->run() == FALSE)
        {
            // form validation error
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors() 
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $project_members = $this->misc_model->value($this->input->post('project_members', TRUE));
            $insert_data = [
                'name' => $this->input->post('name', TRUE),
                //'rel_type' => $this->input->post('rel_type', TRUE),
                'clientid' => $this->input->post('clientid', TRUE),
                'billing_type' => $this->input->post('billing_type', TRUE),
                'start_date' => $this->input->post('start_date', TRUE),
                'status' => $this->input->post('status', TRUE),
                'project_cost' => $this->misc_model->value($this->input->post('project_cost', TRUE)),
                'estimated_hours' => $this->misc_model->value($this->input->post('estimated_hours', TRUE)),
                'progress_from_tasks' => $this->misc_model->value($this->input->post('progress_from_tasks', TRUE)),
                'progress' => $this->misc_model->value($this->input->post('progress', TRUE)),
                'project_rate_per_hour' => $this->misc_model->value($this->input->post('project_rate_per_hour', TRUE)),
                'deadline' => $this->misc_model->value($this->input->post('deadline', TRUE)),
                'description' => $this->misc_model->value($this->input->post('description', TRUE)),
                'tags' => $this->misc_model->value($this->input->post('tags', TRUE)),
                
                'settings' => array( 'available_features' => array( 'project_overview', 'project_milestones', 'project_gantt', 'project_tasks', 'project_estimates', 'project_subscriptions', 'project_invoices', 'project_expenses', 'project_credit_notes', 'project_tickets', 'project_timesheets', 'project_files', 'project_discussions', 'project_notes', 'project_activity'))
            ];
            if ($project_members != '') {
                $insert_data['project_members'] = $project_members;
            }
            if (!empty($this->input->post('custom_fields', TRUE))) {
                $insert_data['custom_fields'] = $this->misc_model->value($this->input->post('custom_fields', TRUE));
            }

            // insert data
            $output = $this->projects_model->add($insert_data, $this->playground());
            if ($output > 0 && !empty($output)) {
                $this->projects_model->handle_project_file_uploads($output, $this->playground());
                // success
                $message = array(
                    'status' => TRUE,
                    'message' => 'Project add successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
             } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Project add failed.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {delete} api/delete/projects/:id Delete a Project
     * @apiName DeleteProject
     * @apiGroup Projects
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} id project unique ID.
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Project Delete successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Project Delete Successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Project Delete Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Project Delete Fail."
     *     }
     */
    public function data_delete($id = '')
    {
        $id = $this->security->xss_clean($id);
        if (empty($id) && !is_numeric($id))
        {
            $message = array(
                'status' => FALSE,
                'message' => 'Invalid Project ID'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            $output = $this->projects_model->delete($id, $this->playground());
            if ($output === TRUE) {
                // success
                $message = array(
                    'status' => TRUE,
                    'message' => 'Project Delete Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Project Delete Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {put} api/projects/:id Update a project
     * @apiName PutProject
     * @apiGroup Projects
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} name                                  Mandatory Project Name.
     * @apiParam {string="lead","customer","internal"} rel_type Mandatory Project Related.
     * @apiParam {Number} clientid                              Mandatory Related ID.
     * @apiParam {Number} billing_type                          Mandatory Billing Type.
     * @apiParam {Date} start_date                              Mandatory Project Start Date.
     * @apiParam {Number} status                                Mandatory Project Status.
     * @apiParam {String} [progress_from_tasks]                 Optional on or off progress from tasks.
     * @apiParam {String} [project_cost]                        Optional Project Cost.
     * @apiParam {String} [progress]                            Optional project progress.
     * @apiParam {String} [project_rate_per_hour]               Optional project rate per hour.
     * @apiParam {String} [estimated_hours]                     Optional Project estimated hours.
     * @apiParam {Number[]} [project_members]                   Optional Project members.
     * @apiParam {Date} [deadline]                              Optional Project deadline.
     * @apiParam {String} [tags]                                Optional Project tags.
     * @apiParam {String} [description]                         Optional Project description.
     *
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *     "name": "Test1",
     *     "rel_type": "lead",
     *     "clientid": "9",
     *     "status": "2",
     *     "progress_from_tasks": "on",
     *     "progress": "0.00", 
     *     "billing_type": "3",
     *     "project_cost": "0",
     *     "project_rate_per_hour": "0",
     *     "estimated_hours": "0",
     *     "project_members":
     *      {
     *          "0": "5"
     *      }
     *     "start_date": "19/04/2019",
     *     "deadline": "30/08/2019",
     *     "tags": "",
     *     "description": "",
     *     "settings": 
     *       {
     *         "available_features":
     *           {
     *            "0": "project_overview",
     *             "1": "project_milestones" ,
     *             "2": "project_gantt" ,
     *             "3": "project_tasks" ,
     *             "4": "project_estimates" ,
     *             "5": "project_credit_notes" ,
     *             "6": "project_invoices" ,
     *             "7": "project_expenses",
     *             "8": "project_subscriptions" ,
     *             "9": "project_activity" ,
     *             "10": "project_tickets" ,
     *             "11": "project_timesheets",
     *             "12": "project_files" ,
     *             "13": "project_discussions" ,
     *             "14": "project_notes" 
     *          }
     *      }
     *  }
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Project Update Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Project Update Successful."
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Project Update Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Project Update Fail."
     *     }
     */
    public function data_put($id = '')
    {
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
            $output = $this->projects_model->update($update_data, $id, $this->playground());
            if (!empty($update_file) && count($update_file)) {
                if ($output <= 0 || empty($output)) {
                    $output = $id;
                }
            }

            if ($output == true && !empty($output)) {
                // success
                $attachments = $this->projects_model->get_files($output, $this->playground());
                foreach ($attachments as $attachment) {
                    $this->projects_model->remove_file($attachment['id'], true, $this->playground());
                }
                $this->handle_project_attachments_array($output, $this->playground());
                $message = array(
                    'status' => TRUE,
                    'message' => 'Project Update Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Project Update Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function handle_project_attachments_array($project_id, $playground = false)
    {
        $hookData = hooks()->apply_filters('before_handle_project_file_uploads', [
            'project_id' => $project_id,
            'index_name' => 'file',
            'handled_externally' => false, // e.g. module upload to s3
            'handled_externally_successfully' => false,
            'files' => $_FILES
        ]);
    
        if ($hookData['handled_externally']) {
            return $hookData['handled_externally_successfully'];
        }
    
        $filesIDS = [];
        $errors   = [];
    
        if (isset($_FILES['file']['name'])
            && ($_FILES['file']['name'] != '' || is_array($_FILES['file']['name']) && count($_FILES['file']['name']) > 0)) {
            hooks()->do_action('before_upload_project_attachment', $project_id);
    
            if (!is_array($_FILES['file']['name'])) {
                $_FILES['file']['name']     = [$_FILES['file']['name']];
                $_FILES['file']['type']     = [$_FILES['file']['type']];
                $_FILES['file']['tmp_name'] = [$_FILES['file']['tmp_name']];
                $_FILES['file']['error']    = [$_FILES['file']['error']];
                $_FILES['file']['size']     = [$_FILES['file']['size']];
            }
    
            $path = $this->misc_model->get_upload_path_by_type('project', $playground) . $project_id . '/';
    
            for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
                if (_perfex_upload_error($_FILES['file']['error'][$i])) {
                    $errors[$_FILES['file']['name'][$i]] = _perfex_upload_error($_FILES['file']['error'][$i]);
    
                    continue;
                }
    
                // Get the temp file path
                $tmpFilePath = $_FILES['file']['tmp_name'][$i];
                // Make sure we have a filepath
                if (!empty($tmpFilePath) && $tmpFilePath != '') {
                    _maybe_create_upload_path($path);
                    $originalFilename = unique_filename($path, $_FILES['file']['name'][$i]);
                    $filename = app_generate_hash() . '.' . get_file_extension($originalFilename);
    
                    // In case client side validation is bypassed
                    if (!_upload_extension_allowed($filename)) {
                        continue;
                    }
    
                    $newFilePath = $path . $filename;
                    // Upload the file into the company uploads dir
                    if (copy($tmpFilePath, $newFilePath)) {
                        unlink($tmpFilePath);
                        if (is_client_logged_in()) {
                            $contact_id = get_contact_user_id();
                            $staffid    = 0;
                        } else {
                            $staffid    = get_staff_user_id();
                            $contact_id = 0;
                        }
                        $data = [
                            'project_id' => $project_id,
                            'file_name'  => $filename,
                            'original_file_name'  => $originalFilename,
                            'filetype'   => $_FILES['file']['type'][$i],
                            'dateadded'  => date('Y-m-d H:i:s'),
                            'staffid'    => $staffid,
                            'contact_id' => $contact_id,
                            'subject'    => $originalFilename,
                        ];
                        if (is_client_logged_in()) {
                            $data['visible_to_customer'] = 1;
                        } else {
                            $data['visible_to_customer'] = ($this->input->post('visible_to_customer') == 'true' ? 1 : 0);
                        }
                        $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'project_files', $data);
    
                        $insert_id = $this->db->insert_id();
                        if ($insert_id) {
                            if (is_image($newFilePath)) {
                                create_img_thumb($path, $filename);
                            }
                            array_push($filesIDS, $insert_id);
                        } else {
                            unlink($newFilePath);
    
                            return false;
                        }
                    }
                }
            }
        }
    
        if (count($filesIDS) > 0) {
            end($filesIDS);
            $lastFileID = key($filesIDS);
            $this->projects_model->new_project_file_notification($filesIDS[$lastFileID], $project_id);
        }
    
        if (count($errors) > 0) {
            $message = '';
            foreach ($errors as $filename => $error_message) {
                $message .= $filename . ' - ' . $error_message . '<br />';
            }
            header('HTTP/1.0 400 Bad error');
            echo $message;
            die;
        }
    
        if (count($filesIDS) > 0) {
            return true;
        }
    
        return false;
    }
}