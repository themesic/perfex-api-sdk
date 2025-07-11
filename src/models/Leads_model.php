<?php

namespace PerfexApiSdk\Models;

use app\services\AbstractKanban;

use PerfexApiSdk\Models\Custom_fields_model;
use PerfexApiSdk\Models\Proposals_model;
use PerfexApiSdk\Models\Staff_model;
use PerfexApiSdk\Models\Misc_model;

require_once(APPPATH . 'core/App_Model.php');

defined('BASEPATH') or exit('No direct script access allowed');

class Leads_model extends \App_Model {
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get lead
     * @param  string $id Optional - leadid
     * @return mixed
     */
    public function get($id = '', $where = [], $playground = false) {
        $this->db->select('*,' . db_prefix() . ($playground ? 'playground_' : '') . 'leads.name, ' . db_prefix() . ($playground ? 'playground_' : '') . 'leads.id,' . db_prefix() . ($playground ? 'playground_' : '') . 'leads_status.name as status_name,' . db_prefix() . ($playground ? 'playground_' : '') . 'leads_sources.name as source_name');
        $this->db->join(db_prefix() . ($playground ? 'playground_' : '') . 'leads_status', db_prefix() . ($playground ? 'playground_' : '') . 'leads_status.id=' . db_prefix() . ($playground ? 'playground_' : '') . 'leads.status', 'left');
        $this->db->join(db_prefix() . ($playground ? 'playground_' : '') . 'leads_sources', db_prefix() . ($playground ? 'playground_' : '') . 'leads_sources.id=' . db_prefix() . ($playground ? 'playground_' : '') . 'leads.source', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . ($playground ? 'playground_' : '') . 'leads.id', $id);
            $lead = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads')->row();
            if ($lead) {
                if ($lead->from_form_id != 0) {
                    $lead->form_data = $this->get_form(['id' => $lead->from_form_id, ]);
                }
                $lead->attachments = $this->get_lead_attachments($id, '', [], $playground);
                $lead->public_url = leads_public_url($id, $playground);
            }
            return $lead;
        }
        return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads')->result_array();
    }

    /**
     * Get lead by given email
     *
     * @since 2.8.0
     *
     * @param  string $email
     *
     * @return \strClass|null
     */
    public function get_lead_by_email($email, $playground = false) {
        $this->db->where('email', $email);
        $this->db->limit(1);
        return $this->db->get(($playground ? 'playground_' : '') . 'leads')->row();
    }

    /**
     * Add new lead to database
     * @param mixed $data lead data
     * @return mixed false || leadid
     */
    public function add($data, $playground = false) {
        if (isset($data['custom_contact_date']) || isset($data['custom_contact_date'])) {
            if (isset($data['contacted_today'])) {
                $data['lastcontact'] = date('Y-m-d H:i:s');
                unset($data['contacted_today']);
            } else {
                $data['lastcontact'] = to_sql_date($data['custom_contact_date'], true);
            }
        }
        if (isset($data['is_public']) && ($data['is_public'] == 1 || $data['is_public'] === 'on')) {
            $data['is_public'] = 1;
        } else {
            $data['is_public'] = 0;
        }
        if (!isset($data['country']) || isset($data['country']) && $data['country'] == '') {
            $data['country'] = 0;
        }
        if (isset($data['custom_contact_date'])) {
            unset($data['custom_contact_date']);
        }
        $data['description'] = nl2br($data['description']);
        $data['dateadded'] = date('Y-m-d H:i:s');
        $data['addedfrom'] = get_staff_user_id();
        $data = hooks()->apply_filters('before_lead_added', $data);
        $tags = '';
        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }
        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);
        $data['email'] = trim($data['email']);
        $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'leads', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Lead Added [ID: ' . $insert_id . ']');
            $this->log_lead_activity($insert_id, 'not_lead_activity_created', false, '', $playground);
            $misc_model = new Misc_model(); 
            $misc_model->handle_tags_save($tags, $insert_id, 'lead');
            if (isset($custom_fields)) {
                $custom_fields_model = new Custom_fields_model();
                $custom_fields_model->handle_custom_fields_post($insert_id, $custom_fields, false, $playground);
            }
            $this->lead_assigned_member_notification($insert_id, $data['assigned'], $playground);
            hooks()->do_action('lead_created', $insert_id);
            return $insert_id;
        }
        return false;
    }

    public function lead_assigned_member_notification($lead_id, $assigned, $integration = false, $playground = false) {
        if (empty($assigned) || $assigned == 0) {
            return;
        }
        if ($integration == false) {
            if ($assigned == get_staff_user_id()) {
                return false;
            }
        }
        $name = $this->db->select('name')->from(db_prefix() . ($playground ? 'playground_' : '') . 'leads')->where('id', $lead_id)->get()->row()->name;
        $notification_data = ['description' => ($integration == false) ? 'not_assigned_lead_to_you' : 'not_lead_assigned_from_form', 'touserid' => $assigned, 'link' => '#leadid=' . $lead_id, 'additional_data' => ($integration == false ? serialize([$name, ]) : serialize([])), ];
        if ($integration != false) {
            $notification_data['fromcompany'] = 1;
        }
        if (add_notification($notification_data)) {
            pusher_trigger_notification([$assigned]);
        }
        $this->db->select('email');
        $this->db->where('staffid', $assigned);
        $email = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'staff')->row()->email;
        send_mail_template('lead_assigned', $lead_id, $email);
        $this->db->where('id', $lead_id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['dateassigned' => date('Y-m-d'), ]);
        $staff_model = new Staff_model();
        $not_additional_data = [e($staff_model->get_staff_full_name('', $playground)), '<a href="' . admin_url('profile/' . $assigned) . '" target="_blank">' . e($staff_model->get_staff_full_name($assigned, $playground)) . '</a>', ];
        if ($integration == true) {
            unset($not_additional_data[0]);
            array_values(($not_additional_data));
        }
        $not_additional_data = serialize($not_additional_data);
        $not_desc = ($integration == false ? 'not_lead_activity_assigned_to' : 'not_lead_activity_assigned_from_form');
        $this->log_lead_activity($lead_id, $not_desc, $integration, $not_additional_data, $playground);
        hooks()->do_action('after_lead_assigned_member_notification_sent', $lead_id);
    }

    /**
     * Update lead
     * @param  array $data lead data
     * @param  mixed $id   leadid
     * @return boolean
     */
    public function update($data, $id, $playground = false) {
        $current_lead_data = $this->get($id);
        $current_status = $this->get_status($current_lead_data->status, $playground);
        if ($current_status) {
            $current_status_id = $current_status->id;
            $current_status = $current_status->name;
        } else {
            if ($current_lead_data->junk == 1) {
                $current_status = _l('lead_junk');
            } else if ($current_lead_data->lost == 1) {
                $current_status = _l('lead_lost');
            } else {
                $current_status = '';
            }
            $current_status_id = 0;
        }
        $affectedRows = 0;
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            $custom_fields_model = new Custom_fields_model();
            if ($custom_fields_model->handle_custom_fields_post($id, $custom_fields, false, $playground)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }
        if (!defined('API')) {
            if (isset($data['is_public'])) {
                $data['is_public'] = 1;
            } else {
                $data['is_public'] = 0;
            }
            if (!isset($data['country']) || isset($data['country']) && $data['country'] == '') {
                $data['country'] = 0;
            }
            if (isset($data['description'])) {
                $data['description'] = nl2br($data['description']);
            }
        }
        if (isset($data['lastcontact']) && $data['lastcontact'] == '' || isset($data['lastcontact']) && $data['lastcontact'] == null) {
            $data['lastcontact'] = null;
        } else if (isset($data['lastcontact'])) {
            $data['lastcontact'] = to_sql_date($data['lastcontact'], true);
        }
        if (isset($data['tags'])) {
            $misc_model = new Misc_model();
            if ($misc_model->handle_tags_save($data['tags'], $id, 'lead')) {
                $affectedRows++;
            }
            unset($data['tags']);
        }
        if (isset($data['remove_attachments'])) {
            foreach ($data['remove_attachments'] as $key => $val) {
                $attachment = $this->get_lead_attachments($id, $key, [], $playground);
                if ($attachment) {
                    $this->delete_lead_attachment($attachment->id, $playground);
                }
            }
            unset($data['remove_attachments']);
        }
        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);
        $data['email'] = trim($data['email']);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if (isset($data['status']) && $current_status_id != $data['status']) {
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['last_status_change' => date('Y-m-d H:i:s'), ]);
                $new_status_name = $this->get_status($data['status'])->name;
                $staff_model = new Staff_model();
                $this->log_lead_activity($id, 'not_lead_activity_status_updated', false, serialize([$staff_model->get_staff_full_name('', $playground), $current_status, $new_status_name, ]), $playground);
                hooks()->do_action('lead_status_changed', ['lead_id' => $id, 'old_status' => $current_status_id, 'new_status' => $data['status'], ]);
            }
            if (($current_lead_data->junk == 1 || $current_lead_data->lost == 1) && $data['status'] != 0) {
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['junk' => 0, 'lost' => 0, ]);
            }
            if (isset($data['assigned'])) {
                if ($current_lead_data->assigned != $data['assigned'] && (!empty($data['assigned']) && $data['assigned'] != 0)) {
                    $this->lead_assigned_member_notification($id, $data['assigned'], $playground);
                }
            }
            log_activity('Lead Updated [ID: ' . $id . ']');
            hooks()->do_action('after_lead_updated', $id);
            return true;
        }
        if ($affectedRows > 0) {
            hooks()->do_action('after_lead_updated', $id);
            return true;
        }
        return false;
    }

    /**
     * Delete lead from database and all connections
     * @param  mixed $id leadid
     * @return boolean
     */
    public function delete($id, $playground = false) {
        $affectedRows = 0;
        hooks()->do_action('before_lead_deleted', $id);
        $lead = $this->get($id);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'leads');
        if ($this->db->affected_rows() > 0) {
            $staff_model = new Staff_model();
            log_activity('Lead Deleted [Deleted by: ' . $staff_model->get_staff_full_name('', $playground) . ', ID: ' . $id . ']');
            $attachments = $this->get_lead_attachments($id, '', [], $playground);
            foreach ($attachments as $attachment) {
                $this->delete_lead_attachment($attachment['id'], $playground);
            }
            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'leads');
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'customfieldsvalues');
            $this->db->where('leadid', $id);
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'lead_activity_log');
            $this->db->where('leadid', $id);
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'lead_integration_emails');
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'lead');
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'notes');
            $this->db->where('rel_type', 'lead');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'reminders');
            $this->db->where('rel_type', 'lead');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'taggables');
            $proposals_model = new Proposals_model();
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'lead');
            $proposals = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'proposals')->result_array();
            foreach ($proposals as $proposal) {
                $proposals_model->delete($proposal['id'], $playground);
            }
            // Get related tasks
            $this->db->where('rel_type', 'lead');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id'], true, $playground);
            }
            if (is_gdpr()) {
                $this->db->where('(description LIKE "%' . $lead->email . '%" OR description LIKE "%' . $lead->name . '%" OR description LIKE "%' . $lead->phonenumber . '%")');
                $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'activity_log');
            }
            $affectedRows++;
        }
        if ($affectedRows > 0) {
            hooks()->do_action('after_lead_deleted', $id);
            return true;
        }
        return false;
    }

    /**
     * Mark lead as lost
     * @param  mixed $id lead id
     * @return boolean
     */
    public function mark_as_lost($id, $playground = false) {
        $this->db->select('status');
        $this->db->from(db_prefix() . ($playground ? 'playground_' : '') . 'leads');
        $this->db->where('id', $id);
        $last_lead_status = $this->db->get()->row()->status;
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['lost' => 1, 'status' => 0, 'last_status_change' => date('Y-m-d H:i:s'), 'last_lead_status' => $last_lead_status, ]);
        if ($this->db->affected_rows() > 0) {
            $this->log_lead_activity($id, 'not_lead_activity_marked_lost', false, '', $playground);
            log_activity('Lead Marked as Lost [ID: ' . $id . ']');
            hooks()->do_action('lead_marked_as_lost', $id);
            return true;
        }
        return false;
    }

    /**
     * Unmark lead as lost
     * @param  mixed $id leadid
     * @return boolean
     */
    public function unmark_as_lost($id, $playground = false) {
        $this->db->select('last_lead_status');
        $this->db->from(db_prefix() . ($playground ? 'playground_' : '') . 'leads');
        $this->db->where('id', $id);
        $last_lead_status = $this->db->get()->row()->last_lead_status;
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['lost' => 0, 'status' => $last_lead_status, ]);
        if ($this->db->affected_rows() > 0) {
            $this->log_lead_activity($id, 'not_lead_activity_unmarked_lost', false, '', $playground);
            log_activity('Lead Unmarked as Lost [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    /**
     * Mark lead as junk
     * @param  mixed $id lead id
     * @return boolean
     */
    public function mark_as_junk($id, $playground = false) {
        $this->db->select('status');
        $this->db->from(db_prefix() . ($playground ? 'playground_' : '') . 'leads');
        $this->db->where('id', $id);
        $last_lead_status = $this->db->get()->row()->status;
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['junk' => 1, 'status' => 0, 'last_status_change' => date('Y-m-d H:i:s'), 'last_lead_status' => $last_lead_status, ]);
        if ($this->db->affected_rows() > 0) {
            $this->log_lead_activity($id, 'not_lead_activity_marked_junk', false, '', $playground);
            log_activity('Lead Marked as Junk [ID: ' . $id . ']');
            hooks()->do_action('lead_marked_as_junk', $id);
            return true;
        }
        return false;
    }

    /**
     * Unmark lead as junk
     * @param  mixed $id leadid
     * @return boolean
     */
    public function unmark_as_junk($id, $playground = false) {
        $this->db->select('last_lead_status');
        $this->db->from(db_prefix() . ($playground ? 'playground_' : '') . 'leads');
        $this->db->where('id', $id);
        $last_lead_status = $this->db->get()->row()->last_lead_status;
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['junk' => 0, 'status' => $last_lead_status, ]);
        if ($this->db->affected_rows() > 0) {
            $this->log_lead_activity($id, 'not_lead_activity_unmarked_junk', false, '', $playground);
            log_activity('Lead Unmarked as Junk [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    /**
     * Get lead attachments
     * @since Version 1.0.4
     * @param  mixed $id lead id
     * @return array
     */
    public function get_lead_attachments($id = '', $attachment_id = '', $where = [], $playground = false) {
        $this->db->where($where);
        $idIsHash = !is_numeric($attachment_id) && strlen($attachment_id) == 32;
        if (is_numeric($attachment_id) || $idIsHash) {
            $this->db->where($idIsHash ? 'attachment_key' : 'id', $attachment_id);
            return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'files')->row();
        }
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'lead');
        $this->db->order_by('dateadded', 'DESC');
        return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'files')->result_array();
    }

    public function add_attachment_to_database($lead_id, $attachment, $external = false, $form_activity = false, $playground = false) {
        $this->misc_model->add_attachment_to_database($lead_id, 'lead', $attachment, $external, $playground);
        if ($form_activity == false) {
            $this->leads_model->log_lead_activity($lead_id, 'not_lead_activity_added_attachment', false, '', $playground);
        } else {
            $this->leads_model->log_lead_activity($lead_id, 'not_lead_activity_log_attachment', true, serialize([$form_activity, ], $playground));
        }
        // No notification when attachment is imported from web to lead form
        if ($form_activity == false) {
            $lead = $this->get($lead_id);
            $not_user_ids = [];
            if ($lead->addedfrom != get_staff_user_id()) {
                array_push($not_user_ids, $lead->addedfrom);
            }
            if ($lead->assigned != get_staff_user_id() && $lead->assigned != 0) {
                array_push($not_user_ids, $lead->assigned);
            }
            $notifiedUsers = [];
            foreach ($not_user_ids as $uid) {
                $notified = add_notification(['description' => 'not_lead_added_attachment', 'touserid' => $uid, 'link' => '#leadid=' . $lead_id, 'additional_data' => serialize([$lead->name, ]), ]);
                if ($notified) {
                    array_push($notifiedUsers, $uid);
                }
            }
            pusher_trigger_notification($notifiedUsers);
        }
    }

    /**
     * Delete lead attachment
     * @param  mixed $id attachment id
     * @return boolean
     */
    public function delete_lead_attachment($id, $playground = false) {
        $attachment = $this->get_lead_attachments('', $id, [], $playground);
        $deleted = false;
        $misc_model = new Misc_model();
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink($misc_model->get_upload_path_by_type('lead', $playground) . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Lead Attachment Deleted [ID: ' . $attachment->rel_id . ']');
            }
            if (is_dir($misc_model->get_upload_path_by_type('lead', $playground) . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files($misc_model->get_upload_path_by_type('lead', $playground) . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir($misc_model->get_upload_path_by_type('lead', $playground) . $attachment->rel_id);
                }
            }
        }
        return $deleted;
    }

    // Sources
    
    /**
     * Get leads sources
     * @param  mixed $id Optional - Source ID
     * @return mixed object if id passed else array
     */
    public function get_source($id = false, $playground = false) {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads_sources')->row();
        }
        $this->db->order_by('name', 'asc');
        return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads_sources')->result_array();
    }

    /**
     * Add new lead source
     * @param mixed $data source data
     */
    public function add_source($data, $playground = false) {
        $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'leads_sources', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Leads Source Added [SourceID: ' . $insert_id . ', Name: ' . $data['name'] . ']');
        }
        return $insert_id;
    }

    /**
     * Update lead source
     * @param  mixed $data source data
     * @param  mixed $id   source id
     * @return boolean
     */
    public function update_source($data, $id) {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads_sources', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Leads Source Updated [SourceID: ' . $id . ', Name: ' . $data['name'] . ']');
            return true;
        }
        return false;
    }

    /**
     * Delete lead source from database
     * @param  mixed $id source id
     * @return mixed
     */
    public function delete_source($id, $playground = false) {
        $current = $this->get_source($id);
        // Check if is already using in table
        if (is_reference_in_table('source', db_prefix() . ($playground ? 'playground_' : '') . 'leads', $id) || is_reference_in_table('lead_source', db_prefix() . ($playground ? 'playground_' : '') . 'leads_email_integration', $id)) {
            return ['referenced' => true, ];
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'leads_sources');
        if ($this->db->affected_rows() > 0) {
            if (get_option('leads_default_source') == $id) {
                update_option('leads_default_source', '');
            }
            log_activity('Leads Source Deleted [SourceID: ' . $id . ']');
            return true;
        }
        return false;
    }

    // Statuses
    
    /**
     * Get lead statuses
     * @param  mixed $id status id
     * @return mixed      object if id passed else array
     */
    public function get_status($id = '', $where = [], $playground = false) {
        if (is_numeric($id)) {
            $this->db->where($where);
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads_status')->row();
        }
        $whereKey = md5(serialize($where));
        $statuses = $this->app_object_cache->get('leads-all-statuses-' . $whereKey);
        if (!$statuses) {
            $this->db->where($where);
            $this->db->order_by('statusorder', 'asc');
            $statuses = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads_status')->result_array();
            $this->app_object_cache->add('leads-all-statuses-' . $whereKey, $statuses);
        }
        return $statuses;
    }

    /**
     * Add new lead status
     * @param array $data lead status data
     */
    public function add_status($data, $playground = false) {
        if (isset($data['color']) && $data['color'] == '') {
            $data['color'] = hooks()->apply_filters('default_lead_status_color', '#757575');
        }
        if (!isset($data['statusorder'])) {
            $data['statusorder'] = total_rows(db_prefix() . ($playground ? 'playground_' : '') . 'leads_status') + 1;
        }
        $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'leads_status', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Leads Status Added [StatusID: ' . $insert_id . ', Name: ' . $data['name'] . ']');
            return $insert_id;
        }
        return false;
    }

    public function update_status($data, $id, $playground = false) {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads_status', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Leads Status Updated [StatusID: ' . $id . ', Name: ' . $data['name'] . ']');
            return true;
        }
        return false;
    }

    /**
     * Delete lead status from database
     * @param  mixed $id status id
     * @return boolean
     */
    public function delete_status($id, $playground = false) {
        $current = $this->get_status($id);
        // Check if is already using in table
        if (is_reference_in_table('status', db_prefix() . ($playground ? 'playground_' : '') . 'leads', $id) || is_reference_in_table('lead_status', db_prefix() . ($playground ? 'playground_' : '') . 'leads_email_integration', $id)) {
            return ['referenced' => true, ];
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'leads_status');
        if ($this->db->affected_rows() > 0) {
            if (get_option('leads_default_status') == $id) {
                update_option('leads_default_status', '');
            }
            log_activity('Leads Status Deleted [StatusID: ' . $id . ']');
            return true;
        }
        return false;
    }

    /**
     * Update canban lead status when drag and drop
     * @param  array $data lead data
     * @return boolean
     */
    public function update_lead_status($data, $playground = false) {
        $this->db->select('status');
        $this->db->where('id', $data['leadid']);
        $_old = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads')->row();
        $old_status = '';
        if ($_old) {
            $old_status = $this->get_status($_old->status, $playground);
            if ($old_status) {
                $old_status = $old_status->name;
            }
        }
        $affectedRows = 0;
        $current_status = $this->get_status($data['status'])->name;
        $this->db->where('id', $data['leadid']);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['status' => $data['status'], ]);
        $_log_message = '';
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if ($current_status != $old_status && $old_status != '') {
                $_log_message = 'not_lead_activity_status_updated';
                $staff_model = new Staff_model();
                $additional_data = serialize([$staff_model->get_staff_full_name('', $playground), $old_status, $current_status, ]);
                hooks()->do_action('lead_status_changed', ['lead_id' => $data['leadid'], 'old_status' => $old_status, 'new_status' => $current_status, ]);
            }
            $this->db->where('id', $data['leadid']);
            $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['last_status_change' => date('Y-m-d H:i:s'), ]);
        }
        if (isset($data['order'])) {
            AbstractKanban::updateOrder($data['order'], 'leadorder', 'leads', $data['status']);
        }
        if ($affectedRows > 0) {
            if ($_log_message == '') {
                return true;
            }
            $this->log_lead_activity($data['leadid'], $_log_message, false, $additional_data, $playground);
            return true;
        }
        return false;
    }
    /* Ajax */
    /**
     * All lead activity by staff
     * @param  mixed $id lead id
     * @return array
     */
    public function get_lead_activity_log($id, $playground = false) {
        $sorting = hooks()->apply_filters('lead_activity_log_default_sort', 'ASC');
        $this->db->where('leadid', $id);
        $this->db->order_by('date', $sorting);
        return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'lead_activity_log')->result_array();
    }

    public function staff_can_access_lead($id, $staff_id = '', $playground = false) {
        $staff_id = $staff_id == '' ? get_staff_user_id() : $staff_id;
        if (has_permission('leads', $staff_id, 'view')) {
            return true;
        }
        if (total_rows(db_prefix() . ($playground ? 'playground_' : '') . 'leads', 'id="' . $this->db->escape_str($id) . '" AND (assigned=' . $this->db->escape_str($staff_id) . ' OR is_public=1 OR addedfrom=' . $CI->db->escape_str($staff_id) . ')') > 0) {
            return true;
        }
        return false;
    }

    /**
     * Add lead activity from staff
     * @param  mixed  $id          lead id
     * @param  string  $description activity description
     */
    public function log_lead_activity($id, $description, $integration = false, $additional_data = '', $playground = false) {
        $staff_model = new Staff_model();
        $log = ['date' => date('Y-m-d H:i:s'), 'description' => $description, 'leadid' => $id, 'staffid' => get_staff_user_id(), 'additional_data' => $additional_data, 'full_name' => $staff_model->get_staff_full_name(get_staff_user_id(), $playground), ];
        if ($integration == true) {
            $log['staffid'] = 0;
            $log['full_name'] = '[CRON]';
        }
        $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'lead_activity_log', $log);
        return $this->db->insert_id();
    }

    /**
     * Get email integration config
     * @return object
     */
    public function get_email_integration($playground = false) {
        $this->db->where('id', 1);
        return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads_email_integration')->row();
    }

    /**
     * Get lead imported email activity
     * @param  mixed $id leadid
     * @return array
     */
    public function get_mail_activity($id, $playground = false) {
        $this->db->where('leadid', $id);
        $this->db->order_by('dateadded', 'asc');
        return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'lead_integration_emails')->result_array();
    }

    /**
     * Update email integration config
     * @param  mixed $data All $_POST data
     * @return boolean
     */
    public function update_email_integration($data, $playground = false) {
        $this->db->where('id', 1);
        $original_settings = $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'leads_email_integration')->row();
        $data['create_task_if_customer'] = isset($data['create_task_if_customer']) ? 1 : 0;
        $data['active'] = isset($data['active']) ? 1 : 0;
        $data['delete_after_import'] = isset($data['delete_after_import']) ? 1 : 0;
        $data['notify_lead_imported'] = isset($data['notify_lead_imported']) ? 1 : 0;
        $data['only_loop_on_unseen_emails'] = isset($data['only_loop_on_unseen_emails']) ? 1 : 0;
        $data['notify_lead_contact_more_times'] = isset($data['notify_lead_contact_more_times']) ? 1 : 0;
        $data['mark_public'] = isset($data['mark_public']) ? 1 : 0;
        $data['responsible'] = !isset($data['responsible']) ? 0 : $data['responsible'];
        if ($data['notify_lead_contact_more_times'] != 0 || $data['notify_lead_imported'] != 0) {
            if (isset($data['notify_type']) && $data['notify_type'] == 'specific_staff') {
                if (isset($data['notify_ids_staff'])) {
                    $data['notify_ids'] = serialize($data['notify_ids_staff']);
                    unset($data['notify_ids_staff']);
                } else {
                    $data['notify_ids'] = serialize([]);
                    unset($data['notify_ids_staff']);
                }
                if (isset($data['notify_ids_roles'])) {
                    unset($data['notify_ids_roles']);
                }
            } else {
                if (isset($data['notify_ids_roles'])) {
                    $data['notify_ids'] = serialize($data['notify_ids_roles']);
                    unset($data['notify_ids_roles']);
                } else {
                    $data['notify_ids'] = serialize([]);
                    unset($data['notify_ids_roles']);
                }
                if (isset($data['notify_ids_staff'])) {
                    unset($data['notify_ids_staff']);
                }
            }
        } else {
            $data['notify_ids'] = serialize([]);
            $data['notify_type'] = null;
            if (isset($data['notify_ids_staff'])) {
                unset($data['notify_ids_staff']);
            }
            if (isset($data['notify_ids_roles'])) {
                unset($data['notify_ids_roles']);
            }
        }
        // Check if not empty $data['password']
        // Get original
        // Decrypt original
        // Compare with $data['password']
        // If equal unset
        // If not encrypt and save
        if (!empty($data['password'])) {
            $or_decrypted = $this->encryption->decrypt($original_settings->password);
            if ($or_decrypted == $data['password']) {
                unset($data['password']);
            } else {
                $data['password'] = $this->encryption->encrypt($data['password']);
            }
        }
        $this->db->where('id', 1);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads_email_integration', $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }

    public function change_status_color($data, $playground = false) {
        $this->db->where('id', $data['status_id']);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads_status', ['color' => $data['color'], ]);
    }

    public function update_status_order($data, $playground = false) {
        foreach ($data['order'] as $status) {
            $this->db->where('id', $status[0]);
            $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads_status', ['statusorder' => $status[1], ]);
        }
    }

    public function get_form($where, $playground = false) {
        $this->db->where($where);
        return $this->db->get(db_prefix() . ($playground ? 'playground_' : '') . 'web_to_lead')->row();
    }

    public function add_form($data, $playground = false) {
        $data = $this->_do_lead_web_to_form_responsibles($data);
        $data['success_submit_msg'] = nl2br($data['success_submit_msg']);
        $data['form_key'] = app_generate_hash();
        $data['create_task_on_duplicate'] = (int)isset($data['create_task_on_duplicate']);
        $data['mark_public'] = (int)isset($data['mark_public']);
        if (isset($data['allow_duplicate'])) {
            $data['allow_duplicate'] = 1;
            $data['track_duplicate_field'] = '';
            $data['track_duplicate_field_and'] = '';
            $data['create_task_on_duplicate'] = 0;
        } else {
            $data['allow_duplicate'] = 0;
        }
        $data['dateadded'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . ($playground ? 'playground_' : '') . 'web_to_lead', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Web to Lead Form Added [' . $data['name'] . ']');
            return $insert_id;
        }
        return false;
    }

    public function update_form($id, $data, $playground = false) {
        $data = $this->_do_lead_web_to_form_responsibles($data, $playground);
        $data['success_submit_msg'] = nl2br($data['success_submit_msg']);
        $data['create_task_on_duplicate'] = (int)isset($data['create_task_on_duplicate']);
        $data['mark_public'] = (int)isset($data['mark_public']);
        if (isset($data['allow_duplicate'])) {
            $data['allow_duplicate'] = 1;
            $data['track_duplicate_field'] = '';
            $data['track_duplicate_field_and'] = '';
            $data['create_task_on_duplicate'] = 0;
        } else {
            $data['allow_duplicate'] = 0;
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'web_to_lead', $data);
        return ($this->db->affected_rows() > 0 ? true : false);
    }

    public function delete_form($id, $playground = false) {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . ($playground ? 'playground_' : '') . 'web_to_lead');
        $this->db->where('from_form_id', $id);
        $this->db->update(db_prefix() . ($playground ? 'playground_' : '') . 'leads', ['from_form_id' => 0, ]);
        if ($this->db->affected_rows() > 0) {
            log_activity('Lead Form Deleted [' . $id . ']');
            return true;
        }
        return false;
    }

    private function _do_lead_web_to_form_responsibles($data, $playground = false) {
        if (isset($data['notify_lead_imported'])) {
            $data['notify_lead_imported'] = 1;
        } else {
            $data['notify_lead_imported'] = 0;
        }
        if ($data['responsible'] == '') {
            $data['responsible'] = 0;
        }
        if ($data['notify_lead_imported'] != 0) {
            if ($data['notify_type'] == 'specific_staff') {
                if (isset($data['notify_ids_staff'])) {
                    $data['notify_ids'] = serialize($data['notify_ids_staff']);
                    unset($data['notify_ids_staff']);
                } else {
                    $data['notify_ids'] = serialize([]);
                    unset($data['notify_ids_staff']);
                }
                if (isset($data['notify_ids_roles'])) {
                    unset($data['notify_ids_roles']);
                }
            } else {
                if (isset($data['notify_ids_roles'])) {
                    $data['notify_ids'] = serialize($data['notify_ids_roles']);
                    unset($data['notify_ids_roles']);
                } else {
                    $data['notify_ids'] = serialize([]);
                    unset($data['notify_ids_roles']);
                }
                if (isset($data['notify_ids_staff'])) {
                    unset($data['notify_ids_staff']);
                }
            }
        } else {
            $data['notify_ids'] = serialize([]);
            $data['notify_type'] = null;
            if (isset($data['notify_ids_staff'])) {
                unset($data['notify_ids_staff']);
            }
            if (isset($data['notify_ids_roles'])) {
                unset($data['notify_ids_roles']);
            }
        }
        return $data;
    }

    public function do_kanban_query($status, $search = '', $page = 1, $sort = [], $count = false) {
        _deprecated_function('Leads_model::do_kanban_query', '2.9.2', 'LeadsKanban class');
        $kanBan = (new LeadsKanban($status))->search($search)->page($page)->sortBy($sort['sort']??null, $sort['sort_by']??null);
        if ($count) {
            return $kanBan->countAll();
        }
        return $kanBan->get();
    }
}
