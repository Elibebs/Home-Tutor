<?php

namespace App\Utilities;

class Constants
{
	const STATUS_ENABLED = 'ENABLED';
	const STATUS_DISABLED = 'DISABLED';

	const USER_TYPE_CONTRACT = 'CONTRACT';
	const USER_TYPE_PREPAID = 'PREPAID';

    const SR_PENDING_PAYMENT = 'PENDING_PAYMENT';
	const SR_STATUS_PENDING = 'PENDING';
	const SR_STATUS_WORKER_COMPLETED = 'WORKER_COMPLETED';
	const SR_STATUS_WORKER_ASSIGNED = 'WORKER_ASSIGNED';
	const SR_STATUS_WORKER_COMMENCED = 'WORKER_COMMENCED';
	const SR_STATUS_WORKER_SUSPENDED = 'WORKER_SUSPENDED';
	const SR_STATUS_WORKER_CANCELLED = 'WORKER_CANCELLED';
	const SR_STATUS_INVOICE_REQUESTED = 'INVOICE_REQUESTED';
	const SR_STATUS_INVOICE_CREATED = 'INVOICE_CREATED';
	const SR_STATUS_INVOICE_PAID = 'INVOICE_PAID';
	const SR_STATUS_INVOICE_REJECTED = 'INVOICE_REJECTED';
	const SR_STATUS_CANCELLED = 'CANCELLED';
	const SR_STATUS_USER_CANCELLED = 'USER_CANCELLED';
	const SR_STATUS_USER_COMPLETED = 'USER_COMPLETED';
	const SR_STATUS_USER_COMPLAINT = 'USER_COMPLAINT';

	const ENV_LOCAL = 'local';
	const ENV_TEST = 'test';
	const ENV_PRODUCTION = 'production';

	const FILTER_PARAM_IGNORE_LIST = ['page','pageSize','q'];

	// Invooice constants
	const INVOICE_STATUS_REQUEST = "INVOICE_REQUEST";
	const INVOICE_STATUS_APPROVED_INVOICE = "INVOICE";
	const INVOICE_STATUS_RECEIPT = "RECEIPT";

	const INVOICE_STATUS_PENDING = "PENDING";
	const INVOICE_STATUS_REJECTED = "REJECTED";
	const INVOICE_STATUS_ACCEPTED = "ACCEPTED";
	const INVOICE_STATUS_PAID = "PAID";

	const INVOICE_REQUEST_STATUS_PENDING = "PENDING";
	const INVOICE_REQUEST_STATUS_CONFIRMED = "CONFIRMED";
	const INVOICE_REQUEST_STATUS_NEW = 'NEW';

    const OFFER_STATUS_PENDING = "PENDING";
    const OFFER_STATUS_REJECTED = "REJECTED";
    const OFFER_STATUS_ACCEPTED = "ACCEPTED";

	const PASSWORD_RESET_SESSION_TIMOUT = 300;

	const PUSH_NOTIFICATION_TYPE_SERVICE_REQUEST = "SERVICE_REQUEST";
	const PUSH_NOTIFICATION_TYPE_SERVICE_REQUEST_FLEX = "SERVICE_REQUEST_FLEX";
	const PUSH_NOTIFICATION_TYPE_SERVICE_REQUEST_FLEX_NEW = "SERVICE_REQUEST_FLEX_NEW";
	const PUSH_NOTIFICATION_TYPE_SERVICE_REQUEST_CLARIFICATION="SERVICE_REQUEST_CLARIFICATION";
	const PUSH_NOTIFICATION_TYPE_WORKER_VERIFICATION = "WORKER_VERIFICATION";
	const PUSH_NOTIFICATION_TYPE_INVOICE = "INVOICE";
	const PUSH_NOTIFICATION_TYPE_OFFER="OFFER";
	const PUSH_NOTIFICATION_TYPE_OFFER_CLARIFICATION="OFFER_CLARIFICATION";
	const PUSH_NOTIFICATION_TYPE_OFFER_MADE="OFFER_MADE";
	const PUSH_NOTIFICATION_TYPE_OFFER_UPDATED="OFFER_UPDATED";
	const PUSH_NOTIFICATION_TYPE_OFFER_ACCEPTED="OFFER_ACCEPTED";

	const USER_COMPLAINT_STATUS_PENDING = 'PENDING';
	const USER_COMPLAINT_STATUS_RESOLVED = 'RESOLVED';
	const USER_COMPLAINT_STATUS_CANCELLED = 'CANCELLED';

	const SERVICE_REQUEST_TYPE_PREMIUM = 'PREMIUM';
	const SERVICE_REQUEST_TYPE_FLEX = 'FLEX';

	const WORKER_TYPE_INDIVIDUAL = 'INDIVIDUAL';
	const WORKER_TYPE_ORGANIZATION = 'ORGANIZATION';
}
