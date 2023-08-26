<?php

namespace App\Repos;

use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Utilities\Constants;
use App\Utilities\Generators;

class InvoiceRepo
{
	public function __construct(Invoice $Invoice)
    {
        $this->model = $Invoice;
    }

	public function createInvoice(Array $data)
	{
		$invoice = new Invoice;
		$invoice->service_request_id = $data['service_request_id'] ?? null;
		$invoice->worker_id = $data['worker_id'] ?? null;
		$invoice->worker_name = $data['worker_name'] ?? null;
		$invoice->user_id = $data['user_id'] ?? null;
		$invoice->clients_name = $data['clients_name'] ?? null;
		$invoice->workmanship = $data['workmanship'];
		$invoice->invoice_status = $data['status'] ?? Constants::INVOICE_STATUS_PENDING;
		$invoice->invoice_date = Carbon::now();
		$invoice->description = $data['description'];

		$invoice->created_at = Carbon::now();
    	$invoice->updated_at = Carbon::now();

    	// Create Service Request Number
    	$query = "select nextval('activities.service_request_invoice_number_seq') as nextVal";
    	$nextSeqValue = \DB::connection('tenant')->select($query)[0]->nextval;

    	$invoice->invoice_number = "IR".sprintf("%'09d", $nextSeqValue);

		if($invoice->save())
    	{
    		return $invoice;
    	}
    	return null;
	}

	public function getInvoice($invoiceNumber)
	{
		$invoice = Invoice::where("invoice_number", $invoiceNumber)->first();
		$invoice['items'] = InvoiceItem::where("invoice_number", $invoice['invoice_number'])->get();

		return $invoice;
	}

	public function getInvoiceBy($Id)
	{
		$invoice = Invoice::where("service_request_id", $Id)->first();
		if($invoice)
		{
			$invoice['items'] = InvoiceItem::where("invoice_number", $invoice['invoice_number'])->get();
			return $invoice;
		}

		return null;
	}

	public function getInvoices($filters, $identifierColumn, $identifier, $request_type)
	{
		$pageSize = $filters['pageSize'] ?? 20;
		$predicate = Invoice::query();
		foreach ($filters as $key => $filter) {
			if(in_array($key, Constants::FILTER_PARAM_IGNORE_LIST))
			{
				continue;
			}

			$predicate->where($key, $filter);
		}

		$invoices = $predicate->where($identifierColumn, $identifier)
					->where('invoice_status','<>',Constants::INVOICE_REQUEST_STATUS_NEW)
					->orderBy("created_at", "DESC")
					->paginate($pageSize);

		foreach ($invoices as $key => $invoice) {
			$invoices[$key]['items'] = InvoiceItem::where("invoice_number", $invoice['invoice_number'])->get();
		}

		return $invoices;
	}

	public function changeInvoiceStatus($status, $invoiceNumber, $description)
	{
		$invoice = Invoice::where("invoice_number", $invoiceNumber)->first();
		if($invoice)
		{
			$invoice->invoice_status = $status;
			if(isset($description)){
			    $invoice->description = $description;
            }
			if($invoice->update())
			{
				return $invoice;
			}
		}

		return null;
	}
}
