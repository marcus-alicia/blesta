-- phpMyAdmin SQL Dump
-- version 2.10.0.2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Oct 26, 2012 at 11:26 AM
-- Server version: 5.1.61
-- PHP Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- Database: `blesta_minphp`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `accounts_ach`
-- 

CREATE TABLE `accounts_ach` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` int(10) unsigned NOT NULL,
  `first_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `address1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'US',
  `account` text COLLATE utf8_unicode_ci,
  `routing` text COLLATE utf8_unicode_ci,
  `last4` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` enum('checking','savings') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'checking',
  `gateway_id` int(10) unsigned DEFAULT NULL,
  `client_reference_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reference_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `gateway_id` (`gateway_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `accounts_cc`
-- 

CREATE TABLE `accounts_cc` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` int(10) unsigned NOT NULL,
  `first_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `address1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'US',
  `number` text COLLATE utf8_unicode_ci,
  `expiration` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `last4` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` enum('amex','bc','cup','dc-cb','dc-er','dc-int','dc-uc','disc','ipi','jcb','lasr','maes','mc','solo','switch','visa') COLLATE utf8_unicode_ci NOT NULL,
  `gateway_id` int(10) unsigned DEFAULT NULL,
  `client_reference_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reference_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `gateway_id` (`gateway_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `acl_acl`
-- 

