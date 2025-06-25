<?php

namespace PerfexApiSdk\Models;

use PerfexApiSdk\Models\Clients_model;
use PerfexApiSdk\Models\Currencies_model;
use PerfexApiSdk\Models\Invoices_model;

require_once(APPPATH . 'core/App_Model.php');

defined('BASEPATH') or exit('No direct script access allowed');

class Statement_model extends \App_Model {
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get customer statement formatted
     * @param  mixed $customer_id customer id
     * @param  string $from        date from
     * @param  string $to          date to
     * @return array
     */
    public function get_statement($customer_id, $from, $to, $playground = false) {
        $sql = 'SELECT
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.id as invoice_id, hash,
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.date as date,
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.duedate, concat(' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.date, \' \', RIGHT(' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.datecreated,LOCATE(\' \',' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.datecreated) - 3)) as tmp_date,
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.duedate as duedate,
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.total as invoice_amount
            FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices WHERE clientid =' . $this->db->escape_str($customer_id);
        if ($from == $to) {
            $sqlDate = 'date="' . $this->db->escape_str($from) . '"';
        } else {
            $sqlDate = '(date BETWEEN "' . $this->db->escape_str($from) . '" AND "' . $this->db->escape_str($to) . '")';
        }
        $sql.= ' AND ' . $sqlDate;
        $invoices = $this->db->query($sql . '
            AND status != ' . Invoices_model::STATUS_DRAFT . '
            AND status != ' . Invoices_model::STATUS_CANCELLED . '
            ORDER By date DESC')->result_array();
        // Credit notes
        $sql_credit_notes = 'SELECT
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.id as credit_note_id,
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.date as date,
            concat(' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.date, \' \', RIGHT(' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.datecreated,LOCATE(\' \',' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.datecreated) - 3)) as tmp_date,
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.total as credit_note_amount
            FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes WHERE clientid =' . $this->db->escape_str($customer_id) . ' AND status != 3';
        $sql_credit_notes.= ' AND ' . $sqlDate;
        $credit_notes = $this->db->query($sql_credit_notes)->result_array();
        // Credits applied
        $sql_credits_applied = 'SELECT ' . db_prefix() . ($playground ? 'playground_' : '') . 'credits.id as credit_id, invoice_id as credit_invoice_id,
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'credits.credit_id as credit_applied_credit_note_id,
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'credits.date as date, concat(' . db_prefix() . ($playground ? 'playground_' : '') . 'credits.date, \' \', RIGHT(' . db_prefix() . ($playground ? 'playground_' : '') . 'credits.date_applied,LOCATE(\' \',' . db_prefix() . ($playground ? 'playground_' : '') . 'credits.date_applied) - 3)) as tmp_date,
            ' . db_prefix() . ($playground ? 'playground_' : '') . 'credits.amount as credit_amount
            FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'credits JOIN ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes ON ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.id = ' . db_prefix() . ($playground ? 'playground_' : '') . 'credits.credit_id
        ';
        $sql_credits_applied.= ' WHERE clientid =' . $this->db->escape_str($customer_id);
        $sqlDateCreditsAplied = str_replace('date', db_prefix() . ($playground ? 'playground_' : '') . 'credits.date', $sqlDate);
        $sql_credits_applied.= ' AND ' . $sqlDateCreditsAplied;
        $credits_applied = $this->db->query($sql_credits_applied)->result_array();
        // Replace error ambigious column in where clause
        $sqlDatePayments = str_replace('date', db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.date', $sqlDate);
        $sql_payments = 'SELECT
        ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.id as payment_id,
        ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.date as date,
        concat(' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.date, \' \', RIGHT(' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.daterecorded,LOCATE(\' \',' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.daterecorded) - 3)) as tmp_date,
        ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.invoiceid as payment_invoice_id,
        ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.amount as payment_total
        FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords
        JOIN ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices ON ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.id = ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.invoiceid
        WHERE ' . $sqlDatePayments . ' AND ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.clientid = ' . $this->db->escape_str($customer_id) . '
        ORDER by ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.date DESC';
        $payments = $this->db->query($sql_payments)->result_array();
        $sqlCreditNoteRefunds = str_replace('date', 'refunded_on', $sqlDate);
        $sql_credit_notes_refunds = 'SELECT id as credit_note_refund_id, credit_note_id as refund_credit_note_id, amount as refund_amount, concat(' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds.refunded_on, \' \', RIGHT(' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds.created_at,LOCATE(\' \',' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds.created_at) - 3)) as tmp_date,
            refunded_on as date FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds
            WHERE ' . $sqlCreditNoteRefunds . ' AND credit_note_id IN (SELECT id FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes WHERE clientid=' . $this->db->escape_str($customer_id) . ')
        ';
        $credit_notes_refunds = $this->db->query($sql_credit_notes_refunds)->result_array();
        // merge results
        $merged = array_merge($invoices, $payments, $credit_notes, $credits_applied, $credit_notes_refunds);
        // sort by date
        usort($merged, function ($a, $b) {
            // fake date select sorting
            return strtotime($a['tmp_date']) - strtotime($b['tmp_date']);
        });
        // Define final result variable
        $result = [];
        // Store in result array key
        $result['result'] = $merged;
        // Invoiced amount during the period
        $result['invoiced_amount'] = $this->db->query('SELECT
            SUM(' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.total) as invoiced_amount
            FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices
            WHERE clientid = ' . $this->db->escape_str($customer_id) . '
            AND ' . $sqlDate . ' AND status != ' . Invoices_model::STATUS_DRAFT . ' AND status != ' . Invoices_model::STATUS_CANCELLED . '')->row()->invoiced_amount;
        if ($result['invoiced_amount'] === null) {
            $result['invoiced_amount'] = 0;
        }
        $result['credit_notes_amount'] = $this->db->query('SELECT
            SUM(' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.total) as credit_notes_amount
            FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes
            WHERE clientid = ' . $this->db->escape_str($customer_id) . '
            AND ' . $sqlDate . ' AND status != 3')->row()->credit_notes_amount;
        if ($result['credit_notes_amount'] === null) {
            $result['credit_notes_amount'] = 0;
        }
        $result['refunds_amount'] = $this->db->query('SELECT
            SUM(' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds.amount) as refunds_amount
            FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds
            WHERE ' . $sqlCreditNoteRefunds . ' AND credit_note_id IN (SELECT id FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes WHERE clientid=' . $this->db->escape_str($customer_id) . ')
        ')->row()->refunds_amount;
        if ($result['refunds_amount'] === null) {
            $result['refunds_amount'] = 0;
        }
        $result['invoiced_amount'] = $result['invoiced_amount'] - $result['credit_notes_amount'];
        // Amount paid during the period
        $result['amount_paid'] = $this->db->query('SELECT
            SUM(' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.amount) as amount_paid
            FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords
            JOIN ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices ON ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.id = ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.invoiceid
            WHERE ' . $sqlDatePayments . ' AND ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.clientid = ' . $this->db->escape_str($customer_id))->row()->amount_paid;
        if ($result['amount_paid'] === null) {
            $result['amount_paid'] = 0;
        }
        // Beginning balance is all invoices amount before the FROM date - payments received before FROM date
        $result['beginning_balance'] = $this->db->query('
            SELECT (
            COALESCE(SUM(' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.total),0) - (
                (
                    SELECT COALESCE(SUM(' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.amount),0)
                    FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords
                    JOIN ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices ON ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.id = ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.invoiceid
                    WHERE ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoicepaymentrecords.date < "' . $this->db->escape_str($from) . '"
                    AND ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices.clientid=' . $this->db->escape_str($customer_id) . '
                    ) + (
                        SELECT COALESCE(SUM(' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.total),0)
                        FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes
                        WHERE ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.date < "' . $this->db->escape_str($from) . '"
                        AND ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes.clientid=' . $this->db->escape_str($customer_id) . '
                    ) - (
                        SELECT COALESCE(SUM(' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds.amount),0)
                        FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds
                        WHERE ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds.refunded_on < "' . $this->db->escape_str($from) . '"
                        AND ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnote_refunds.credit_note_id IN (SELECT id FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'creditnotes WHERE clientid=' . $this->db->escape_str($customer_id) . ')
                    )
                )
            )
            as beginning_balance FROM ' . db_prefix() . ($playground ? 'playground_' : '') . 'invoices
            WHERE date < "' . $this->db->escape_str($from) . '"
            AND clientid = ' . $this->db->escape_str($customer_id) . '
            AND status != ' . Invoices_model::STATUS_DRAFT . '
            AND status != ' . Invoices_model::STATUS_CANCELLED)->row()->beginning_balance;
        if ($result['beginning_balance'] === null) {
            $result['beginning_balance'] = 0;
        }
        $dec = get_decimal_places();
        if (function_exists('bcsub')) {
            $result['balance_due'] = bcsub($result['invoiced_amount'], $result['amount_paid'], $dec);
            $result['balance_due'] = bcadd($result['balance_due'], $result['beginning_balance'], $dec);
            $result['balance_due'] = bcadd($result['balance_due'], $result['refunds_amount'], $dec);
        } else {
            $result['balance_due'] = number_format($result['invoiced_amount'] - $result['amount_paid'], $dec, '.', '');
            $result['balance_due'] = $result['balance_due'] + number_format($result['beginning_balance'], $dec, '.', '');
            $result['balance_due'] = $result['balance_due'] + number_format($result['refunds_amount'], $dec, '.', '');
        }
        // Subtract amount paid - refund, because the refund is not actually paid amount
        $result['amount_paid'] = $result['amount_paid'] - $result['refunds_amount'];
        $result['client_id'] = $customer_id;
        $result['client'] = $clients_model->get($customer_id, $playground);
        $result['from'] = $from;
        $result['to'] = $to;
        $customer_currency = $clients_model->get_customer_default_currency($customer_id, $playground);
        $currencies_model = new Currencies_model();
        if ($customer_currency != 0) {
            $currency = $currencies_model->get($customer_currency, $playground);
        } else {
            $currency = $currencies_model->get_base_currency($playground, $playground);
        }
        $result['currency'] = $currency;
        return hooks()->apply_filters('statement', $result);
    }

    /**
     * Send customer statement to email
     * @param  mixed $customer_id customer id
     * @param  array $send_to     array of contact emails to send
     * @param  string $from        date from
     * @param  string $to          date to
     * @param  string $cc          email CC
     * @return boolean
     */
    public function send_statement_to_email($customer_id, $send_to, $from, $to, $cc = '', $playground = false) {
        $sent = false;
        if (is_array($send_to) && count($send_to) > 0) {
            $statement = $this->get_statement($customer_id, to_sql_date($from), to_sql_date($to), $playground);
            set_mailing_constant();
            $pdf = statement_pdf($statement);
            $pdf_file_name = slug_it(_l('customer_statement') . '-' . $statement['client']->company);
            $attach = $pdf->Output($pdf_file_name . '.pdf', 'S');
            $i = 0;
            $clients_model = new Clients_model();
            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }
                    $contact = $clients_model->get_contact($contact_id, $playground);
                    $template = mail_template('customer_statement', $contact->email, $contact_id, $statement, $cc);
                    $template->add_attachment(['attachment' => $attach, 'filename' => $pdf_file_name . '.pdf', 'type' => 'application/pdf', ]);
                    if ($template->send()) {
                        $sent = true;
                    }
                }
                $i++;
            }
            if ($sent) {
                return true;
            }
        }
        return false;
    }
}
