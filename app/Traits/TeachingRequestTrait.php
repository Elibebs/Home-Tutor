<?php

namespace App\Traits;

trait TeachingRequestTrait
{
	protected $serviceRequestRequiredParams = [
		"service_id",
		// "fulfilment_date",
		"description",
		"address",
		"latitude",
		"longitude",
		"down_payment_amount",
		"service_request_date"
	];

    protected $serviceRequestRequiredParamsFlex = [
        "title",
        "speciality",
        // "fulfilment_date",
        "description",
        "address",
        "latitude",
        "longitude",
        "budgeted_amount",
        "service_request_date"
    ];

	protected $changeServiceRequestStatusRequiredParams = [
		"status",
		"description"
	];

	protected $descriptionServiceRequestRequiredParams = [
		"description"
	];

	protected $serviceRequestInvoiceRequiredParams = [
		"description",
		// "items"
	];

	protected $serviceRequestInvoiceItemsRequiredParams = [
		"item_name",
		"unit_price",
		"quantity"
	];

    protected $serviceRequestOfferRequiredParams = [
        "amount",
        "service_request_id"
    ];

    protected $serviceRequestOfferCreateRequiredParams = [
        "amount",
        "service_request_id"
    ];

    protected $serviceRequestOfferUserClarificationRequiredParams = [
        "message",
        "offer_id"
    ];

    protected $serviceRequestOfferWorkerClarificationRequiredParams = [
        "message",
        "offer_id"
    ];

    protected $serviceRequestUserClarificationRequiredParams = [
        "message"
    ];

    protected $serviceRequestWorkerClarificationRequiredParams = [
        "message"
    ];

	protected $requiredDateFormat = "Y-m-d H:i:s";
}
