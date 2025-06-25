<?php

namespace PerfexApiSdk\Controllers;

use CI_Controller;

use PerfexApiSdk\Models\REST_model;
use PerfexApiSdk\Libraries\Authorization_Token;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Rest Controller
 * A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.
 *
 * @package         CodeIgniter
 * @subpackage      Libraries
 * @category        Libraries
 * @version         3.0.0
 */
abstract class REST_Controller extends CI_Controller {
    // Note: Only the widely used HTTP status codes are documented
    // Informational
    const HTTP_CONTINUE = 100;
    const HTTP_SWITCHING_PROTOCOLS = 101;
    const HTTP_PROCESSING = 102; // RFC2518
    // Success
    
    /**
     * The request has succeeded
     */
    const HTTP_OK = 200;

    /**
     * The server successfully created a new resource
     */
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;

    /**
     * The server successfully processed the request, though no content is returned
     */
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTI_STATUS = 207; // RFC4918
    const HTTP_ALREADY_REPORTED = 208; // RFC5842
    const HTTP_IM_USED = 226; // RFC3229

    // Redirection
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;

    /**
     * The resource has not been modified since the last request
     */
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_RESERVED = 306;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENTLY_REDIRECT = 308; // RFC7238

    // Client Error
    
    /**
     * The request cannot be fulfilled due to multiple errors
     */
    const HTTP_BAD_REQUEST = 400;

    /**
     * The user is unauthorized to access the requested resource
     */
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;

    /**
     * The requested resource is unavailable at this present time
     */
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_PERMISSION = 403;

    /**
     * The requested resource could not be found
     *
     * Note: This is sometimes used to mask if there was an UNAUTHORIZED (401) or
     * FORBIDDEN (403) error, for security reasons
     */
    const HTTP_NOT_FOUND = 404;

    /**
     * The request method is not supported by the following resource
     */
    const HTTP_METHOD_NOT_ALLOWED = 405;

    /**
     * The request was not acceptable
     */
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIMEOUT = 408;

    /**
     * The request could not be completed due to a conflict with the current state
     * of the resource
     */
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    const HTTP_I_AM_A_TEAPOT = 418; // RFC2324
    const HTTP_UNPROCESSABLE_ENTITY = 422; // RFC4918
    const HTTP_LOCKED = 423; // RFC4918
    const HTTP_FAILED_DEPENDENCY = 424; // RFC4918
    const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425; // RFC2817
    const HTTP_UPGRADE_REQUIRED = 426; // RFC2817
    const HTTP_PRECONDITION_REQUIRED = 428; // RFC6585
    const HTTP_TOO_MANY_REQUESTS = 429; // RFC6585
    const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431; // RFC6585

    // Server Error
    
    /**
     * The server encountered an unexpected error
     *
     * Note: This is a generic error message when no specific message
     * is suitable
     */
    const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * The server does not recognise the request method
     */
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506; // RFC2295
    const HTTP_INSUFFICIENT_STORAGE = 507; // RFC4918
    const HTTP_LOOP_DETECTED = 508; // RFC5842
    const HTTP_NOT_EXTENDED = 510; // RFC2774
    const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * This defines the rest format
     * Must be overridden it in a controller so that it is set
     *
     * @var string|NULL
     */
    protected $rest_format = NULL;

    /**
     * Defines the list of method properties such as limit, log and level
     *
     * @var array
     */
    protected $methods = [];

    /**
     * List of allowed HTTP methods
     *
     * @var array
     */
    protected $allowed_http_methods = ['get', 'delete', 'post', 'put', 'options', 'patch', 'head'];

    /**
     * Contains details about the request
     * Fields: body, format, method, ssl
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $request = NULL;

    /**
     * Contains details about the response
     * Fields: format, lang
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $response = NULL;

    /**
     * Contains details about the REST API
     * Fields: db, ignore_limits, key, level, user
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $rest = NULL;

    /**
     * The arguments for the GET request method
     *
     * @var array
     */
    protected $_get_args = [];

    /**
     * The arguments for the POST request method
     *
     * @var array
     */
    protected $_post_args = [];

    /**
     * The arguments for the PUT request method
     *
     * @var array
     */
    protected $_put_args = [];

    /**
     * The arguments for the DELETE request method
     *
     * @var array
     */
    protected $_delete_args = [];

    /**
     * The arguments for the PATCH request method
     *
     * @var array
     */
    protected $_patch_args = [];

    /**
     * The arguments for the HEAD request method
     *
     * @var array
     */
    protected $_head_args = [];

    /**
     * The arguments for the OPTIONS request method
     *
     * @var array
     */
    protected $_options_args = [];

    /**
     * The arguments for the query parameters
     *
     * @var array
     */
    protected $_query_args = [];

    /**
     * The arguments from GET, POST, PUT, DELETE, PATCH, HEAD and OPTIONS request methods combined
     *
     * @var array
     */
    protected $_args = [];

    /**
     * The insert_id of the log entry (if we have one)
     *
     * @var string
     */
    protected $_insert_id = '';

    /**
     * If the request is allowed based on the API key provided
     *
     * @var bool
     */
    protected $_allow = TRUE;

    /**
     * The LDAP Distinguished Name of the User post authentication
     *
     * @var string
     */
    protected $_user_ldap_dn = '';

    /**
     * The start of the response time from the server
     *
     * @var number
     */
    protected $_start_rtime;

    /**
     * The end of the response time from the server
     *
     * @var number
     */
    protected $_end_rtime;

    /**
     * List all supported methods, the first will be the default format
     *
     * @var array
     */
    protected $_supported_formats = ['json' => 'application/json', 'array' => 'application/json', 'csv' => 'application/csv', 'html' => 'text/html', 'jsonp' => 'application/javascript', 'php' => 'text/plain', 'serialized' => 'application/vnd.php.serialized', 'xml' => 'application/xml'];

    /**
     * Information about the current API user
     *
     * @var object
     */
    protected $_apiuser;

    /**
     * Whether or not to perform a CORS check and apply CORS headers to the request
     *
     * @var bool
     */
    protected $check_cors = NULL;

    /**
     * Enable XSS flag
     * Determines whether the XSS filter is always active when
     * GET, OPTIONS, HEAD, POST, PUT, DELETE and PATCH data is encountered
     * Set automatically based on config setting
     *
     * @var bool
     */
    protected $_enable_xss = FALSE;
    private $is_valid_request = TRUE;

    /**
     * HTTP status codes and their respective description
     * Note: Only the widely used HTTP status codes are used
     *
     * @var array
     * @link http://www.restapitutorial.com/httpstatuscodes.html
     */
    protected $http_status_codes = [
        self::HTTP_OK => 'OK',
        self::HTTP_CREATED => 'CREATED',
        self::HTTP_NO_CONTENT => 'NO CONTENT',
        self::HTTP_NOT_MODIFIED => 'NOT MODIFIED',
        self::HTTP_BAD_REQUEST => 'BAD REQUEST',
        self::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
        self::HTTP_FORBIDDEN => 'FORBIDDEN',
        self::HTTP_NOT_FOUND => 'NOT FOUND',
        self::HTTP_NOT_PERMISSION => 'NOT PERMISSION',
        self::HTTP_METHOD_NOT_ALLOWED => 'METHOD NOT ALLOWED',
        self::HTTP_NOT_ACCEPTABLE => 'NOT ACCEPTABLE',
        self::HTTP_CONFLICT => 'CONFLICT',
        self::HTTP_INTERNAL_SERVER_ERROR => 'INTERNAL SERVER ERROR',
        self::HTTP_NOT_IMPLEMENTED => 'NOT IMPLEMENTED'
    ];

    /**
     * @var Format
     */
    private $format;

    /**
     * @var bool
     */
    private $auth_override;

