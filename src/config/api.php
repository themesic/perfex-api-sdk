<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API Key Header Name
 */
$config['api_key_header_name'] = 'X-API-KEY';


/**
 * API Key GET Request Parameter Name
 */
$config['api_key_get_name'] = 'key';


/**
 * API Key POST Request Parameter Name
 */
$config['api_key_post_name'] = 'key';


/**
 * Set API Timezone 
 */
$config['api_timezone'] = 'Europe/London';


/**
 * API Limit database table name
 *
 * Default table schema:
 *  CREATE TABLE `rest_api_limits` (
 *      `id` INT(11) NOT NULL AUTO_INCREMENT,
 *      `api_id` INT(11) NOT NULL,
 *      `uri` VARCHAR(511) NOT NULL,
 *      `class` VARCHAR(511) NOT NULL,
 *      `method` VARCHAR(511) NOT NULL,
 *      `ip_address` VARCHAR(63) NOT NULL,
 *      `time` DATETIME NOT NULL,
 *      PRIMARY KEY (`id`));
 *  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * 
*/
$config['api_limit_table_name'] = 'rest_api_limits';

/**
 * API keys database table name 
 *
 * Default table schema:
 *  CREATE TABLE `rest_api_keys` (
 *     `id` INT(11) NOT NULL AUTO_INCREMENT,
 *     `user` VARCHAR(50) NOT NULL,
 *     `name` VARCHAR(50) NOT NULL,
 *     `token` VARCHAR(255) NOT NULL,
 *     `expiration_date` DATETIME NULL,
 *     `permission_enable` TINYINT(4) DEFAULT 0,
 *     `quota_limit` INT(11) NOT NULL DEFAULT 1000,
 *     `quota_remaining` INT(11) NOT NULL DEFAULT 1000,
 *     `quota_reset` DATETIME NOT NULL,
 *     `rate_limit`  INT(11) NOT NULL DEFAULT 60,
 *     `rate_remaining` INT(11) NOT NULL DEFAULT 60,
 *     `rate_reset` DATETIME NOT NULL,
 *  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 *
 */
$config['api_keys_table_name'] = 'rest_api_keys';