CREATE TABLE `acl_acl` (
  `aro_id` int(11) NOT NULL,
  `aco_id` int(11) NOT NULL,
  `action` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `permission` enum('allow','deny') COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`aro_id`,`aco_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `acl_aco`
-- 

CREATE TABLE `acl_aco` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `acl_aro`
-- 

CREATE TABLE `acl_aro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lineage` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '/',
  PRIMARY KEY (`id`),
  UNIQUE KEY `alias` (`alias`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `api_keys`
-- 

CREATE TABLE `api_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `user` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `key` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `date_created` datetime NOT NULL,
  `notes` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`user`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `calendar_events`
-- 

CREATE TABLE `calendar_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `shared` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `url` text COLLATE utf8_unicode_ci,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `all_day` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `staff_id` (`staff_id`),
  KEY `shared` (`shared`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `clients`
-- 

CREATE TABLE `clients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_format` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `id_value` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `client_group_id` int(10) unsigned NOT NULL,
  `primary_account_id` int(10) unsigned DEFAULT NULL,
  `primary_account_type` enum('ach','cc') COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','fraud') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `primary_account_id` (`primary_account_id`),
  KEY `id_format` (`id_format`),
  KEY `id_value` (`id_value`),
  KEY `status` (`status`),
  KEY `client_group_id` (`client_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `client_account`
-- 

CREATE TABLE `client_account` (
  `client_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `type` enum('ach','cc') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'cc',
  PRIMARY KEY (`client_id`),
  KEY `account_id` (`account_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `client_fields`
-- 

CREATE TABLE `client_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_group_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_lang` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `type` enum('text','checkbox','select','textarea') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'text',
  `values` text COLLATE utf8_unicode_ci,
  `regex` text COLLATE utf8_unicode_ci,
  `show_client` tinyint(1) NOT NULL DEFAULT '0',
  `encrypted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `group_idclient_group_id` (`client_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `client_groups`
-- 

CREATE TABLE `client_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `color` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `client_group_settings`
-- 

CREATE TABLE `client_group_settings` (
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `client_group_id` int(10) unsigned NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `encrypted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`key`,`client_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `client_notes`
-- 

CREATE TABLE `client_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `stickied` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `staff_id` (`staff_id`),
  KEY `stickied` (`stickied`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `client_packages`
-- 

CREATE TABLE `client_packages` (
  `client_id` int(10) unsigned NOT NULL,
  `package_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`client_id`,`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `client_settings`
-- 

CREATE TABLE `client_settings` (
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` int(10) unsigned NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `encrypted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`key`,`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `client_values`
-- 

CREATE TABLE `client_values` (
  `client_field_id` int(10) unsigned NOT NULL,
  `client_id` int(10) unsigned NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `encrypted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`client_field_id`,`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `companies`
-- 

CREATE TABLE `companies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `hostname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address` text COLLATE utf8_unicode_ci,
  `phone` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hostname` (`hostname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `company_settings`
-- 

CREATE TABLE `company_settings` (
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `encrypted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`key`,`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `contacts`
-- 

CREATE TABLE `contacts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `contact_type` enum('primary','billing','other') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'primary',
  `contact_type_id` int(10) unsigned DEFAULT NULL,
  `first_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `company` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address2` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'US',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `user_id` (`user_id`),
  KEY `contact_type` (`contact_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `contact_numbers`
-- 

CREATE TABLE `contact_numbers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` int(10) unsigned NOT NULL,
  `number` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('phone','fax') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'phone',
  `location` enum('home','work','mobile') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'home',
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `contact_types`
-- 

CREATE TABLE `contact_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_lang` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `countries`
-- 

CREATE TABLE `countries` (
  `alpha2` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `alpha3` char(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alt_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`alpha2`),
  UNIQUE KEY `alpha3` (`alpha3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `coupons`
-- 

CREATE TABLE `coupons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `used_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `max_qty` int(10) unsigned NOT NULL DEFAULT '0',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  `type` enum('inclusive','exclusive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'exclusive',
  `recurring` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `limit_recurring` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`,`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `coupon_amounts`
-- 

CREATE TABLE `coupon_amounts` (
  `coupon_id` int(10) unsigned NOT NULL,
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'USD',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `type` enum('amount','percent') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'percent',
  PRIMARY KEY (`coupon_id`,`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `coupon_packages`
-- 

CREATE TABLE `coupon_packages` (
  `coupon_id` int(10) unsigned NOT NULL,
  `package_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`coupon_id`,`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `cron_tasks`
-- 

CREATE TABLE `cron_tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `plugin_dir` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `is_lang` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `type` enum('time','interval') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'interval',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`,`plugin_dir`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `cron_task_runs`
-- 

CREATE TABLE `cron_task_runs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(10) unsigned NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `time` time DEFAULT NULL,
  `interval` int(10) unsigned DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `date_enabled` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `currencies`
-- 

CREATE TABLE `currencies` (
  `code` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `format` enum('#,###.##','#.###,##','# ###.##','# ###,##','#,##,###.##','# ###','#.###','#,###') COLLATE utf8_unicode_ci NOT NULL DEFAULT '#,###.##',
  `prefix` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `suffix` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `exchange_rate` decimal(14,6) NOT NULL DEFAULT '1.000000',
  `exchange_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`code`,`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `emails`
-- 

CREATE TABLE `emails` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email_group_id` int(10) unsigned NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `lang` char(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en_us',
  `from` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `from_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `text` mediumtext COLLATE utf8_unicode_ci,
  `html` mediumtext COLLATE utf8_unicode_ci,
  `email_signature_id` int(10) unsigned DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_group_id` (`email_group_id`,`company_id`,`lang`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `email_groups`
-- 

CREATE TABLE `email_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('client','staff','shared') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'client',
  `plugin_dir` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tags` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `action` (`action`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `email_signatures`
-- 

CREATE TABLE `email_signatures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `html` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `gateways`
-- 

CREATE TABLE `gateways` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `class` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('merchant','nonmerchant','hybrid') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'merchant',
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`class`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `gateway_currencies`
-- 

CREATE TABLE `gateway_currencies` (
  `gateway_id` int(10) unsigned NOT NULL,
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`gateway_id`,`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `gateway_meta`
-- 

CREATE TABLE `gateway_meta` (
  `gateway_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `encrypted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gateway_id`,`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoices`
-- 

CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_format` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `id_value` int(10) unsigned NOT NULL,
  `client_id` int(10) unsigned NOT NULL,
  `date_billed` datetime NOT NULL,
  `date_due` datetime NOT NULL,
  `date_closed` datetime DEFAULT NULL,
  `date_autodebit` datetime DEFAULT NULL,
  `status` enum('active','draft','void') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  `previous_due` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'USD',
  `note_public` text COLLATE utf8_unicode_ci,
  `note_private` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  KEY `id_value` (`id_value`),
  KEY `id_format` (`id_format`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoices_recur`
-- 

CREATE TABLE `invoices_recur` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `term` smallint(5) unsigned NOT NULL DEFAULT '1',
  `period` enum('day','week','month','year') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'month',
  `duration` smallint(5) DEFAULT NULL,
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'USD',
  `date_renews` datetime NOT NULL,
  `date_last_renewed` datetime DEFAULT NULL,
  `note_public` text COLLATE utf8_unicode_ci,
  `note_private` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoices_recur_created`
-- 

CREATE TABLE `invoices_recur_created` (
  `invoice_recur_id` int(10) unsigned NOT NULL,
  `invoice_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`invoice_recur_id`,`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_delivery`
-- 

CREATE TABLE `invoice_delivery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `method` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `date_sent` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_fields`
-- 

CREATE TABLE `invoice_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_group_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_lang` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `type` enum('text','checkbox','select','textarea') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'text',
  `values` text COLLATE utf8_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `client_group_id` (`client_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_lines`
-- 

CREATE TABLE `invoice_lines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `service_id` int(10) unsigned DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `qty` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `amount` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `order` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `service_id` (`service_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_line_taxes`
-- 

CREATE TABLE `invoice_line_taxes` (
  `line_id` int(10) unsigned NOT NULL,
  `tax_id` int(10) unsigned NOT NULL,
  `cascade` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`line_id`,`tax_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_meta`
-- 

CREATE TABLE `invoice_meta` (
  `invoice_id` int(10) unsigned NOT NULL,
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`invoice_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_recur_delivery`
-- 

CREATE TABLE `invoice_recur_delivery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_recur_id` int(10) unsigned NOT NULL,
  `method` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_recur_id` (`invoice_recur_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_recur_lines`
-- 

CREATE TABLE `invoice_recur_lines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_recur_id` int(10) unsigned NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `qty` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `amount` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `taxable` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `order` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_recur_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_recur_values`
-- 

CREATE TABLE `invoice_recur_values` (
  `invoice_field_id` int(10) unsigned NOT NULL,
  `invoice_recur_id` int(10) unsigned NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`invoice_field_id`,`invoice_recur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `invoice_values`
-- 

CREATE TABLE `invoice_values` (
  `invoice_field_id` int(10) unsigned NOT NULL,
  `invoice_id` int(10) unsigned NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`invoice_field_id`,`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `languages`
-- 

CREATE TABLE `languages` (
  `code` char(5) COLLATE utf8_unicode_ci NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`code`,`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `log_account_access`
-- 

CREATE TABLE `log_account_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `first_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('ach','cc') COLLATE utf8_unicode_ci NOT NULL,
  `account_type` enum('checking','savings','amex','bc','cup','dc-cb','dc-er','dc-int','dc-uc','disc','ipi','jcb','lasr','maes','mc','solo','switch','visa') COLLATE utf8_unicode_ci NOT NULL,
  `last4` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `date_accessed` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `log_contacts`
-- 

CREATE TABLE `log_contacts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` int(10) unsigned NOT NULL,
  `change` text COLLATE utf8_unicode_ci NOT NULL,
  `date_changed` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `log_cron`
-- 

CREATE TABLE `log_cron` (
  `run_id` int(10) unsigned NOT NULL,
  `event` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `group` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `output` mediumtext COLLATE utf8_unicode_ci,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`run_id`,`group`,`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `log_emails`
-- 

CREATE TABLE `log_emails` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `to_client_id` int(10) unsigned DEFAULT NULL,
  `from_staff_id` int(10) unsigned DEFAULT NULL,
  `to_address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `from_address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `from_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cc_address` text COLLATE utf8_unicode_ci,
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `body_text` mediumtext COLLATE utf8_unicode_ci,
  `body_html` mediumtext COLLATE utf8_unicode_ci,
  `sent` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `error` text COLLATE utf8_unicode_ci,
  `date_sent` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `from_staff_id` (`from_staff_id`),
  KEY `to_client_id` (`to_client_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `log_gateways`
-- 

CREATE TABLE `log_gateways` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `gateway_id` int(10) unsigned NOT NULL,
  `direction` enum('input','output') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'input',
  `url` text COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci,
  `date_added` datetime NOT NULL,
  `status` enum('error','success') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'error',
  `group` char(8) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `gateway_id` (`gateway_id`),
  KEY `group` (`group`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `log_modules`
-- 

CREATE TABLE `log_modules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `module_id` int(10) unsigned NOT NULL,
  `direction` enum('input','output') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'input',
  `url` text COLLATE utf8_unicode_ci NOT NULL,
  `data` text COLLATE utf8_unicode_ci,
  `date_added` datetime NOT NULL,
  `status` enum('error','success') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'error',
  `group` char(8) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `module_id` (`module_id`),
  KEY `group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `log_services`
-- 

CREATE TABLE `log_services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `service_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `status` enum('suspended','unsuspended') COLLATE utf8_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `service_id` (`service_id`,`status`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `log_transactions`
-- 

CREATE TABLE `log_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `transaction_id` int(10) unsigned NOT NULL,
  `change` text COLLATE utf8_unicode_ci NOT NULL,
  `date_changed` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`),
  KEY `transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `log_users`
-- 

CREATE TABLE `log_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `ip_address` varchar(39) COLLATE utf8_unicode_ci NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `date_added` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `result` enum('success','failure') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'failure',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `result` (`result`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `modules`
-- 

CREATE TABLE `modules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `class` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`class`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `module_groups`
-- 

CREATE TABLE `module_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  `add_order` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `module_meta`
-- 

CREATE TABLE `module_meta` (
  `module_id` int(10) unsigned NOT NULL,
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `serialized` tinyint(1) NOT NULL DEFAULT '0',
  `encrypted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`module_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `module_rows`
-- 

CREATE TABLE `module_rows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `module_row_groups`
-- 

CREATE TABLE `module_row_groups` (
  `module_group_id` int(10) unsigned NOT NULL,
  `module_row_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`module_group_id`,`module_row_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `module_row_meta`
-- 

CREATE TABLE `module_row_meta` (
  `module_row_id` int(10) unsigned NOT NULL,
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `serialized` tinyint(1) NOT NULL DEFAULT '0',
  `encrypted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`module_row_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `packages`
-- 

CREATE TABLE `packages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_format` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `id_value` int(10) unsigned NOT NULL,
  `module_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `description_html` text COLLATE utf8_unicode_ci,
  `qty` int(10) unsigned DEFAULT NULL,
  `module_row` int(10) unsigned NOT NULL DEFAULT '0',
  `module_group` int(10) unsigned DEFAULT NULL,
  `taxable` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive','restricted') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  `company_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  KEY `company_id` (`company_id`),
  KEY `module_row` (`module_row`),
  KEY `module_group` (`module_group`),
  KEY `id_value` (`id_value`),
  KEY `id_format` (`id_format`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `package_emails`
-- 

CREATE TABLE `package_emails` (
  `package_id` int(10) unsigned NOT NULL,
  `lang` char(5) COLLATE utf8_unicode_ci NOT NULL,
  `html` mediumtext COLLATE utf8_unicode_ci,
  `text` mediumtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`package_id`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `package_group`
-- 

CREATE TABLE `package_group` (
  `package_id` int(10) unsigned NOT NULL,
  `package_group_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`package_id`,`package_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `package_groups`
-- 

CREATE TABLE `package_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('standard','addon') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'standard',
  `company_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `package_group_parents`
-- 

CREATE TABLE `package_group_parents` (
  `group_id` int(10) unsigned NOT NULL,
  `parent_group_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`group_id`,`parent_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `package_meta`
-- 

CREATE TABLE `package_meta` (
  `package_id` int(10) unsigned NOT NULL,
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  `serialized` tinyint(1) NOT NULL DEFAULT '0',
  `encrypted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`package_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `package_pricing`
-- 

CREATE TABLE `package_pricing` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `package_id` int(10) unsigned NOT NULL,
  `term` smallint(5) unsigned NOT NULL DEFAULT '1',
  `period` enum('day','week','month','year','onetime') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'month',
  `price` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `setup_fee` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `cancel_fee` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'USD',
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `permissions`
-- 

CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `action` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `plugin_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`group_id`),
  KEY `plugin_id` (`plugin_id`),
  KEY `alias` (`alias`,`action`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `permission_groups`
-- 

CREATE TABLE `permission_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `level` enum('staff','client') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'staff',
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `plugin_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `level` (`level`,`alias`),
  KEY `plugin_id` (`plugin_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `plugins`
-- 

CREATE TABLE `plugins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dir` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `version` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dir` (`dir`,`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `plugin_actions`
-- 

CREATE TABLE `plugin_actions` (
  `plugin_id` int(10) unsigned NOT NULL,
  `action` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `options` text COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`plugin_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `plugin_events`
-- 

CREATE TABLE `plugin_events` (
  `plugin_id` int(10) unsigned NOT NULL,
  `event` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `callback` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`plugin_id`,`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `services`
-- 

CREATE TABLE `services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_service_id` int(10) unsigned DEFAULT NULL,
  `id_format` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `id_value` int(10) NOT NULL,
  `pricing_id` int(10) unsigned NOT NULL,
  `client_id` int(10) unsigned NOT NULL,
  `module_row_id` int(10) unsigned NOT NULL,
  `coupon_id` int(10) unsigned DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL DEFAULT '1',
  `status` enum('active','canceled','pending','suspended', 'in_review') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'pending',
  `date_added` datetime NOT NULL,
  `date_renews` datetime DEFAULT NULL,
  `date_last_renewed` datetime DEFAULT NULL,
  `date_suspended` datetime DEFAULT NULL,
  `date_canceled` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pricing_id` (`pricing_id`),
  KEY `client_id` (`client_id`),
  KEY `module_row_id` (`module_row_id`),
  KEY `status` (`status`),
  KEY `parent_service_id` (`parent_service_id`),
  KEY `id_format` (`id_format`),
  KEY `id_value` (`id_value`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `service_fields`
-- 

CREATE TABLE `service_fields` (
  `service_id` int(10) unsigned NOT NULL,
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `serialized` tinyint(1) NOT NULL DEFAULT '0',
  `encrypted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`service_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `sessions`
-- 

CREATE TABLE `sessions` (
  `id` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `expire` datetime NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `expire` (`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `settings`
-- 

CREATE TABLE `settings` (
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `encrypted` tinyint(1) NOT NULL DEFAULT '0',
  `comment` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff`
-- 

CREATE TABLE `staff` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `first_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_mobile` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff_group`
-- 

CREATE TABLE `staff_group` (
  `staff_id` int(10) unsigned NOT NULL,
  `staff_group_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`staff_id`,`staff_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff_groups`
-- 

CREATE TABLE `staff_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff_group_notices`
-- 

CREATE TABLE `staff_group_notices` (
  `staff_group_id` int(10) unsigned NOT NULL,
  `action` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`staff_group_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff_links`
-- 

CREATE TABLE `staff_links` (
  `staff_id` int(10) unsigned NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `order` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`staff_id`,`company_id`,`uri`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff_notices`
-- 

CREATE TABLE `staff_notices` (
  `staff_group_id` int(10) unsigned NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `action` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`staff_group_id`,`staff_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff_settings`
-- 

CREATE TABLE `staff_settings` (
  `key` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `staff_id` int(10) unsigned NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`key`,`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `states`
-- 

CREATE TABLE `states` (
  `country_alpha2` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`country_alpha2`,`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `system_overview_settings`
-- 

CREATE TABLE `system_overview_settings` (
  `staff_id` int(10) unsigned NOT NULL,
  `company_id` int(10) unsigned NOT NULL,
  `key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `order` int(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`staff_id`,`company_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `taxes`
-- 

CREATE TABLE `taxes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `level` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `type` enum('exclusive','inclusive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'exclusive',
  `country` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(2) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `country` (`country`,`state`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `themes`
-- 

CREATE TABLE `themes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('admin','client') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'admin',
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `transactions`
-- 

CREATE TABLE `transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'USD',
  `type` enum('cc','ach','other') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'other',
  `transaction_type_id` int(10) unsigned DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `gateway_id` int(10) unsigned DEFAULT NULL,
  `transaction_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `parent_transaction_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reference_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('approved','declined','void','error','pending','refunded','returned') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'approved',
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `reference_id` (`reference_id`),
  KEY `gateway_id` (`gateway_id`),
  KEY `account_id` (`account_id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `transaction_applied`
-- 

CREATE TABLE `transaction_applied` (
  `transaction_id` int(10) unsigned NOT NULL,
  `invoice_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `date` datetime NOT NULL,
  PRIMARY KEY (`transaction_id`,`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `transaction_types`
-- 

CREATE TABLE `transaction_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `is_lang` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `users`
-- 

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `two_factor_mode` enum('none','motp','totp') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'none',
  `two_factor_key` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `two_factor_pin` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `user_otps`
-- 

CREATE TABLE `user_otps` (
  `user_id` int(10) unsigned NOT NULL,
  `otp` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`user_id`,`otp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