    /**
     * Extend this function to apply additional checking early on in the process
     *
     * @access protected
     * @return void
     */
    protected function early_checks() {

        if (!$this->db->table_exists(db_prefix() . 'playground_contacts')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_contacts' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `userid` int(11) NOT NULL,
                `is_primary` int(11) NOT NULL DEFAULT 1,
                `firstname` varchar(191) NOT NULL,
                `lastname` varchar(191) NOT NULL,
                `email` varchar(100) NOT NULL,
                `phonenumber` varchar(100) NOT NULL,
                `title` varchar(100) DEFAULT NULL,
                `datecreated` datetime NOT NULL,
                `password` varchar(255) DEFAULT NULL,
                `new_pass_key` varchar(32) DEFAULT NULL,
                `new_pass_key_requested` datetime DEFAULT NULL,
                `email_verified_at` datetime DEFAULT NULL,
                `email_verification_key` varchar(32) DEFAULT NULL,
                `email_verification_sent_at` datetime DEFAULT NULL,
                `last_ip` varchar(40) DEFAULT NULL,
                `last_login` datetime DEFAULT NULL,
                `last_password_change` datetime DEFAULT NULL,
                `active` tinyint(1) NOT NULL DEFAULT 1,
                `profile_image` varchar(191) DEFAULT NULL,
                `direction` varchar(3) DEFAULT NULL,
                `invoice_emails` tinyint(1) NOT NULL DEFAULT 1,
                `estimate_emails` tinyint(1) NOT NULL DEFAULT 1,
                `credit_note_emails` tinyint(1) NOT NULL DEFAULT 1,
                `contract_emails` tinyint(1) NOT NULL DEFAULT 1,
                `task_emails` tinyint(1) NOT NULL DEFAULT 1,
                `project_emails` tinyint(1) NOT NULL DEFAULT 1,
                `ticket_emails` tinyint(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`),
                KEY `userid` (`userid`),
                KEY `firstname` (`firstname`),
                KEY `lastname` (`lastname`),
                KEY `email` (`email`),
                KEY `is_primary` (`is_primary`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_contact_permissions')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_contact_permissions' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `permission_id` int(11) NOT NULL,
                `userid` int(11) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_events')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_events' . '` (
                `eventid` int(11) NOT NULL AUTO_INCREMENT,
                `title` longtext NOT NULL,
                `description` mediumtext DEFAULT NULL,
                `userid` int(11) NOT NULL,
                `start` datetime NOT NULL,
                `end` datetime DEFAULT NULL,
                `public` int(11) NOT NULL DEFAULT 0,
                `color` varchar(10) DEFAULT NULL,
                `isstartnotified` tinyint(1) NOT NULL DEFAULT 0,
                `reminder_before` int(11) NOT NULL DEFAULT 0,
                `reminder_before_type` varchar(10) DEFAULT NULL,
                PRIMARY KEY (`eventid`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_customer_groups')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_customer_groups' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `groupid` int(11) NOT NULL,
                `customer_id` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `groupid` (`groupid`),
                KEY `customer_id` (`customer_id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_customers_groups')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_customers_groups' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(191) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `name` (`name`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_vault')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_vault' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `customer_id` int(11) NOT NULL,
                `server_address` varchar(191) NOT NULL,
                `port` int(11) DEFAULT NULL,
                `username` varchar(191) NOT NULL,
                `password` mediumtext NOT NULL,
                `description` mediumtext DEFAULT NULL,
                `creator` int(11) NOT NULL,
                `creator_name` varchar(100) DEFAULT NULL,
                `visibility` tinyint(1) NOT NULL DEFAULT 1,
                `share_in_projects` tinyint(1) NOT NULL DEFAULT 0,
                `last_updated` datetime DEFAULT NULL,
                `last_updated_from` varchar(100) DEFAULT NULL,
                `date_created` datetime NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_clients')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_clients' . '` (
                `userid` int(11) NOT NULL AUTO_INCREMENT,
                `company` varchar(191) DEFAULT NULL,
                `vat` varchar(50) DEFAULT NULL,
                `phonenumber` varchar(30) DEFAULT NULL,
                `country` int(11) NOT NULL DEFAULT 0,
                `city` varchar(100) DEFAULT NULL,
                `zip` varchar(15) DEFAULT NULL,
                `state` varchar(50) DEFAULT NULL,
                `address` varchar(191) DEFAULT NULL,
                `website` varchar(150) DEFAULT NULL,
                `datecreated` datetime NOT NULL,
                `active` int(11) NOT NULL DEFAULT 1,
                `leadid` int(11) DEFAULT NULL,
                `billing_street` varchar(200) DEFAULT NULL,
                `billing_city` varchar(100) DEFAULT NULL,
                `billing_state` varchar(100) DEFAULT NULL,
                `billing_zip` varchar(100) DEFAULT NULL,
                `billing_country` int(11) DEFAULT 0,
                `shipping_street` varchar(200) DEFAULT NULL,
                `shipping_city` varchar(100) DEFAULT NULL,
                `shipping_state` varchar(100) DEFAULT NULL,
                `shipping_zip` varchar(100) DEFAULT NULL,
                `shipping_country` int(11) DEFAULT 0,
                `longitude` varchar(191) DEFAULT NULL,
                `latitude` varchar(191) DEFAULT NULL,
                `default_language` varchar(40) DEFAULT NULL,
                `default_currency` int(11) NOT NULL DEFAULT 0,
                `show_primary_contact` int(11) NOT NULL DEFAULT 0,
                `stripe_id` varchar(40) DEFAULT NULL,
                `registration_confirmed` int(11) NOT NULL DEFAULT 1,
                `addedfrom` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`userid`),
                KEY `country` (`country`),
                KEY `leadid` (`leadid`),
                KEY `company` (`company`),
                KEY `active` (`active`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_contracts')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_contracts' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `content` longtext DEFAULT NULL,
                `description` mediumtext DEFAULT NULL,
                `subject` varchar(191) DEFAULT NULL,
                `client` int(11) NOT NULL,
                `datestart` date DEFAULT NULL,
                `dateend` date DEFAULT NULL,
                `contract_type` int(11) DEFAULT NULL,
                `project_id` int(11) DEFAULT NULL,
                `addedfrom` int(11) NOT NULL,
                `dateadded` datetime NOT NULL,
                `isexpirynotified` int(11) NOT NULL DEFAULT 0,
                `contract_value` decimal(15,2) DEFAULT NULL,
                `trash` tinyint(1) DEFAULT 0,
                `not_visible_to_client` tinyint(1) NOT NULL DEFAULT 0,
                `hash` varchar(32) DEFAULT NULL,
                `signed` tinyint(1) NOT NULL DEFAULT 0,
                `signature` varchar(40) DEFAULT NULL,
                `marked_as_signed` tinyint(1) NOT NULL DEFAULT 0,
                `acceptance_firstname` varchar(50) DEFAULT NULL,
                `acceptance_lastname` varchar(50) DEFAULT NULL,
                `acceptance_email` varchar(100) DEFAULT NULL,
                `acceptance_date` datetime DEFAULT NULL,
                `acceptance_ip` varchar(40) DEFAULT NULL,
                `short_link` varchar(100) DEFAULT NULL,
                `last_sent_at` datetime DEFAULT NULL,
                `contacts_sent_to` mediumtext DEFAULT NULL,
                `last_sign_reminder_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `client` (`client`),
                KEY `contract_type` (`contract_type`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_contract_comments')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_contract_comments' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `content` longtext DEFAULT NULL,
                `contract_id` int(11) NOT NULL,
                `staffid` int(11) NOT NULL,
                `dateadded` datetime NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_contract_renewals')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_contract_renewals' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `contractid` int(11) NOT NULL,
                `old_start_date` date NOT NULL,
                `new_start_date` date NOT NULL,
                `old_end_date` date DEFAULT NULL,
                `new_end_date` date DEFAULT NULL,
                `old_value` decimal(15,2) DEFAULT NULL,
                `new_value` decimal(15,2) DEFAULT NULL,
                `date_renewed` datetime NOT NULL,
                `renewed_by` varchar(100) NOT NULL,
                `renewed_by_staff_id` int(11) NOT NULL DEFAULT 0,
                `is_on_old_expiry_notified` int(11) DEFAULT 0,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_contracts_types')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_contracts_types' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` longtext NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_creditnotes')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_creditnotes' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `clientid` int(11) NOT NULL,
                `deleted_customer_name` varchar(100) DEFAULT NULL,
                `number` int(11) NOT NULL,
                `prefix` varchar(50) DEFAULT NULL,
                `number_format` int(11) NOT NULL DEFAULT 1,
                `formatted_number` varchar(100) DEFAULT NULL,
                `datecreated` datetime NOT NULL,
                `date` date NOT NULL,
                `adminnote` mediumtext DEFAULT NULL,
                `terms` mediumtext DEFAULT NULL,
                `clientnote` mediumtext DEFAULT NULL,
                `currency` int(11) NOT NULL,
                `subtotal` decimal(15,2) NOT NULL,
                `total_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
                `total` decimal(15,2) NOT NULL,
                `adjustment` decimal(15,2) DEFAULT NULL,
                `addedfrom` int(11) DEFAULT NULL,
                `status` int(11) DEFAULT 1,
                `project_id` int(11) NOT NULL DEFAULT 0,
                `discount_percent` decimal(15,2) DEFAULT 0.00,
                `discount_total` decimal(15,2) DEFAULT 0.00,
                `discount_type` varchar(30) NOT NULL,
                `billing_street` varchar(200) DEFAULT NULL,
                `billing_city` varchar(100) DEFAULT NULL,
                `billing_state` varchar(100) DEFAULT NULL,
                `billing_zip` varchar(100) DEFAULT NULL,
                `billing_country` int(11) DEFAULT NULL,
                `shipping_street` varchar(200) DEFAULT NULL,
                `shipping_city` varchar(100) DEFAULT NULL,
                `shipping_state` varchar(100) DEFAULT NULL,
                `shipping_zip` varchar(100) DEFAULT NULL,
                `shipping_country` int(11) DEFAULT NULL,
                `include_shipping` tinyint(1) NOT NULL,
                `show_shipping_on_credit_note` tinyint(1) NOT NULL DEFAULT 1,
                `show_quantity_as` int(11) NOT NULL DEFAULT 1,
                `reference_no` varchar(100) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `currency` (`currency`),
                KEY `clientid` (`clientid`),
                KEY `project_id` (`project_id`),
                KEY `formatted_number` (`formatted_number`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_creditnote_refunds')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_creditnote_refunds' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `credit_note_id` int(11) NOT NULL,
                `staff_id` int(11) NOT NULL,
                `refunded_on` date NOT NULL,
                `payment_mode` varchar(40) NOT NULL,
                `note` mediumtext DEFAULT NULL,
                `amount` decimal(15,2) NOT NULL,
                `created_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_credits')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_credits' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `invoice_id` int(11) NOT NULL,
                `credit_id` int(11) NOT NULL,
                `staff_id` int(11) NOT NULL,
                `date` date NOT NULL,
                `date_applied` datetime NOT NULL,
                `amount` decimal(15,2) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_currencies')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_currencies' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `symbol` varchar(10) NOT NULL,
                `name` varchar(100) NOT NULL,
                `decimal_separator` varchar(5) DEFAULT NULL,
                `thousand_separator` varchar(5) DEFAULT NULL,
                `placement` varchar(10) DEFAULT NULL,
                `isdefault` tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_customfields')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . ($playground ? 'playground_' : '') . 'playground_customfields' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `fieldto` varchar(30) DEFAULT NULL,
                `name` varchar(150) NOT NULL,
                `slug` varchar(150) NOT NULL,
                `required` tinyint(1) NOT NULL DEFAULT 0,
                `type` varchar(20) NOT NULL,
                `options` longtext DEFAULT NULL,
                `display_inline` tinyint(1) NOT NULL DEFAULT 0,
                `field_order` int(11) DEFAULT 0,
                `active` int(11) NOT NULL DEFAULT 1,
                `show_on_pdf` int(11) NOT NULL DEFAULT 0,
                `show_on_ticket_form` tinyint(1) NOT NULL DEFAULT 0,
                `only_admin` tinyint(1) NOT NULL DEFAULT 0,
                `show_on_table` tinyint(1) NOT NULL DEFAULT 0,
                `show_on_client_portal` int(11) NOT NULL DEFAULT 0,
                `disalow_client_to_edit` int(11) NOT NULL DEFAULT 0,
                `bs_column` int(11) NOT NULL DEFAULT 12,
                `default_value` mediumtext DEFAULT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_customfieldsvalues')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . ($playground ? 'playground_' : '') . 'playground_customfieldsvalues' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `relid` int(11) NOT NULL,
                `fieldid` int(11) NOT NULL,
                `fieldto` varchar(15) NOT NULL,
                `value` mediumtext NOT NULL,
                PRIMARY KEY (`id`),
                KEY `relid` (`relid`),
                KEY `fieldto` (`fieldto`),
                KEY `fieldid` (`fieldid`));
            ');
        }
        if (!$this->db->table_exists(db_prefix() . 'playground_departments')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_departments' . '` (
                `departmentid` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `imap_username` varchar(191) DEFAULT NULL,
                `email` varchar(100) DEFAULT NULL,
                `email_from_header` tinyint(1) NOT NULL DEFAULT 0,
                `host` varchar(150) DEFAULT NULL,
                `password` longtext DEFAULT NULL,
                `encryption` varchar(3) DEFAULT NULL,
                `folder` varchar(191) NOT NULL DEFAULT \'INBOX\',
                `delete_after_import` int(11) NOT NULL DEFAULT 0,
                `calendar_id` longtext DEFAULT NULL,
                `hidefromclient` tinyint(1) NOT NULL DEFAULT 0,
                `parent_id` int(11) DEFAULT 0,
                `manager_id` int(11) DEFAULT 0,
                PRIMARY KEY (`departmentid`),
                KEY `name` (`name`));
            ');
        }
        if (!$this->db->table_exists(db_prefix() . 'playground_scheduled_emails')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_scheduled_emails' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `rel_id` int(11) NOT NULL,
                `rel_type` varchar(15) NOT NULL,
                `scheduled_at` datetime NOT NULL,
                `contacts` varchar(197) NOT NULL,
                `cc` mediumtext DEFAULT NULL,
                `attach_pdf` tinyint(1) NOT NULL DEFAULT 1,
                `template` varchar(197) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_estimate_requests')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_estimate_requests' . '` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `email` varchar(100) NOT NULL,
                `submission` longtext NOT NULL,
                `last_status_change` datetime DEFAULT NULL,
                `date_estimated` datetime DEFAULT NULL,
                `from_form_id` int(11) DEFAULT NULL,
                `assigned` int(11) DEFAULT NULL,
                `status` int(11) DEFAULT NULL,
                `default_language` int(11) NOT NULL,
                `date_added` datetime NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_estimate_request_forms')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_estimate_request_forms' . '` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `form_key` varchar(32) NOT NULL,
                `type` varchar(100) NOT NULL,
                `name` varchar(191) NOT NULL,
                `form_data` longtext DEFAULT NULL,
                `recaptcha` int(11) DEFAULT NULL,
                `status` int(11) NOT NULL,
                `submit_btn_name` varchar(100) DEFAULT NULL,
                `submit_btn_bg_color` varchar(10) DEFAULT \'#84c529\',
                `submit_btn_text_color` varchar(10) DEFAULT \'#ffffff\',
                `success_submit_msg` mediumtext DEFAULT NULL,
                `submit_action` int(11) DEFAULT 0,
                `submit_redirect_url` longtext DEFAULT NULL,
                `language` varchar(100) DEFAULT NULL,
                `dateadded` datetime DEFAULT NULL,
                `notify_type` varchar(100) DEFAULT NULL,
                `notify_ids` longtext DEFAULT NULL,
                `responsible` int(11) DEFAULT NULL,
                `notify_request_submitted` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_estimate_request_status')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_estimate_request_status' . '` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                `statusorder` int(11) DEFAULT NULL,
                `color` varchar(10) DEFAULT NULL,
                `flag` varchar(30) DEFAULT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_estimates')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_estimates' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `sent` tinyint(1) NOT NULL DEFAULT 0,
                `datesend` datetime DEFAULT NULL,
                `clientid` int(11) NOT NULL,
                `deleted_customer_name` varchar(100) DEFAULT NULL,
                `project_id` int(11) NOT NULL DEFAULT 0,
                `number` int(11) NOT NULL,
                `prefix` varchar(50) DEFAULT NULL,
                `number_format` int(11) NOT NULL DEFAULT 0,
                `formatted_number` varchar(100) DEFAULT NULL,
                `hash` varchar(32) DEFAULT NULL,
                `datecreated` datetime NOT NULL,
                `date` date NOT NULL,
                `expirydate` date DEFAULT NULL,
                `currency` int(11) NOT NULL,
                `subtotal` decimal(15,2) NOT NULL,
                `total_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
                `total` decimal(15,2) NOT NULL,
                `adjustment` decimal(15,2) DEFAULT NULL,
                `addedfrom` int(11) NOT NULL,
                `status` int(11) NOT NULL DEFAULT 1,
                `clientnote` mediumtext DEFAULT NULL,
                `adminnote` mediumtext DEFAULT NULL,
                `discount_percent` decimal(15,2) DEFAULT 0.00,
                `discount_total` decimal(15,2) DEFAULT 0.00,
                `discount_type` varchar(30) DEFAULT NULL,
                `invoiceid` int(11) DEFAULT NULL,
                `invoiced_date` datetime DEFAULT NULL,
                `terms` mediumtext DEFAULT NULL,
                `reference_no` varchar(100) DEFAULT NULL,
                `sale_agent` int(11) NOT NULL DEFAULT 0,
                `billing_street` varchar(200) DEFAULT NULL,
                `billing_city` varchar(100) DEFAULT NULL,
                `billing_state` varchar(100) DEFAULT NULL,
                `billing_zip` varchar(100) DEFAULT NULL,
                `billing_country` int(11) DEFAULT NULL,
                `shipping_street` varchar(200) DEFAULT NULL,
                `shipping_city` varchar(100) DEFAULT NULL,
                `shipping_state` varchar(100) DEFAULT NULL,
                `shipping_zip` varchar(100) DEFAULT NULL,
                `shipping_country` int(11) DEFAULT NULL,
                `include_shipping` tinyint(1) NOT NULL,
                `show_shipping_on_estimate` tinyint(1) NOT NULL DEFAULT 1,
                `show_quantity_as` int(11) NOT NULL DEFAULT 1,
                `pipeline_order` int(11) DEFAULT 1,
                `is_expiry_notified` int(11) NOT NULL DEFAULT 0,
                `acceptance_firstname` varchar(50) DEFAULT NULL,
                `acceptance_lastname` varchar(50) DEFAULT NULL,
                `acceptance_email` varchar(100) DEFAULT NULL,
                `acceptance_date` datetime DEFAULT NULL,
                `acceptance_ip` varchar(40) DEFAULT NULL,
                `signature` varchar(40) DEFAULT NULL,
                `short_link` varchar(100) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `clientid` (`clientid`),
                KEY `currency` (`currency`),
                KEY `project_id` (`project_id`),
                KEY `sale_agent` (`sale_agent`),
                KEY `status` (`status`),
                KEY `formatted_number` (`formatted_number`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_expenses')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . ($playground ? 'playground_' : '') . 'playground_expenses' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `category` int(11) NOT NULL,
                `currency` int(11) NOT NULL,
                `amount` decimal(15,2) NOT NULL,
                `tax` int(11) DEFAULT NULL,
                `tax2` int(11) NOT NULL DEFAULT 0,
                `reference_no` varchar(100) DEFAULT NULL,
                `note` mediumtext DEFAULT NULL,
                `expense_name` varchar(191) DEFAULT NULL,
                `clientid` int(11) NOT NULL,
                `project_id` int(11) NOT NULL DEFAULT 0,
                `billable` int(11) DEFAULT 0,
                `invoiceid` int(11) DEFAULT NULL,
                `paymentmode` varchar(50) DEFAULT NULL,
                `date` date NOT NULL,
                `recurring_type` varchar(10) DEFAULT NULL,
                `repeat_every` int(11) DEFAULT NULL,
                `recurring` int(11) NOT NULL DEFAULT 0,
                `cycles` int(11) NOT NULL DEFAULT 0,
                `total_cycles` int(11) NOT NULL DEFAULT 0,
                `custom_recurring` int(11) NOT NULL DEFAULT 0,
                `last_recurring_date` date DEFAULT NULL,
                `create_invoice_billable` tinyint(1) DEFAULT NULL,
                `send_invoice_to_customer` tinyint(1) NOT NULL,
                `recurring_from` int(11) DEFAULT NULL,
                `dateadded` datetime NOT NULL,
                `addedfrom` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `clientid` (`clientid`),
                KEY `project_id` (`project_id`),
                KEY `category` (`category`),
                KEY `currency` (`currency`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_expenses_categories')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . ($playground ? 'playground_' : '') . 'playground_expenses_categories' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(191) NOT NULL,
                `description` mediumtext DEFAULT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_itemable')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_itemable' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `rel_id` int(11) NOT NULL,
                `rel_type` varchar(15) NOT NULL,
                `description` longtext NOT NULL,
                `long_description` longtext DEFAULT NULL,
                `qty` decimal(15,2) NOT NULL,
                `rate` decimal(15,2) NOT NULL,
                `unit` varchar(40) DEFAULT NULL,
                `item_order` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `rel_id` (`rel_id`),
                KEY `rel_type` (`rel_type`),
                KEY `qty` (`qty`),
                KEY `rate` (`rate`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_items')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_items' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `description` longtext NOT NULL,
                `long_description` mediumtext DEFAULT NULL,
                `rate` decimal(15,2) NOT NULL,
                `tax` int(11) DEFAULT NULL,
                `tax2` int(11) DEFAULT NULL,
                `unit` varchar(40) DEFAULT NULL,
                `group_id` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `tax` (`tax`),
                KEY `tax2` (`tax2`),
                KEY `group_id` (`group_id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_items_groups')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_items_groups' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_item_tax')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_item_tax' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `itemid` int(11) NOT NULL,
                `rel_id` int(11) NOT NULL,
                `rel_type` varchar(20) NOT NULL,
                `taxrate` decimal(15,2) NOT NULL,
                `taxname` varchar(100) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `itemid` (`itemid`),
                KEY `rel_id` (`rel_id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_related_items')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_related_items' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `rel_id` int(11) NOT NULL,
                `rel_type` varchar(30) NOT NULL,
                `item_id` int(11) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_invoices')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_invoices' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `sent` tinyint(1) NOT NULL DEFAULT 0,
                `datesend` datetime DEFAULT NULL,
                `clientid` int(11) NOT NULL,
                `deleted_customer_name` varchar(100) DEFAULT NULL,
                `number` int(11) NOT NULL,
                `prefix` varchar(50) DEFAULT NULL,
                `number_format` int(11) NOT NULL DEFAULT 0,
                `formatted_number` varchar(100) DEFAULT NULL,
                `datecreated` datetime NOT NULL,
                `date` date NOT NULL,
                `duedate` date DEFAULT NULL,
                `currency` int(11) NOT NULL,
                `coupon_id` int(11) DEFAULT NULL,
                `subtotal` decimal(15,2) NOT NULL,
                `total_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
                `coupon_discount` decimal(15,2) DEFAULT 0.00,
                `total` decimal(15,2) NOT NULL,
                `adjustment` decimal(15,2) DEFAULT NULL,
                `addedfrom` int(11) DEFAULT NULL,
                `hash` varchar(32) NOT NULL,
                `status` int(11) DEFAULT 1,
                `clientnote` mediumtext DEFAULT NULL,
                `adminnote` mediumtext DEFAULT NULL,
                `last_overdue_reminder` date DEFAULT NULL,
                `last_due_reminder` date DEFAULT NULL,
                `cancel_overdue_reminders` int(11) NOT NULL DEFAULT 0,
                `allowed_payment_modes` longtext DEFAULT NULL,
                `token` longtext DEFAULT NULL,
                `discount_percent` decimal(15,2) DEFAULT 0.00,
                `discount_total` decimal(15,2) DEFAULT 0.00,
                `discount_type` varchar(30) NOT NULL,
                `recurring` int(11) NOT NULL DEFAULT 0,
                `recurring_type` varchar(10) DEFAULT NULL,
                `custom_recurring` tinyint(1) NOT NULL DEFAULT 0,
                `cycles` int(11) NOT NULL DEFAULT 0,
                `total_cycles` int(11) NOT NULL DEFAULT 0,
                `is_recurring_from` int(11) DEFAULT NULL,
                `last_recurring_date` date DEFAULT NULL,
                `terms` mediumtext DEFAULT NULL,
                `sale_agent` int(11) NOT NULL DEFAULT 0,
                `billing_street` varchar(200) DEFAULT NULL,
                `billing_city` varchar(100) DEFAULT NULL,
                `billing_state` varchar(100) DEFAULT NULL,
                `billing_zip` varchar(100) DEFAULT NULL,
                `billing_country` int(11) DEFAULT NULL,
                `shipping_street` varchar(200) DEFAULT NULL,
                `shipping_city` varchar(100) DEFAULT NULL,
                `shipping_state` varchar(100) DEFAULT NULL,
                `shipping_zip` varchar(100) DEFAULT NULL,
                `shipping_country` int(11) DEFAULT NULL,
                `include_shipping` tinyint(1) NOT NULL,
                `show_shipping_on_invoice` tinyint(1) NOT NULL DEFAULT 1,
                `show_quantity_as` int(11) NOT NULL DEFAULT 1,
                `project_id` int(11) DEFAULT 0,
                `subscription_id` int(11) NOT NULL DEFAULT 0,
                `short_link` varchar(100) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `currency` (`currency`),
                KEY `clientid` (`clientid`),
                KEY `project_id` (`project_id`),
                KEY `sale_agent` (`sale_agent`),
                KEY `total` (`total`),
                KEY `status` (`status`),
                KEY `formatted_number` (`formatted_number`),
                KEY `coupon_id` (`coupon_id`));
            ');
            // CONSTRAINT `' . db_prefix() . 'invoices_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `' . db_prefix() . 'playground_invoices' . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_sales_activity')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_sales_activity' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `rel_type` varchar(20) DEFAULT NULL,
                `rel_id` int(11) NOT NULL,
                `description` mediumtext NOT NULL,
                `additional_data` mediumtext DEFAULT NULL,
                `staffid` varchar(11) DEFAULT NULL,
                `full_name` varchar(100) DEFAULT NULL,
                `date` datetime NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_leads')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_leads' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `hash` varchar(65) DEFAULT NULL,
                `name` varchar(191) NOT NULL,
                `title` varchar(100) DEFAULT NULL,
                `company` varchar(191) DEFAULT NULL,
                `description` mediumtext DEFAULT NULL,
                `country` int(11) NOT NULL DEFAULT 0,
                `zip` varchar(15) DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `state` varchar(50) DEFAULT NULL,
                `address` varchar(100) DEFAULT NULL,
                `assigned` int(11) NOT NULL DEFAULT 0,
                `dateadded` datetime NOT NULL,
                `from_form_id` int(11) NOT NULL DEFAULT 0,
                `status` int(11) NOT NULL,
                `source` int(11) NOT NULL,
                `lastcontact` datetime DEFAULT NULL,
                `dateassigned` date DEFAULT NULL,
                `last_status_change` datetime DEFAULT NULL,
                `addedfrom` int(11) NOT NULL,
                `email` varchar(100) DEFAULT NULL,
                `website` varchar(150) DEFAULT NULL,
                `leadorder` int(11) DEFAULT 1,
                `phonenumber` varchar(50) DEFAULT NULL,
                `date_converted` datetime DEFAULT NULL,
                `lost` tinyint(1) NOT NULL DEFAULT 0,
                `junk` int(11) NOT NULL DEFAULT 0,
                `last_lead_status` int(11) NOT NULL DEFAULT 0,
                `is_imported_from_email_integration` tinyint(1) NOT NULL DEFAULT 0,
                `email_integration_uid` varchar(30) DEFAULT NULL,
                `is_public` tinyint(1) NOT NULL DEFAULT 0,
                `default_language` varchar(40) DEFAULT NULL,
                `client_id` int(11) NOT NULL DEFAULT 0,
                `lead_value` decimal(15,2) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `name` (`name`),
                KEY `company` (`company`),
                KEY `email` (`email`),
                KEY `assigned` (`assigned`),
                KEY `status` (`status`),
                KEY `source` (`source`),
                KEY `lastcontact` (`lastcontact`),
                KEY `dateadded` (`dateadded`),
                KEY `leadorder` (`leadorder`),
                KEY `from_form_id` (`from_form_id`));
            ');
        }
        if (!$this->db->table_exists(db_prefix() . 'playground_leads_email_integration')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_leads_email_integration' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT COMMENT \'the ID always must be 1\',
                `active` int(11) NOT NULL,
                `email` varchar(100) NOT NULL,
                `imap_server` varchar(100) NOT NULL,
                `password` longtext NOT NULL,
                `check_every` int(11) NOT NULL DEFAULT 5,
                `responsible` int(11) NOT NULL,
                `lead_source` int(11) NOT NULL,
                `lead_status` int(11) NOT NULL,
                `encryption` varchar(3) DEFAULT NULL,
                `folder` varchar(100) NOT NULL,
                `last_run` varchar(50) DEFAULT NULL,
                `notify_lead_imported` tinyint(1) NOT NULL DEFAULT 1,
                `notify_lead_contact_more_times` tinyint(1) NOT NULL DEFAULT 1,
                `notify_type` varchar(20) DEFAULT NULL,
                `notify_ids` longtext DEFAULT NULL,
                `mark_public` int(11) NOT NULL DEFAULT 0,
                `only_loop_on_unseen_emails` tinyint(1) NOT NULL DEFAULT 1,
                `delete_after_import` int(11) NOT NULL DEFAULT 0,
                `create_task_if_customer` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`));
            ');
        }
        if (!$this->db->table_exists(db_prefix() . 'playground_leads_sources')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_leads_sources' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(150) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `name` (`name`));
            ');
        }
        if (!$this->db->table_exists(db_prefix() . 'playground_leads_status')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_leads_status' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                `statusorder` int(11) DEFAULT NULL,
                `color` varchar(10) DEFAULT \'#28B8DA\',
                `isdefault` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `name` (`name`));
            ');
        }
        if (!$this->db->table_exists(db_prefix() . 'playground_lead_activity_log')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_lead_activity_log' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `leadid` int(11) NOT NULL,
                `description` longtext NOT NULL,
                `additional_data` mediumtext DEFAULT NULL,
                `date` datetime NOT NULL,
                `staffid` int(11) NOT NULL,
                `full_name` varchar(100) DEFAULT NULL,
                `custom_activity` tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`));
            ');
        }
        if (!$this->db->table_exists(db_prefix() . 'playground_lead_integration_emails')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_lead_integration_emails' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `subject` longtext DEFAULT NULL,
                `body` longtext DEFAULT NULL,
                `dateadded` datetime NOT NULL,
                `leadid` int(11) NOT NULL,
                `emailid` int(11) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_payment_attempts')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_payment_attempts' . '` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `reference` varchar(100) NOT NULL,
                `invoice_id` int(11) NOT NULL,
                `amount` double NOT NULL,
                `fee` double NOT NULL,
                `payment_gateway` varchar(100) NOT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }
                
        if (!$this->db->table_exists(db_prefix() . 'playground_payment_modes')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_payment_modes' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `description` mediumtext DEFAULT NULL,
                `show_on_pdf` int(11) NOT NULL DEFAULT 0,
                `invoices_only` int(11) NOT NULL DEFAULT 0,
                `expenses_only` int(11) NOT NULL DEFAULT 0,
                `selected_by_default` int(11) NOT NULL DEFAULT 1,
                `active` tinyint(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`));
            ');
        }
        

        if (!$this->db->table_exists(db_prefix() . 'playground_invoicepaymentrecords')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_invoicepaymentrecords' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `invoiceid` int(11) NOT NULL,
                `amount` decimal(15,2) NOT NULL,
                `paymentmode` varchar(40) DEFAULT NULL,
                `paymentmethod` varchar(191) DEFAULT NULL,
                `date` date NOT NULL,
                `daterecorded` datetime NOT NULL,
                `note` mediumtext DEFAULT NULL,
                `transactionid` longtext DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `invoiceid` (`invoiceid`),
                KEY `paymentmethod` (`paymentmethod`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_payment_files')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_payment_files' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `invoiceid` int(11) NOT NULL,
                `amount` decimal(15,2) NOT NULL,
                `paymentmode` varchar(40) DEFAULT NULL,
                `paymentmethod` varchar(191) DEFAULT NULL,
                `date` date NOT NULL,
                `daterecorded` datetime NOT NULL,
                `note` mediumtext DEFAULT NULL,
                `transactionid` longtext DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `invoiceid` (`invoiceid`),
                KEY `paymentmethod` (`paymentmethod`));
            ');
        }
        

        if (!$this->db->table_exists(db_prefix() . 'playground_projects')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_projects' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(191) NOT NULL,
                `description` mediumtext DEFAULT NULL,
                `status` int(11) NOT NULL DEFAULT 0,
                `clientid` int(11) NOT NULL,
                `billing_type` int(11) NOT NULL,
                `start_date` date NOT NULL,
                `deadline` date DEFAULT NULL,
                `project_created` date NOT NULL,
                `date_finished` datetime DEFAULT NULL,
                `progress` int(11) DEFAULT 0,
                `progress_from_tasks` int(11) NOT NULL DEFAULT 1,
                `project_cost` decimal(15,2) DEFAULT NULL,
                `project_rate_per_hour` decimal(15,2) DEFAULT NULL,
                `estimated_hours` decimal(15,2) DEFAULT NULL,
                `addedfrom` int(11) NOT NULL,
                `contact_notification` int(11) DEFAULT 1,
                `notify_contacts` mediumtext DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `clientid` (`clientid`),
                KEY `name` (`name`));
            ');
        }
        if (!$this->db->table_exists(db_prefix() . 'playground_projectdiscussioncomments')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_projectdiscussioncomments' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `discussion_id` int(11) NOT NULL,
                `discussion_type` varchar(10) NOT NULL,
                `parent` int(11) DEFAULT NULL,
                `created` datetime NOT NULL,
                `modified` datetime DEFAULT NULL,
                `content` mediumtext NOT NULL,
                `staff_id` int(11) NOT NULL,
                `contact_id` int(11) DEFAULT 0,
                `fullname` varchar(191) DEFAULT NULL,
                `file_name` varchar(191) DEFAULT NULL,
                `file_mime_type` varchar(70) DEFAULT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_projectdiscussions')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_projectdiscussions' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `project_id` int(11) NOT NULL,
                `subject` varchar(191) NOT NULL,
                `description` mediumtext NOT NULL,
                `show_to_customer` tinyint(1) NOT NULL DEFAULT 0,
                `datecreated` datetime NOT NULL,
                `last_activity` datetime DEFAULT NULL,
                `staff_id` int(11) NOT NULL DEFAULT 0,
                `contact_id` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_project_activity')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_project_activity' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `project_id` int(11) NOT NULL,
                `staff_id` int(11) NOT NULL DEFAULT 0,
                `contact_id` int(11) NOT NULL DEFAULT 0,
                `fullname` varchar(100) DEFAULT NULL,
                `visible_to_customer` int(11) NOT NULL DEFAULT 0,
                `description_key` varchar(191) NOT NULL COMMENT \'Language file key\',
                `additional_data` mediumtext DEFAULT NULL,
                `dateadded` datetime NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_project_files')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_project_files' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `file_name` varchar(191) NOT NULL,
                `original_file_name` longtext DEFAULT NULL,
                `subject` varchar(191) DEFAULT NULL,
                `description` mediumtext DEFAULT NULL,
                `filetype` varchar(50) DEFAULT NULL,
                `dateadded` datetime NOT NULL,
                `last_activity` datetime DEFAULT NULL,
                `project_id` int(11) NOT NULL,
                `visible_to_customer` tinyint(1) DEFAULT 0,
                `staffid` int(11) NOT NULL,
                `contact_id` int(11) NOT NULL DEFAULT 0,
                `external` varchar(40) DEFAULT NULL,
                `external_link` mediumtext DEFAULT NULL,
                `thumbnail_link` mediumtext DEFAULT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_project_members')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_project_members' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `project_id` int(11) NOT NULL,
                `staff_id` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `project_id` (`project_id`),
                KEY `staff_id` (`staff_id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_project_notes')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_project_notes' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `project_id` int(11) NOT NULL,
                `content` mediumtext NOT NULL,
                `staff_id` int(11) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_project_settings')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_project_settings' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `project_id` int(11) NOT NULL,
                `name` varchar(100) NOT NULL,
                `value` mediumtext DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `project_id` (`project_id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_pinned_projects')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_pinned_projects' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `project_id` int(11) NOT NULL,
                `staff_id` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `project_id` (`project_id`));
            ');
        }
        
        
        if (!$this->db->table_exists(db_prefix() . 'playground_proposals')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_proposals' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `subject` varchar(191) DEFAULT NULL,
                `content` longtext DEFAULT NULL,
                `addedfrom` int(11) NOT NULL,
                `datecreated` datetime NOT NULL,
                `total` decimal(15,2) DEFAULT NULL,
                `subtotal` decimal(15,2) NOT NULL,
                `total_tax` decimal(15,2) NOT NULL DEFAULT 0.00,
                `adjustment` decimal(15,2) DEFAULT NULL,
                `discount_percent` decimal(15,2) NOT NULL,
                `discount_total` decimal(15,2) NOT NULL,
                `discount_type` varchar(30) DEFAULT NULL,
                `show_quantity_as` int(11) NOT NULL DEFAULT 1,
                `currency` int(11) NOT NULL,
                `open_till` date DEFAULT NULL,
                `date` date NOT NULL,
                `rel_id` int(11) DEFAULT NULL,
                `rel_type` varchar(40) DEFAULT NULL,
                `assigned` int(11) DEFAULT NULL,
                `hash` varchar(32) NOT NULL,
                `proposal_to` varchar(191) DEFAULT NULL,
                `project_id` int(11) DEFAULT NULL,
                `country` int(11) NOT NULL DEFAULT 0,
                `zip` varchar(50) DEFAULT NULL,
                `state` varchar(100) DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `address` varchar(200) DEFAULT NULL,
                `email` varchar(150) DEFAULT NULL,
                `phone` varchar(50) DEFAULT NULL,
                `allow_comments` tinyint(1) NOT NULL DEFAULT 1,
                `status` int(11) NOT NULL,
                `estimate_id` int(11) DEFAULT NULL,
                `invoice_id` int(11) DEFAULT NULL,
                `date_converted` datetime DEFAULT NULL,
                `pipeline_order` int(11) DEFAULT 1,
                `is_expiry_notified` int(11) NOT NULL DEFAULT 0,
                `acceptance_firstname` varchar(50) DEFAULT NULL,
                `acceptance_lastname` varchar(50) DEFAULT NULL,
                `acceptance_email` varchar(100) DEFAULT NULL,
                `acceptance_date` datetime DEFAULT NULL,
                `acceptance_ip` varchar(40) DEFAULT NULL,
                `signature` varchar(40) DEFAULT NULL,
                `short_link` varchar(100) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `status` (`status`));
            ');
        }
        

        if (!$this->db->table_exists(db_prefix() . 'playground_roles')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_roles' . '` (
                `roleid` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(150) NOT NULL,
                `permissions` longtext DEFAULT NULL,
                PRIMARY KEY (`roleid`));
            ');
        }
        
        
        if (!$this->db->table_exists(db_prefix() . 'playground_spam_filters')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_spam_filters' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `type` varchar(40) NOT NULL,
                `rel_type` varchar(10) NOT NULL,
                `value` mediumtext NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }
        
        
        if (!$this->db->table_exists(db_prefix() . 'playground_staff')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_staff' . '` (
                `staffid` int(11) NOT NULL AUTO_INCREMENT,
                `email` varchar(100) NOT NULL,
                `firstname` varchar(50) NOT NULL,
                `lastname` varchar(50) NOT NULL,
                `facebook` longtext DEFAULT NULL,
                `linkedin` longtext DEFAULT NULL,
                `phonenumber` varchar(30) DEFAULT NULL,
                `skype` varchar(50) DEFAULT NULL,
                `password` varchar(250) NOT NULL,
                `datecreated` datetime NOT NULL,
                `profile_image` varchar(191) DEFAULT NULL,
                `last_ip` varchar(40) DEFAULT NULL,
                `last_login` datetime DEFAULT NULL,
                `last_activity` datetime DEFAULT NULL,
                `last_password_change` datetime DEFAULT NULL,
                `new_pass_key` varchar(32) DEFAULT NULL,
                `new_pass_key_requested` datetime DEFAULT NULL,
                `admin` int(11) NOT NULL DEFAULT 0,
                `role` int(11) DEFAULT NULL,
                `active` int(11) NOT NULL DEFAULT 1,
                `default_language` varchar(40) DEFAULT NULL,
                `direction` varchar(3) DEFAULT NULL,
                `media_path_slug` varchar(191) DEFAULT NULL,
                `is_not_staff` int(11) NOT NULL DEFAULT 0,
                `hourly_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
                `two_factor_auth_enabled` tinyint(1) DEFAULT 0,
                `two_factor_auth_code` varchar(100) DEFAULT NULL,
                `two_factor_auth_code_requested` datetime DEFAULT NULL,
                `email_signature` mediumtext DEFAULT NULL,
                `birthday` date DEFAULT NULL,
                `birthplace` varchar(200) DEFAULT NULL,
                `sex` varchar(15) DEFAULT NULL,
                `marital_status` varchar(25) DEFAULT NULL,
                `nation` varchar(25) DEFAULT NULL,
                `religion` varchar(50) DEFAULT NULL,
                `identification` varchar(100) DEFAULT NULL,
                `days_for_identity` date DEFAULT NULL,
                `home_town` varchar(200) DEFAULT NULL,
                `resident` varchar(200) DEFAULT NULL,
                `current_address` varchar(200) DEFAULT NULL,
                `literacy` varchar(50) DEFAULT NULL,
                `orther_infor` text DEFAULT NULL,
                `job_position` int(11) DEFAULT NULL,
                `workplace` int(11) DEFAULT NULL,
                `place_of_issue` varchar(50) DEFAULT NULL,
                `account_number` varchar(50) DEFAULT NULL,
                `name_account` varchar(50) DEFAULT NULL,
                `issue_bank` varchar(200) DEFAULT NULL,
                `records_received` longtext DEFAULT NULL,
                `Personal_tax_code` varchar(50) DEFAULT NULL,
                `google_auth_secret` mediumtext DEFAULT NULL,
                `team_manage` int(11) DEFAULT 0,
                `staff_identifi` varchar(200) DEFAULT NULL,
                `status_work` varchar(100) DEFAULT NULL,
                `date_update` date DEFAULT NULL,
                `token` varchar(255) DEFAULT NULL,
                `mail_password` varchar(250) DEFAULT NULL,
                `mail_signature` varchar(250) DEFAULT NULL,
                `last_email_check` varchar(50) DEFAULT NULL,
                PRIMARY KEY (`staffid`),
                KEY `firstname` (`firstname`),
                KEY `lastname` (`lastname`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_staff_departments')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_staff_departments' . '` (
                `staffdepartmentid` int(11) NOT NULL AUTO_INCREMENT,
                `staffid` int(11) NOT NULL,
                `departmentid` int(11) NOT NULL,
                PRIMARY KEY (`staffdepartmentid`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_staff_permissions')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_staff_permissions' . '` (
                `staff_id` int(11) NOT NULL,
                `feature` varchar(40) NOT NULL,
                `capability` varchar(100) NOT NULL);
            ');
        }
        

        if (!$this->db->table_exists(db_prefix() . 'playground_subscriptions')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_subscriptions' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(191) NOT NULL,
                `description` mediumtext DEFAULT NULL,
                `description_in_item` tinyint(1) NOT NULL DEFAULT 0,
                `clientid` int(11) NOT NULL,
                `date` date DEFAULT NULL,
                `terms` mediumtext DEFAULT NULL,
                `currency` int(11) NOT NULL,
                `tax_id` int(11) NOT NULL DEFAULT 0,
                `stripe_tax_id` varchar(50) DEFAULT NULL,
                `tax_id_2` int(11) NOT NULL DEFAULT 0,
                `stripe_tax_id_2` varchar(50) DEFAULT NULL,
                `stripe_plan_id` mediumtext DEFAULT NULL,
                `stripe_subscription_id` mediumtext NOT NULL,
                `next_billing_cycle` bigint(20) DEFAULT NULL,
                `ends_at` bigint(20) DEFAULT NULL,
                `status` varchar(50) DEFAULT NULL,
                `quantity` int(11) NOT NULL DEFAULT 1,
                `project_id` int(11) NOT NULL DEFAULT 0,
                `hash` varchar(32) NOT NULL,
                `created` datetime NOT NULL,
                `created_from` int(11) NOT NULL,
                `date_subscribed` datetime DEFAULT NULL,
                `in_test_environment` int(11) DEFAULT NULL,
                `last_sent_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `clientid` (`clientid`),
                KEY `currency` (`currency`),
                KEY `tax_id` (`tax_id`));
            ');
        }
        

        if (!$this->db->table_exists(db_prefix() . 'playground_tasks')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_tasks' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` longtext DEFAULT NULL,
                `description` mediumtext DEFAULT NULL,
                `priority` int(11) DEFAULT NULL,
                `dateadded` datetime NOT NULL,
                `startdate` date NOT NULL,
                `duedate` date DEFAULT NULL,
                `datefinished` datetime DEFAULT NULL,
                `addedfrom` int(11) NOT NULL,
                `is_added_from_contact` tinyint(1) NOT NULL DEFAULT 0,
                `status` int(11) NOT NULL DEFAULT 0,
                `recurring_type` varchar(10) DEFAULT NULL,
                `repeat_every` int(11) DEFAULT NULL,
                `recurring` int(11) NOT NULL DEFAULT 0,
                `is_recurring_from` int(11) DEFAULT NULL,
                `cycles` int(11) NOT NULL DEFAULT 0,
                `total_cycles` int(11) NOT NULL DEFAULT 0,
                `custom_recurring` tinyint(1) NOT NULL DEFAULT 0,
                `last_recurring_date` date DEFAULT NULL,
                `rel_id` int(11) DEFAULT NULL,
                `rel_type` varchar(30) DEFAULT NULL,
                `is_public` tinyint(1) NOT NULL DEFAULT 0,
                `billable` tinyint(1) NOT NULL DEFAULT 0,
                `billed` tinyint(1) NOT NULL DEFAULT 0,
                `invoice_id` int(11) NOT NULL DEFAULT 0,
                `hourly_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
                `milestone` int(11) DEFAULT 0,
                `kanban_order` int(11) DEFAULT 1,
                `milestone_order` int(11) NOT NULL DEFAULT 0,
                `visible_to_client` tinyint(1) NOT NULL DEFAULT 0,
                `deadline_notified` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `rel_id` (`rel_id`),
                KEY `rel_type` (`rel_type`),
                KEY `milestone` (`milestone`),
                KEY `kanban_order` (`kanban_order`),
                KEY `status` (`status`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_taskstimers')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_taskstimers' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `task_id` int(11) NOT NULL,
                `start_time` varchar(64) NOT NULL,
                `end_time` varchar(64) DEFAULT NULL,
                `staff_id` int(11) NOT NULL,
                `hourly_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
                `note` mediumtext DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `task_id` (`task_id`),
                KEY `staff_id` (`staff_id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_tasks_checklist_templates')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_tasks_checklist_templates' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `description` mediumtext DEFAULT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_task_assigned')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_task_assigned' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `staffid` int(11) NOT NULL,
                `taskid` int(11) NOT NULL,
                `assigned_from` int(11) NOT NULL DEFAULT 0,
                `is_assigned_from_contact` tinyint(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `taskid` (`taskid`),
                KEY `staffid` (`staffid`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_task_checklist_items')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_task_checklist_items' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `taskid` int(11) NOT NULL,
                `description` mediumtext NOT NULL,
                `finished` int(11) NOT NULL DEFAULT 0,
                `dateadded` datetime NOT NULL,
                `addedfrom` int(11) NOT NULL,
                `finished_from` int(11) DEFAULT 0,
                `list_order` int(11) NOT NULL DEFAULT 0,
                `assigned` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `taskid` (`taskid`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_task_comments')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_task_comments' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `content` mediumtext NOT NULL,
                `taskid` int(11) NOT NULL,
                `staffid` int(11) NOT NULL,
                `contact_id` int(11) NOT NULL DEFAULT 0,
                `file_id` int(11) NOT NULL DEFAULT 0,
                `dateadded` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `file_id` (`file_id`),
                KEY `taskid` (`taskid`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_task_followers')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_task_followers' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `staffid` int(11) NOT NULL,
                `taskid` int(11) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }
        

        if (!$this->db->table_exists(db_prefix() . 'playground_taxes')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_taxes' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `taxrate` decimal(15,2) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_user_auto_login')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_user_auto_login' . '` (
                `key_id` char(32) NOT NULL,
                `user_id` int(11) NOT NULL,
                `user_agent` varchar(150) NOT NULL,
                `last_ip` varchar(40) NOT NULL,
                `last_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                `staff` int(11) NOT NULL);
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_tickets')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_tickets' . '` (
                `ticketid` int(11) NOT NULL AUTO_INCREMENT,
                `adminreplying` int(11) NOT NULL DEFAULT 0,
                `userid` int(11) NOT NULL,
                `contactid` int(11) NOT NULL DEFAULT 0,
                `merged_ticket_id` int(11) DEFAULT NULL,
                `email` mediumtext DEFAULT NULL,
                `name` mediumtext DEFAULT NULL,
                `department` int(11) NOT NULL,
                `priority` int(11) NOT NULL,
                `status` int(11) NOT NULL,
                `service` int(11) DEFAULT NULL,
                `ticketkey` varchar(32) NOT NULL,
                `subject` varchar(191) NOT NULL,
                `message` mediumtext DEFAULT NULL,
                `admin` int(11) DEFAULT NULL,
                `date` datetime NOT NULL,
                `project_id` int(11) NOT NULL DEFAULT 0,
                `lastreply` datetime DEFAULT NULL,
                `clientread` int(11) NOT NULL DEFAULT 0,
                `adminread` int(11) NOT NULL DEFAULT 0,
                `assigned` int(11) NOT NULL DEFAULT 0,
                `staff_id_replying` int(11) DEFAULT NULL,
                `cc` varchar(191) DEFAULT NULL,
                PRIMARY KEY (`ticketid`),
                KEY `service` (`service`),
                KEY `department` (`department`),
                KEY `status` (`status`),
                KEY `userid` (`userid`),
                KEY `priority` (`priority`),
                KEY `project_id` (`project_id`),
                KEY `contactid` (`contactid`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_tickets_pipe_log')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_tickets_pipe_log' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `date` datetime NOT NULL,
                `email_to` varchar(100) NOT NULL,
                `name` varchar(191) NOT NULL,
                `subject` varchar(191) NOT NULL,
                `message` longtext NOT NULL,
                `email` varchar(100) NOT NULL,
                `status` varchar(100) NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_tickets_predefined_replies')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_tickets_predefined_replies' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(191) NOT NULL,
                `message` mediumtext NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_tickets_priorities')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_tickets_priorities' . '` (
                `priorityid` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                PRIMARY KEY (`priorityid`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_tickets_status')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_tickets_status' . '` (
                `ticketstatusid` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                `isdefault` int(11) NOT NULL DEFAULT 0,
                `statuscolor` varchar(7) DEFAULT NULL,
                `statusorder` int(11) DEFAULT NULL,
                PRIMARY KEY (`ticketstatusid`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_ticket_attachments')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_ticket_attachments' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ticketid` int(11) NOT NULL,
                `replyid` int(11) DEFAULT NULL,
                `file_name` varchar(191) NOT NULL,
                `filetype` varchar(50) DEFAULT NULL,
                `dateadded` datetime NOT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_ticket_replies')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_ticket_replies' . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ticketid` int(11) NOT NULL,
                `userid` int(11) DEFAULT NULL,
                `contactid` int(11) NOT NULL DEFAULT 0,
                `name` mediumtext DEFAULT NULL,
                `email` mediumtext DEFAULT NULL,
                `date` datetime NOT NULL,
                `message` mediumtext DEFAULT NULL,
                `attachment` int(11) DEFAULT NULL,
                `admin` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`));
            ');
        }

        if (!$this->db->table_exists(db_prefix() . 'playground_services')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'playground_services' . '` (
                `serviceid` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                PRIMARY KEY (`serviceid`));
            ');
        }
    }

    /**
     * Constructor for the REST API
     *
     * @access public
     * @param string $config Configuration filename minus the file extension
     * e.g: my_rest.php is passed as 'my_rest'
     */
    public function __construct($config = 'rest') {
        parent::__construct();
        
        $this->rest_model = new REST_model();
        $this->authorization_token = new Authorization_Token();

        $this->preflight_checks();

        // Set the default value of global xss filtering. Same approach as CodeIgniter 3
        $this->_enable_xss = ($this->config->item('global_xss_filtering') === TRUE);

        // Don't try to parse template variables like {elapsed_time} and {memory_usage}

        // when output is displayed for not damaging data accidentally
        $this->output->parse_exec_vars = FALSE;

        // Start the timer for how long the request takes
        $this->_start_rtime = microtime(TRUE);

        $this->get_local_config('api');
        // Load the rest.php configuration file
        $this->get_local_config($config);

        // At present the library is bundled with REST_Controller 2.5+, but will eventually be part of CodeIgniter (no citation)
        if (class_exists('Format')) {
            $this->format = new \Format();
        } else {
            $this->load->library('Format', NULL, 'libraryFormat');
            $this->format = $this->libraryFormat;
        }

        // Determine supported output formats from configuration
        $supported_formats = $this->config->item('rest_supported_formats');

        // Validate the configuration setting output formats
        if (empty($supported_formats)) {
            $supported_formats = [];
        }
        if (!is_array($supported_formats)) {
            $supported_formats = [$supported_formats];
        }

        // Add silently the default output format if it is missing
        $default_format = $this->_get_default_output_format();
        if (!in_array($default_format, $supported_formats)) {
            $supported_formats[] = $default_format;
        }

        // Now update $this->_supported_formats
        $this->_supported_formats = array_intersect_key($this->_supported_formats, array_flip($supported_formats));

        // Get the language
        $language = $this->config->item('rest_language');
        if ($language === NULL) {
            $language = 'english';
        }

        // Load the language file
        $this->lang->load('rest_controller', $language, FALSE, TRUE, __DIR__ . '/../');

        // Initialise the response, request and rest objects
        $this->request = new \stdClass();
        $this->response = new \stdClass();
        $this->rest = new \stdClass();

        // Check to see if the current IP address is blacklisted
        if ($this->config->item('rest_ip_blacklist_enabled') === TRUE) {
            $this->_check_blacklist_auth();
        }

        // Determine whether the connection is HTTPS
        $this->request->ssl = is_https();

        // How is this request being made? GET, POST, PATCH, DELETE, INSERT, PUT, HEAD or OPTIONS
        $this->request->method = $this->_detect_method();

        // Check for CORS access request
        $check_cors = $this->config->item('check_cors');
        if ($check_cors === TRUE) {
            $this->_check_cors();
        }

        // Create an argument container if it doesn't exist e.g. _get_args
        if (isset($this->{'_' . $this->request->method . '_args'}) === FALSE) {
            $this->{'_' . $this->request->method . '_args'} = [];
        }

        // Set up the query parameters
        $this->_parse_query();

        // Set up the GET variables
        $this->_get_args = array_merge($this->_get_args, $this->uri->ruri_to_assoc());

        // Try to find a format for the request (means we have a request body)
        $this->request->format = $this->_detect_input_format();

        // Not all methods have a body attached with them
        $this->request->body = NULL;
        $this->{'_parse_' . $this->request->method}();

        // Fix parse method return arguments null
        if ($this->{'_' . $this->request->method . '_args'} === null) {
            $this->{'_' . $this->request->method . '_args'} = [];
        }

        // Now we know all about our request, let's try and parse the body if it exists
        if ($this->request->format && $this->request->body) {
            $this->request->body = $this->format->factory($this->request->body, $this->request->format)->to_array();
            // Assign payload arguments to proper method container
            $this->{'_' . $this->request->method . '_args'} = $this->request->body;
        }
        //get header vars
        $this->_head_args = $this->input->request_headers();

        // Merge both for one mega-args variable
        $this->_args = array_merge($this->_get_args, $this->_options_args, $this->_patch_args, $this->_head_args, $this->_put_args, $this->_post_args, $this->_delete_args, $this->{'_' . $this->request->method . '_args'});

        // Which format should the data be returned in?
        $this->response->format = $this->_detect_output_format();

        // Which language should the data be returned in?
        $this->response->lang = $this->_detect_lang();

        // Extend this function to apply additional checking early on in the process
        $this->early_checks();
        $this->load->library('app_modules');
        if (!$this->app_modules->is_active('api')) {
            $this->response(['status' => FALSE, 'message' => "API Module is not active"], REST_Controller::HTTP_UNAUTHORIZED);
        }

        // Load DB if its enabled
        if ($this->config->item('rest_database_group') && ($this->config->item('rest_enable_keys') || $this->config->item('rest_enable_logging'))) {
            $this->rest->db = $this->load->database($this->config->item('rest_database_group'), TRUE);
        }

        // Use whatever database is in use (isset returns FALSE)
        else if (property_exists($this, 'db')) {
            $this->rest->db = $this->db;
        }

        // Check if there is a specific auth type for the current class/method
        // _auth_override_check could exit so we need $this->rest->db initialized before
        $this->auth_override = $this->_auth_override_check();

        // Checking for keys? GET TO WorK!
        // Skip keys test for $config['auth_override_class_method']['class'['method'] = 'none'
        if ($this->config->item('rest_enable_keys') && $this->auth_override !== TRUE) {
            $this->_allow = $this->_detect_api_key();
        }

        // Only allow ajax requests
        if ($this->input->is_ajax_request() === FALSE && $this->config->item('rest_ajax_only')) {
            // Display an error response
            $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ajax_only') ], self::HTTP_NOT_ACCEPTABLE);
        }

        // When there is no specific override for the current class/method, use the default auth value set in the config
        if ($this->auth_override === FALSE && (!($this->config->item('rest_enable_keys') && $this->_allow === TRUE) || ($this->config->item('allow_auth_and_keys') === TRUE && $this->_allow === TRUE))) {
            $rest_auth = strtolower($this->config->item('rest_auth'));
            switch ($rest_auth) {
                case 'basic':
                    $this->_prepare_basic_auth();
                break;
                case 'digest':
                    $this->_prepare_digest_auth();
                break;
                case 'session':
                    $this->_check_php_session();
                break;
            }
            if ($this->config->item('rest_ip_whitelist_enabled') === TRUE) {
                $this->_check_whitelist_auth();
            }
        }

        // load authorization token library
        $is_valid_token = $check_token = $this->authorization_token->validateToken();
        $token = $this->authorization_token->get_token();
        $check_token = $this->rest_model->check_token($token);
        if ($is_valid_token['status'] == false || $check_token === false) {
            $message = array(
                'status' => FALSE,
                'message' => isset($is_valid_token['message']) ? $is_valid_token['message'] : sprintf($this->lang->line('text_rest_invalid_api_key'), $this->rest->key)
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public static function get_swagger_file() {
        echo file_get_contents(dirname(__DIR__) . '/config/swagger.json');
    }

    /**
     * @param $config_file
     */
    private function get_local_config($config_file) {
        if (file_exists(__DIR__ . "/../config/" . $config_file . ".php")) {
            $config = array();
            include (__DIR__ . "/../config/" . $config_file . ".php");
            foreach ($config AS $key => $value) {
                $this->config->set_item($key, $value);
            }
        }
        $this->load->config($config_file, FALSE, TRUE);
    }

    /**
     * De-constructor
     *
     * @author Chris Kacerguis
     * @access public
     * @return void
     */
    public function __destruct() {
        // Get the current timestamp
        $this->_end_rtime = microtime(TRUE);

        // Log the loading time to the log table
        if ($this->config->item('rest_enable_logging') === TRUE) {
            $this->_log_access_time();
        }
    }

    /**
     * Checks to see if we have everything we need to run this library.
     *
     * @access protected
     * @throws Exception
     */
    protected function preflight_checks() {
        // Check to see if PHP is equal to or greater than 5.4.x
        if (is_php('5.4') === FALSE) {
            // CodeIgniter 3 is recommended for v5.4 or above
            throw new \Exception('Using PHP v' . PHP_VERSION . ', though PHP v5.4 or greater is required');
        }

        // Check to see if this is CI 3.x
        if (explode('.', CI_VERSION, 2) [0] < 3) {
            throw new \Exception('REST Server requires CodeIgniter 3.x');
        }
    }

    /**
     * Requests are not made to methods directly, the request will be for
     * an "object". This simply maps the object and method to the correct
     * Controller method
     *
     * @access public
     * @param string $object_called
     * @param array $arguments The arguments passed to the controller method
     * @throws Exception
     */
    public function _remap($object_called, $arguments = []) {
        // Should we answer if not over SSL?
        if ($this->config->item('force_https') && $this->request->ssl === FALSE) {
            $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unsupported') ], self::HTTP_FORBIDDEN);
            $this->is_valid_request = false;
        }
        
        // Remove the supported format from the function name e.g. index.json => index
        $object_called = preg_replace('/^(.*)\.(?:' . implode('|', array_keys($this->_supported_formats)) . ')$/', '$1', $object_called);
        $controller_method = $object_called . '_' . $this->request->method;

        // Does this method exist? If not, try executing an index method
        if (!method_exists($this, $controller_method)) {
            $controller_method = "data_" . $this->request->method;
            array_unshift($arguments, $object_called);
        }

        // Do we want to log this method (if allowed by config)?
        $log_method = !(isset($this->methods[$controller_method]['log']) && $this->methods[$controller_method]['log'] === FALSE);

        // Use keys for this method?
        $use_key = !(isset($this->methods[$controller_method]['key']) && $this->methods[$controller_method]['key'] === FALSE);

        // They provided a key, but it wasn't valid, so get them out of here
        if ($this->config->item('rest_enable_keys') && $use_key && $this->_allow === FALSE) {
            if ($this->config->item('rest_enable_logging') && $log_method) {
                $this->_log_request();
            }
            // fix cross site to option request error
            if ($this->request->method == 'options') {
                exit;
            }
            $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => sprintf($this->lang->line('text_rest_invalid_api_key'), $this->rest->key) ], self::HTTP_FORBIDDEN);
            $this->is_valid_request = false;
        }

        // Check to see if this key has access to the requested controller
        if ($this->config->item('rest_enable_keys') && $use_key && empty($this->rest->key) === FALSE && $this->_check_access() === FALSE) {
            if ($this->config->item('rest_enable_logging') && $log_method) {
                $this->_log_request();
            }
            $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_unauthorized') ], self::HTTP_UNAUTHORIZED);
            $this->is_valid_request = false;
        }

        // Sure it exists, but can they do anything with it?
        if (!method_exists($this, $controller_method)) {
            $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unknown_method') ], self::HTTP_METHOD_NOT_ALLOWED);
            $this->is_valid_request = false;
        }

        $object_name = get_class($this);
        $token = $this->authorization_token->get_token();
        $check_token_permission = $this->rest_model->check_token_permission($token, strtolower($object_name), str_replace("data_", "", $controller_method));
        if ($check_token_permission === false) {
            $message = array('status' => FALSE, 'message' => $this->lang->line('text_rest_api_key_permissions'));
            $this->response($message, self::HTTP_NOT_PERMISSION);
            $this->is_valid_request = false;
        }
        
        // Doing key related stuff? Can only do it if they have a key right?
        if ($this->config->item('rest_enable_keys') && empty($this->rest->key) === FALSE) {
            // Check the limit
            if ($this->config->item('rest_enable_limits') && $this->_check_limit($controller_method) === FALSE) {
                $response = [$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_time_limit') ];
                $this->response($response, self::HTTP_UNAUTHORIZED);
                $this->is_valid_request = false;
            }
            // If no level is set use 0, they probably aren't using permissions
            $level = isset($this->methods[$controller_method]['level']) ? $this->methods[$controller_method]['level'] : 0;
            // If no level is set, or it is lower than/equal to the key's level
            $authorized = $level <= $this->rest->level;
            // IM TELLIN!
            if ($this->config->item('rest_enable_logging') && $log_method) {
                $this->_log_request($authorized);
            }
            if ($authorized === FALSE) {
                // They don't have good enough perms
                $response = [$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_permissions') ];
                $this->response($response, self::HTTP_UNAUTHORIZED);
                $this->is_valid_request = false;
            }
        }
        //check request limit by ip without login
        else if ($this->config->item('rest_limits_method') == "IP_ADDRESS" && $this->config->item('rest_enable_limits') && $this->_check_limit($controller_method) === FALSE) {
            $response = [$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ip_address_time_limit') ];
            $this->response($response, self::HTTP_UNAUTHORIZED);
            $this->is_valid_request = false;
        }
        // No key stuff, but record that stuff is happening
        else if ($this->config->item('rest_enable_logging') && $log_method) {
            $this->_log_request($authorized = TRUE);
        }

        // Call the controller method and passed arguments
        try {
            if ($this->is_valid_request) {
                call_user_func_array([$this, $controller_method], $arguments);
            }
        }
        catch(Exception $ex) {
            if ($this->config->item('rest_handle_exceptions') === FALSE) {
                throw $ex;
            }
            // If the method doesn't exist, then the error will be caught and an error response shown
            $_error = & load_class('Exceptions', 'core');
            $_error->show_exception($ex);
        }
    }

    /**
     * Takes mixed data and optionally a status code, then creates the response
     *
     * @access public
     * @param array|NULL $data Data to output to the user
     * @param int|NULL $http_code HTTP status code
     * @param bool $continue TRUE to flush the response to the client and continue
     * running the script; otherwise, exit
     */
    public function response($data = NULL, $http_code = NULL, $continue = FALSE) {
        ob_start();

        // If the HTTP status is not NULL, then cast as an integer
        if ($http_code !== NULL) {
            // So as to be safe later on in the process
            $http_code = (int)$http_code;
        }

        // Set the output as NULL by default
        $output = NULL;

        // If data is NULL and no HTTP status code provided, then display, error and exit
        if ($data === NULL && $http_code === NULL) {
            $http_code = self::HTTP_NOT_FOUND;
        }

        // If data is not NULL and a HTTP status code provided, then continue
        else if ($data !== NULL) {
            // If the format method exists, call and return the output in that format
            if (method_exists($this->format, 'to_' . $this->response->format)) {
                // Set the format header
                $this->output->set_content_type($this->_supported_formats[$this->response->format], strtolower($this->config->item('charset')));
                $output = $this->format->factory($data)->{'to_' . $this->response->format}();
                // An array must be parsed as a string, so as not to cause an array to string error
                // Json is the most appropriate form for such a data type
                if ($this->response->format === 'array') {
                    $output = $this->format->factory($output)->{'to_json'}();
                }
            } else {
                // If an array or object, then parse as a json, so as to be a 'string'
                if (is_array($data) || is_object($data)) {
                    $data = $this->format->factory($data)->{'to_json'}();
                }
                // Format is not supported, so output the raw data as a string
                $output = $data;
            }
        }

        // If not greater than zero, then set the HTTP status code as 200 by default
        // Though perhaps 500 should be set instead, for the developer not passing a
        // correct HTTP status code
        $http_code > 0 || $http_code = self::HTTP_OK;
        $this->output->set_status_header($http_code);

        // JC: Log response code only if rest logging enabled
        if ($this->config->item('rest_enable_logging') === TRUE) {
            $this->_log_response_code($http_code);
        }

        // Output the data
        $this->output->set_output($output);
        if ($continue === FALSE) {
            // Display the data and exit execution
            $this->output->_display();
            exit;
        } else {
            ob_end_flush();
        }

        // Otherwise dump the output automatically        
    }

    /**
     * Takes mixed data and optionally a status code, then creates the response
     * within the buffers of the Output class. The response is sent to the client
     * lately by the framework, after the current controller's method termination.
     * All the hooks after the controller's method termination are executable
     *
     * @access public
     * @param array|NULL $data Data to output to the user
     * @param int|NULL $http_code HTTP status code
     */
    public function set_response($data = NULL, $http_code = NULL) {
        $this->response($data, $http_code, TRUE);
    }

    /**
     * Get the input format e.g. json or xml
     *
     * @access protected
     * @return string|NULL Supported input format; otherwise, NULL
     */
    protected function _detect_input_format() {
        // Get the CONTENT-TYPE value from the SERVER variable
        $content_type = $this->input->server('CONTENT_TYPE');
        if (empty($content_type) === FALSE) {
            // If a semi-colon exists in the string, then explode by ; and get the value of where
            // the current array pointer resides. This will generally be the first element of the array
            $content_type = (strpos($content_type, ';') !== FALSE ? current(explode(';', $content_type)) : $content_type);
            // Check all formats against the CONTENT-TYPE header
            foreach ($this->_supported_formats as $type => $mime) {
                // $type = format e.g. csv
                // $mime = mime type e.g. application/csv
                // If both the mime types match, then return the format
                if ($content_type === $mime) {
                    return $type;
                }
            }
        }
        return NULL;
    }

    /**
     * Gets the default format from the configuration. Fallbacks to 'json'
     * if the corresponding configuration option $config['rest_default_format']
     * is missing or is empty
     *
     * @access protected
     * @return string The default supported input format
     */
    protected function _get_default_output_format() {
        $default_format = (string)$this->config->item('rest_default_format');
        return $default_format === '' ? 'json' : $default_format;
    }

    /**
     * Detect which format should be used to output the data
     *
     * @access protected
     * @return mixed|NULL|string Output format
     */
    protected function _detect_output_format() {
        // Concatenate formats to a regex pattern e.g. \.(csv|json|xml)
        $pattern = '/\.(' . implode('|', array_keys($this->_supported_formats)) . ')($|\/)/';
        $matches = [];

        // Check if a file extension is used e.g. http://example.com/api/index.json?param1=param2
        if (preg_match($pattern, $this->uri->uri_string(), $matches)) {
            return $matches[1];
        }

        // Get the format parameter named as 'format'
        if (isset($this->_get_args['format'])) {
            $format = strtolower($this->_get_args['format']);
            if (isset($this->_supported_formats[$format]) === TRUE) {
                return $format;
            }
        }

        // Get the HTTP_ACCEPT server variable
        $http_accept = $this->input->server('HTTP_ACCEPT');

        // Otherwise, check the HTTP_ACCEPT server variable
        if ($this->config->item('rest_ignore_http_accept') === FALSE && $http_accept !== NULL) {
            // Check all formats against the HTTP_ACCEPT header
            foreach (array_keys($this->_supported_formats) as $format) {
                // Has this format been requested?
                if (strpos($http_accept, $format) !== FALSE) {
                    if ($format !== 'html' && $format !== 'xml') {
                        // If not HTML or XML assume it's correct
                        return $format;
                    } else if ($format === 'html' && strpos($http_accept, 'xml') === FALSE) {
                        // HTML or XML have shown up as a match
                        // If it is truly HTML, it wont want any XML
                        return $format;
                    } else if ($format === 'xml' && strpos($http_accept, 'html') === FALSE) {
                        // If it is truly XML, it wont want any HTML
                        return $format;
                    }
                }
            }
        }

        // Check if the controller has a default format
        if (empty($this->rest_format) === FALSE) {
            return $this->rest_format;
        }

        // Obtain the default format from the configuration
        return $this->_get_default_output_format();
    }

    /**
     * Get the HTTP request string e.g. get or post
     *
     * @access protected
     * @return string|NULL Supported request method as a lowercase string; otherwise, NULL if not supported
     */
    protected function _detect_method() {
        // Declare a variable to store the method
        $method = NULL;

        // Determine whether the 'enable_emulate_request' setting is enabled
        if ($this->config->item('enable_emulate_request') === TRUE) {
            $method = $this->input->post('_method');
            if ($method === NULL) {
                $method = $this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            }
            $method = strtolower($method??'');
        }
        if (empty($method)) {
            // Get the request method as a lowercase string
            $method = $this->input->method();
        }
        return in_array($method, $this->allowed_http_methods) && method_exists($this, '_parse_' . $method) ? $method : 'get';
    }

    /**
     * See if the user has provided an API key
     *
     * @access protected
     * @return bool
     */
    protected function _detect_api_key() {
        // Get the api key name variable set in the rest config file
        $api_key_variable = $this->config->item('rest_key_name');

        // Work out the name of the SERVER entry based on config
        $key_name = 'HTTP_' . strtoupper(str_replace('-', '_', $api_key_variable));
        $this->rest->key = NULL;
        $this->rest->level = NULL;
        $this->rest->user = NULL;
        $this->rest->ignore_limits = FALSE;
        $this->rest->playground = FALSE;

        // Find the key from server or arguments
        if (($key = isset($this->_args[$api_key_variable]) ? $this->_args[$api_key_variable] : $this->input->server($key_name))) {
            if (!($row = $this->rest->db->where($this->config->item('rest_key_column'), $key)->get($this->config->item('rest_keys_table'))->row())) {
                return FALSE;
            }

            $this->rest->key = $row->{$this->config->item('rest_key_column')};
            isset($row->user) && $this->rest->user = $row->user;
            isset($row->level) && $this->rest->level = $row->level;
            isset($row->ignore_limits) && $this->rest->ignore_limits = $row->ignore_limits;
            $this->rest->playground = isset($this->_args[strtolower($this->config->item('rest_playground_name'))]) ? TRUE : FALSE;
            $this->_apiuser = $row;

            /*            
             * If "is private key" is enabled, compare the ip address with the list            
             * of valid ip addresses stored in the database            
            */
            if (empty($row->is_private_key) === FALSE) {
                // Check for a list of valid ip addresses
                if (isset($row->ip_addresses)) {
                    // multiple ip addresses must be separated using a comma, explode and loop
                    $list_ip_addresses = explode(',', $row->ip_addresses);
                    $found_address = FALSE;
                    foreach ($list_ip_addresses as $ip_address) {
                        if ($this->input->ip_address() === trim($ip_address)) {
                            // there is a match, set the the value to TRUE and break out of the loop
                            $found_address = TRUE;
                            break;
                        }
                    }
                    return $found_address;
                } else {
                    // There should be at least one IP address for this private key
                    return FALSE;
                }
            }
            return TRUE;
        }

        // No key has been sent
        return FALSE;
    }

    /**
     * Preferred return language
     *
     * @access protected
     * @return string|NULL|array The language code
     */
    protected function _detect_lang() {
        $lang = $this->input->server('HTTP_ACCEPT_LANGUAGE');
        if ($lang === NULL) {
            return NULL;
        }

        // It appears more than one language has been sent using a comma delimiter
        if (strpos($lang, ',') !== FALSE) {
            $langs = explode(',', $lang);
            $return_langs = [];
            foreach ($langs as $lang) {
                // Remove weight and trim leading and trailing whitespace
                list($lang) = explode(';', $lang);
                $return_langs[] = trim($lang);
            }
            return $return_langs;
        }

        // Otherwise simply return as a string
        return $lang;
    }

    /**
     * Add the request to the log table
     *
     * @access protected
     * @param bool $authorized TRUE the user is authorized; otherwise, FALSE
     * @return bool TRUE the data was inserted; otherwise, FALSE
     */
    protected function _log_request($authorized = FALSE) {
        // Insert the request into the log table
        $is_inserted = $this->rest->db->insert($this->config->item('rest_logs_table'),
            [
                'uri' => $this->uri->uri_string(),
                'method' => $this->request->method,
                'params' => $this->_args ? ($this->config->item('rest_logs_json_params') === TRUE ? json_encode($this->_args) : serialize($this->_args)) : NULL,
                'api_key' => isset($this->rest->key) ? $this->rest->key : '',
                'ip_address' => $this->input->ip_address(),
                'time' => time(),
                'authorized' => $authorized
            ]);

        // Get the last insert id to update at a later stage of the request
        $this->_insert_id = $this->rest->db->insert_id();
        return $is_inserted;
    }

    /**
     * Check if the requests to a controller method exceed a limit
     *
     * @access protected
     * @param string $controller_method The method being called
     * @return bool TRUE the call limit is below the threshold; otherwise, FALSE
     */
    protected function _check_limit($controller_method) {
        // They are special, or it might not even have a limit
        if (empty($this->rest->ignore_limits) === FALSE) {
            // Everything is fine
            return TRUE;
        }
        $api_key = isset($this->rest->key) ? $this->rest->key : '';

        switch ($this->config->item('rest_limits_method')) {
            case 'IP_ADDRESS':
                $limited_uri = $this->input->ip_address();
                $api_key = $this->input->ip_address();
            break;
            case 'API_KEY':
                $limited_uri = $api_key;
            break;
            case 'METHOD_NAME':
                $limited_uri = $controller_method;
            break;
            case 'ROUTED_URL':
            default:
                $limited_uri = $this->uri->ruri_string();
                if (strpos(strrev($limited_uri), strrev($this->response->format)) === 0) {
                    $limited_uri = substr($limited_uri, 0, -strlen($this->response->format) - 1);
                }
                $limited_uri = 'uri:' . $limited_uri . ':' . $this->request->method; // It's good to differentiate GET from PUT
                
            break;
        }

        if (isset($this->methods[$controller_method]['limit']) === FALSE) {
            // Everything is fine
            return TRUE;
        }

        // How many times can you get to this method in a defined time_limit (default: 1 hour)?
        $limit = $this->methods[$controller_method]['limit'];
        $time_limit = (isset($this->methods[$controller_method]['time']) ? $this->methods[$controller_method]['time'] : 3600); // 3600 = 60 * 60
        // Get data about a keys' usage and limit to one row
        $result = $this->rest->db->where('uri', $limited_uri)->where('api_key', $api_key)
            ->get($this->config->item('rest_limits_table'))->row();

        // No calls have been made for this key
        if ($result === NULL) {
            // Create a new row for the following key
            $this->rest->db->insert($this->config->item('rest_limits_table'), ['uri' => $limited_uri, 'api_key' => $api_key, 'count' => 1, 'hour_started' => time() ]);
        }

        // Been a time limit (or by default an hour) since they called
        else if ($result->hour_started < (time() - $time_limit)) {
            // Reset the started period and count
            $this->rest->db->where('uri', $limited_uri)->where('api_key', $api_key)->set('hour_started', time())->set('count', 1)->update($this->config->item('rest_limits_table'));
        }

        // They have called within the hour, so lets update
        else {
            // The limit has been exceeded
            if ($result->count >= $limit) {
                return FALSE;
            }
            // Increase the count by one
            $this->rest->db->where('uri', $limited_uri)->where('api_key', $api_key)->set('count', 'count + 1', FALSE)->update($this->config->item('rest_limits_table'));
        }
        return TRUE;
    }

    /**
     * Check if there is a specific auth type set for the current class/method/HTTP-method being called
     *
     * @access protected
     * @return bool
     */
    protected function _auth_override_check() {
        // Assign the class/method auth type override array from the config
        $auth_override_class_method = $this->config->item('auth_override_class_method');

        // Check to see if the override array is even populated
        if (!empty($auth_override_class_method)) {
            // Check for wildcard flag for rules for classes
            if (!empty($auth_override_class_method[$this->router->class]['*'])) // Check for class overrides
            {
                // No auth override found, prepare nothing but send back a TRUE override flag
                if ($auth_override_class_method[$this->router->class]['*'] === 'none') {
                    return TRUE;
                }
                // Basic auth override found, prepare basic
                if ($auth_override_class_method[$this->router->class]['*'] === 'basic') {
                    $this->_prepare_basic_auth();
                    return TRUE;
                }
                // Digest auth override found, prepare digest
                if ($auth_override_class_method[$this->router->class]['*'] === 'digest') {
                    $this->_prepare_digest_auth();
                    return TRUE;
                }
                // Session auth override found, check session
                if ($auth_override_class_method[$this->router->class]['*'] === 'session') {
                    $this->_check_php_session();
                    return TRUE;
                }
                // Whitelist auth override found, check client's ip against config whitelist
                if ($auth_override_class_method[$this->router->class]['*'] === 'whitelist') {
                    $this->_check_whitelist_auth();
                    return TRUE;
                }
            }
            // Check to see if there's an override value set for the current class/method being called
            if (!empty($auth_override_class_method[$this->router->class][$this->router->method])) {
                // None auth override found, prepare nothing but send back a TRUE override flag
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'none') {
                    return TRUE;
                }
                // Basic auth override found, prepare basic
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'basic') {
                    $this->_prepare_basic_auth();
                    return TRUE;
                }
                // Digest auth override found, prepare digest
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'digest') {
                    $this->_prepare_digest_auth();
                    return TRUE;
                }
                // Session auth override found, check session
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'session') {
                    $this->_check_php_session();
                    return TRUE;
                }
                // Whitelist auth override found, check client's ip against config whitelist
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'whitelist') {
                    $this->_check_whitelist_auth();
                    return TRUE;
                }
            }
        }

        // Assign the class/method/HTTP-method auth type override array from the config
        $auth_override_class_method_http = $this->config->item('auth_override_class_method_http');

        // Check to see if the override array is even populated
        if (!empty($auth_override_class_method_http)) {
            // check for wildcard flag for rules for classes
            if (!empty($auth_override_class_method_http[$this->router->class]['*'][$this->request->method])) {
                // None auth override found, prepare nothing but send back a TRUE override flag
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'none') {
                    return TRUE;
                }
                // Basic auth override found, prepare basic
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'basic') {
                    $this->_prepare_basic_auth();
                    return TRUE;
                }
                // Digest auth override found, prepare digest
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'digest') {
                    $this->_prepare_digest_auth();
                    return TRUE;
                }
                // Session auth override found, check session
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'session') {
                    $this->_check_php_session();
                    return TRUE;
                }
                // Whitelist auth override found, check client's ip against config whitelist
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'whitelist') {
                    $this->_check_whitelist_auth();
                    return TRUE;
                }
            }
            // Check to see if there's an override value set for the current class/method/HTTP-method being called
            if (!empty($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method])) {
                // None auth override found, prepare nothing but send back a TRUE override flag
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'none') {
                    return TRUE;
                }
                // Basic auth override found, prepare basic
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'basic') {
                    $this->_prepare_basic_auth();
                    return TRUE;
                }
                // Digest auth override found, prepare digest
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'digest') {
                    $this->_prepare_digest_auth();
                    return TRUE;
                }
                // Session auth override found, check session
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'session') {
                    $this->_check_php_session();
                    return TRUE;
                }
                // Whitelist auth override found, check client's ip against config whitelist
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'whitelist') {
                    $this->_check_whitelist_auth();
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    /**
     * Parse the GET request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_get() {
        // Merge both the URI segments and query parameters
        $this->_get_args = array_merge($this->_get_args, $this->_query_args);
    }

    /**
     * Parse the POST request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_post() {
        $this->_post_args = $_POST;
        if ($this->request->format) {
            $this->request->body = $this->input->raw_input_stream;
        }
    }

    /**
     * Parse the PUT request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_put() {
        if ($this->request->format) {
            $this->request->body = $this->input->raw_input_stream;
            if ($this->request->format === 'json') {
                $this->_put_args = json_decode($this->input->raw_input_stream);
            }
        } else if ($this->input->method() === 'put') {
            // If no file type is provided, then there are probably just arguments
            $this->_put_args = $this->input->input_stream();
        }
    }

    /**
     * Parse the HEAD request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_head() {
        // Parse the HEAD variables
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $head);

        // Merge both the URI segments and HEAD params
        $this->_head_args = array_merge($this->_head_args, $head);
    }

    /**
     * Parse the OPTIONS request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_options() {
        // Parse the OPTIONS variables
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $options);

        // Merge both the URI segments and OPTIONS params
        $this->_options_args = array_merge($this->_options_args, $options);
    }

    /**
     * Parse the PATCH request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_patch() {
        // It might be a HTTP body
        if ($this->request->format) {
            $this->request->body = $this->input->raw_input_stream;
        } else if ($this->input->method() === 'patch') {
            // If no file type is provided, then there are probably just arguments
            $this->_patch_args = $this->input->input_stream();
        }
    }

    /**
     * Parse the DELETE request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_delete() {
        // These should exist if a DELETE request
        if ($this->input->method() === 'delete') {
            $this->_delete_args = $this->input->input_stream();
        }
    }

    /**
     * Parse the query parameters
     *
     * @access protected
     * @return void
     */
    protected function _parse_query() {
        $this->_query_args = $this->input->get();
    }

    // INPUT FUNCTION --------------------------------------------------------------
    
    /**
     * Retrieve a value from a GET request
     *
     * @access public
     * @param NULL $key Key to retrieve from the GET request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the GET request; otherwise, NULL
     */
    public function get($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $this->_get_args;
        }
        return isset($this->_get_args[$key]) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a OPTIONS request
     *
     * @access public
     * @param NULL $key Key to retrieve from the OPTIONS request.
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the OPTIONS request; otherwise, NULL
     */
    public function options($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $this->_options_args;
        }
        return isset($this->_options_args[$key]) ? $this->_xss_clean($this->_options_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a HEAD request
     *
     * @access public
     * @param NULL $key Key to retrieve from the HEAD request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the HEAD request; otherwise, NULL
     */
    public function head($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $this->_head_args;
        }
        return isset($this->_head_args[$key]) ? $this->_xss_clean($this->_head_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a POST request
     *
     * @access public
     * @param NULL $key Key to retrieve from the POST request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the POST request; otherwise, NULL
     */
    public function post($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $this->_post_args;
        }
        return isset($this->_post_args[$key]) ? $this->_xss_clean($this->_post_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a PUT request
     *
     * @access public
     * @param NULL $key Key to retrieve from the PUT request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the PUT request; otherwise, NULL
     */
    public function put($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $this->_put_args;
        }
        return isset($this->_put_args[$key]) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a DELETE request
     *
     * @access public
     * @param NULL $key Key to retrieve from the DELETE request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the DELETE request; otherwise, NULL
     */
    public function delete($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $this->_delete_args;
        }
        return isset($this->_delete_args[$key]) ? $this->_xss_clean($this->_delete_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a PATCH request
     *
     * @access public
     * @param NULL $key Key to retrieve from the PATCH request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the PATCH request; otherwise, NULL
     */
    public function patch($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $this->_patch_args;
        }
        return isset($this->_patch_args[$key]) ? $this->_xss_clean($this->_patch_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from the query parameters
     *
     * @access public
     * @param NULL $key Key to retrieve from the query parameters
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the query parameters; otherwise, NULL
     */
    public function query($key = NULL, $xss_clean = NULL) {
        if ($key === NULL) {
            return $this->_query_args;
        }
        return isset($this->_query_args[$key]) ? $this->_xss_clean($this->_query_args[$key], $xss_clean) : NULL;
    }

    /**
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented
     *
     * @access protected
     * @param string $value Input data
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return string
     */
    protected function _xss_clean($value, $xss_clean) {
        is_bool($xss_clean) || $xss_clean = $this->_enable_xss;
        return $xss_clean === TRUE ? $this->security->xss_clean($value) : $value;
    }

    /**
     * Retrieve the validation errors
     *
     * @access public
     * @return array
     */
    public function validation_errors() {
        $string = strip_tags($this->form_validation->error_string());
        return explode(PHP_EOL, trim($string, PHP_EOL));
    }

    // SECURITY FUNCTIONS ---------------------------------------------------------
    
    /**
     * Perform LDAP Authentication
     *
     * @access protected
     * @param string $username The username to validate
     * @param string $password The password to validate
     * @return bool
     */
    protected function _perform_ldap_auth($username = '', $password = NULL) {
        if (empty($username)) {
            log_message('debug', 'LDAP Auth: failure, empty username');
            return FALSE;
        }
        log_message('debug', 'LDAP Auth: Loading configuration');
        $this->config->load('ldap', TRUE);
        $ldap = ['timeout' => $this->config->item('timeout', 'ldap'), 'host' => $this->config->item('server', 'ldap'), 'port' => $this->config->item('port', 'ldap'), 'rdn' => $this->config->item('binduser', 'ldap'), 'pass' => $this->config->item('bindpw', 'ldap'), 'basedn' => $this->config->item('basedn', 'ldap'), ];
        log_message('debug', 'LDAP Auth: Connect to ' . (isset($ldaphost) ? $ldaphost : '[ldap not configured]'));

        // Connect to the ldap server
        $ldapconn = ldap_connect($ldap['host'], $ldap['port']);
        if ($ldapconn) {
            log_message('debug', 'Setting timeout to ' . $ldap['timeout'] . ' seconds');
            ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, $ldap['timeout']);
            log_message('debug', 'LDAP Auth: Binding to ' . $ldap['host'] . ' with dn ' . $ldap['rdn']);
            // Binding to the ldap server
            $ldapbind = ldap_bind($ldapconn, $ldap['rdn'], $ldap['pass']);
            // Verify the binding
            if ($ldapbind === FALSE) {
                log_message('error', 'LDAP Auth: bind was unsuccessful');
                return FALSE;
            }
            log_message('debug', 'LDAP Auth: bind successful');
        }

        // Search for user
        if (($res_id = ldap_search($ldapconn, $ldap['basedn'], "uid=$username")) === FALSE) {
            log_message('error', 'LDAP Auth: User ' . $username . ' not found in search');
            return FALSE;
        }
        if (ldap_count_entries($ldapconn, $res_id) !== 1) {
            log_message('error', 'LDAP Auth: Failure, username ' . $username . 'found more than once');
            return FALSE;
        }
        if (($entry_id = ldap_first_entry($ldapconn, $res_id)) === FALSE) {
            log_message('error', 'LDAP Auth: Failure, entry of search result could not be fetched');
            return FALSE;
        }
        if (($user_dn = ldap_get_dn($ldapconn, $entry_id)) === FALSE) {
            log_message('error', 'LDAP Auth: Failure, user-dn could not be fetched');
            return FALSE;
        }

        // User found, could not authenticate as user
        if (($link_id = ldap_bind($ldapconn, $user_dn, $password)) === FALSE) {
            log_message('error', 'LDAP Auth: Failure, username/password did not match: ' . $user_dn);
            return FALSE;
        }
        log_message('debug', 'LDAP Auth: Success ' . $user_dn . ' authenticated successfully');
        $this->_user_ldap_dn = $user_dn;
        ldap_close($ldapconn);
        return TRUE;
    }

    /**
     * Perform Library Authentication - Override this function to change the way the library is called
     *
     * @access protected
     * @param string $username The username to validate
     * @param string $password The password to validate
     * @return bool
     */
    protected function _perform_library_auth($username = '', $password = NULL) {
        if (empty($username)) {
            log_message('error', 'Library Auth: Failure, empty username');
            return FALSE;
        }
        $auth_library_class = strtolower($this->config->item('auth_library_class'));
        $auth_library_function = strtolower($this->config->item('auth_library_function'));
        if (empty($auth_library_class)) {
            log_message('debug', 'Library Auth: Failure, empty auth_library_class');
            return FALSE;
        }
        if (empty($auth_library_function)) {
            log_message('debug', 'Library Auth: Failure, empty auth_library_function');
            return FALSE;
        }
        if (is_callable([$auth_library_class, $auth_library_function]) === FALSE) {
            $this->load->library($auth_library_class);
        }
        return $this->{$auth_library_class}->$auth_library_function($username, $password);
    }

    /**
     * Check if the user is logged in
     *
     * @access protected
     * @param string $username The user's name
     * @param bool|string $password The user's password
     * @return bool
     */
    protected function _check_login($username = NULL, $password = FALSE) {
        if (empty($username)) {
            return FALSE;
        }
        $auth_source = strtolower($this->config->item('auth_source'));
        $rest_auth = strtolower($this->config->item('rest_auth'));
        $valid_logins = $this->config->item('rest_valid_logins');
        if (!$this->config->item('auth_source') && $rest_auth === 'digest') {
            // For digest we do not have a password passed as argument
            return md5($username . ':' . $this->config->item('rest_realm') . ':' . (isset($valid_logins[$username]) ? $valid_logins[$username] : ''));
        }
        if ($password === FALSE) {
            return FALSE;
        }
        if ($auth_source === 'ldap') {
            log_message('debug', "Performing LDAP authentication for $username");
            return $this->_perform_ldap_auth($username, $password);
        }
        if ($auth_source === 'library') {
            log_message('debug', "Performing Library authentication for $username");
            return $this->_perform_library_auth($username, $password);
        }
        if (array_key_exists($username, $valid_logins) === FALSE) {
            return FALSE;
        }

        
        if ($valid_logins[$username] !== $password) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Check to see if the user is logged in with a PHP session key
     *
     * @access protected
     * @return void
     */
    protected function _check_php_session() {
        // Get the auth_source config item
        $key = $this->config->item('auth_source');

        // If false, then the user isn't logged in
        if (!$this->session->userdata($key)) {
            // Display an error response
            $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unauthorized') ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Prepares for basic authentication
     *
     * @access protected
     * @return void
     */
    protected function _prepare_basic_auth() {
        // If whitelist is enabled it has the first chance to kick them out
        if ($this->config->item('rest_ip_whitelist_enabled')) {
            $this->_check_whitelist_auth();
        }

        // Returns NULL if the SERVER variables PHP_AUTH_USER and HTTP_AUTHENTICATION don't exist
        $username = $this->input->server('PHP_AUTH_USER');
        $http_auth = $this->input->server('HTTP_AUTHENTICATION') ? : $this->input->server('HTTP_AUTHORIZATION');
        $password = NULL;
        if ($username !== NULL) {
            $password = $this->input->server('PHP_AUTH_PW');
        } else if ($http_auth !== NULL) {
            // If the authentication header is set as basic, then extract the username and password from
            // HTTP_AUTHORIZATION e.g. my_username:my_password. This is passed in the .htaccess file
            if (strpos(strtolower($http_auth), 'basic') === 0) {
                // Search online for HTTP_AUTHORIZATION workaround to explain what this is doing
                list($username, $password) = explode(':', base64_decode(substr($this->input->server('HTTP_AUTHORIZATION'), 6)));
            }
        }

        // Check if the user is logged into the system
        if ($this->_check_login($username, $password) === FALSE) {
            $this->_force_login();
        }
    }

    /**
     * Prepares for digest authentication
     *
     * @access protected
     * @return void
     */
    protected function _prepare_digest_auth() {
        // If whitelist is enabled it has the first chance to kick them out
        if ($this->config->item('rest_ip_whitelist_enabled')) {
            $this->_check_whitelist_auth();
        }

        // We need to test which server authentication variable to use,
        // because the PHP ISAPI module in IIS acts different from CGI
        $digest_string = $this->input->server('PHP_AUTH_DIGEST');
        if ($digest_string === NULL) {
            $digest_string = $this->input->server('HTTP_AUTHORIZATION');
        }
        $unique_id = uniqid();

        // The $_SESSION['error_prompted'] variable is used to ask the password
        // again if none given or if the user enters wrong auth information
        if (empty($digest_string)) {
            $this->_force_login($unique_id);
        }

        // We need to retrieve authentication data from the $digest_string variable
        $matches = [];
        preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
        $digest = (empty($matches[1]) || empty($matches[2])) ? [] : array_combine($matches[1], $matches[2]);

        // For digest authentication the library function should return already stored md5(username:restrealm:password) for that username see rest.php::auth_library_function config
        $username = $this->_check_login($digest['username'], TRUE);
        if (array_key_exists('username', $digest) === FALSE || $username === FALSE) {
            $this->_force_login($unique_id);
        }
        $md5 = md5(strtoupper($this->request->method) . ':' . $digest['uri']);
        $valid_response = md5($username . ':' . $digest['nonce'] . ':' . $digest['nc'] . ':' . $digest['cnonce'] . ':' . $digest['qop'] . ':' . $md5);

        // Check if the string don't compare (case-insensitive)
        if (strcasecmp($digest['response'], $valid_response) !== 0) {
            // Display an error response
            $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_invalid_credentials') ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Checks if the client's ip is in the 'rest_ip_blacklist' config and generates a 401 response
     *
     * @access protected
     * @return void
     */
    protected function _check_blacklist_auth() {
        // Match an ip address in a blacklist e.g. 127.0.0.0, 0.0.0.0
        $pattern = sprintf('/(?:,\s*|^)\Q%s\E(?=,\s*|$)/m', $this->input->ip_address());

        // Returns 1, 0 or FALSE (on error only). Therefore implicitly convert 1 to TRUE
        if (preg_match($pattern, $this->config->item('rest_ip_blacklist'))) {
            // Display an error response
            $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ip_denied') ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Check if the client's ip is in the 'rest_ip_whitelist' config and generates a 401 response
     *
     * @access protected
     * @return void
     */
    protected function _check_whitelist_auth() {
        $whitelist = explode(',', $this->config->item('rest_ip_whitelist'));
        array_push($whitelist, '127.0.0.1', '0.0.0.0');
        foreach ($whitelist as & $ip) {
            // As $ip is a reference, trim leading and trailing whitespace, then store the new value
            // using the reference
            $ip = trim($ip);
        }
        if (in_array($this->input->ip_address(), $whitelist) === FALSE) {
            $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ip_unauthorized') ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Force logging in by setting the WWW-Authenticate header
     *
     * @access protected
     * @param string $nonce A server-specified data string which should be uniquely generated
     * each time
     * @return void
     */
    protected function _force_login($nonce = '') {
        $rest_auth = $this->config->item('rest_auth');
        $rest_realm = $this->config->item('rest_realm');
        if (strtolower($rest_auth) === 'basic') {
            // See http://tools.ietf.org/html/rfc2617#page-5
            header('WWW-Authenticate: Basic realm="' . $rest_realm . '"');
        } else if (strtolower($rest_auth) === 'digest') {
            // See http://tools.ietf.org/html/rfc2617#page-18
            header('WWW-Authenticate: Digest realm="' . $rest_realm . '", qop="auth", nonce="' . $nonce . '", opaque="' . md5($rest_realm) . '"');
        }
        if ($this->config->item('strict_api_and_auth') === true) {
            $this->is_valid_request = false;
        }

        // Display an error response
        $this->response([$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unauthorized') ], self::HTTP_UNAUTHORIZED);
    }

    /**
     * Updates the log table with the total access time
     *
     * @access protected
     * @author Chris Kacerguis
     * @return bool TRUE log table updated; otherwise, FALSE
     */
    protected function _log_access_time() {
        if ($this->_insert_id == '') {
            return false;
        }
        $payload['rtime'] = $this->_end_rtime - $this->_start_rtime;
        return $this->rest->db->update($this->config->item('rest_logs_table'), $payload, ['id' => $this->_insert_id]);
    }

    /**
     * Updates the log table with HTTP response code
     *
     * @access protected
     * @author Justin Chen
     * @param $http_code int HTTP status code
     * @return bool TRUE log table updated; otherwise, FALSE
     */
    protected function _log_response_code($http_code) {
        if ($this->_insert_id == '') {
            return false;
        }
        $payload['response_code'] = $http_code;
        return $this->rest->db->update($this->config->item('rest_logs_table'), $payload, ['id' => $this->_insert_id]);
    }

    /**
     * Check to see if the API key has access to the controller and methods
     *
     * @access protected
     * @return bool TRUE the API key has access; otherwise, FALSE
     */
    protected function _check_access() {
        // If we don't want to check access, just return TRUE
        if ($this->config->item('rest_enable_access') === FALSE) {
            return TRUE;
        }
        //check if the key has all_access
        $accessRow = $this->rest->db->where('key', $this->rest->key)->get($this->config->item('rest_access_table'))->row_array();
        if (!empty($accessRow) && !empty($accessRow['all_access'])) {
            return TRUE;
        }

        // Fetch controller based on path and controller name
        $controller = implode('/', [$this->router->directory, $this->router->class]);

        // Remove any double slashes for safety
        $controller = str_replace('//', '/', $controller);

        // Query the access table and get the number of results
        return $this->rest->db->where('key', $this->rest->key)->where('controller', $controller)->get($this->config->item('rest_access_table'))->num_rows() > 0;
    }

    /**
     * Checks allowed domains, and adds appropriate headers for HTTP access control (CORS)
     *
     * @access protected
     * @return void
     */
    protected function _check_cors() {
        // Convert the config items into strings
        $allowed_headers = implode(', ', $this->config->item('allowed_cors_headers'));
        $allowed_methods = implode(', ', $this->config->item('allowed_cors_methods'));

        // If we want to allow any domain to access the API
        if ($this->config->item('allow_any_cors_domain') === TRUE) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: ' . $allowed_headers);
            header('Access-Control-Allow-Methods: ' . $allowed_methods);
        } else {
            // We're going to allow only certain domains access
            // Store the HTTP Origin header
            $origin = $this->input->server('HTTP_ORIGIN');
            if ($origin === NULL) {
                $origin = '';
            }
            // If the origin domain is in the allowed_cors_origins list, then add the Access Control headers
            if (in_array($origin, $this->config->item('allowed_cors_origins'))) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Headers: ' . $allowed_headers);
                header('Access-Control-Allow-Methods: ' . $allowed_methods);
            }
        }

        // If the request HTTP method is 'OPTIONS', kill the response and send it to the client
        if ($this->input->method() === 'options') {
            exit;
        }
    }

    public function playground() {
        return $this->rest->playground;
    }
}
