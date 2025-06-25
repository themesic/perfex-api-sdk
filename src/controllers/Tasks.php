<?php

namespace PerfexApiSdk\Controllers;

use PerfexApiSdk\Controllers\REST_Controller;

use PerfexApiSdk\Libraries\Parse_Input_Stream;

use PerfexApiSdk\Models\Custom_fields_model;
use PerfexApiSdk\Models\Tasks_model;
use PerfexApiSdk\Models\Misc_model;

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
class Tasks extends REST_Controller {
    private $custom_fields_model;
    private $tasks_model;
    private $misc_model;

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        
        $this->tasks_model = new Tasks_model();
        $this->custom_fields_model = new Custom_fields_model();
        $this->misc_model = new Misc_model();
    }

    /**
     * @api {get} api/tasks/:id Request Task information
     * @apiName GetTask
     * @apiGroup Tasks
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} id Task unique ID.
     *
     * @apiSuccess {Object} Tasks information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "10",
     *         "name": "This is a task",
     *         "description": "",
     *         "priority": "2",
     *         "dateadded": "2019-02-25 12:26:37",
     *         "startdate": "2019-01-02 00:00:00",
     *         "duedate": "2019-01-04 00:00:00",
     *         "datefinished": null,
     *         "addedfrom": "9",
     *         "is_added_from_contact": "0",
     *         "status": "4",
     *         "recurring_type": null,
     *         "repeat_every": "0",
     *         "recurring": "0",
     *         "is_recurring_from": null,
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
        $data = $this->tasks_model->get_tasks($id, $this->playground());

        // Check if the data store contains
        if ($data)
        {
            $data = $this->custom_fields_model->get_custom_data($data, "tasks", $id, false, $this->playground());

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
     * @api {get} api/tasks/search/:keysearch Search Tasks Information
     * @apiName GetTaskSearch
     * @apiGroup Tasks
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} keysearch Search Keywords.
     *
     * @apiSuccess {Object} Tasks information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "10",
     *         "name": "This is a task",
     *         "description": "",
     *         "priority": "2",
     *         "dateadded": "2019-02-25 12:26:37",
     *         "startdate": "2019-01-02 00:00:00",
     *         "duedate": "2019-01-04 00:00:00",
     *         "datefinished": null,
     *         "addedfrom": "9",
     *         "is_added_from_contact": "0",
     *         "status": "4",
     *         "recurring_type": null,
     *         "repeat_every": "0",
     *         "recurring": "0",
     *         "is_recurring_from": null,
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
        // If the id parameter doesn't exist return all the
        $data = $this->custom_fields_model->get_relation_data_api('tasks', $key, $this->playground());

        // Check if the data store contains
        if ($data)
        {
			usort($data, function($a, $b) {
				return $a['id'] - $b['id'];
			});
            $data = $this->custom_fields_model->get_custom_data($data, "tasks", "", false, $this->playground());

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
     * @api {post} api/tasks Add New Task
     * @apiName PostTask
     * @apiGroup Tasks
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} name              Mandatory Task Name.
     * @apiParam {Date} startdate           Mandatory Task Start Date.
     * @apiParam {String} [is_public]       Optional Task public.
     * @apiParam {String} [billable]        Optional Task billable.
     * @apiParam {String} [hourly_rate]     Optional Task hourly rate.
     * @apiParam {String} [milestone]       Optional Task milestone.
     * @apiParam {Date} [duedate]           Optional Task deadline.
     * @apiParam {String} [priority]        Optional Task priority.
     * @apiParam {String} [repeat_every]    Optional Task repeat every.
     * @apiParam {Number} [repeat_every_custom]     Optional Task repeat every custom.
     * @apiParam {String} [repeat_type_custom]      Optional Task repeat type custom.
     * @apiParam {Number} [cycles]                  Optional cycles.
     * @apiParam {string="lead","customer","invoice", "project", "quotation", "contract", "annex", "ticket", "expense", "proposal"} rel_type Mandatory Task Related.
     * @apiParam {Number} rel_id            Optional Related ID.
     * @apiParam {String} [tags]            Optional Task tags.
     * @apiParam {String} [description]     Optional Task description.
     *
     *
     * @apiParamExample {Multipart Form} Request-Example:
     *     array (size=15)
     *     'is_public' => string 'on' (length=2)
     *     'billable' => string 'on' (length=2)
     *     'name' => string 'Task 12' (length=7)
     *     'hourly_rate' => string '0' (length=1)
     *     'milestone' => string '' (length=0)
     *     'startdate' => string '17/07/2019' (length=10)
     *     'duedate' => string '31/07/2019 11:07' (length=16)
     *     'priority' => string '2' (length=1)
     *     'repeat_every' => string '' (length=0)
     *     'repeat_every_custom' => string '1' (length=1)
     *     'repeat_type_custom' => string 'day' (length=3)
     *     'rel_type' => string 'customer' (length=8)
     *     'rel_id' => string '9' (length=1)
     *     'tags' => string '' (length=0)
     *     'description' => string '<span>Task Description</span>' (length=29)
     *
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Task add successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Task add successful."
     *     }
     *
     * @apiError {String} status Request status.
     * @apiError {String} message Task add fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Task add fail."
     *     }
     * 
     */
    public function data_post()
    {        
        // form validation
        $this->form_validation->set_rules('name', 'Task Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Task Name'));
        $this->form_validation->set_rules('startdate', 'Task Start Date', 'trim|required', array('is_unique' => 'This %s already exists please enter another Task Start Date'));
        $this->form_validation->set_rules('is_public', 'Publicly available task', 'trim', array('is_unique' => 'Public state can be 1. Skip it completely to set it at non-public'));
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
            $insert_data = [
                'name' => $this->input->post('name', TRUE),
                'startdate' => $this->input->post('startdate', TRUE),
                'is_public' => $this->input->post('is_public', TRUE),
                'billable' => $this->misc_model->value($this->input->post('billable', TRUE)),
                'hourly_rate' => $this->misc_model->value($this->input->post('hourly_rate', TRUE)),
                'milestone' => $this->misc_model->value($this->input->post('milestone', TRUE)),
                'duedate' => $this->misc_model->value($this->input->post('duedate', TRUE)),
                'priority' => $this->misc_model->value($this->input->post('priority', TRUE)),
                'repeat_every' => $this->misc_model->value($this->input->post('repeat_every', TRUE)),
                'repeat_every_custom' => $this->misc_model->value($this->input->post('repeat_every_custom', TRUE)),
                'repeat_type_custom' => $this->misc_model->value($this->input->post('repeat_type_custom', TRUE)),
                'cycles' => $this->misc_model->value($this->input->post('cycles', TRUE)),
                'rel_type' => $this->misc_model->value($this->input->post('rel_type', TRUE)),
                'rel_id' => $this->misc_model->value($this->input->post('rel_id', TRUE)),
                'tags' => $this->misc_model->value($this->input->post('tags', TRUE)),
                'description' => $this->misc_model->value($this->input->post('description', TRUE))
            ];
            
            if (!empty($this->input->post('custom_fields', TRUE))) {
                $insert_data['custom_fields'] = $this->misc_model->value($this->input->post('custom_fields', TRUE));
            }
            // insert data
            $output = $this->tasks_model->add($insert_data, false, $this->playground());
            if ($output > 0 && !empty($output)) {
                // success
                $this->handle_task_attachments_array($output, $this->playground());
                $message = array(
                    'status' => TRUE,
                    'message' => 'Task add successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Task add failed.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {delete} api/delete/tasks/:id Delete a Task
     * @apiName DeleteTask
     * @apiGroup Tasks
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {Number} id Task unique ID.
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Task Delete Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Task Delete Successful."
     *     }
     *
     * @apiError {String} status Request status.
     * @apiError {String} message Task Delete Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Task Delete Fail."
     *     }
     */
    public function data_delete($id = '')
    {
        $id = $this->security->xss_clean($id);
        if (empty($id) && !is_numeric($id)) {
            $message = array(
                'status' => FALSE,
                'message' => 'Invalid Task ID'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            $output = $this->tasks_model->delete_task($id, true, $this->playground());
            if ($output === TRUE) {
                // success
                $message = array(
                    'status' => TRUE,
                    'message' => 'Task Delete Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Task Delete Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {put} api/tasks/:id Update a task
     * @apiName PutTask
     * @apiGroup Tasks
     *
     * @apiHeader {String} authtoken Authentication token, generated from admin area
     *
     * @apiParam {String} name              Mandatory Task Name.
     * @apiParam {Date} startdate           Mandatory Task Start Date.
     * @apiParam {String} [is_public]       Optional Task public.
     * @apiParam {String} [billable]        Optional Task billable.
     * @apiParam {String} [hourly_rate]     Optional Task hourly rate.
     * @apiParam {String} [milestone]       Optional Task milestone.
     * @apiParam {Date} [duedate]           Optional Task deadline.
     * @apiParam {String} [priority]        Optional Task priority.
     * @apiParam {String} [repeat_every]    Optional Task repeat every.
     * @apiParam {Number} [repeat_every_custom]     Optional Task repeat every custom.
     * @apiParam {String} [repeat_type_custom]      Optional Task repeat type custom.
     * @apiParam {Number} [cycles]                  Optional cycles.
     * @apiParam {string="lead","customer","invoice", "project", "quotation", "contract", "annex", "ticket", "expense", "proposal"} rel_type Mandatory Task Related.
     * @apiParam {Number} rel_id            Optional Related ID.
     * @apiParam {String} [tags]            Optional Task tags.
     * @apiParam {String} [description]     Optional Task description.
     *
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *      "billable": "1", 
     *      "is_public": "1",
     *      "name": "Task 1",
     *      "hourly_rate": "0.00",
     *      "milestone": "0",
     *      "startdate": "27/08/2019",
     *      "duedate": null,
     *      "priority": "0",
     *      "repeat_every": "",
     *      "repeat_every_custom": "1",
     *      "repeat_type_custom": "day",
     *      "cycles": "0",
     *      "rel_type": "lead",
     *      "rel_id": "11",
     *      "tags": "",
     *      "description": ""
     *   }
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Task Update Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Task Update Successful."
     *     }
     *
     * @apiError {String} status Request status.
     * @apiError {String} message Task Update Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Task Update Fail."
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
            $output = $this->tasks_model->update($update_data, $id, false, $this->playground());
            if (!empty($update_file) && count($update_file)) {
                if ($output <= 0 || empty($output)) {
                    $output = $id;
                }
            }

            if ($output > 0 && !empty($output)) {
                // success
                $attachments = $this->tasks_model->get_task_attachments($output, [], $this->playground());
                foreach ($attachments as $attachment) {
                    $this->tasks_model->remove_task_attachment($attachment['id'], $this->playground());
                }
                $this->handle_task_attachments_array($output);
                $message = array(
                    'status' => TRUE,
                    'message' => 'Task Update Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Task Update Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    function handle_task_attachments_array($task_id, $index_name = 'file', $playground = false) {
        $path = $this->misc_model->get_upload_path_by_type('task', $playground) . $task_id . '/';
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
                        $this->tasks_model->add_attachment_to_database($task_id, $data, false, true, $playground);
                    }
                }
            }
        }
        return true;
    }
}