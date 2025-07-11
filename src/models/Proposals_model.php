<?php

namespace PerfexApiSdk\Models;

use app\services\AbstractKanban;
use app\services\proposals\ProposalsPipeline;

use PerfexApiSdk\Models\Clients_model;
use PerfexApiSdk\Models\Custom_fields_model;
use PerfexApiSdk\Models\Estimate_request_model;
use PerfexApiSdk\Models\Invoice_items_model;
use PerfexApiSdk\Models\Invoices_model;
use PerfexApiSdk\Models\Leads_model;
use PerfexApiSdk\Models\Payment_modes_model;
use PerfexApiSdk\Models\Projects_model;
use PerfexApiSdk\Models\Tasks_model;
use PerfexApiSdk\Models\Misc_model;

require_once(APPPATH . 'core/App_Model.php');

defined('BASEPATH') or exit('No direct script access allowed');

class Proposals_model extends \App_Model {
    private $statuses;
    private $copy = false;

    public function __construct() {
        parent::__construct();

        $this->statuses = hooks()->apply_filters('before_set_proposal_statuses', [6, 4, 1, 5, 2, 3, ]);
    }

    public function get_statuses() {
        return $this->statuses;
    }

    public function get_sale_agents($playground = false) {
        return $this->db->query('SELECT DISTINCT(assigned) as sale_agent FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'proposals WHERE assigned != 0')->result_array();
    }

    public function get_proposals_years($playground = false) {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'proposals')->result_array();
    }

    /**
     * Inserting new proposal function
     * @param mixed $data $_POST data
     */
    public function add($data, $playground = false) {
        $data['allow_comments'] = isset($data['allow_comments']) ? 1 : 0;
        $save_and_send = isset($data['save_and_send']);
        $tags = isset($data['tags']) ? $data['tags'] : '';
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }
        $estimateRequestID = false;
        if (isset($data['estimate_request_id'])) {
            $estimateRequestID = $data['estimate_request_id'];
            unset($data['estimate_request_id']);
        }
        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['addedfrom'] = get_staff_user_id();
        $data['hash'] = app_generate_hash();
        if (empty($data['rel_type'])) {
            unset($data['rel_type']);
            unset($data['rel_id']);
        } else {
            if (empty($data['rel_id'])) {
                unset($data['rel_type']);
                unset($data['rel_id']);
            }
        }
        $items = [];
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }
        if ($this->copy == false) {
            $data['content'] = '{proposal_items}';
        }
        if (isset($data['rel_id'], $data['rel_type']) && $data['rel_type'] !== 'customer') {
            $data['project_id'] = null;
        }
        $hook = hooks()->apply_filters('before_create_proposal', ['data' => $data, 'items' => $items, ]);
        $data = $hook['data'];
        $items = $hook['items'];
        $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if ($estimateRequestID !== false && $estimateRequestID != '') {
                $estimate_request_model = new Estimate_request_model();
                $completedStatus = $estimate_request_model->get_status_by_flag('completed', $playground);
                $estimate_request_model->update_request_status(['requestid' => $estimateRequestID, 'status' => $completedStatus->id, ], $playground);
            }
            if (isset($custom_fields)) {
                $custom_fields_model = new Custom_fields_model();
                $custom_fields_model->handle_custom_fields_post($insert_id, $custom_fields, false, $playground);
            }
            $misc_model = new Misc_model();
            $misc_model->handle_tags_save($tags, $insert_id, 'proposal', $playground);
            $invoice_items_model = new Invoice_items_model();
            foreach ($items as $key => $item) {
                if ($itemid = $invoice_items_model->add_new_sales_item_post($item, $insert_id, 'proposal', $playground)) {
                    $invoice_items_model->_maybe_insert_post_item_tax($itemid, $item, $insert_id, 'proposal', $playground);
                }
            }
            $proposal = $this->get($insert_id, [], false, $playground);
            if ($proposal->assigned != 0) {
                if ($proposal->assigned != get_staff_user_id()) {
                    $notified = add_notification(['description' => 'not_proposal_assigned_to_you', 'touserid' => $proposal->assigned, 'fromuserid' => get_staff_user_id(), 'link' => 'proposals/list_proposals/' . $insert_id, 'additional_data' => serialize([$proposal->subject, ]), ]);
                    if ($notified) {
                        pusher_trigger_notification([$proposal->assigned]);
                    }
                }
            }
            if ($data['rel_type'] == 'lead') {
                $leads_model = new Leads_model();
                $leads_model->log_lead_activity($data['rel_id'], 'not_lead_activity_created_proposal', false, serialize(['<a href="' . admin_url('proposals/list_proposals/' . $insert_id) . '" target="_blank">' . $data['subject'] . '</a>', ]), $playground);
            }
            update_sales_total_tax_column($insert_id, 'proposal', db_prefix() . ($playground ? 'playground_' : '') . 'proposals');
            log_activity('New Proposal Created [ID: ' . $insert_id . ']');
            if ($save_and_send === true) {
                $this->send_proposal_to_email($insert_id, true, '', $playground);
            }
            hooks()->do_action('proposal_created', $insert_id);
            return $insert_id;
        }
        return false;
    }

    /**
     * Update proposal
     * @param  mixed $data $_POST data
     * @param  mixed $id   proposal id
     * @return boolean
     */
    public function update($data, $id, $playground = false) {
        $affectedRows = 0;
        $data['allow_comments'] = isset($data['allow_comments']) ? 1 : 0;
        $current_proposal = $this->get($id, [], false, $playground);
        $save_and_send = isset($data['save_and_send']);
        if (empty($data['rel_type'])) {
            $data['rel_id'] = null;
            $data['rel_type'] = '';
        } else {
            if (empty($data['rel_id'])) {
                $data['rel_id'] = null;
                $data['rel_type'] = '';
            }
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            $custom_fields_model = new Custom_fields_model();
            if ($custom_fields_model->handle_custom_fields_post($id, $custom_fields, false, $playground)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }
        $items = [];
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
        }
        $newitems = [];
        if (isset($data['newitems'])) {
            $newitems = $data['newitems'];
            unset($data['newitems']);
        }
        if (isset($data['tags'])) {
            $misc_model = new Misc_model();
            if ($misc_model->handle_tags_save($data['tags'], $id, 'proposal', $playground)) {
                $affectedRows++;
            }
        }
        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);
        $hook = hooks()->apply_filters('before_proposal_updated', ['data' => $data, 'items' => $items, 'newitems' => $newitems, 'removed_items' => isset($data['removed_items']) ? $data['removed_items'] : [], ], $id);
        $data = $hook['data'];
        $data['removed_items'] = $hook['removed_items'];
        $newitems = $hook['newitems'];
        $items = $hook['items'];
        // Delete items checked to be removed from database
        foreach ($data['removed_items'] as $remove_item_id) {
            if (handle_removed_sales_item_post($remove_item_id, 'proposal')) {
                $affectedRows++;
            }
        }
        unset($data['removed_items']);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            $proposal_now = $this->get($id, [], false, $playground);
            if ($current_proposal->assigned != $proposal_now->assigned) {
                if ($proposal_now->assigned != get_staff_user_id()) {
                    $notified = add_notification(['description' => 'not_proposal_assigned_to_you', 'touserid' => $proposal_now->assigned, 'fromuserid' => get_staff_user_id(), 'link' => 'proposals/list_proposals/' . $id, 'additional_data' => serialize([$proposal_now->subject, ]), ]);
                    if ($notified) {
                        pusher_trigger_notification([$proposal_now->assigned]);
                    }
                }
            }
        }
        $custom_fields_model = new Custom_fields_model();
        foreach ($items as $key => $item) {
            if (update_sales_item_post($item['itemid'], $item)) {
                $affectedRows++;
            }
            if (isset($item['custom_fields'])) {
                if ($custom_fields_model->handle_custom_fields_post($item['itemid'], $item['custom_fields'], false, $playground)) {
                    $affectedRows++;
                }
            }
            if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                if (delete_taxes_from_item($item['itemid'], 'proposal')) {
                    $affectedRows++;
                }
            } else {
                $item_taxes = $this->get_proposal_item_taxes($item['itemid'], $playground);
                $_item_taxes_names = [];
                foreach ($item_taxes as $_item_tax) {
                    array_push($_item_taxes_names, $_item_tax['taxname']);
                }
                $i = 0;
                foreach ($_item_taxes_names as $_item_tax) {
                    if (!in_array($_item_tax, $item['taxname'])) {
                        $this->db->where('id', $item_taxes[$i]['id'])->delete(db_prefix() . ($playground ? 'playground_' : '') . 'item_tax');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                    $i++;
                }
                if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'proposal')) {
                    $affectedRows++;
                }
            }
        }
        $invoice_items_model = new Invoice_items_model();
        foreach ($newitems as $key => $item) {
            if ($new_item_added = $invoice_items_model->add_new_sales_item_post($item, $id, 'proposal', $playground)) {
                $invoice_items_model->_maybe_insert_post_item_tax($new_item_added, $item, $id, 'proposal', $playground);
                $affectedRows++;
            }
        }
        if ($affectedRows > 0) {
            update_sales_total_tax_column($id, 'proposal', db_prefix() . ($playground ? 'playground_' : '') . 'proposals');
            log_activity('Proposal Updated [ID:' . $id . ']');
        }
        if ($save_and_send === true) {
            $this->send_proposal_to_email($id, true, '', $playground);
        }
        if ($affectedRows > 0) {
            hooks()->do_action('after_proposal_updated', $id);
            return true;
        }
        return false;
    }

    /**
     * Get proposals
     * @param  mixed $id proposal id OPTIONAL
     * @return mixed
     */
    public function get($id = '', $where = [], $for_editor = false, $playground = false) {
        $this->db->where($where);
        if (is_client_logged_in()) {
            $this->db->where('status !=', 0);
        }
        $this->db->select('*,' . db_prefix() . ($playground ? 'playground_' : '') . 'currencies.id as currencyid, ' . db_prefix() . ($playground ? 'playground_' : '') . 'proposals.id as id, ' . db_prefix() . ($playground ? 'playground_' : '') . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . ($playground ? 'playground_' : '') . 'proposals');
        $this->db->join(db_prefix() . ($playground ? 'playground_' : '') . 'currencies', db_prefix() . ($playground ? 'playground_' : '') . 'currencies.id = ' . db_prefix() . ($playground ? 'playground_' : '') . 'proposals.currency', 'left');
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . ($playground ? 'playground_' : '') . 'proposals.id', $id);
            $proposal = $this->db->get()->row();
            if ($proposal) {
                $proposal->attachments = $this->get_attachments($id, $playground);
                $invoice_items_model = new Invoice_items_model();
                $proposal->items = $this->invoice_items_model->get_items_by_type('proposal', $id, $playground);
                $proposal->visible_attachments_to_customer_found = false;
                foreach ($proposal->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $proposal->visible_attachments_to_customer_found = true;
                        break;
                    }
                }
                if ($proposal->project_id) {
                    $projects_model = new Projects_model();
                    $proposal->project_data = $projects_model->get($proposal->project_id, $playground);
                }
                if ($for_editor == false) {
                    $proposal = parse_proposal_content_merge_fields($proposal, $playground);
                }
            }
            return $proposal;
        }
        return $this->db->get()->result_array();
    }

    public function clear_signature($id, $playground = false) {
        $this->db->select('signature');
        $this->db->where('id', $id);
        $proposal = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'proposals')->row();
        $misc_model = new Misc_model();
        if ($proposal) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', ['signature' => null]);
            if (!empty($proposal->signature)) {
                unlink($misc_model->get_upload_path_by_type('proposal', $playground) . $id . '/' . $proposal->signature);
            }
            return true;
        }
        return false;
    }

    public function update_pipeline($data, $playground = false) {
        $this->mark_action_status($data['status'], $data['proposalid'], false, $playground);
        AbstractKanban::updateOrder($data['order'], 'pipeline_order', ($playground ? 'playground_' : '') . 'proposals', $data['status']);
    }

    public function get_attachments($proposal_id, $id = '', $playground = false) {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $proposal_id);
        }
        $this->db->where('rel_type', 'proposal');
        $result = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }
        return $result->result_array();
    }

    /**
     *  Delete proposal attachment
     * @param   mixed $id  attachmentid
     * @return  boolean
     */
    public function delete_attachment($id, $playground = false) {
        $attachment = $this->get_attachments('', $id, $playground);
        $deleted = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('proposal', $playground) . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Proposal Attachment Deleted [ID: ' . $attachment->rel_id . ']');
            }
            if (is_dir(get_upload_path_by_type('proposal') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('proposal', $playground) . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('proposal', $playground) . $attachment->rel_id);
                }
            }
        }
        return $deleted;
    }

    /**
     * Add proposal comment
     * @param mixed  $data   $_POST comment data
     * @param boolean $client is request coming from the client side
     */
    public function add_comment($data, $client = false, $playground = false) {
        if (is_staff_logged_in()) {
            $client = false;
        }
        if (isset($data['action'])) {
            unset($data['action']);
        }
        $data['dateadded'] = date('Y-m-d H:i:s');
        if ($client == false) {
            $data['staffid'] = get_staff_user_id();
        }
        $data['content'] = nl2br($data['content']);
        $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'proposal_comments', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            $proposal = $this->get($data['proposalid'], [], false, $playground);
            // No notifications client when proposal is with draft status
            if ($proposal->status == '6' && $client == false) {
                return true;
            }
            if ($client == true) {
                // Get creator and assigned
                $this->db->select('staffid,email,phonenumber');
                $this->db->where('staffid', $proposal->addedfrom);
                $this->db->or_where('staffid', $proposal->assigned);
                $staff_proposal = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'staff')->result_array();
                $notifiedUsers = [];
                foreach ($staff_proposal as $member) {
                    $notified = add_notification(['description' => 'not_proposal_comment_from_client', 'touserid' => $member['staffid'], 'fromcompany' => 1, 'fromuserid' => 0, 'link' => 'proposals/list_proposals/' . $data['proposalid'], 'additional_data' => serialize([$proposal->subject, ]), ], $playground);
                    if ($notified) {
                        array_push($notifiedUsers, $member['staffid']);
                    }
                    $template = mail_template('proposal_comment_to_staff', $proposal->id, $member['email']);
                    $merge_fields = $template->get_merge_fields();
                    $template->send();
                    // Send email/sms to admin that client commented
                    $this->app_sms->trigger(SMS_TRIGGER_PROPOSAL_NEW_COMMENT_TO_STAFF, $member['phonenumber'], $merge_fields);
                }
                hooks()->do_action('after_proposal_client_add_comment', $proposal->id);
                pusher_trigger_notification($notifiedUsers);
            } else {
                // Send email/sms to client that admin commented
                $template = mail_template('proposal_comment_to_customer', $proposal);
                $merge_fields = $template->get_merge_fields();
                $template->send();
                $this->app_sms->trigger(SMS_TRIGGER_PROPOSAL_NEW_COMMENT_TO_CUSTOMER, $proposal->phone, $merge_fields);
                hooks()->do_action('after_proposal_staff_add_comment', $proposal->id);
            }
            return true;
        }
        return false;
    }

    public function edit_comment($data, $id, $playground = false) {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposal_comments', ['content' => nl2br($data['content']), ]);
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Get proposal comments
     * @param  mixed $id proposal id
     * @return array
     */
    public function get_comments($id, $playground = false) {
        $this->db->where('proposalid', $id);
        $this->db->order_by('dateadded', 'ASC');
        return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'proposal_comments')->result_array();
    }

    /**
     * Get proposal single comment
     * @param  mixed $id  comment id
     * @return object
     */
    public function get_comment($id, $playground = false) {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'proposal_comments')->row();
    }

    /**
     * Remove proposal comment
     * @param  mixed $id comment id
     * @return boolean
     */
    public function remove_comment($id, $playground = false) {
        $comment = $this->get_comment($id, $playground);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'proposal_comments');
        if ($this->db->affected_rows() > 0) {
            log_activity('Proposal Comment Removed [ProposalID:' . $comment->proposalid . ', Comment Content: ' . $comment->content . ']');
            return true;
        }
        return false;
    }

    /**
     * Copy proposal
     * @param  mixed $id proposal id
     * @return mixed
     */
    public function copy($id, $playground = false) {
        $this->copy = true;
        $proposal = $this->get($id, [], true, $playground);
        $not_copy_fields = ['addedfrom', 'id', 'datecreated', 'hash', 'status', 'invoice_id', 'estimate_id', 'is_expiry_notified', 'date_converted', 'signature', 'acceptance_firstname', 'acceptance_lastname', 'acceptance_email', 'acceptance_date', 'acceptance_ip', ];
        $fields = $this->db->list_fields(db_prefix() . ($playground ? 'playground_' : '') . 'proposals');
        $insert_data = [];
        foreach ($fields as $field) {
            if (!in_array($field, $not_copy_fields)) {
                $insert_data[$field] = $proposal->$field;
            }
        }
        $insert_data['addedfrom'] = get_staff_user_id();
        $insert_data['datecreated'] = date('Y-m-d H:i:s');
        $insert_data['date'] = _d(date('Y-m-d'));
        $insert_data['status'] = 6;
        $insert_data['hash'] = app_generate_hash();
        // in case open till is expired set new 7 days starting from current date
        if ($insert_data['open_till'] && get_option('proposal_due_after') != 0) {
            $insert_data['open_till'] = _d(date('Y-m-d', strtotime('+' . get_option('proposal_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        } else if ($insert_data['open_till']) {
            $dDate = new DateTime(date('Y-m-d'));
            $dOpenTill = new DateTime($insert_data['open_till']);
            $dDiff = $dDate->diff($dOpenTill);
            $insert_data['open_till'] = _d($dDate->modify('+ ' . $dDiff->days . ' DAY')->format('Y-m-d'));
        }
        $insert_data['newitems'] = [];
        $custom_fields_model = new Custom_fields_model();
        $custom_fields_items = $custom_fields_model->get_custom_fields('items', [], false, $playground);
        $key = 1;
        foreach ($proposal->items as $item) {
            $insert_data['newitems'][$key]['description'] = $item['description'];
            $insert_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $insert_data['newitems'][$key]['qty'] = $item['qty'];
            $insert_data['newitems'][$key]['unit'] = $item['unit'];
            $insert_data['newitems'][$key]['taxname'] = [];
            $taxes = $this->get_proposal_item_taxes($item['id'], $playground);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($insert_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $insert_data['newitems'][$key]['rate'] = $item['rate'];
            $insert_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $insert_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = $custom_fields_model->get_custom_field_value($item['id'], $cf['id'], 'items', false, $playground);
                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($insert_data, $playground);
        if ($id) {
            $custom_fields_model = new Custom_fields_model();
            $custom_fields = $custom_fields_model->get_custom_fields('proposal', [], false, $playground);
            foreach ($custom_fields as $field) {
                $value = $custom_fields_model->get_custom_field_value($proposal->id, $field['id'], 'proposal', false, $playground);
                if ($value == '') {
                    continue;
                }
                $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'customfieldsvalues', ['relid' => $id, 'fieldid' => $field['id'], 'fieldto' => 'proposal', 'value' => $value, ]);
            }
            $tags = get_tags_in($proposal->id, 'proposal', $playground);
            $misc_model = new Misc_model();
            $misc_model->handle_tags_save($tags, $id, 'proposal', $playground);
            log_activity('Copied Proposal ' . format_proposal_number($proposal->id));
            return $id;
        }
        return false;
    }

    /**
     * Take proposal action (change status) manually
     * @param  mixed $status status id
     * @param  mixed  $id     proposal id
     * @param  boolean $client is request coming from client side or not
     * @return boolean
     */
    public function mark_action_status($status, $id, $client = false, $playground = false) {
        $original_proposal = $this->get($id, false, [], $playground);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', ['status' => $status, ]);
        if ($this->db->affected_rows() > 0) {
            // Client take action
            if ($client == true) {
                $revert = false;
                // Declined
                if ($status == 2) {
                    $message = 'not_proposal_proposal_declined';
                } else if ($status == 3) {
                    // Accepted
                    if (get_option('proposal_auto_convert_to_invoice_on_client_accept') == '1') {
                        $this->convert_to_invoice($id, $playground);
                    }
                    $message = 'not_proposal_proposal_accepted';
                } else {
                    $revert = true;
                }
                // This is protection that only 3 and 4 statuses can be taken as action from the client side
                if ($revert == true) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', ['status' => $original_proposal->status, ]);
                    return false;
                }
                // Get creator and assigned;
                $this->db->where('staffid', $original_proposal->addedfrom);
                $this->db->or_where('staffid', $original_proposal->assigned);
                $staff_proposal = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'staff')->result_array();
                $notifiedUsers = [];
                foreach ($staff_proposal as $member) {
                    $notified = add_notification(['fromcompany' => true, 'touserid' => $member['staffid'], 'description' => $message, 'link' => 'proposals/list_proposals/' . $id, 'additional_data' => serialize([format_proposal_number($id), ]), ]);
                    if ($notified) {
                        array_push($notifiedUsers, $member['staffid']);
                    }
                }
                pusher_trigger_notification($notifiedUsers);
                // Send thank you to the customer email template
                if ($status == 3) {
                    foreach ($staff_proposal as $member) {
                        send_mail_template('proposal_accepted_to_staff', $original_proposal, $member['email']);
                    }
                    send_mail_template('proposal_accepted_to_customer', $original_proposal);
                    hooks()->do_action('proposal_accepted', $id);
                } else {
                    // Client declined send template to admin
                    foreach ($staff_proposal as $member) {
                        send_mail_template('proposal_declined_to_staff', $original_proposal, $member['email']);
                    }
                    hooks()->do_action('proposal_declined', $id);
                }
            } else {
                // in case admin mark as open the the open till date is smaller then current date set open till date 7 days more
                if ((date('Y-m-d', strtotime($original_proposal->open_till)) < date('Y-m-d')) && $status == 1) {
                    $open_till = date('Y-m-d', strtotime('+7 DAY', strtotime(date('Y-m-d'))));
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', ['open_till' => $open_till, ]);
                }
            }
            log_activity('Proposal Status Changes [ProposalID:' . $id . ', Status:' . format_proposal_status($status, '', false) . ',Client Action: ' . (int)$client . ']');
            return true;
        }
        return false;
    }

    /**
     * Delete proposal
     * @param  mixed $id proposal id
     * @return boolean
     */
    public function delete($id, $playground = false) {
        hooks()->do_action('before_proposal_deleted', $id);
        $this->clear_signature($id, $playground);
        $proposal = $this->get($id, [], false, $playground);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'proposals');
        if ($this->db->affected_rows() > 0) {
            if (!is_null($proposal->short_link)) {
                app_archive_short_link($proposal->short_link, $playground);
            }
            $misc_model = new Misc_model();
            $misc_model->delete_tracked_emails($id, 'proposal', $playground);
            $this->db->where('proposalid', $id);
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'proposal_comments');
            // Get related tasks
            $this->db->where('rel_type', 'proposal');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'tasks')->result_array();
            $tasks_model = new Tasks_model();
            foreach ($tasks as $task) {
                $tasks_model->delete_task($task['id'], true, $playground);
            }
            $attachments = $this->get_attachments($id, $playground);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id'], $playground);
            }
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'proposal');
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'notes');
            $this->db->where('relid IN (SELECT id from ' . db_prefix() . ($playground ? 'playground_' : '') . 'itemable WHERE rel_type="proposal" AND rel_id="' . $this->db->escape_str($id) . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'customfieldsvalues');
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'proposal');
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'itemable');
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'proposal');
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'item_tax');
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'proposal');
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'taggables');
            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'proposal');
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'customfieldsvalues');
            $this->db->where('rel_type', 'proposal');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'reminders');
            $this->db->where('rel_type', 'proposal');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'views_tracking');
            log_activity('Proposal Deleted [ProposalID:' . $id . ']');
            hooks()->do_action('after_proposal_deleted', $id);
            return true;
        }
        return false;
    }

    /**
     * Get relation proposal data. Ex lead or customer will return the necesary db fields
     * @param  mixed $rel_id
     * @param  string $rel_type customer/lead
     * @return object
     */
    public function get_relation_data_values($rel_id, $rel_type, $playground = false) {
        $data = new StdClass();
        if ($rel_type == 'customer') {
            $clients_model = new Clients_model();
            $this->db->where('userid', $rel_id);
            $_data = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'clients')->row();
            $primary_contact_id = $clients_model->get_primary_contact_user_id($rel_id, $playground);
            if ($primary_contact_id) {
                $contact = $clients_model->get_contact($primary_contact_id, ['active' => 1], [], $playground);
                $data->email = $contact->email;
            }
            $data->phone = $_data->phonenumber;
            $data->is_using_company = false;
            if (isset($contact)) {
                $data->to = $contact->firstname . ' ' . $contact->lastname;
            } else {
                if (!empty($_data->company)) {
                    $data->to = $_data->company;
                    $data->is_using_company = true;
                }
            }
            $data->company = $_data->company;
            $data->address = clear_textarea_breaks($_data->address, $playground);
            $data->zip = $_data->zip;
            $data->country = $_data->country;
            $data->state = $_data->state;
            $data->city = $_data->city;
            $default_currency = $clients_model->get_customer_default_currency($rel_id);
            if ($default_currency != 0) {
                $data->currency = $default_currency;
            }
        } else if ($rel_type = 'lead') {
            $this->db->where('id', $rel_id);
            $_data = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads')->row();
            $data->phone = $_data->phonenumber;
            $data->is_using_company = false;
            if (empty($_data->company)) {
                $data->to = $_data->name;
            } else {
                $data->to = $_data->company;
                $data->is_using_company = true;
            }
            $data->company = $_data->company;
            $data->address = $_data->address;
            $data->email = $_data->email;
            $data->zip = $_data->zip;
            $data->country = $_data->country;
            $data->state = $_data->state;
            $data->city = $_data->city;
        }
        return $data;
    }

    /**
     * Sent proposal to email
     * @param  mixed  $id        proposalid
     * @param  string  $template  email template to sent
     * @param  boolean $attachpdf attach proposal pdf or not
     * @return boolean
     */
    public function send_expiry_reminder($id, $playground = false) {
        $proposal = $this->get($id, [], false, $playground);
        // For all cases update this to prevent sending multiple reminders eq on fail
        $this->db->where('id', $proposal->id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', ['is_expiry_notified' => 1, ]);
        $template = mail_template('proposal_expiration_reminder', $proposal);
        $merge_fields = $template->get_merge_fields();
        $template->send();
        if (can_send_sms_based_on_creation_date($proposal->datecreated, $playground)) {
            $sms_sent = $this->app_sms->trigger(SMS_TRIGGER_PROPOSAL_EXP_REMINDER, $proposal->phone, $merge_fields);
        }
        hooks()->do_action('after_proposal_expiry_reminder_sent', $id);
        return true;
    }

    public function send_proposal_to_email($id, $attachpdf = true, $cc = '', $playground = false) {
        // Proposal status is draft update to sent
        if (total_rows(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', ['id' => $id, 'status' => 6]) > 0) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', ['status' => 4]);
        }
        $proposal = $this->get($id, [], false, $playground);
        $sent = send_mail_template('proposal_send_to_customer', $proposal, $attachpdf, $cc, $playground);
        if ($sent) {
            // Set to status sent
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', ['status' => 4, ]);
            hooks()->do_action('proposal_sent', $id);
            return true;
        }
        return false;
    }

    public function do_kanban_query($status, $search = '', $page = 1, $sort = [], $count = false, $playground = false) {
        _deprecated_function('Proposal_model::do_kanban_query', '2.9.2', 'ProposalsPipeline class');
        $kanBan = (new ProposalsPipeline($status))->search($search)->page($page)->sortBy($sort['sort']??null, $sort['sort_by']??null);
        if ($count) {
            return $kanBan->countAll();
        }
        return $kanBan->get();
    }

    /**
     * Convert proposal to invoice
     * @param mixed $id proposal id
     * @return mixed     New invoice ID
     */
    public function convert_to_invoice($id, $playground = false) {
        // Recurring invoice date is okey lets convert it to new invoice
        $proposal = $this->get($id, [], false, $playground);
        if ($proposal->rel_type != 'customer') {
            return false;
        }
        $new_invoice_data = [];
        $new_invoice_data['clientid'] = $proposal->rel_id;
        $new_invoice_data['project_id'] = $proposal->project_id;
        $new_invoice_data['number'] = get_option('next_invoice_number');
        $new_invoice_data['date'] = _d(date('Y-m-d'));
        $new_invoice_data['duedate'] = _d(date('Y-m-d'));
        if (get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }
        $new_invoice_data['show_quantity_as'] = $proposal->show_quantity_as;
        $new_invoice_data['currency'] = $proposal->currency;
        $new_invoice_data['subtotal'] = $proposal->subtotal;
        $new_invoice_data['total'] = $proposal->total;
        $new_invoice_data['adjustment'] = $proposal->adjustment;
        $new_invoice_data['discount_percent'] = $proposal->discount_percent;
        $new_invoice_data['discount_total'] = $proposal->discount_total;
        $new_invoice_data['discount_type'] = $proposal->discount_type;
        $new_invoice_data['sale_agent'] = $proposal->assigned;
        $new_invoice_data['billing_street'] = clear_textarea_breaks($proposal->address);
        $new_invoice_data['billing_city'] = $proposal->city;
        $new_invoice_data['billing_state'] = $proposal->state;
        $new_invoice_data['billing_zip'] = $proposal->zip;
        $new_invoice_data['billing_country'] = $proposal->country;
        $new_invoice_data['shipping_street'] = '';
        $new_invoice_data['shipping_city'] = '';
        $new_invoice_data['shipping_state'] = '';
        $new_invoice_data['shipping_zip'] = '';
        $new_invoice_data['shipping_country'] = '';
        $new_invoice_data['include_shipping'] = 0;
        $new_invoice_data['show_shipping_on_invoice'] = 0;
        $new_invoice_data['terms'] = get_option('predefined_terms_invoice');
        $new_invoice_data['clientnote'] = get_option('predefined_clientnote_invoice');
        // Set to unpaid status automatically
        $new_invoice_data['status'] = 1;
        $new_invoice_data['adminnote'] = '';
        $payment_modes_model = new Payment_modes_model();
        $modes = $payment_modes_model->get('', ['expenses_only !=' => 1, ]);
        $temp_modes = [];
        foreach ($modes as $mode) {
            if ($mode['selected_by_default'] == 0) {
                continue;
            }
            $temp_modes[] = $mode['id'];
        }
        $new_invoice_data['allowed_payment_modes'] = $temp_modes;
        $new_invoice_data['newitems'] = [];
        $key = 1;
        foreach ($proposal->items as $item) {
            $new_invoice_data['newitems'][$key]['description'] = $item['description'];
            $new_invoice_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_invoice_data['newitems'][$key]['qty'] = $item['qty'];
            $new_invoice_data['newitems'][$key]['unit'] = $item['unit'];
            $new_invoice_data['newitems'][$key]['taxname'] = [];
            $taxes = $this->get_proposal_item_taxes($item['id'], $playground);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_invoice_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_invoice_data['newitems'][$key]['rate'] = $item['rate'];
            $new_invoice_data['newitems'][$key]['order'] = $item['item_order'];
            $key++;
        }
        $invoices_model = new Invoices_model();
        $invoice_id = $invoices_model->add($new_invoice_data, $playground);
        if ($invoice_id) {
            // Customer accepted the estimate and is auto converted to invoice
            if (!is_staff_logged_in()) {
                $this->db->where('rel_type', 'invoice');
                $this->db->where('rel_id', $invoice_id);
                $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'sales_activity');
                $invoices_model->log_invoice_activity($id, 'invoice_activity_auto_converted_from_proposal', true, serialize(['<a href="' . admin_url('proposals#' . $proposal->id) . '">' . format_proposal_number($proposal->id) . '</a>', ]), $playground);
            }
            // For all cases update addefrom and sale agent from the invoice
            // May happen staff is not logged in and these values to be 0
            $this->db->where('id', $invoice_id);
            $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'invoices', ['addedfrom' => $proposal->addedfrom, 'sale_agent' => $proposal->assigned, ]);
            // Update estimate with the new invoice data and set to status accepted
            $this->db->where('id', $proposal->id);
            $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'proposals', ['invoice_id' => $invoice_id, 'status' => 3, ]);
            if (is_custom_fields_smart_transfer_enabled()) {
                $this->db->where('fieldto', 'proposal');
                $this->db->where('active', 1);
                $cfProposals = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'customfields')->result_array();
                foreach ($cfProposals as $field) {
                    $tmpSlug = explode('_', $field['slug'], 2);
                    if (isset($tmpSlug[1])) {
                        $this->db->where('fieldto', 'invoice');
                        $this->db->group_start();
                        $this->db->like('slug', 'invoice_' . $tmpSlug[1], 'after');
                        $this->db->where('type', $field['type']);
                        $this->db->where('options', $field['options']);
                        $this->db->where('active', 1);
                        $this->db->group_end();
                        // $this->db->where('slug LIKE "invoice_' . $tmpSlug[1] . '%" AND type="' . $field['type'] . '" AND options="' . $field['options'] . '" AND active=1');
                        $cfTransfer = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'customfields')->result_array();
                        // Don't make mistakes
                        // Only valid if 1 result returned
                        // + if field names similarity is equal or more then CUSTOM_FIELD_TRANSFER_SIMILARITY%
                        if (count($cfTransfer) == 1 && ((similarity($field['name'], $cfTransfer[0]['name']) * 100) >= CUSTOM_FIELD_TRANSFER_SIMILARITY)) {
                            $value = get_custom_field_value($proposal->id, $field['id'], 'estimate', false, $playground);
                            if ($value == '') {
                                continue;
                            }
                            $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'customfieldsvalues', ['relid' => $id, 'fieldid' => $cfTransfer[0]['id'], 'fieldto' => 'invoice', 'value' => $value, ]);
                        }
                    }
                }
            }
            hooks()->do_action('after_proposal_converted_to_invoice', ['proposal_id' => $id, 'invoice_id' => $invoice_id]);
            log_activity('Proposal Converted to Invoice [InvoiceID: ' . $invoice_id . ', ProposalID: ' . $id . ']');
        }
        return $id;
    }
    
    /**
    * Function that return proposal item taxes based on passed item id
    * @param  mixed $itemid
    * @return array
    */
    public function get_proposal_item_taxes($itemid, $playground = false)
    {
        $CI = &get_instance();
        $CI->db->where('itemid', $itemid);
        $CI->db->where('rel_type', 'proposal');
        $taxes = $CI->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'item_tax')->result_array();
        $i     = 0;
        foreach ($taxes as $tax) {
            $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
            $i++;
        }

        return $taxes;
    }
}
